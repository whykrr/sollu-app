<?php

namespace App\Http\Controllers\App\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportDownloadController extends Controller
{
    public function download(Request $request)
    {
        $fileName = $request->query('file');

        if (! $fileName) {
            abort(404, 'File not specified.');
        }

        // Validate filename to prevent directory traversal
        if (preg_match('/\.\./', $fileName) || strpos($fileName, '/') !== false) {
            abort(403, 'Invalid file name.');
        }

        $filePath = 'exports/'.$fileName;

        // Check in public export storage disk, fallback to default disk if legacy
        $disk = 'public';
        if (! Storage::disk($disk)->exists($filePath)) {
            if (Storage::disk(config('filesystems.default'))->exists($filePath)) {
                $disk = config('filesystems.default');
            } else {
                abort(404, 'File not found or has expired.');
            }
        }

        // Stream file content to local temporary file
        $stream = Storage::disk($disk)->readStream($filePath);
        $tempPath = tempnam(sys_get_temp_dir(), 'sollu_export_');
        $localStream = fopen($tempPath, 'w');

        stream_copy_to_stream($stream, $localStream);

        if (is_resource($localStream)) {
            fclose($localStream);
        }
        if (is_resource($stream)) {
            fclose($stream);
        }

        // Delete the original file from storage
        Storage::disk($disk)->delete($filePath);

        // Download the local temporary file and delete it after sending
        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }
}
