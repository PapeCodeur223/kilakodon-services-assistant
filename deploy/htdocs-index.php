<?php

/*
 * Contrôleur frontal de production (LWS Perso) — copié dans ~/htdocs/index.php par deploy/kh-deploy.php.
 * Le code reste hors du dossier web, dans ~/home/kilakodon-hub/current ; le .env et les journaux
 * dans ~/home/kilakodon-hub/shared.
 * Seuls les fichiers publics (CSS, JS, images) sont dans htdocs.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appPath = __DIR__.'/../home/kilakodon-hub/current';

// Mode maintenance (php artisan down), dans le storage partagé…
if (file_exists($maintenance = __DIR__.'/../home/kilakodon-hub/shared/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appPath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

// Les fichiers publics sont servis depuis htdocs (Vite, images, favicon).
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
