<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');

// Catalogue des composants — environnement local uniquement (404 ailleurs).
Route::view('/_styleguide', 'pages.styleguide')
    ->middleware('env:local')
    ->name('styleguide');
