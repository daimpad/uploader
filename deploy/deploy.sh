#!/usr/bin/env bash
#
# UploadEz – Deployment auf dem Server
#
# Läuft AUF dem Netcup-Server, nicht bei GitHub. Holt den Stand von
# origin/<branch>, installiert die Abhängigkeiten und prüft das Ergebnis.
# Geht dabei etwas schief, wird auf den vorherigen Commit zurückgesetzt.
#
# Nicht versionierte Dateien überleben den Abgleich: git reset fasst
# ignorierte Pfade nicht an. Das betrifft .env, uploads/, tmp/ und vendor/.
#
# Aufruf:
#   bash ./deploy/deploy.sh
#
# Stellschrauben (als Umgebungsvariablen, alle optional):
#   DEPLOY_BRANCH   Branch, der ausgeliefert wird        (Vorgabe: main)
#   COMPOSER_BIN    Pfad zu Composer                     (Vorgabe: composer)
#   PHP_BIN         Pfad zu PHP                          (Vorgabe: php)
#   RELOAD_CMD      Befehl zum Neuladen von PHP-FPM      (Vorgabe: keiner)
#   HEALTH_URL      URL für die Abnahme nach dem Deploy  (Vorgabe: keine)
#   APPLY_SCHEMA    1 = schema.sql einspielen            (Vorgabe: aus)

set -euo pipefail

log()  { printf '[deploy] %s\n' "$*"; }
warn() { printf '[deploy] Hinweis: %s\n' "$*" >&2; }
die()  { printf '[deploy] FEHLER: %s\n' "$*" >&2; exit 1; }

