<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::view('/upload', 'upload')->name('upload.form');

Route::post('/upload', function (Request $request) {
    $request->validate([
        'site_archive' => 'required|file|mimes:gz,tgz|max:512000',
    ]);

    $file = $request->file('site_archive');
    $tmpPath = $file->storeAs('uploads', 'site.tar.gz');

    if (!$tmpPath) {
        return back()->with('error', 'Failed to save uploaded file.');
    }

    $fullPath = storage_path('app/' . $tmpPath);

    // Create extraction directory
    $extractDir = '/var/www/tenanttrust/public/site';
    if (!is_dir($extractDir)) {
        mkdir($extractDir, 0755, true);
    }

    // Extract the tar.gz
    $cmd = "cd " . escapeshellarg($extractDir) . " && tar xzf " . escapeshellarg($fullPath) . " 2>&1";
    exec($cmd, $output, $exitCode);

    if ($exitCode !== 0) {
        return back()->with('error', 'Extraction failed: ' . implode("\n", $output));
    }

    return back()->with('success', 'Site uploaded and extracted successfully! Files are at: ' . $extractDir);
})->name('upload.process');
