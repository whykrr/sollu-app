<?php

namespace App\Jobs\ImportExport;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Notifications\CsvImportCompleted;

abstract class AbstractCsvImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $filePath;
    
    public function __construct(User $user, string $filePath)
    {
        $this->user = $user;
        $this->filePath = $filePath; // Path in 'local' disk
    }

    /**
     * Get the name of the module for the notification.
     */
    abstract protected function getModuleName(): string;

    /**
     * Process a single row from the CSV.
     * Throw an exception if validation or processing fails.
     */
    abstract protected function processRow(array $row): void;

    public function handle(): void
    {
        if (!Storage::disk('local')->exists($this->filePath)) {
            return; // File doesn't exist anymore
        }

        $stream = Storage::disk('local')->readStream($this->filePath);
        
        // Skip BOM if present
        $bom = fread($stream, 3);
        if ($bom !== b"\xEF\xBB\xBF") {
            rewind($stream);
        }

        // Detect delimiter
        $firstLine = fgets($stream);
        $delimiter = ',';
        if ($firstLine !== false) {
            $commaCount = substr_count($firstLine, ',');
            $semicolonCount = substr_count($firstLine, ';');
            if ($semicolonCount > $commaCount) {
                $delimiter = ';';
            }
        }

        // Rewind and skip BOM again for actual reading
        rewind($stream);
        $bom = fread($stream, 3);
        if ($bom !== b"\xEF\xBB\xBF") {
            rewind($stream);
        }
        
        $headers = fgetcsv($stream, 0, $delimiter);
        if (!$headers) {
            fclose($stream);
            return;
        }

        $successCount = 0;
        $failedRows = [];

        while (($row = fgetcsv($stream, 0, $delimiter)) !== false) {
            if (empty(array_filter($row))) {
                continue; // skip completely empty rows
            }

            // Map row data using headers
            $rowData = [];
            foreach ($headers as $index => $headerName) {
                // Trim header to remove hidden characters, usually BOM issue but handled above
                $headerName = trim($headerName); 
                $rowData[$headerName] = isset($row[$index]) ? trim($row[$index]) : null;
            }

            try {
                $this->processRow($rowData);
                $successCount++;
            } catch (\Exception $e) {
                $rowData['Error Message'] = $e->getMessage();
                $failedRows[] = $rowData;
            }
        }

        fclose($stream);
        
        // Clean up the uploaded file
        Storage::disk('local')->delete($this->filePath);

        $failedUrl = null;
        $failedCount = count($failedRows);

        // If there are failures, generate a failed rows CSV
        if ($failedCount > 0) {
            $failedFileName = 'failed_import_' . time() . '.csv';
            $failedFilePath = 'exports/' . $failedFileName; // Public directory
            
            $failedFile = fopen('php://temp', 'w+');
            fputs($failedFile, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            $failedHeaders = array_keys($failedRows[0]);
            fputcsv($failedFile, $failedHeaders, $delimiter);
            
            foreach ($failedRows as $failedRow) {
                fputcsv($failedFile, $failedRow, $delimiter);
            }
            
            rewind($failedFile);
            $content = stream_get_contents($failedFile);
            fclose($failedFile);

            Storage::disk('local')->put($failedFilePath, $content);
            $failedUrl = route('exports.download', ['file' => $failedFileName]);
        }

        // Notify user
        $expiresAt = $failedCount > 0 ? now()->addDays(1) : null;
        $this->user->notify(new CsvImportCompleted(
            $this->getModuleName(), 
            $successCount, 
            $failedCount, 
            $failedUrl,
            $expiresAt
        ));
    }
}