# Der gesamte Ablauf steckt in einer Funktion, und das hat einen Grund:
# git reset --hard schreibt deploy.sh neu, WÄHREND deploy.sh läuft — das
# Skript liegt ja selbst im Repository. Bash liest ein Skript aber nicht
# am Stück, sondern holt die nächsten Zeilen erst, wenn es sie braucht.
# Ohne diese Klammer liefe ab dem reset die neue Datei ab einem Byte-Versatz
# weiter, der zur alten gehört. Eine Funktion wird dagegen bis zur
# schließenden Klammer eingelesen, bevor ihr erster Befehl ausgeführt wird.
main() {

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BRANCH="${DEPLOY_BRANCH:-main}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
PHP_BIN="${PHP_BIN:-php}"
RELOAD_CMD="${RELOAD_CMD:-}"
HEALTH_URL="${HEALTH_URL:-}"
APPLY_SCHEMA="${APPLY_SCHEMA:-0}"

cd "$APP_DIR"

# ── Vorbedingungen ───────────────────────────────────────────────
[ -d .git ] || die "$APP_DIR ist kein Git-Arbeitsverzeichnis. Einmalige Einrichtung siehe deploy/README.md."
command -v git >/dev/null 2>&1 || die "git nicht gefunden."
command -v "$PHP_BIN" >/dev/null 2>&1 || die "PHP nicht gefunden (PHP_BIN=$PHP_BIN)."

# Ohne .env läuft die App nicht. Sie liegt bewusst nicht im Repository.
[ -f .env ] || die ".env fehlt in $APP_DIR. Aus .env.example anlegen und ausfüllen."

# ── Stand holen ─────────────────────────────────────────────────
log "Hole origin/$BRANCH …"
git fetch --prune --quiet origin "$BRANCH"

PREVIOUS="$(git rev-parse HEAD)"
TARGET="$(git rev-parse "origin/$BRANCH")"

if [ "$PREVIOUS" = "$TARGET" ]; then
    log "Bereits auf dem aktuellen Stand ($(git rev-parse --short HEAD))."
else
    log "$(git rev-parse --short "$PREVIOUS") → $(git rev-parse --short "$TARGET")"
fi

# Ab hier kann etwas schiefgehen. Der Rückweg steht bereit.
rollback() {
    warn "Setze zurück auf $(git rev-parse --short "$PREVIOUS") …"
    git reset --hard --quiet "$PREVIOUS" || warn "Rücksetzen fehlgeschlagen. Bitte von Hand prüfen."
    [ -f composer.json ] && "$COMPOSER_BIN" install --no-dev --no-interaction --quiet 2>/dev/null || true
}

git reset --hard --quiet "$TARGET"

# ── Abhängigkeiten ──────────────────────────────────────────────
if [ -f composer.json ]; then
    if command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
        log "Installiere Abhängigkeiten …"
        if ! "$COMPOSER_BIN" install \
                --no-dev --optimize-autoloader --no-interaction --prefer-dist --quiet; then
            rollback
            die "composer install fehlgeschlagen."
        fi
    else
        warn "Composer nicht gefunden. PHPMailer fehlt, die App fällt auf mail() zurück."
    fi
fi

# ── Verzeichnisse für Uploads und Chunks ───────────────────────────────
# Beide sind gitignored, existieren nach einem frischen Clone also nicht.
mkdir -p uploads tmp
chmod 750 uploads tmp 2>/dev/null || warn "Rechte auf uploads/ und tmp/ nicht setzbar."

# Die .htaccess-Sperren liegen im Repository und sind nach dem reset wieder da.
for d in uploads tmp; do
    [ -f "$d/.htaccess" ] || warn "$d/.htaccess fehlt. Direktzugriff ist nicht gesperrt."
done

# ── Datenbankschema (nur auf ausdrücklichen Wunsch) ────────────────────────
# schema.sql ist mit CREATE ... IF NOT EXISTS geschrieben und damit
# wiederholbar. Trotzdem standardmäßig aus: es braucht erhöhte Rechte.
if [ "$APPLY_SCHEMA" = "1" ]; then
    if command -v mysql >/dev/null 2>&1; then
        log "Spiele schema.sql ein …"
        # Zugangsdaten kommen aus der .env, nicht aus der Kommandozeile.
        DB_HOST="$(grep -E '^DB_HOST=' .env | cut -d= -f2- | tr -d '"'"'"' ' || echo localhost)"
        DB_USER="$(grep -E '^DB_USER=' .env | cut -d= -f2- | tr -d '"'"'"' ' || true)"
        DB_PASS="$(grep -E '^DB_PASS=' .env | cut -d= -f2- | tr -d '"'"'"' ' || true)"
        [ -n "$DB_USER" ] || die "DB_USER steht nicht in der .env."
        MYSQL_PWD="$DB_PASS" mysql -h "$DB_HOST" -u "$DB_USER" < schema.sql \
            || warn "schema.sql konnte nicht eingespielt werden. Bitte von Hand prüfen."
    else
        warn "APPLY_SCHEMA=1 gesetzt, aber der mysql-Client fehlt."
    fi
fi

# ── Abnahme: Syntax ─────────────────────────────────────────────
log "Prüfe Syntax …"
SYNTAX_ERRORS=0
while IFS= read -r f; do
    "$PHP_BIN" -l "$f" >/dev/null 2>&1 || { warn "Syntaxfehler in $f"; SYNTAX_ERRORS=1; }
done < <(git ls-files '*.php')

if [ "$SYNTAX_ERRORS" = "1" ]; then
    rollback
    die "Ausgelieferter Stand hat Syntaxfehler. Zurückgesetzt."
fi

# ── Opcache leeren ──────────────────────────────────────────────
# Ohne das liefert PHP-FPM bis zum Ablauf der Prüfintervalle den alten Code aus.
if [ -n "$RELOAD_CMD" ]; then
    log "Lade PHP neu …"
    eval "$RELOAD_CMD" || warn "Neuladen fehlgeschlagen. Alter Code kann noch im Opcache liegen."
else
    warn "RELOAD_CMD nicht gesetzt. Bei aktivem Opcache kann der alte Code weiterlaufen."
fi

# ── Abnahme: Health-Endpunkt ──────────────────────────────────────
if [ -n "$HEALTH_URL" ]; then
    if command -v curl >/dev/null 2>&1; then
        log "Frage $HEALTH_URL ab …"
        # curl schreibt bei Verbindungsfehler selbst schon 000. Ein zusätzliches
        # echo im Fehlerzweig würde daraus 000000 machen, darum überschreiben.
        if ! CODE="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 15 "$HEALTH_URL" 2>/dev/null)"; then
            CODE="000"
        fi
        if [ "$CODE" != "200" ]; then
            rollback
            [ -n "$RELOAD_CMD" ] && eval "$RELOAD_CMD" >/dev/null 2>&1 || true
            die "Health-Check antwortet mit HTTP $CODE. Zurückgesetzt."
        fi
        log "Health-Check: HTTP 200"
    else
        warn "HEALTH_URL gesetzt, aber curl fehlt."
    fi
fi

log "Fertig. Ausgeliefert: $(git rev-parse --short HEAD) ($(git log -1 --format=%s))"

}

main "$@"
