<?php
declare(strict_types=1);

/**
 * UploadEz – nozilla Corporate Identity
 *
 * Einzige Quelle für Marken und Zeichen. Übernommen aus dem CI-Dokument
 * daimpad/nozilla-ci (design-system.css, project/assets/icons/nz-*.svg).
 *
 * Marken heißen hier wie dort (--nz-*), damit ein Abgleich mechanisch bleibt
 * und nicht jedes Mal übersetzt werden muss.
 *
 * Formsprache: Kanten. Immer. Keine Verläufe, kein Weichzeichner, kein Glas.
 * Schatten sind harte Versätze, keine Wolken.
 */

/**
 * Marken als CSS. Gehört in einen <style>-Block im <head>.
 *
 * Helles Erscheinungsbild ist der Normalfall. Dunkel kommt über die
 * Systemeinstellung oder über data-theme="dark" am Wurzelelement; die
 * ausdrückliche Angabe schlägt die Systemeinstellung.
 */
function nzTokens(): string
{
    return <<<'CSS'
:root {
  /* Signal — die Farbe der Handlung. In beiden Erscheinungsbildern dieselbe. */
  --nz-c-green:        #00FF9C;
  --nz-c-green-strong: #00E88D;
  --nz-c-green-soft:   #B7FFE0;

  /* Papier — warmes Creme. */
  --nz-c-paper:        #FFFEE5;
  --nz-c-paper-alt:    #FFFEE5;
  --nz-c-paper-deep:   #FFFEE5;

  /* Tinte — echtes Schwarz und warme Fast-Schwarz für die dunkle Fläche. */
  --nz-c-ink:          #000000;
  --nz-c-ink-900:      #0C0C0A;
  --nz-c-ink-800:      #17160F;
  --nz-c-ink-700:      #201F16;
  --nz-c-ink-600:      #2C2B20;

  /* Rückmeldung — bewusst außerhalb des Markenklangs, nie als Zierde. */
  --nz-c-warn:         #FF5F1F;
  --nz-c-danger:       #E5484D;
  --nz-c-info:         #3E7BFA;

  --nz-space-1:   4px;
  --nz-space-2:   8px;
  --nz-space-3:  12px;
  --nz-space-4:  16px;
  --nz-space-5:  24px;
  --nz-space-6:  32px;
  --nz-space-7:  48px;
  --nz-space-8:  64px;

  --nz-radius:        0;
  --nz-stroke-hair:   1.5px;
  --nz-stroke-rule:   2px;
  --nz-stroke-strong: 3px;

  --nz-font-display: "Zilla Slab", Georgia, "Times New Roman", serif;
  --nz-font-body:    "Inter", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  --nz-font-mono:    "Space Mono", ui-monospace, "SFMono-Regular", Menlo, monospace;

  --nz-text-xs:   12px;
  --nz-text-sm:   13px;
  --nz-text-base: 16px;
  --nz-text-lg:   21px;
  --nz-text-xl:   34px;
  --nz-text-2xl:  48px;

  --nz-dur:      160ms;
  --nz-dur-fast:  90ms;
  --nz-ease:     cubic-bezier(.2, 0, 0, 1);

  color-scheme: light;
}

:root,
:root[data-theme="light"] {
  --nz-bg:             var(--nz-c-paper);
  --nz-surface:        #FFFFFF;
  --nz-surface-alt:    var(--nz-c-paper-alt);
  --nz-surface-raised: #FFFFFF;

  --nz-text:           var(--nz-c-ink);
  --nz-text-muted:     rgba(0, 0, 0, .66);
  --nz-text-faint:     rgba(0, 0, 0, .42);

  --nz-line:           var(--nz-c-ink);
  --nz-line-soft:      rgba(0, 0, 0, .16);

  --nz-signal:         var(--nz-c-green);
  --nz-signal-strong:  var(--nz-c-green-strong);
  --nz-signal-soft:    var(--nz-c-green-soft);
  --nz-on-signal:      var(--nz-c-ink);

  --nz-shadow-color:   var(--nz-c-ink);
  --nz-focus:          var(--nz-c-green-strong);

  --nz-warn-bg:        #FFF0E8;
  --nz-danger-bg:      #FDEBEC;
  --nz-info-bg:        #ECF1FE;
  color-scheme: light;
}

/* Dunkel — warm, nicht kalt. Papier wird zur Schrift, Tinte zum Raum. */
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --nz-bg:             var(--nz-c-ink-900);
    --nz-surface:        var(--nz-c-ink-800);
    --nz-surface-alt:    var(--nz-c-ink-700);
    --nz-surface-raised: var(--nz-c-ink-700);

    --nz-text:           var(--nz-c-paper);
    --nz-text-muted:     rgba(255, 254, 229, .64);
    --nz-text-faint:     rgba(255, 254, 229, .40);

    --nz-line:           var(--nz-c-paper);
    --nz-line-soft:      rgba(255, 254, 229, .18);

    --nz-signal:         var(--nz-c-green);
    --nz-signal-strong:  var(--nz-c-green-strong);
    --nz-signal-soft:    rgba(0, 255, 156, .16);
    --nz-on-signal:      var(--nz-c-ink);

    --nz-shadow-color:   var(--nz-c-paper);
    --nz-focus:          var(--nz-c-green);

    --nz-warn-bg:        rgba(255, 95, 31, .16);
    --nz-danger-bg:      rgba(229, 72, 77, .18);
    --nz-info-bg:        rgba(62, 123, 250, .18);
    color-scheme: dark;
  }
}

