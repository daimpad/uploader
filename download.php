<?php
declare(strict_types=1);

/**
 * UploadEz – Download-Handler
 *
 * URL: /download.php?token=<64-hex-zeichen>
 *
 * Sicherheitsmaßnahmen:
 *  • Token-Validierung (Format + DB-Lookup)
 *  • Ablauf-Prüfung (expiry)
 *  • Dateiname-Validierung vor Dateizugriff (Traversal-Schutz)
 *  • Content-Type aus DB (kein User-Input)
 *  • Download-Counter inkrementieren
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/ci.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => isset($_SERVER['HTTPS'])]);
session_start();

// ── Token lesen & validieren ────────────────────────────────────────
$token = trim($_GET['token'] ?? '');

if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
    respondWithError(400, 'Ungültiger Download-Link.');
}

// ── Datenbankabfrage ─────────────────────────────────────────────
try {
    $pdo = getDb();
} catch (Throwable $e) {
    error_log('UploadEz DB-Fehler (download): ' . $e->getMessage());
    respondWithError(503, 'Dienst vorübergehend nicht verfügbar.');
}

$stmt = $pdo->prepare(
    'SELECT id, original_name, stored_name, mime_type, file_size, expiry, link_password_hash
     FROM files
     WHERE token = :token
     LIMIT 1'
);
$stmt->execute([':token' => $token]);
$file = $stmt->fetch();

if ($file === false) {
    respondWithError(404, 'Datei nicht gefunden oder Link ungültig.');
}

// Ablauf prüfen
$expiryUtc = new DateTimeImmutable($file['expiry'], new DateTimeZone('UTC'));
$nowUtc    = new DateTimeImmutable('now', new DateTimeZone('UTC'));

if ($nowUtc > $expiryUtc) {
    respondWithError(410, 'Dieser Download-Link ist abgelaufen.');
}

// ── Passwortschutz ──────────────────────────────────────────────
if (!empty($file['link_password_hash'])) {
    $sessionKey = 'dl_auth_' . $token;

    if (empty($_SESSION[$sessionKey])) {
        $pwError = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_password'])) {
            if (password_verify($_POST['link_password'], $file['link_password_hash'])) {
                $_SESSION[$sessionKey] = true;
                // Nach korrektem Passwort: GET-Redirect verhindert Formular-Resubmit
                header('Location: download.php?token=' . urlencode($token));
                exit;
            }
            $pwError = true;
        }

        showPasswordForm($token, $file['original_name'], $pwError);
    }
}

// ── Datei validieren ────────────────────────────────────────────
// Stored-Name darf kein Pfadteil enthalten (Traversal-Schutz)
$storedName = basename($file['stored_name']);
if ($storedName !== $file['stored_name'] || $storedName === '') {
    error_log('UploadEz: Ungültiger stored_name in DB: ' . $file['stored_name']);
    respondWithError(500, 'Interner Fehler.');
}

$filePath = UPLOAD_DIR . $storedName;

// realpath() schlägt fehl wenn Datei nicht existiert → sicher
$uploadDir = realpath(UPLOAD_DIR);
$realPath  = realpath($filePath);
if ($uploadDir === false || $realPath === false || strpos($realPath, $uploadDir) !== 0) {
    error_log('UploadEz: Datei nicht gefunden auf Disk: ' . $filePath);
    respondWithError(404, 'Datei nicht auf dem Server gefunden.');
}

if (!is_file($realPath) || !is_readable($realPath)) {
    respondWithError(403, 'Datei kann nicht gelesen werden.');
}

// ── Download-Counter inkrementieren ──────────────────────────────────
$pdo->prepare('UPDATE files SET download_count = download_count + 1 WHERE id = :id')
    ->execute([':id' => $file['id']]);

// ── HTTP-Headers für Download ────────────────────────────────────────
// Content-Type aus DB (validiert beim Upload), nie vom User-Agent übernehmen
$mimeType = $file['mime_type'];

// Sicherer Dateiname für Content-Disposition (RFC 5987)
$safeOriginal = rawurlencode(
    preg_replace('/[\r\n\t]/', '', $file['original_name'])
);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename*=UTF-8\'\'' . $safeOriginal);
header('Content-Length: ' . $file['file_size']);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

// ── Datei ausgeben (chunk-weise für große Dateien) ──────────────────────────
$fp = fopen($realPath, 'rb');
if ($fp === false) {
    respondWithError(500, 'Datei konnte nicht geöffnet werden.');
}

while (!feof($fp)) {
    echo fread($fp, 8192);
    flush();
}

fclose($fp);
exit;

// ── Passwort-Formular ─────────────────────────────────────────────
function showPasswordForm(string $token, string $filename, bool $error): never
{
    $fileSafe  = htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
    $tokenSafe = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
    $tokens    = nzTokens();
    $lockIcon  = nzIcon('lock', 44);

    $err = $error
        ? '<p class="alert">' . nzIcon('triangle-exclamation', 18)
          . '<span>Falsches Passwort. Bitte erneut versuchen.</span></p>'
        : '';

    http_response_code(200);
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="de" data-theme="light">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Passwort erforderlich – UploadEz</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@700&family=Inter:wght@400;600&family=Space+Mono:wght@700&display=swap" rel="stylesheet">
    <style>
    {$tokens}
    * { box-sizing: border-box; }
    body {
        font-family: var(--nz-font-body);
        font-size: var(--nz-text-base);
        background: var(--nz-bg);
        color: var(--nz-text);
        display: flex; align-items: center; justify-content: center;
        min-height: 100vh; margin: 0; padding: var(--nz-space-4);
    }
    .card {
        background: var(--nz-surface);
        border: var(--nz-stroke-rule) solid var(--nz-line);
        border-radius: var(--nz-radius);
        box-shadow: 6px 6px 0 0 var(--nz-shadow-color);
        padding: var(--nz-space-6);
        width: 100%; max-width: 400px;
    }
    .icon { margin-bottom: var(--nz-space-4); line-height: 0; }
    h1 {
        font-family: var(--nz-font-display);
        font-size: var(--nz-text-lg);
        margin: 0 0 var(--nz-space-2);
        line-height: 1.15;
    }
    .sub {
        color: var(--nz-text-muted);
        font-size: var(--nz-text-sm);
        margin: 0 0 var(--nz-space-5);
    }
    .sub strong { color: var(--nz-text); }
    label {
        display: block;
        font-family: var(--nz-font-mono);
        font-size: var(--nz-text-xs);
        text-transform: uppercase;
        letter-spacing: .12em;
        margin-bottom: var(--nz-space-2);
    }
    input[type=password] {
        width: 100%;
        padding: 13px var(--nz-space-3);
        font-family: var(--nz-font-body);
        font-size: var(--nz-text-base);
        color: var(--nz-text);
        background: var(--nz-bg);
        border: var(--nz-stroke-rule) solid var(--nz-line);
        border-radius: var(--nz-radius);
        outline: none;
    }
    input[type=password]:focus {
        box-shadow: 3px 3px 0 0 var(--nz-focus);
    }
    button {
        width: 100%;
        margin-top: var(--nz-space-4);
        padding: 14px;
        font-family: var(--nz-font-body);
        font-size: var(--nz-text-base);
        font-weight: 600;
        color: var(--nz-on-signal);
        background: var(--nz-signal);
        border: var(--nz-stroke-rule) solid var(--nz-line);
        border-radius: var(--nz-radius);
        box-shadow: 3px 3px 0 0 var(--nz-shadow-color);
        cursor: pointer;
        transition: transform var(--nz-dur-fast) var(--nz-ease),
                    box-shadow var(--nz-dur-fast) var(--nz-ease);
    }
    button:hover { transform: translate(-1px, -1px); box-shadow: 4px 4px 0 0 var(--nz-shadow-color); }
    button:active { transform: translate(3px, 3px); box-shadow: none; }
    .alert {
        display: flex; align-items: center; gap: var(--nz-space-2);
        background: var(--nz-warn-bg);
        border: var(--nz-stroke-hair) solid var(--nz-c-warn);
        color: var(--nz-text);
        font-size: var(--nz-text-sm);
        padding: var(--nz-space-3);
        margin: 0 0 var(--nz-space-4);
    }
    </style>
    </head>
    <body>
    <main class="card">
        <div class="icon">{$lockIcon}</div>
        <h1>Passwort erforderlich</h1>
        <p class="sub">Die Datei <strong>{$fileSafe}</strong> ist passwortgeschützt.</p>
        {$err}
        <form method="POST" action="download.php?token={$tokenSafe}">
            <label for="pw">Passwort</label>
            <input type="password" id="pw" name="link_password" autofocus required>
            <button type="submit">Zugriff bestätigen</button>
        </form>
    </main>
    </body>
    </html>
    HTML;
    exit;
}

// ── Fehler-Handler ─────────────────────────────────────────────────
function respondWithError(int $code, string $message): never
{
    http_response_code($code);

    $msgSafe  = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $tokens   = nzTokens();
    $warnIcon = nzIcon('triangle-exclamation', 40);
    $backIcon = nzIcon('arrow-left', 16);

    // Ohne externe Ressourcen: die Schriftstapel greifen auf ihre
    // ehrlichen Ersatzschriften zurück, wenn nichts geladen wird.
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="de" data-theme="light">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Fehler {$code} – UploadEz</title>
    <style>
    {$tokens}
    * { box-sizing: border-box; }
    body {
        font-family: var(--nz-font-body);
        font-size: var(--nz-text-base);
        background: var(--nz-bg);
        color: var(--nz-text);
        display: flex; align-items: center; justify-content: center;
        min-height: 100vh; margin: 0; padding: var(--nz-space-4);
    }
    .card {
        background: var(--nz-surface);
        border: var(--nz-stroke-rule) solid var(--nz-line);
        box-shadow: 6px 6px 0 0 var(--nz-shadow-color);
        padding: var(--nz-space-6);
        width: 100%; max-width: 440px;
    }
    .icon { color: var(--nz-c-warn); margin-bottom: var(--nz-space-4); line-height: 0; }
    .code {
        font-family: var(--nz-font-display);
        font-size: var(--nz-text-2xl);
        line-height: 1;
        margin: 0 0 var(--nz-space-3);
    }
    p { color: var(--nz-text-muted); margin: 0 0 var(--nz-space-5); }
    a {
        display: inline-flex; align-items: center; gap: var(--nz-space-2);
        font-family: var(--nz-font-mono);
        font-size: var(--nz-text-xs);
        text-transform: uppercase;
        letter-spacing: .12em;
        color: var(--nz-text);
        text-decoration-color: var(--nz-signal);
        text-decoration-thickness: var(--nz-stroke-strong);
        text-underline-offset: 4px;
    }
    </style>
    </head>
    <body>
    <main class="card">
        <div class="icon">{$warnIcon}</div>
        <h1 class="code">{$code}</h1>
        <p>{$msgSafe}</p>
        <a href="/">{$backIcon}<span>Zurück zum Upload</span></a>
    </main>
    </body>
    </html>
    HTML;
    exit;
}
