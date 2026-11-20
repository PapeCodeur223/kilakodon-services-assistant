<?php

/*
 * Fabrique l'archive de mise en ligne (lot 7), à lancer en local depuis la racine du projet :
 *
 *   C:\php\8.2\php.exe scripts/build-release.php [chemin/vers/composer.phar]
 *
 * - part du dernier commit (git archive) : rien de non commité ne part en production ;
 * - ajoute les fichiers compilés (npm run build) et les assets Filament, qui ne sont pas dans Git ;
 * - installe les dépendances de production seulement (composer --no-dev), dans un dossier à part :
 *   le vendor/ de développement local n'est pas touché ;
 * - écrit dist/kilakodon-hub-<date>-<commit>.zip et dist/kh-deploy.sh (fins de ligne Unix).
 */

$root = dirname(__DIR__);
$composer = $argv[1] ?? 'C:/Programmation Web/MinGW/Composer/composer.phar';
$php = PHP_BINARY;

function run(string $command, string $cwd, int $attempts = 1): void
{
    $code = 1;
    for ($try = 1; $try <= $attempts; $try++) {
        echo "  > {$command}".($try > 1 ? " (essai {$try})" : '')."\n";
        passthru('cd '.escapeshellarg($cwd)." && {$command}", $code);
        if ($code === 0) {
            return;
        }
        sleep(3); // verrou passager de Windows : Composer reprend là où il s'est arrêté
    }
    fwrite(STDERR, "Échec ({$code}) : {$command}\n");
    exit(1);
}

function copyDir(string $from, string $to): void
{
    if (! is_dir($from)) {
        fwrite(STDERR, "Dossier manquant : {$from} (lancez npm run build et php artisan filament:assets)\n");
        exit(1);
    }
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    @mkdir($to, 0777, true);
    foreach ($items as $item) {
        $target = $to.DIRECTORY_SEPARATOR.substr($item->getPathname(), strlen($from) + 1);
        $item->isDir() ? @mkdir($target, 0777, true) : copy($item->getPathname(), $target);
    }
}

function removeDir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

chdir($root);
$status = trim((string) shell_exec('git status --porcelain'));
if ($status !== '') {
    echo "Attention : des modifications ne sont pas commitées ; seule la version commitée sera empaquetée.\n";
}
$sha = trim((string) shell_exec('git rev-parse --short HEAD'));
$name = 'kilakodon-hub-'.date('Ymd-Hi').'-'.$sha;
$dist = $root.'/dist';
// Dossier de travail hors du projet (moins exposé aux verrous de l'indexeur et de l'antivirus Windows).
$build = sys_get_temp_dir().'/kh-release/'.$name;

echo "1. Copie du commit {$sha}\n";
@mkdir($dist, 0777, true);
@mkdir(dirname($build), 0777, true);
removeDir($build);
run('git archive --format=zip -o '.escapeshellarg("{$dist}/source.zip").' HEAD', $root);
$zip = new ZipArchive;
$zip->open("{$dist}/source.zip");
$zip->extractTo($build);
$zip->close();
unlink("{$dist}/source.zip");

echo "2. Fichiers compilés et assets Filament\n";
foreach (['public/build', 'public/css/filament', 'public/js/filament', 'public/fonts/filament'] as $dir) {
    copyDir("{$root}/{$dir}", "{$build}/{$dir}");
}

echo "3. Dépendances de production\n";
run(escapeshellarg($php).' '.escapeshellarg($composer).' install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --no-progress', $build, 3);

echo "4. Nettoyage\n";
foreach (['tests', 'docs', '.github', 'node_modules', 'scripts'] as $dir) {
    removeDir("{$build}/{$dir}");
}
foreach (['phpunit.xml', 'phpstan.neon', 'package.json', 'package-lock.json', 'vite.config.js', '.editorconfig', 'CLAUDE.md'] as $file) {
    @unlink("{$build}/{$file}");
}
removeDir("{$build}/.claude");

echo "5. Archive\n";
$archive = "{$dist}/{$name}.zip";
$out = new ZipArchive;
$out->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($build, FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($build) + 1));
    $out->addFile($file->getPathname(), $relative);
}
$out->close();
removeDir($build);

// Script de déploiement à côté de l'archive, avec des fins de ligne Unix (sinon bash échoue sur LWS).
file_put_contents("{$dist}/kh-deploy.sh", str_replace("\r\n", "\n", (string) file_get_contents("{$root}/deploy/kh-deploy.sh")));

printf("\nPrêt :\n  %s (%.1f Mo)\n  %s\n", $archive, filesize($archive) / 1048576, "{$dist}/kh-deploy.sh");
