#!/usr/bin/env bash
# =============================================================================
# kh-deploy.sh — mise en ligne de Kilakodon Hub sur LWS Perso (Terminal du panneau, sans SSH)
#
#   bash kh-deploy.sh check                     Vérifie PHP, extensions et dossiers (ne modifie rien)
#   bash kh-deploy.sh install <archive.zip>     Installe ou met à jour le site à partir de l'archive
#   bash kh-deploy.sh rollback                  Revient à la version précédente (fichiers, pas la base)
#   bash kh-deploy.sh cron                      Affiche la ligne à coller dans « Tâches cron »
#
# Organisation sur le serveur :
#   ~/home/kilakodon-hub/releases/<date>/   versions successives du code (3 gardées)
#   ~/home/kilakodon-hub/current            lien vers la version en ligne
#   ~/home/kilakodon-hub/shared/.env        réglages et secrets (hors du web, jamais écrasé)
#   ~/home/kilakodon-hub/shared/storage/    journaux, sessions, caches (conservés d'une version à l'autre)
#   ~/htdocs/                               fichiers publics seulement (CSS, JS, images, index.php)
# =============================================================================
set -euo pipefail

PHP_BIN="${PHP_BIN:-php}"
BASE="$HOME/home/kilakodon-hub"
WEB="$HOME/htdocs"
SITE_URL="https://www.kilakodon-services.com"
KEEP=3

ok()   { printf '  \033[32m✔\033[0m %s\n' "$1"; }
warn() { printf '  \033[33m!\033[0m %s\n' "$1"; }
fail() { printf '  \033[31m✘ %s\033[0m\n' "$1"; exit 1; }
step() { printf '\n\033[1m%s\033[0m\n' "$1"; }

artisan() { (cd "$1" && "$PHP_BIN" artisan "${@:2}"); }

check() {
    step "1. PHP"
    local version
    version="$("$PHP_BIN" -r 'echo PHP_VERSION;')"
    "$PHP_BIN" -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' \
        && ok "PHP $version (ligne de commande)" || fail "PHP $version : il faut PHP 8.2 ou plus récent."
    warn "Dans « Configuration php » du panneau, choisissez la MÊME version ($version) pour le site."

    step "2. Extensions PHP"
    local missing=0
    for ext in pdo_mysql mbstring intl gd fileinfo bcmath openssl curl dom xml tokenizer ctype json sodium zip; do
        if "$PHP_BIN" -m | grep -qi "^${ext}$"; then ok "$ext"; else warn "$ext manquante"; missing=1; fi
    done
    [ "$missing" -eq 0 ] || warn "Activez les extensions manquantes dans « Configuration php », puis relancez."

    step "3. Dossiers"
    [ -d "$WEB" ] && ok "Dossier web : $WEB" || fail "Dossier web introuvable : $WEB"
    mkdir -p "$BASE" && ok "Dossier de l'application : $BASE (hors du web)"
    command -v unzip >/dev/null && ok "unzip disponible" || warn "unzip absent : l'archive sera extraite par PHP"
    command -v curl >/dev/null && ok "curl disponible" || warn "curl absent : la vérification finale sera à faire dans le navigateur"
    local tool
    for tool in basename cp ln rm mkdir date grep sed tail ls chmod touch; do
        command -v "$tool" >/dev/null || fail "Commande « $tool » introuvable sur le serveur : envoyez une capture à l'équipe technique."
    done
    ok "Commandes de base disponibles"
    # Pas de xargs sur LWS : uniquement des commandes de base.
    echo "  · memory_limit = $("$PHP_BIN" -r 'echo ini_get("memory_limit");')"
}

extract() {
    local archive="$1" dest="$2"
    mkdir -p "$dest"
    if command -v unzip >/dev/null; then
        unzip -q "$archive" -d "$dest"
    else
        "$PHP_BIN" -r '$z = new ZipArchive; if ($z->open($argv[1]) !== true) exit(1); $z->extractTo($argv[2]); $z->close();' "$archive" "$dest"
    fi
}

sync_web() {
    local release="$1"
    # Fichiers publics de la version : CSS/JS compilés, images, polices, .htaccess…
    cp -a "$release/public/." "$WEB/"
    # Contrôleur frontal qui charge l'application rangée hors du web.
    cp "$release/deploy/htdocs-index.php" "$WEB/index.php"
    ok "Fichiers publics copiés dans $WEB"
}

