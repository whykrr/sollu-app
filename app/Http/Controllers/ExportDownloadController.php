<?php

namespace App\Http\Controllers;

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

        if (! Storage::exists($filePath)) {
            abort(404, 'File not found or has expired.');
        }

        // Stream file content from default/cloud storage to local temporary file
        $stream = Storage::readStream($filePath);
        $tempPath = tempnam(sys_get_temp_dir(), 'sollu_export_');
        $localStream = fopen($tempPath, 'w');

        stream_copy_to_stream($stream, $localStream);

        if (is_resource($localStream)) {
            fclose($localStream);
        }
        if (is_resource($stream)) {
            fclose($stream);
        }

        // Delete the original file from cloud storage
        Storage::delete($filePath);

        // Download the local temporary file and delete it after sending
        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }
}
