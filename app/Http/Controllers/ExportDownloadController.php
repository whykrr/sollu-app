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

        if (! Storage::disk('local')->exists($filePath)) {
            abort(404, 'File not found or has expired.');
        }

        $absolutePath = Storage::disk('local')->path($filePath);

        return response()->download($absolutePath)->deleteFileAfterSend(true);
    }
}