first_web_install() {
    # Première installation : l'ancien site occupe htdocs (sauvegardé par la direction le 2026-11-20).
    if [ -f "$WEB/.kh-installed" ]; then return 0; fi
    # Contenu actuel de htdocs, sans find ni wc (absents de certains hébergements) : globbing bash.
    local old=() entry name
    shopt -s nullglob dotglob
    for entry in "$WEB"/*; do
        name="$(basename "$entry")"
        case "$name" in .quarantaine | .well-known) continue ;; esac
        old+=("$entry")
    done
    shopt -u nullglob dotglob
    if [ "${#old[@]}" -gt 0 ]; then
        step "Ancien site détecté dans $WEB (${#old[@]} élément(s))"
        for entry in "${old[@]}"; do echo "  · $(basename "$entry")"; done
        echo
        echo "  Ces fichiers vont être SUPPRIMÉS (vous avez déjà sauvegardé l'ancien site)."
        read -r -p "  Tapez REMPLACER pour continuer, ou autre chose pour annuler : " answer
        [ "$answer" = "REMPLACER" ] || fail "Annulé : rien n'a été supprimé."
        rm -rf "${old[@]}"
        ok "Ancien site retiré de $WEB"
    fi
    touch "$WEB/.kh-installed"
}

install() {
    local archive="${1:-}"
    [ -n "$archive" ] && [ -f "$archive" ] || fail "Archive introuvable. Usage : bash kh-deploy.sh install kilakodon-hub-XXXX.zip"

    check

    local stamp release
    stamp="$(date +%Y%m%d-%H%M%S)"
    release="$BASE/releases/$stamp"

    step "4. Extraction de la version $stamp"
    mkdir -p "$BASE/releases" "$BASE/shared/storage"/{app/private,framework/{cache/data,sessions,views},logs}
    extract "$archive" "$release"
    ok "Code extrait dans $release"

    # Dossiers partagés entre les versions : .env et storage.
    rm -rf "$release/storage"
    ln -s "$BASE/shared/storage" "$release/storage"
    if [ ! -f "$BASE/shared/.env" ]; then
        cp "$release/deploy/env.production.example" "$BASE/shared/.env"
        chmod 600 "$BASE/shared/.env"
        ln -s "$BASE/shared/.env" "$release/.env"
        step "Fichier de réglages créé : $BASE/shared/.env"
        echo "  1. Ouvrez-le (gestionnaire de fichiers LWS, dossier home/kilakodon-hub/shared) ou : nano $BASE/shared/.env"
        echo "  2. Remplissez les lignes « À REMPLIR » (base de données, e-mail, clé Anthropic)."
        echo "  3. Relancez exactement la même commande : bash kh-deploy.sh install $archive"
        rm -rf "$release"
        exit 0
    fi
    ln -s "$BASE/shared/.env" "$release/.env"
    chmod 600 "$BASE/shared/.env"

    step "5. Clé de l'application et base de données"
    if ! grep -qE '^APP_KEY=base64:' "$BASE/shared/.env"; then
        artisan "$release" key:generate --force
        ok "Clé de chiffrement générée (dans shared/.env)"
    fi
    artisan "$release" migrate --force
    artisan "$release" kh:seed-production

    step "6. Caches de production"
    artisan "$release" optimize
    artisan "$release" filament:optimize || warn "filament:optimize ignoré"

    step "7. Mise en ligne"
    artisan "$BASE/current" down --retry=30 2>/dev/null || true
    ln -sfn "$release" "$BASE/current"
    first_web_install
    sync_web "$release"
    artisan "$BASE/current" up || true
    artisan "$BASE/current" queue:restart || true

    # Ne garder que les $KEEP dernières versions.
    ls -1dt "$BASE/releases"/*/ 2>/dev/null | tail -n +$((KEEP + 1)) | while read -r old; do rm -rf "$old"; done

    step "8. Vérification"
    local code
    code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$SITE_URL/up" || echo 000)"
    [ "$code" = "200" ] && ok "$SITE_URL/up répond 200" || warn "$SITE_URL/up répond $code — voir $BASE/shared/storage/logs/"
    echo
    echo "  Version en ligne : $stamp"
    echo "  Première installation ? Créez un administrateur : cd $BASE/current && $PHP_BIN artisan kh:make-admin"
    echo "  Puis affichez la ligne cron : bash kh-deploy.sh cron"
}

rollback() {
    local previous
    previous="$(ls -1dt "$BASE/releases"/*/ | sed -n 2p)"
    [ -n "$previous" ] || fail "Aucune version précédente."
    previous="${previous%/}"
    ln -sfn "$previous" "$BASE/current"
    sync_web "$previous"
    artisan "$BASE/current" optimize
    ok "Retour à la version $(basename "$previous") (la base de données n'est pas modifiée)."
}

cron() {
    # Le panneau LWS compte les chemins depuis la racine du compte (exemple : php /htdocs/monfichier.php),
    # alors que le Terminal les voit sous $HOME : on retire ce préfixe pour la ligne cron.
    local root="${BASE#"$HOME"}"
    echo
    echo "  Dans le panneau LWS → Tâches cron → Création d'une tâche Cron :"
    echo "  · Minute : toutes les minutes ; Heure, Jour du mois, Mois, Jour de la semaine : tous"
    echo "  · Commande :"
    echo
    echo "    php $root/current/artisan schedule:run >> $root/shared/storage/logs/cron.log 2>&1"
    echo
    echo "  Vérification deux minutes plus tard, dans le Terminal : tail $BASE/shared/storage/logs/cron.log"
    echo "  Si le fichier reste vide, essayez la variante avec le chemin complet :"
    echo "    $PHP_BIN $BASE/current/artisan schedule:run >> $BASE/shared/storage/logs/cron.log 2>&1"
}

case "${1:-}" in
    check) check ;;
    install) install "${2:-}" ;;
    rollback) rollback ;;
    cron) cron ;;
    *) sed -n '2,10p' "$0"; exit 1 ;;
esac