:root[data-theme="dark"] {
  --nz-bg:             var(--nz-c-ink-900);
  --nz-surface:        var(--nz-c-ink-800);
  --nz-surface-alt:    var(--nz-c-ink-700);
  --nz-surface-raised: var(--nz-c-ink-700);

  --nz-text:           var(--nz-c-paper);
  --nz-text-muted:     rgba(255, 254, 229, .64);
  --nz-text-faint:     rgba(255, 254, 229, .40);

  --nz-line:           var(--nz-c-paper);
  --nz-line-soft:      rgba(255, 254, 229, .18);

  --nz-signal:         var(--nz-c-green);
  --nz-signal-strong:  var(--nz-c-green-strong);
  --nz-signal-soft:    rgba(0, 255, 156, .16);
  --nz-on-signal:      var(--nz-c-ink);

  --nz-shadow-color:   var(--nz-c-paper);
  --nz-focus:          var(--nz-c-green);

  --nz-warn-bg:        rgba(255, 95, 31, .16);
  --nz-danger-bg:      rgba(229, 72, 77, .18);
  --nz-info-bg:        rgba(62, 123, 250, .18);
  color-scheme: dark;
}
CSS;
}

/**
 * Die Zeichen aus dem Katalog, als Innenteil ohne <svg>-Hülle.
 *
 * Der Katalog schreibt Tinte-Schwarz fest; hier steht stattdessen
 * currentColor, damit ein Zeichen im dunklen Erscheinungsbild mitgeht.
 * Die grüne Signatur unten rechts bleibt unverändert — sie ist das
 * Erkennungszeichen des Satzes, kein Farbwert der Umgebung.
 */
const NZ_ICON_PATHS = [
    'file' =>
        '<path d="M14 8 H38 L50 20 V56 H14 Z"/><path d="M38 8 V20 H50"/>',
    'file-lines' =>
        '<path d="M14 8 H38 L50 20 V56 H14 Z"/><path d="M38 8 V20 H50"/>'
        . '<path d="M22 30 H42"/><path d="M22 38 H42"/><path d="M22 46 H34"/>',
    'file-pdf' =>
        '<path d="M14 8 H38 L50 20 V56 H14 Z"/><path d="M38 8 V20 H50"/><path d="M22 34 V50"/>'
        . '<path d="M22 34 H29 A5 5 0 0 1 29 44 H22"/><path d="M36 34 V50 H41 A6 6 0 0 0 41 34 Z"/>',
    'file-zipper' =>
        '<path d="M14 8 H38 L50 20 V56 H14 Z"/><path d="M38 8 V20 H50"/><path d="M26 8 V14"/>'
        . '<path d="M32 14 V20"/><path d="M26 20 V26"/><path d="M32 26 V32"/>'
        . '<rect x="24" y="34" width="12" height="14"/>',
    'image' =>
        '<rect x="8" y="12" width="48" height="40"/><circle cx="22" cy="25" r="5"/>'
        . '<path d="M12 48 L26 34 L34 42 L42 34 L52 48"/>',
    'video' =>
        '<rect x="6" y="18" width="34" height="28"/><path d="M40 30 L58 20 V44 L40 34 Z"/>',
    'music' =>
        '<path d="M24 46 V12 L54 6 V40"/><circle cx="16" cy="46" r="8"/>'
        . '<circle cx="46" cy="40" r="8"/><path d="M24 22 L54 16"/>',
    'lock' =>
        '<rect x="10" y="28" width="44" height="28"/>'
        . '<path d="M20 28 V18 A12 12 0 0 1 44 18 V28"/><path d="M32 38 V46"/>',
    'circle-check' =>
        '<circle cx="32" cy="32" r="22"/><path d="M21 32 L29 40 L44 24"/>',
    'triangle-exclamation' =>
        '<path d="M32 8 L58 54 H6 Z"/><path d="M32 24 V38"/><path d="M32 44 V48"/>',
    'copy' =>
        '<rect x="8" y="8" width="32" height="38"/><rect x="24" y="18" width="32" height="38"/>',
    'trash' =>
        '<path d="M10 16 H54"/><path d="M26 16 V10 H38 V16"/><path d="M16 16 V56 H48 V16"/>'
        . '<path d="M26 26 V46"/><path d="M38 26 V46"/>',
    'arrow-left' =>
        '<path d="M54 32 H14"/><path d="M26 20 L12 32 L26 44"/>',
    'envelope' =>
        '<rect x="6" y="14" width="52" height="36"/><path d="M6 14 L32 34 L58 14"/>',
    'magnifying-glass' =>
        '<circle cx="28" cy="28" r="16"/><path d="M40 40 L56 56"/>',
    'inbox' =>
        '<path d="M8 32 L14 10 H50 L56 32 V54 H8 Z"/><path d="M8 32 H22 A10 10 0 0 0 42 32 H56"/>',
    'key' =>
        '<circle cx="20" cy="20" r="12"/><path d="M28 28 L56 56"/><path d="M46 46 L52 40"/>'
        . '<path d="M38 38 L44 32"/>',
    'clock' =>
        '<circle cx="32" cy="32" r="22"/><path d="M32 16 V32 L43 40"/>',
    'folder-open' =>
        '<path d="M8 52 V14 H26 L32 22 H50 V30"/><path d="M8 52 L18 30 H58 L48 52 Z"/>',
    'xmark' =>
        '<path d="M16 16 L48 48"/><path d="M48 16 L16 48"/>',
    'arrow-up' =>
        '<path d="M32 54 V14"/><path d="M20 26 L32 12 L44 26"/>',
    'arrow-down' =>
        '<path d="M32 10 V50"/><path d="M20 38 L32 52 L44 38"/>',
    // Der Katalog füllt diese beiden Dreiecke mit Tinte; hier currentColor,
    // damit sie im dunklen Erscheinungsbild nicht verschwinden.
    'sort' =>
        '<path d="M32 10 L44 26 H20 Z" fill="currentColor"/>'
        . '<path d="M32 54 L44 38 H20 Z" fill="currentColor"/>',
    'box-archive' =>
        '<rect x="8" y="10" width="48" height="14"/><path d="M12 24 V54 H52 V24"/>'
        . '<path d="M26 34 H38"/>',
];

