<?php

namespace App\Jobs\ImportExport;

use App\Models\User;
use App\Notifications\ExcelExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Facades\Excel;

abstract class AbstractExcelExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $userId;

    protected ?User $user = null;

    public function __construct(User $user)
    {
        $this->userId = $user->id;
    }

    /**
     * Get the query builder that returns the data to be exported.
     */
    abstract public function getQuery();

    /**
     * Get the headers for the Excel file.
     */
    abstract public function getHeaders(): array;

    /**
     * Map a single row from the database to an array of Excel columns.
     */
    abstract public function mapRow($row): array;

    /**
     * Get the name of the module for the notification.
     */
    abstract public function getModuleName(): string;

    /**
     * Get the desired file name for the export.
     */
    abstract public function getFileName(): string;

    public function handle(): void
    {
        $this->user = User::find($this->userId);

        if (! $this->user) {
            return;
        }

        Storage::makeDirectory('exports');

        $fileName = $this->getFileName();
        // Force the extension to be .xlsx
        if (! str_ends_with($fileName, '.xlsx')) {
            // Replace .csv with .xlsx if it exists
            $fileName = str_replace('.csv', '.xlsx', $fileName);
            if (! str_ends_with($fileName, '.xlsx')) {
                $fileName .= '.xlsx';
            }
        }
        $filePath = 'exports/'.$fileName;

        // Create an export class on the fly
        $job = $this;
        $export = new class($job) implements FromQuery, WithHeadings, WithMapping
        {
            private $job;

            public function __construct($job)
            {
                $this->job = $job;
            }

            public function query()
            {
                return $this->job->getQuery();
            }

            public function headings(): array
            {
                return $this->job->getHeaders();
            }

            public function map($row): array
            {
                return $this->job->mapRow($row);
            }
        };

        // Store the file using Laravel Excel
        Excel::store($export, $filePath, 'public', \Maatwebsite\Excel\Excel::XLSX);

        $url = route('exports.download', ['file' => $fileName]);

        // Notify user
        $expiresAt = now()->addDays(1);
        $this->user->notify(new ExcelExportCompleted($this->getModuleName(), $fileName, $url, $expiresAt));
    }
}
