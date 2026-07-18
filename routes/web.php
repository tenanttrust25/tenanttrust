<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
\n// Tenant Trust Routes\nrequire __DIR__.'/tenanttrust.php';
