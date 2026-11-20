<?php

/*
 * =============================================================================
 * kh-deploy.php — mise en ligne de Kilakodon Hub sur LWS Perso (Terminal du panneau, sans SSH)
 *
 *   php kh-deploy.php check                    Vérifie PHP, extensions et dossiers (ne modifie rien)
 *   php kh-deploy.php install <archive.zip>    Installe ou met à jour le site à partir de l'archive
 *   php kh-deploy.php publish                  Termine la mise en ligne (caches, htdocs) si l'installation a été interrompue
 *   php kh-deploy.php rollback                 Revient à la version précédente (fichiers, pas la base)
 *   php kh-deploy.php cron                     Affiche la ligne à coller dans « Tâches cron »
 *
 * Écrit en PHP pur : l'hébergement ne fournit pas toutes les commandes Unix (ni xargs, ni basename),
 * et aucun lien symbolique n'est utilisé.
 *
 * Organisation sur le serveur :
 *   ~/home/kilakodon-hub/current/    version en ligne (code)
 *   ~/home/kilakodon-hub/previous/   version précédente (retour arrière)
 *   ~/home/kilakodon-hub/shared/     .env (secrets, jamais écrasé) et storage/ (journaux, sessions)
 *   ~/htdocs/                        fichiers publics seulement (CSS, JS, images, index.php)
 * =============================================================================
 */

const SITE_URL = 'https://www.kilakodon-services.com';
const EXTENSIONS = ['pdo_mysql', 'mbstring', 'intl', 'gd', 'fileinfo', 'bcmath', 'openssl', 'curl', 'dom', 'xml', 'tokenizer', 'ctype', 'json', 'sodium', 'zip'];

$home = rtrim((string) (getenv('HOME') ?: dirname(__DIR__)), '/');
$base = $home.'/home/kilakodon-hub';
$web = $home.'/htdocs';
$shared = $base.'/shared';
$php = getenv('PHP_BIN') ?: PHP_BINARY;

function ok(string $m): void
{
    echo "  \033[32m✔\033[0m {$m}\n";
}
function warn(string $m): void
{
    echo "  \033[33m!\033[0m {$m}\n";
}
function step(string $m): void
{
    echo "\n\033[1m{$m}\033[0m\n";
}
function fail(string $m): never
{
    echo "  \033[31m✘ {$m}\033[0m\n";
    exit(1);
}

function removeTree(string $path): void
{
    if (is_link($path) || is_file($path)) {
        unlink($path);

        return;
    }
    if (! is_dir($path)) {
        return;
    }
    foreach (scandir($path) ?: [] as $item) {
        if ($item !== '.' && $item !== '..') {
            removeTree($path.'/'.$item);
        }
    }
    rmdir($path);
}

function copyTree(string $from, string $to): void
{
    if (is_dir($from)) {
        if (! is_dir($to)) {
            mkdir($to, 0755, true);
        }
        foreach (scandir($from) ?: [] as $item) {
            if ($item !== '.' && $item !== '..') {
                copyTree($from.'/'.$item, $to.'/'.$item);
            }
        }

        return;
    }
    copy($from, $to) || fail("Copie impossible : {$from}");
}

/** Lance une commande artisan dans un dossier ; s'arrête si elle échoue (sauf $allowFailure). */
function artisan(string $dir, string $command, bool $allowFailure = false): bool
{
    global $php;
    // --no-interaction : jamais de question cachée qui bloquerait le Terminal ; une erreur s'affiche à la place.
    $line = 'cd '.escapeshellarg($dir).' && '.escapeshellarg($php).' -d memory_limit=512M artisan '.$command.' --no-interaction';
    if (! function_exists('passthru')) {
        fail("La fonction PHP passthru est désactivée. Lancez à la main : {$line}");
    }
    passthru($line, $code);
    if ($code !== 0 && ! $allowFailure) {
        fail("Échec de : php artisan {$command} (voir le message ci-dessus et {$GLOBALS['shared']}/storage/logs/). Pour revenir à la version précédente : php kh-deploy.php rollback");
    }

    return $code === 0;
}

