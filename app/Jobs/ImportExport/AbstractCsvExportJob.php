<?php

namespace App\Jobs\ImportExport;

use App\Models\User;
use App\Notifications\CsvExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

abstract class AbstractCsvExportJob implements ShouldQueue
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
    abstract protected function getQuery();

    /**
     * Get the headers for the CSV file.
     */
    abstract protected function getHeaders(): array;

    /**
     * Map a single row from the database to an array of CSV columns.
     */
    abstract protected function mapRow($row): array;

    /**
     * Get the name of the module for the notification.
     */
    abstract protected function getModuleName(): string;

    /**
     * Get the desired file name for the export.
     */
    abstract protected function getFileName(): string;

    public function handle(): void
    {
        $this->user = User::find($this->userId);

        if (! $this->user) {
            return;
        }

        Storage::disk('local')->makeDirectory('exports');

        $fileName = $this->getFileName();
        $filePath = 'exports/'.$fileName;

        // Open temp file stream
        $file = fopen('php://temp', 'w+');

        // Write BOM for Excel compatibility (UTF-8)
        fwrite($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write headers
        fputcsv($file, $this->getHeaders());

        // Process data in chunks to save memory
        $this->getQuery()->chunk(500, function ($rows) use ($file) {
            foreach ($rows as $row) {
                fputcsv($file, $this->mapRow($row));
            }
        });

        rewind($file);
        $content = stream_get_contents($file);
        fclose($file);

        Storage::disk('local')->put($filePath, $content);
        $url = route('exports.download', ['file' => $fileName]);

        // Notify user
        $expiresAt = now()->addDays(1);
        $this->user->notify(new CsvExportCompleted($this->getModuleName(), $fileName, $url, $expiresAt));
    }
}
