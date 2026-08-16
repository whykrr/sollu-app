<?php

namespace App\Jobs\ImportExport;

use App\Models\User;
use App\Notifications\ExcelImportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

abstract class AbstractExcelImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $userId;

    public ?User $user = null;

    protected $filePath;

    public function __construct(User $user, string $filePath)
    {
        $this->user = $user;
        $this->userId = $user->id;
        $this->filePath = $filePath; // Path in 'local' disk
    }

    /**
     * Get the name of the module for the notification.
     */
    abstract public function getModuleName(): string;

    /**
     * Process a single row from the Excel.
     * Throw an exception if validation or processing fails.
     */
    abstract public function processRow(array $row): void;

    public function handle(): void
    {
        if (! $this->user) {
            $this->user = User::find($this->userId);
        }

        if (! $this->user) {
            return;
        }

        if (! Storage::disk('local')->exists($this->filePath)) {
            return; // File doesn't exist anymore
        }

        // Configure heading row formatter to 'none' so column names stay untouched
        config(['excel.imports.heading_row.formatter' => 'none']);
        HeadingRowFormatter::reset();

        $job = $this;

        $importClass = new class($job) implements ToCollection, WithHeadingRow
        {
            private $job;

            public $successCount = 0;

            public $failedRows = [];

            public function __construct($job)
            {
                $this->job = $job;
            }

            public function collection(Collection $rows)
            {
                foreach ($rows as $row) {
                    $rowData = $row->toArray();

                    // Skip completely empty rows
                    if (empty(array_filter($rowData, fn ($val) => $val !== null && $val !== ''))) {
                        continue;
                    }

                    // Trim keys and string values
                    $cleanRowData = [];
                    foreach ($rowData as $k => $v) {
                        $cleanKey = is_string($k) ? trim($k) : $k;
                        $cleanVal = is_string($v) ? trim($v) : $v;
                        $cleanRowData[$cleanKey] = $cleanVal;
                    }

                    try {
                        $this->job->processRow($cleanRowData);
                        $this->successCount++;
                    } catch (\Throwable $e) {
                        $cleanRowData['Error Message'] = $e->getMessage();
                        $this->failedRows[] = $cleanRowData;
                    }
                }
            }
        };

        // Execute import from local disk path
        Excel::import($importClass, Storage::disk('local')->path($this->filePath));

        // Clean up the uploaded file
        Storage::disk('local')->delete($this->filePath);

        $failedUrl = null;
        $failedCount = count($importClass->failedRows);

        // If there are failures, generate a failed rows Excel
        if ($failedCount > 0) {
            Storage::makeDirectory('exports');
            $failedFileName = 'failed_import_'.time().'.xlsx';
            $failedFilePath = 'exports/'.$failedFileName; // Public directory

            $exportFailed = new class($importClass->failedRows) implements FromArray
            {
                private $data;

                public function __construct(array $data)
                {
                    $this->data = $data;
                }

                public function array(): array
                {
                    if (empty($this->data)) {
                        return [];
                    }
                    $headers = array_keys($this->data[0]);

                    return array_merge([$headers], $this->data);
                }
            };

            Excel::store($exportFailed, $failedFilePath, 'public', \Maatwebsite\Excel\Excel::XLSX);
            $failedUrl = route('exports.download', ['file' => $failedFileName]);
        }

        // Notify user
        $expiresAt = $failedCount > 0 ? now()->addDays(1) : null;
        $this->user->notify(new ExcelImportCompleted(
            $this->getModuleName(),
            $importClass->successCount,
            $failedCount,
            $failedUrl,
            $expiresAt
        ));
    }
}
