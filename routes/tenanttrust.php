<?php

use Illuminate\Support\Facades\Route;
use App\Models\Property;

Route::get('/', function () {
    $properties = Property::available()->latest()->paginate(12);
    return view('welcome', compact('properties'));
})->name('home');

Route::view('/portals', 'portals')->name('portals');