/**
 * Ein Zeichen als eingebettetes SVG.
 *
 * Ohne $label ist das Zeichen für Vorleseprogramme unsichtbar; das ist der
 * Normalfall, weil daneben fast immer schon Text steht.
 */
function nzIcon(string $name, int $size = 20, ?string $label = null): string
{
    $inner = NZ_ICON_PATHS[$name] ?? NZ_ICON_PATHS['file'];

    $a11y = $label === null
        ? ' aria-hidden="true" focusable="false"'
        : ' role="img" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"';

    // Die Strichstärke ist im 64er-Raster festgelegt und skaliert mit.
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"'
        . ' width="' . $size . '" height="' . $size . '"'
        . ' fill="none" stroke="currentColor" stroke-width="4"'
        . ' stroke-linecap="square" stroke-linejoin="miter"' . $a11y . '>'
        . $inner
        . '<rect x="54" y="54" width="6" height="6" fill="#00FF9C" stroke="none"/>'
        . '</svg>';
}

// ── E-Mail ──────────────────────────────────────────────────────
//
// E-Mail-Programme können keine Marken, keine eingebetteten Zeichen und in
// Outlook auch kein rgba(). Deshalb stehen hier feste Werte statt var(--nz-*).
// Es sind dieselben Farben, nur ausgeschrieben.
//
// NZ_MAIL_MUTED ist --nz-text-muted (rgba(0,0,0,.66)) auf Weiß verrechnet.
// Kein geschätzter Grauwert, sondern derselbe Wert ohne Alphakanal.

const NZ_MAIL_INK   = '#000000';
const NZ_MAIL_PAPER = '#FFFEE5';
const NZ_MAIL_GREEN = '#00FF9C';
const NZ_MAIL_WHITE = '#FFFFFF';
const NZ_MAIL_MUTED = '#575757';

const NZ_MAIL_BODY_FONT = "Inter,system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif";
const NZ_MAIL_MONO_FONT = "'Space Mono',Consolas,'Courier New',monospace";
const NZ_MAIL_DISP_FONT = "'Zilla Slab',Georgia,'Times New Roman',serif";

const NZ_MAIL_P    = 'margin:0 0 14px;font-size:16px;line-height:1.55;color:#000000;';
const NZ_MAIL_NOTE = 'margin:22px 0 0;font-size:13px;line-height:1.55;color:#575757;';

/**
 * Der Rahmen einer E-Mail: Kopfband, weiße Fläche, Fußzeile.
 *
 * $title ist der Titel im <head>, $kicker die Zeile unter der Wortmarke,
 * $content fertiges HTML.
 */