function check(): void
{
    global $web, $base, $php;

    step('1. PHP');
    PHP_VERSION_ID >= 80200 ? ok('PHP '.PHP_VERSION.' (ligne de commande)') : fail('PHP '.PHP_VERSION.' : il faut PHP 8.2 ou plus récent.');
    warn('Dans « Configuration php » du panneau, choisissez la MÊME version ('.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.') pour le site.');

    step('2. Extensions PHP');
    $missing = array_values(array_filter(EXTENSIONS, fn (string $e): bool => ! extension_loaded($e)));
    foreach (EXTENSIONS as $e) {
        in_array($e, $missing, true) ? warn("{$e} manquante") : ok($e);
    }
    if ($missing !== []) {
        fail('Activez les extensions manquantes dans « Configuration php », puis relancez.');
    }

    step('3. Dossiers et fonctions');
    is_dir($web) ? ok("Dossier web : {$web}") : fail("Dossier web introuvable : {$web}");
    is_dir($base) || mkdir($base, 0755, true);
    ok("Dossier de l'application : {$base} (hors du web)");
    function_exists('passthru') ? ok('Lancement des commandes artisan possible') : fail('La fonction PHP passthru est désactivée : envoyez une capture à l\'équipe technique.');
    ok('memory_limit = '.ini_get('memory_limit'));
    ok("PHP utilisé pour artisan : {$php}");
}

