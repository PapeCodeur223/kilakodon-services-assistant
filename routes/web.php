<?php

use App\Http\Controllers\Public\AppointmentController;
use App\Http\Controllers\Public\ArticleController;
use App\Http\Controllers\Public\AssistantController;
use App\Http\Controllers\Public\BriefController;
use App\Http\Controllers\Public\CaseStudyController;
use App\Http\Controllers\Public\ConsentController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\DiagnosticController;
use App\Http\Controllers\Public\EventController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LabController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PrivacyController;
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

Route::get('/rendez-vous', [AppointmentController::class, 'show'])->name('appointment');
Route::post('/rendez-vous', [AppointmentController::class, 'store'])
    ->middleware([ProtectAgainstSpam::class, 'throttle:contact'])
    ->name('appointment.store');

// « Gérer mes données » : demandes de droits (PRV-02), traitées à la main dans l'admin.
Route::get('/mes-donnees', [PrivacyController::class, 'show'])->name('privacy.form');
Route::post('/mes-donnees', [PrivacyController::class, 'store'])
    ->middleware([ProtectAgainstSpam::class, 'throttle:privacy'])
    ->name('privacy.store');

// Diagnostic (Livewire) et brief privé — liens à token limités en débit.
Route::get('/diagnostic', [DiagnosticController::class, 'start'])->name('diagnostic.start');
Route::middleware('throttle:public-token')->group(function (): void {
    Route::get('/diagnostic/reprendre/{token}', [DiagnosticController::class, 'resume'])->name('diagnostic.resume');
    Route::get('/brief/{token}', [BriefController::class, 'show'])->name('brief.show');
    Route::get('/brief/{token}/pdf', [BriefController::class, 'pdf'])->name('brief.pdf');
});

// Démo Lab (TRD §8) : espace fictif par visiteur, cookie chiffré `kh_lab`.
Route::get('/demo-lab', [LabController::class, 'index'])->name('lab.index');
Route::middleware('throttle:lab')->group(function (): void {
    Route::get('/demo-lab/boutique', [LabController::class, 'retail'])->name('lab.retail');
    Route::get('/demo-lab/boutique/factures/{number}', [LabController::class, 'invoice'])
        ->where('number', 'DEMO-[0-9]{4,6}')
        ->name('lab.invoice');
});
Route::get('/demo-lab/{slug}', [LabController::class, 'guided'])->name('lab.guided');

// Pages éditoriales (table `pages`).
Route::get('/methode', [PageController::class, 'show'])->defaults('slug', 'methode')->name('pages.method');
Route::get('/a-propos', [PageController::class, 'show'])->defaults('slug', 'a-propos')->name('pages.about');
Route::get('/mentions-legales', [PageController::class, 'show'])->defaults('slug', 'mentions-legales')->name('legal.notice');
Route::get('/confidentialite', [PageController::class, 'show'])->defaults('slug', 'confidentialite')->name('legal.privacy');
Route::get('/cookies', [PageController::class, 'show'])->defaults('slug', 'cookies')->name('legal.cookies');

// API interne de l'assistant (TRD §5.2) : JSON, même origine, CSRF (groupe web) et limitation de débit.
Route::prefix('api/assistant')->name('assistant.')->group(function (): void {
    Route::middleware('throttle:assistant')->group(function (): void {
        Route::post('/conversations', [AssistantController::class, 'start'])->name('start');
        Route::post('/conversations/{conversation}/messages', [AssistantController::class, 'message'])->name('message');
    });
    Route::middleware('throttle:assistant-actions')->group(function (): void {
        Route::post('/actions/{proposal}/revise', [AssistantController::class, 'revise'])->name('actions.revise');
        Route::post('/actions/{proposal}/confirm', [AssistantController::class, 'confirm'])->name('actions.confirm');
        Route::post('/actions/{proposal}/cancel', [AssistantController::class, 'cancel'])->name('actions.cancel');
        Route::post('/conversations/{conversation}/messages/{message}/feedback', [AssistantController::class, 'feedback'])
            ->whereNumber('message')->name('feedback');
    });
});

// Mesure first-party (TRD §9) : liste blanche, CSRF (groupe web), débit limité.
Route::post('/api/events', [EventController::class, 'store'])->middleware('throttle:events')->name('events.store');
// Bannière cookies : formulaire simple, fonctionne sans JavaScript (PRV-01).
Route::post('/cookies/preferences', [ConsentController::class, 'update'])->middleware('throttle:consent')->name('consent.update');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Catalogue des composants — environnement local uniquement (404 ailleurs).
Route::view('/_styleguide', 'pages.styleguide')
    ->middleware('env:local')
    ->name('styleguide');