function nzMailLayout(string $title, string $kicker, string $content): string
{
    $t      = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $k      = htmlspecialchars($kicker, ENT_QUOTES, 'UTF-8');
    $body   = NZ_MAIL_BODY_FONT;
    $mono   = NZ_MAIL_MONO_FONT;
    $disp   = NZ_MAIL_DISP_FONT;
    $ink    = NZ_MAIL_INK;
    $paper  = NZ_MAIL_PAPER;
    $green  = NZ_MAIL_GREEN;
    $white  = NZ_MAIL_WHITE;

    return <<<HTML
    <!DOCTYPE html>
    <html lang="de">
    <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{$t}</title></head>
    <body style="margin:0;padding:0;background:{$paper};font-family:{$body};color:{$ink};">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:{$paper};border-collapse:collapse;">
        <tr><td align="center" style="padding:24px 12px;">

          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;border-collapse:collapse;border:2px solid {$ink};">

            <tr><td bgcolor="{$ink}" style="background:{$ink};padding:22px 26px;">
              <div style="font-family:{$disp};font-size:28px;font-weight:700;line-height:1;color:{$paper};">Upload<span style="color:{$green};">Ez</span></div>
              <div style="font-family:{$mono};font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:{$paper};padding-top:8px;">{$k}</div>
            </td></tr>

            <tr><td bgcolor="{$white}" style="background:{$white};padding:26px;">
              {$content}
            </td></tr>

            <tr><td bgcolor="{$paper}" style="background:{$paper};padding:16px 26px;border-top:2px solid {$ink};">
              <div style="font-family:{$mono};font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:{$ink};">UploadEz · Sicheres File-Sharing</div>
            </td></tr>

          </table>

        </td></tr>
      </table>
    </body>
    </html>
    HTML;
}

/**
 * Wertetabelle mit Haarlinien. Schlüssel sind Beschriftungen, Werte roh —
 * maskiert wird hier, damit kein Aufrufer es doppelt tut.
 */
function nzMailDataTable(array $rows): string
{
    $mono  = NZ_MAIL_MONO_FONT;
    $ink   = NZ_MAIL_INK;
    $out   = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"'
           . ' style="border-collapse:collapse;margin:22px 0;">';

    $last = array_key_last($rows);
    foreach ($rows as $label => $value) {
        $edge = $label === $last ? ';border-bottom:1px solid ' . $ink : '';
        $l    = htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8');
        $v    = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

        $out .= '<tr>'
             . '<td style="padding:11px 12px 11px 0;border-top:1px solid ' . $ink . $edge . ';'
             . 'font-family:' . $mono . ';font-size:12px;letter-spacing:.12em;text-transform:uppercase;'
             . 'color:' . $ink . ';vertical-align:top;width:38%;">' . $l . '</td>'
             . '<td style="padding:11px 0;border-top:1px solid ' . $ink . $edge . ';'
             . 'font-size:15px;font-weight:600;color:' . $ink . ';vertical-align:top;">' . $v . '</td>'
             . '</tr>';
    }

    return $out . '</table>';
}

/**
 * Die Handlung. Grün, schwarz umrandet, eckig — als Tabelle, weil ein
 * gestyltes <a> in Outlook nicht zuverlässig als Fläche ankommt.
 */
function nzMailButton(string $url, string $label): string
{
    $u    = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $l    = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    $body = NZ_MAIL_BODY_FONT;

    return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:26px 0 4px;">'
        . '<tr><td bgcolor="' . NZ_MAIL_GREEN . '" style="background:' . NZ_MAIL_GREEN . ';border:2px solid ' . NZ_MAIL_INK . ';">'
        . '<a href="' . $u . '" style="display:inline-block;padding:14px 30px;font-family:' . $body . ';'
        . 'font-size:16px;font-weight:600;line-height:1;color:' . NZ_MAIL_INK . ';text-decoration:none;">' . $l . '</a>'
        . '</td></tr></table>';
}

/**
 * Welches Zeichen zu welchem MIME-Typ gehört.
 */
function nzIconForMime(string $mime): string
{
    return match (true) {
        str_starts_with($mime, 'image/') => 'image',
        str_starts_with($mime, 'video/') => 'video',
        str_starts_with($mime, 'audio/') => 'music',
        $mime === 'application/pdf'      => 'file-pdf',
        str_starts_with($mime, 'text/')  => 'file-lines',
        (bool) preg_match('#(zip|rar|7z|tar|gzip|compressed)#', $mime) => 'file-zipper',
        (bool) preg_match('#(word|excel|powerpoint|officedocument|msword)#', $mime) => 'file-lines',
        default => 'file',
    };
}
