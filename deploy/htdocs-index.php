<?php

/*
 * Contrôleur frontal de production (LWS Perso) — copié dans ~/htdocs/index.php par deploy/kh-deploy.php.
 * Le code reste hors du dossier web, dans ~/home/kilakodon-hub/current ; le .env et les journaux
 * dans ~/home/kilakodon-hub/shared. Seuls les fichiers publics (CSS, JS, images) sont dans htdocs.
 *
 * Chez LWS, le site web voit htdocs sous « /htdocs » (raccourci) alors que le compte est sous « /home » :
 * un chemin relatif (__DIR__/..) ne mène pas à l'application. On essaie donc le chemin relatif (Terminal,
 * hébergement classique) puis le chemin absolu constaté sur le serveur (diagnostic du 2026-11-20).
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appBase = null;
foreach ([dirname(__DIR__).'/home/kilakodon-hub', '/home/home/kilakodon-hub'] as $candidate) {
    if (is_file($candidate.'/current/vendor/autoload.php')) {
        $appBase = $candidate;
        break;
    }
}

if ($appBase === null) {
    http_response_code(503);
    header('Retry-After: 300');
    exit('Site en cours de mise à jour. Merci de revenir dans quelques minutes.');
}

// Mode maintenance (php artisan down), dans le storage partagé…
if (file_exists($maintenance = $appBase.'/shared/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appBase.'/current/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appBase.'/current/bootstrap/app.php';

// Les fichiers publics sont servis depuis htdocs (Vite, images, favicon).
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
