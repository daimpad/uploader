/**
 * UploadEz – Projektseite
 *
 * Einzige Aufgabe: das Erscheinungsbild umschalten.
 *
 * Das CI kennt drei Zustände, nicht zwei: hell, dunkel und "wie das System es
 * will". Der Schalter wechselt darum zwischen zwei ausdrücklichen Werten und
 * merkt sie sich; wer nie geklickt hat, bleibt beim Wunsch des Systems.
 */

(function () {
    'use strict';

    var SPEICHER = 'uploadez-theme';
    var wurzel   = document.documentElement;
    var schalter = document.getElementById('theme-toggle');
    var symbol   = document.getElementById('theme-icon');
    var text     = document.getElementById('theme-label');

    if (!schalter) { return; }

    // Zeichen aus dem CI-Katalog, 64er-Raster, mit der grünen Signatur.
    function zeichen(pfade) {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"' +
               ' width="16" height="16" fill="none" stroke="currentColor"' +
               ' stroke-width="4" stroke-linecap="square" stroke-linejoin="miter"' +
               ' aria-hidden="true" focusable="false">' + pfade +
               '<rect x="54" y="54" width="6" height="6" fill="#00FF9C" stroke="none"/></svg>';
    }

    var MOND  = zeichen('<path d="M40 8 A26 26 0 1 0 56 40 A20 20 0 0 1 40 8 Z"/>');
    var SONNE = zeichen('<circle cx="32" cy="32" r="12"/><path d="M32 6 V14"/>' +
                        '<path d="M32 50 V58"/><path d="M6 32 H14"/><path d="M50 32 H58"/>' +
                        '<path d="M14 14 L20 20"/><path d="M44 44 L50 50"/>' +
                        '<path d="M50 14 L44 20"/><path d="M20 44 L14 50"/>');

    /** Was gerade zu sehen ist — auch ohne ausdrückliche Wahl. */
    function istDunkel() {
        var gesetzt = wurzel.getAttribute('data-theme');
        if (gesetzt === 'dark')  { return true; }
        if (gesetzt === 'light') { return false; }
        return window.matchMedia &&
               window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    /** Der Schalter zeigt an, wohin er führt, nicht wo man ist. */
    function beschriften() {
        var dunkel = istDunkel();
        symbol.innerHTML   = dunkel ? SONNE : MOND;
        text.textContent   = dunkel ? 'Hell' : 'Dunkel';
        schalter.setAttribute('aria-pressed', dunkel ? 'true' : 'false');
        schalter.setAttribute('aria-label',
            dunkel ? 'Zum hellen Erscheinungsbild wechseln'
                   : 'Zum dunklen Erscheinungsbild wechseln');
    }

    function setzen(wert) {
        wurzel.setAttribute('data-theme', wert);
        try { localStorage.setItem(SPEICHER, wert); } catch (e) { /* privater Modus */ }
        beschriften();
    }

    // Gemerkte Wahl wiederherstellen. Ohne sie gilt die Systemeinstellung,
    // und dafür steht bewusst kein data-theme am Wurzelelement.
    try {
        var gemerkt = localStorage.getItem(SPEICHER);
        if (gemerkt === 'dark' || gemerkt === 'light') {
            wurzel.setAttribute('data-theme', gemerkt);
        }
    } catch (e) { /* localStorage nicht verfügbar */ }

    beschriften();

    schalter.addEventListener('click', function () {
        setzen(istDunkel() ? 'light' : 'dark');
    });

    // Ändert das System seine Einstellung, zieht die Beschriftung mit —
    // aber nur, solange niemand ausdrücklich gewählt hat.
    if (window.matchMedia) {
        var abfrage = window.matchMedia('(prefers-color-scheme: dark)');
        var reagieren = function () {
            if (!wurzel.hasAttribute('data-theme')) { beschriften(); }
        };
        if (abfrage.addEventListener) {
            abfrage.addEventListener('change', reagieren);
        } else if (abfrage.addListener) {
            abfrage.addListener(reagieren);   // ältere Browser
        }
    }
})();
