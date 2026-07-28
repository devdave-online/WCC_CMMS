<?php
/**
 * WCC CMMS — inline SVG icon helper (Tabler-style).
 *
 * These are self-contained inline SVGs in the Tabler Icons visual language
 * (24x24, stroke-based, round caps/joins). Bundled locally rather than pulled
 * from a CDN, because the shop-floor intranet has no internet — an external icon
 * font would simply fail to load there.
 *
 * Because they draw with `stroke="currentColor"` / `fill="currentColor"`, they
 * inherit the surrounding text colour and adapt to light and dark automatically —
 * something emoji cannot do. Colour a status by wrapping in a span or passing a
 * class that sets `color`.
 *
 * Tabler Icons are MIT licensed (https://tabler.io/icons).
 */

/** name => inner SVG markup (no <svg> wrapper; that is added by wcc_icon()). */
const WCC_TABLER_ICONS = [
    // stroke icons ------------------------------------------------------------
    'circle-check' =>
        '<circle cx="12" cy="12" r="9"/><path d="M9 12l2 2l4 -4"/>',
    'alert-triangle' =>
        '<path d="M12 4l9 15H3z"/><path d="M12 10v4"/><path d="M12 17h.01"/>',
    'alert-octagon' =>
        '<path d="M7 3h10l4 4v10l-4 4H7l-4 -4V7z"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
    'circle-x' =>
        '<circle cx="12" cy="12" r="9"/><path d="M10 10l4 4m0 -4l-4 4"/>',
    'ban' =>
        '<circle cx="12" cy="12" r="9"/><path d="M5.7 5.7l12.6 12.6"/>',
    'truck-delivery' =>
        '<path d="M3 5h11v10H3z"/><path d="M14 8h4l3 3v4h-7z"/>'
        . '<circle cx="7.5" cy="17.5" r="1.7"/><circle cx="17.5" cy="17.5" r="1.7"/>',
    'package' =>
        '<path d="M12 3l8 4.5v9L12 21l-8 -4.5v-9z"/><path d="M12 12l8 -4.5"/>'
        . '<path d="M12 12v9"/><path d="M12 12L4 7.5"/>',

    // fill icons (need fill="currentColor" stroke="none") ---------------------
    'star-filled' =>
        '<path fill="currentColor" stroke="none" d="M12 2l3.09 6.26l6.91 1l-5 4.87l1.18 6.88'
        . 'l-6.18 -3.25l-6.18 3.25l1.18 -6.88l-5 -4.87l6.91 -1z"/>',
];

/**
 * Render an icon as inline SVG.
 *
 * @param string $name  key in WCC_TABLER_ICONS
 * @param array  $opts  class, title (tooltip + aria-label), size (px), color (CSS)
 */
function wcc_icon(string $name, array $opts = []): string
{
    $inner = WCC_TABLER_ICONS[$name] ?? null;
    if ($inner === null) return '';

    $size  = (int)($opts['size'] ?? 20);
    $class = trim('wcc-icon ' . ($opts['class'] ?? ''));
    $title = $opts['title'] ?? '';
    $color = $opts['color'] ?? '';

    // A titled icon is meaningful to assistive tech; an untitled one is decoration.
    $a11y  = $title !== ''
        ? 'role="img" aria-label="' . htmlspecialchars($title) . '"'
        : 'aria-hidden="true" focusable="false"';
    $style = 'vertical-align:middle;' . ($color !== '' ? 'color:' . htmlspecialchars($color) . ';' : '');

    $svg = '<svg class="' . htmlspecialchars($class) . '" ' . $a11y
         . ' width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24"'
         . ' fill="none" stroke="currentColor" stroke-width="2"'
         . ' stroke-linecap="round" stroke-linejoin="round" style="' . $style . '">';
    if ($title !== '') $svg .= '<title>' . htmlspecialchars($title) . '</title>';
    $svg .= $inner . '</svg>';

    return $svg;
}
