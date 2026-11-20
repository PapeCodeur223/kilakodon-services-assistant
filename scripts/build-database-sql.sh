#!/usr/bin/env bash
# Fabrique dist/kilakodon-hub-base.sql : la base de production complète (tables + contenus validés,
# questionnaire, réglages, base de connaissances en brouillon), SANS compte ni donnée de test.
# Plan B du lot 7 : à importer dans phpMyAdmin quand le Terminal LWS interrompt « php artisan migrate ».
#
#   bash scripts/build-database-sql.sh
#
# Utilise une base locale temporaire (kilakodon_hub_release), supprimée à la fin.
# La base de développement n'est jamais touchée.
set -euo pipefail

cd "$(dirname "$0")/.."
PHP="${PHP:-/c/php/8.2/php.exe}"
MYSQL_BIN="${MYSQL_BIN:-/c/xampp/mysql/bin}"
DB=kilakodon_hub_release
USER="$(grep -E '^DB_USERNAME=' .env | cut -d= -f2-)"
PASS="$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//')"
AUTH=(-h 127.0.0.1 -u "$USER")
[ -n "$PASS" ] && AUTH+=(-p"$PASS")

echo "1. Base temporaire $DB"
"$MYSQL_BIN/mysql.exe" "${AUTH[@]}" -e "DROP DATABASE IF EXISTS \`$DB\`; CREATE DATABASE \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "2. Tables et données de production (APP_ENV=production : aucun compte ni donnée de test)"
export DB_DATABASE="$DB" APP_ENV=production APP_DEBUG=false
"$PHP" artisan migrate --force --no-interaction
"$PHP" artisan kh:seed-production --no-interaction

echo "3. Export SQL"
mkdir -p dist
OUT=dist/kilakodon-hub-base.sql
{
    echo "-- Kilakodon Hub — base de production initiale (lot 7), générée le $(date '+%Y-%m-%d %H:%M')."
    echo "-- À importer dans phpMyAdmin, dans la base VIDE du site. Aucun compte utilisateur : créez l'administrateur ensuite."
    echo "SET NAMES utf8mb4;"
    "$MYSQL_BIN/mysqldump.exe" "${AUTH[@]}" --single-transaction --skip-lock-tables --no-create-db --skip-comments \
        --default-character-set=utf8mb4 --ignore-table="$DB.kh_sessions" --ignore-table="$DB.kh_cache" \
        --ignore-table="$DB.kh_cache_locks" --ignore-table="$DB.kh_jobs" --ignore-table="$DB.kh_failed_jobs" "$DB"
    # Tables techniques : structure seule (sessions, cache, file d'attente).
    "$MYSQL_BIN/mysqldump.exe" "${AUTH[@]}" --no-data --skip-comments --default-character-set=utf8mb4 "$DB" \
        kh_sessions kh_cache kh_cache_locks kh_jobs kh_failed_jobs
} > "$OUT"

echo "4. Nettoyage"
"$MYSQL_BIN/mysql.exe" "${AUTH[@]}" -e "DROP DATABASE \`$DB\`;"

grep -c "INSERT INTO \`kh_users\`" "$OUT" >/dev/null && { echo "ERREUR : des comptes utilisateurs sont dans l'export"; exit 1; } || true
printf 'Prêt : %s (%s Ko)\n' "$OUT" "$(( $(wc -c < "$OUT") / 1024 ))"
