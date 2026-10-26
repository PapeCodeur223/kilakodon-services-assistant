<?php

use App\Http\Controllers\Public\ArticleController;
use App\Http\Controllers\Public\CaseStudyController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\SectorController;
use App\Http\Controllers\Public\SeoController;
use App\Http\Controllers\Public\SolutionController;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

Route::get('/', HomeController::class)->name('home');

Route::get('/solutions', [SolutionController::class, 'index'])->name('solutions.index');
Route::get('/solutions/{slug}', [SolutionController::class, 'show'])->name('solutions.show');
Route::get('/secteurs/{slug}', [SectorController::class, 'show'])->name('sectors.show');
Route::get('/realisations', [CaseStudyController::class, 'index'])->name('cases.index');
Route::get('/realisations/{slug}', [CaseStudyController::class, 'show'])->name('cases.show');
Route::get('/ressources', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/ressources/{slug}', [ArticleController::class, 'show'])->name('articles.show');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware([ProtectAgainstSpam::class, 'throttle:contact'])
    ->name('contact.store');

// Pages éditoriales (table `pages`).
Route::get('/methode', [PageController::class, 'show'])->defaults('slug', 'methode')->name('pages.method');
Route::get('/a-propos', [PageController::class, 'show'])->defaults('slug', 'a-propos')->name('pages.about');
Route::get('/mentions-legales', [PageController::class, 'show'])->defaults('slug', 'mentions-legales')->name('legal.notice');
Route::get('/confidentialite', [PageController::class, 'show'])->defaults('slug', 'confidentialite')->name('legal.privacy');
Route::get('/cookies', [PageController::class, 'show'])->defaults('slug', 'cookies')->name('legal.cookies');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Catalogue des composants — environnement local uniquement (404 ailleurs).
Route::view('/_styleguide', 'pages.styleguide')
    ->middleware('env:local')
    ->name('styleguide');
