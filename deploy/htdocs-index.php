<?php

/*
 * Contrôleur frontal de production (LWS Perso) — copié dans ~/htdocs/index.php par deploy/kh-deploy.sh.
 * Le code, le .env et les journaux restent hors du dossier web, dans ~/home/kilakodon-hub/current.
 * Seuls les fichiers publics (CSS, JS, images) sont dans htdocs.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appPath = __DIR__.'/../home/kilakodon-hub/current';

// Mode maintenance (php artisan down)…
if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appPath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

// Les fichiers publics sont servis depuis htdocs (Vite, images, favicon).
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