function install(?string $archive): void
{
    global $base, $web, $shared;

    if ($archive === null || ! is_file($archive)) {
        fail('Archive introuvable. Usage : php kh-deploy.php install kilakodon-hub-XXXX.zip');
    }

    check();

    // Dossiers partagés : .env et storage, conservés d'une version à l'autre.
    foreach (['storage/app/private', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $dir) {
        is_dir("{$shared}/{$dir}") || mkdir("{$shared}/{$dir}", 0755, true);
    }
    touch("{$shared}/.kh-shared");

    $stamp = date('Ymd-His');
    $incoming = "{$base}/incoming";
    step("4. Extraction de la version {$stamp}");
    removeTree($incoming);
    $zip = new ZipArchive;
    $zip->open($archive) === true || fail("Archive illisible : {$archive}");
    $zip->extractTo($incoming) || fail('Extraction impossible (espace disque ?)');
    $zip->close();
    removeTree("{$incoming}/storage"); // le storage partagé est utilisé à la place
    file_put_contents("{$incoming}/.kh-version", $stamp.' '.basename($archive)."\n");
    ok("Code extrait dans {$incoming}");

    if (! is_file("{$shared}/.env")) {
        copy("{$incoming}/deploy/env.production.example", "{$shared}/.env");
        chmod("{$shared}/.env", 0600);
        removeTree($incoming);
        step("Fichier de réglages créé : {$shared}/.env");
        echo "  1. Ouvrez-le : gestionnaire de fichiers LWS → home → kilakodon-hub → shared → .env\n";
        echo "     (fichier caché), ou dans le Terminal : nano {$shared}/.env\n";
        echo "  2. Remplissez les lignes « À REMPLIR » (base de données, e-mail, clé Anthropic). Enregistrez.\n";
        echo '  3. Relancez exactement la même commande : php kh-deploy.php install '.basename($archive)."\n";
        exit(0);
    }
    chmod("{$shared}/.env", 0600);

    step('5. Mise en ligne de la nouvelle version');
    if (is_dir("{$base}/current")) {
        artisan("{$base}/current", 'down --retry=30', true);
        removeTree("{$base}/previous");
        rename("{$base}/current", "{$base}/previous") || fail('Impossible de mettre de côté la version actuelle.');
        ok('Version actuelle conservée dans previous/ (retour arrière possible)');
    }
    rename($incoming, "{$base}/current") || fail('Impossible d\'installer la nouvelle version.');
    ok('Nouvelle version installée dans current/');

    step('6. Clé de l\'application et base de données');
    if (! preg_match('/^APP_KEY=base64:/m', (string) file_get_contents("{$shared}/.env"))) {
        artisan("{$base}/current", 'key:generate --force');
        ok('Clé de chiffrement générée (dans shared/.env)');
    }
    artisan("{$base}/current", 'migrate --force');
    artisan("{$base}/current", 'kh:seed-production');

    publish();

    echo "\n  Version en ligne : {$stamp}\n";
    echo "  Première installation ? Créez un administrateur : cd {$base}/current && php artisan kh:make-admin\n";
    echo "  Puis affichez la ligne cron : cd ~ && php kh-deploy.php cron\n";
}

/**
 * Étapes 7 et 8 seules (caches, fichiers publics, vérification) : à relancer si le Terminal LWS
 * a interrompu l'installation (« Killed ») après la base de données.
 */
function publish(): void
{
    global $base, $shared;

    is_dir("{$base}/current/vendor") || fail('Aucune version installée dans current/ : lancez d\'abord install.');

    step('7. Caches de production et fichiers publics');
    artisan("{$base}/current", 'optimize');
    artisan("{$base}/current", 'filament:optimize', true);
    firstWebInstall();
    syncWeb("{$base}/current");
    artisan("{$base}/current", 'up', true);
    artisan("{$base}/current", 'queue:restart', true);

    step('8. Vérification');
    $context = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
    $body = @file_get_contents(SITE_URL.'/up', false, $context);
    // http_get_last_response_headers() remplace $http_response_header, déprécié en PHP 8.5.
    $headers = function_exists('http_get_last_response_headers') ? (http_get_last_response_headers() ?? []) : ($http_response_header ?? []);
    $status = $headers[0] ?? 'pas de réponse';
    $body !== false && str_contains($status, '200') ? ok(SITE_URL."/up : {$status}") : warn(SITE_URL."/up : {$status} — vérifiez dans le navigateur ; journaux dans {$shared}/storage/logs/");
}

function firstWebInstall(): void
{
    global $web;

    if (is_file("{$web}/.kh-installed")) {
        return;
    }
    $old = array_values(array_filter(scandir($web) ?: [], fn (string $i): bool => ! in_array($i, ['.', '..', '.quarantaine', '.well-known'], true)));
    if ($old !== []) {
        step('Ancien site détecté dans '.$web.' ('.count($old).' élément(s))');
        foreach ($old as $item) {
            echo "  · {$item}\n";
        }
        echo "\n  Ces fichiers vont être SUPPRIMÉS (vous avez déjà sauvegardé l'ancien site).\n";
        echo '  Tapez REMPLACER pour continuer, ou autre chose pour annuler : ';
        $answer = trim((string) fgets(STDIN));
        if ($answer !== 'REMPLACER') {
            fail('Annulé : l\'ancien site n\'a pas été touché. Le nouveau code est installé mais pas visible ; relancez la commande pour terminer.');
        }
        foreach ($old as $item) {
            removeTree("{$web}/{$item}");
        }
        ok("Ancien site retiré de {$web}");
    }
    touch("{$web}/.kh-installed");
}

function syncWeb(string $release): void
{
    global $web;

    // Fichiers publics de la version (CSS/JS compilés, images, polices, .htaccess), puis le contrôleur frontal.
    copyTree("{$release}/public", $web);
    copy("{$release}/deploy/htdocs-index.php", "{$web}/index.php") || fail('Copie de index.php impossible');
    ok("Fichiers publics copiés dans {$web}");
}

function rollback(): void
{
    global $base;

    is_dir("{$base}/previous") || fail('Aucune version précédente.');
    rename("{$base}/current", "{$base}/rollback-tmp");
    rename("{$base}/previous", "{$base}/current");
    rename("{$base}/rollback-tmp", "{$base}/previous");
    artisan("{$base}/current", 'optimize');
    syncWeb("{$base}/current");
    artisan("{$base}/current", 'up', true);
    ok('Retour à la version précédente : '.trim((string) @file_get_contents("{$base}/current/.kh-version")).' (la base de données n\'est pas modifiée).');
}

function cron(): void
{
    global $base, $shared, $home, $php;

    // Le panneau LWS compte les chemins depuis la racine du compte (exemple : php /htdocs/monfichier.php),
    // alors que le Terminal les voit sous $HOME : on retire ce préfixe pour la ligne cron.
    $root = substr($base, strlen($home));
    $rootShared = substr($shared, strlen($home));
    echo "\n  Panneau LWS → Tâches cron → Création d'une tâche Cron :\n";
    echo "  · Minute : toutes les minutes ; Heure, Jour du mois, Mois, Jour de la semaine : tous\n";
    echo "  · Commande :\n\n";
    echo "    php {$root}/current/artisan schedule:run >> {$rootShared}/storage/logs/cron.log 2>&1\n\n";
    echo "  Vérification deux minutes plus tard, dans le Terminal : tail {$shared}/storage/logs/cron.log\n";
    echo "  Si le fichier reste vide, remplacez la commande par la variante au chemin complet :\n";
    echo "    {$php} {$base}/current/artisan schedule:run >> {$shared}/storage/logs/cron.log 2>&1\n";
}

match ($argv[1] ?? '') {
    'check' => check(),
    'install' => install($argv[2] ?? null),
    'rollback' => rollback(),
    'publish' => publish(),
    'cron' => cron(),
    default => print ("Usage : php kh-deploy.php check | install <archive.zip> | publish | rollback | cron\n"),
};
