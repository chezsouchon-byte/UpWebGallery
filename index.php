<?php
declare(strict_types=1);

/**
 * ==========================================================
 * UpWebGallery
 * ==========================================================
 *
 * Version : 1.4.0
 * Statut  : Stable
 * Auteur  : Sébastien Souchon / UP-WEB
 *
 * Principe :
 * - Un seul fichier PHP
 * - Aucun framework
 * - Aucune base de données
 * - Scan automatique du dossier /photos/
 *
 * ----------------------------------------------------------
 * CHANGELOG
 * ----------------------------------------------------------
 *
 * v1.4.0 - Premium UX
 * Ajouts :
 * + Hero éditorial plus premium
 * + Bandeau d’introduction plus lisible
 * + Barre de progression de lecture
 * + Indicateur de collection active
 * + Micro-interactions plus sobres
 * + Footer projet plus professionnel
 *
 * Modifications :
 * * Fond encore plus discret et photographique
 * * Navigation principale clarifiée
 * * Styles mobiles affinés
 *
 * Corrections :
 * * Conservation de la base stable v1.3.0
 * * Aucune reprise des hotfixs abandonnés v1.3.1/v1.3.2
 *
 * v1.3.0 - Photographe Pro
 * Ajouts :
 * + Diaporama automatique dans la lightbox
 * + Lecture EXIF si disponible
 * + Métadonnées photo affichées dans la lightbox
 * + SEO images renforcé : alt/title/contextes plus propres
 * + Cache automatique du scan /photos/
 * + Menu burger mobile
 * + Barre mobile app-like
 *
 * Modifications :
 * * Navigation mobile plus claire
 * * Données structurées ImageObject enrichies
 * * UX lightbox améliorée
 *
 * Corrections :
 * * Fallback si EXIF indisponible
 * * Fallback si le cache n’est pas inscriptible
 *
 * v1.2.0 - Luxury UX
 * Ajouts :
 * + Direction artistique plus sobre et premium
 * + Fond éditorial avec grain photographique discret
 * + Header affiné, moins massif
 * + Mobile-first amélioré
 * + Carrousel plus immersif avec boutons mieux intégrés
 * + Effets premium sur les images
 * + Apparition progressive des images
 * + Lightbox plus élégante avec miniatures
 * + Support swipe tactile dans la lightbox
 * + Support molette souris dans la lightbox
 * + Bouton retour haut
 * + Indication discrète de navigation horizontale
 *
 * Modifications :
 * * Réduction des effets trop “template IA”
 * * Hiérarchie visuelle plus photo / galerie
 * * Cartes dossiers plus éditoriales
 * * Espacements et typographie retravaillés
 * * SEO/GEO conservé et clarifié
 *
 * Corrections :
 * * Meilleure robustesse des chemins images
 * * Amélioration responsive
 * * Meilleure compatibilité PHP 7.4+
 *
 * v1.1.0
 * + Boutons visibles gauche/droite pour carrousel
 * + Miniatures sous la lightbox
 * + SEO/GEO renforcé
 *
 * v1.0.0
 * + Base stable validée
 * + Scan automatique /photos/
 * + Sous-dossiers
 * + Fil d’Ariane
 * + Galerie horizontale
 * + Mode sombre
 * + Lightbox
 *
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

/* =========================
   CONFIGURATION
========================= */

$scriptVersion   = '1.4.0';

$siteTitle       = 'UpWebGallery';
$siteSubtitle    = 'Galerie photographique immersive';
$siteDescription = 'Portfolio photo dynamique généré automatiquement depuis un dossier photos, avec navigation, carrousel, mode sombre, lightbox, miniatures, SEO et GEO.';
$logoText        = 'PS';

$contactEmail    = 'contact@example.com';
$contactText     = 'Pour une collaboration, une séance photo ou une demande de tirage, contactez-moi.';

/*
 * Logo optionnel.
 * Si /logo.svg, /logo.png, /assets/logo.svg ou /assets/logo.png existe,
 * il sera utilisé automatiquement.
 */
$logoCandidates = [
    __DIR__ . '/logo.svg'        => 'logo.svg',
    __DIR__ . '/logo.png'        => 'logo.png',
    __DIR__ . '/assets/logo.svg' => 'assets/logo.svg',
    __DIR__ . '/assets/logo.png' => 'assets/logo.png',
];

/*
 * SEO / GEO
 * À personnaliser selon ton site.
 */
$authorName      = 'Sébastien Souchon';
$businessName    = 'UpWebGallery';
$businessCity    = 'Grasse';
$businessRegion  = 'Provence-Alpes-Côte d’Azur';
$businessCountry = 'FR';
$businessArea    = 'Grasse, Cannes, Nice, Antibes, Monaco, Côte d’Azur';
$businessPhone   = '';
$businessUrl     = '';

$socialLinks = [
    ['label' => 'Instagram', 'url' => 'https://instagram.com/'],
    ['label' => 'Facebook',  'url' => 'https://facebook.com/'],
    ['label' => 'LinkedIn',  'url' => 'https://linkedin.com/'],
    ['label' => 'Behance',   'url' => 'https://behance.net/'],
];

define('PHOTOS_DIR', __DIR__ . '/photos');
define('CACHE_DIR', __DIR__ . '/cache');

$cacheEnabled = true;
$cacheTtl = 600; // secondes, soit 10 minutes
$slideshowDelay = 4200; // millisecondes

/* =========================
   OUTILS
========================= */

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function is_image_file($filename): bool
{
    return preg_match('/\.(jpg|jpeg|png|gif|webp|avif)$/i', $filename) === 1;
}

function clean_path($path): string
{
    $path = str_replace('\\', '/', (string)$path);
    $path = trim($path, '/');
    $parts = explode('/', $path);
    $clean = [];

    foreach ($parts as $part) {
        if ($part === '' || $part === '.' || $part === '..') {
            continue;
        }

        $safe = preg_replace('/[^a-zA-Z0-9À-ÿ _.-]/u', '', $part);
        if ($safe !== null && $safe !== '') {
            $clean[] = $safe;
        }
    }

    return implode('/', $clean);
}

function script_base_url(): string
{
    $dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $dir = str_replace('\\', '/', $dir);
    $dir = rtrim($dir, '/');

    if ($dir === '.' || $dir === '/') {
        $dir = '';
    }

    return $dir;
}

function site_origin(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function file_url($relativePath): string
{
    $base = script_base_url();
    $segments = explode('/', str_replace('\\', '/', $relativePath));
    $encoded = array_map('rawurlencode', $segments);
    return $base . '/photos/' . implode('/', $encoded);
}

function absolute_url($url): string
{
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }

    return site_origin() . $url;
}

function page_url(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return site_origin() . $uri;
}

function human_title($text): string
{
    $text = str_replace(['_', '-'], ' ', (string)$text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim((string)$text);
}

function cache_key_for_path($relativePath): string
{
    $key = $relativePath === '' ? 'root' : md5($relativePath);
    return CACHE_DIR . '/portfolio-scan-' . $key . '.json';
}

function read_cache($relativePath, $ttl)
{
    $file = cache_key_for_path($relativePath);

    if (!is_file($file)) {
        return null;
    }

    if ((time() - filemtime($file)) > $ttl) {
        return null;
    }

    $json = @file_get_contents($file);
    if (!$json) {
        return null;
    }

    $data = json_decode($json, true);
    return is_array($data) ? $data : null;
}

function write_cache($relativePath, array $data): void
{
    if (!is_dir(CACHE_DIR)) {
        @mkdir(CACHE_DIR, 0755, true);
    }

    if (!is_dir(CACHE_DIR) || !is_writable(CACHE_DIR)) {
        return;
    }

    $file = cache_key_for_path($relativePath);
    @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function camera_value($exif, $key)
{
    return isset($exif[$key]) && is_string($exif[$key]) ? trim($exif[$key]) : '';
}

function rational_to_float($value)
{
    if (is_array($value)) {
        return null;
    }

    $value = (string)$value;

    if (strpos($value, '/') !== false) {
        list($num, $den) = explode('/', $value, 2);
        $num = (float)$num;
        $den = (float)$den;
        if ($den == 0.0) {
            return null;
        }
        return $num / $den;
    }

    if (is_numeric($value)) {
        return (float)$value;
    }

    return null;
}

function format_exif_value($key, $value)
{
    if ($value === null || $value === '') {
        return '';
    }

    if ($key === 'FNumber') {
        $f = rational_to_float($value);
        return $f ? 'f/' . rtrim(rtrim(number_format($f, 1, '.', ''), '0'), '.') : '';
    }

    if ($key === 'ExposureTime') {
        return (string)$value . ' s';
    }

    if ($key === 'ISOSpeedRatings') {
        if (is_array($value)) {
            $value = reset($value);
        }
        return 'ISO ' . (string)$value;
    }

    if ($key === 'FocalLength') {
        $f = rational_to_float($value);
        return $f ? rtrim(rtrim(number_format($f, 1, '.', ''), '0'), '.') . ' mm' : '';
    }

    if (is_array($value)) {
        return '';
    }

    return trim((string)$value);
}

function read_photo_exif($fullPath): array
{
    if (!function_exists('exif_read_data')) {
        return [];
    }

    if (!preg_match('/\.(jpg|jpeg|tiff|tif)$/i', $fullPath)) {
        return [];
    }

    $exif = @exif_read_data($fullPath, null, true, false);

    if (!is_array($exif)) {
        return [];
    }

    $flat = [];
    foreach ($exif as $section) {
        if (is_array($section)) {
            foreach ($section as $key => $value) {
                $flat[$key] = $value;
            }
        }
    }

    $data = [
        'camera' => trim(camera_value($flat, 'Make') . ' ' . camera_value($flat, 'Model')),
        'lens' => camera_value($flat, 'LensModel'),
        'aperture' => isset($flat['FNumber']) ? format_exif_value('FNumber', $flat['FNumber']) : '',
        'speed' => isset($flat['ExposureTime']) ? format_exif_value('ExposureTime', $flat['ExposureTime']) : '',
        'iso' => isset($flat['ISOSpeedRatings']) ? format_exif_value('ISOSpeedRatings', $flat['ISOSpeedRatings']) : '',
        'focal' => isset($flat['FocalLength']) ? format_exif_value('FocalLength', $flat['FocalLength']) : '',
        'date' => camera_value($flat, 'DateTimeOriginal'),
    ];

    return array_filter($data, function ($value) {
        return $value !== '';
    });
}

function image_alt_text($siteTitle, $imageTitle, $currentPath): string
{
    $parts = [];
    $parts[] = $imageTitle;

    if ($currentPath) {
        $parts[] = 'collection ' . human_title(basename($currentPath));
    }

    $parts[] = $siteTitle;
    return implode(' — ', array_filter($parts));
}


function scan_gallery($relativePath): array
{
    global $cacheEnabled, $cacheTtl, $siteTitle;

    if ($cacheEnabled) {
        $cached = read_cache($relativePath, $cacheTtl);
        if (is_array($cached)) {
            $cached['from_cache'] = true;
            return $cached;
        }
    }

    $base = realpath(PHOTOS_DIR);

    if ($base === false || !is_dir($base)) {
        return [
            'valid' => false,
            'path' => '',
            'directories' => [],
            'images' => [],
        ];
    }

    $target = realpath(PHOTOS_DIR . ($relativePath ? '/' . $relativePath : ''));

    if ($target === false || strpos($target, $base) !== 0 || !is_dir($target)) {
        $target = $base;
        $relativePath = '';
    }

    $directories = [];
    $images = [];

    $items = scandir($target);
    if ($items === false) {
        $items = [];
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $full = $target . DIRECTORY_SEPARATOR . $item;
        $rel = ltrim(($relativePath ? $relativePath . '/' : '') . $item, '/');

        if (is_dir($full)) {
            $directories[] = [
                'name' => $item,
                'title' => human_title($item),
                'path' => $rel,
                'url' => '?path=' . rawurlencode($rel),
                'cover' => find_cover($full, $rel),
                'count' => count_images_recursive($full),
            ];
            continue;
        }

        if (is_file($full) && is_image_file($item)) {
            $size = @getimagesize($full);
            $width = $size ? (int)$size[0] : 0;
            $height = $size ? (int)$size[1] : 0;
            $title = pathinfo($item, PATHINFO_FILENAME);

            $imageTitle = human_title($title);

            $images[] = [
                'name' => $item,
                'title' => $imageTitle,
                'alt' => image_alt_text($siteTitle, $imageTitle, $relativePath),
                'path' => $rel,
                'url' => file_url($rel),
                'absolute_url' => absolute_url(file_url($rel)),
                'width' => $width,
                'height' => $height,
                'ratio' => ($width > 0 && $height > 0) ? $width . '/' . $height : '4/3',
                'modified' => @filemtime($full) ?: time(),
                'exif' => read_photo_exif($full),
            ];
        }
    }

    usort($directories, function ($a, $b) {
        return strnatcasecmp($a['name'], $b['name']);
    });

    usort($images, function ($a, $b) {
        return strnatcasecmp($a['name'], $b['name']);
    });

    $result = [
        'valid' => true,
        'path' => $relativePath,
        'directories' => $directories,
        'images' => $images,
        'from_cache' => false,
    ];

    if ($cacheEnabled) {
        write_cache($relativePath, $result);
    }

    return $result;
}

function find_cover($directory, $relativePath)
{
    $items = scandir($directory);

    if ($items === false) {
        return null;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $full = $directory . DIRECTORY_SEPARATOR . $item;

        if (is_file($full) && is_image_file($item)) {
            return file_url($relativePath . '/' . $item);
        }
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $full = $directory . DIRECTORY_SEPARATOR . $item;

        if (is_dir($full)) {
            $found = find_cover($full, $relativePath . '/' . $item);
            if ($found) {
                return $found;
            }
        }
    }

    return null;
}

function count_images_recursive($directory): int
{
    $count = 0;
    $items = scandir($directory);

    if ($items === false) {
        return 0;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $full = $directory . DIRECTORY_SEPARATOR . $item;

        if (is_file($full) && is_image_file($item)) {
            $count++;
        }

        if (is_dir($full)) {
            $count += count_images_recursive($full);
        }
    }

    return $count;
}

function breadcrumbs($path): array
{
    $crumbs = [
        ['label' => 'Accueil', 'url' => '?']
    ];

    if (!$path) {
        return $crumbs;
    }

    $parts = explode('/', $path);
    $acc = '';

    foreach ($parts as $part) {
        $acc .= ($acc ? '/' : '') . $part;
        $crumbs[] = [
            'label' => human_title($part),
            'url' => '?path=' . rawurlencode($acc)
        ];
    }

    return $crumbs;
}

function detect_logo($logoCandidates)
{
    foreach ($logoCandidates as $file => $url) {
        if (is_file($file)) {
            return script_base_url() . '/' . $url;
        }
    }

    return null;
}

/* =========================
   DONNÉES
========================= */

$currentPath = clean_path($_GET['path'] ?? '');
$gallery = scan_gallery($currentPath);
$currentPath = $gallery['path'];
$crumbs = breadcrumbs($currentPath);
$logoImage = detect_logo($logoCandidates);

$pageTitle = $currentPath ? human_title(basename($currentPath)) . ' — ' . $siteTitle : $siteTitle;
$currentUrl = page_url();
$siteRootUrl = site_origin() . script_base_url() . '/';
$ogImage = !empty($gallery['images']) ? $gallery['images'][0]['absolute_url'] : '';

$seoKeywords = implode(', ', array_filter([
    'portfolio photo',
    'galerie photo',
    'photographe',
    'photographie',
    $businessCity,
    $businessRegion,
    'Côte d’Azur',
    'reportage photo',
    'portrait',
    'tirage photo',
]));

$imageObjects = [];
foreach (array_slice($gallery['images'], 0, 24) as $image) {
    $imageObjects[] = [
        '@type' => 'ImageObject',
        'name' => $image['title'],
        'description' => isset($image['alt']) ? $image['alt'] : $image['title'],
        'contentUrl' => $image['absolute_url'],
        'url' => $image['absolute_url'],
        'width' => $image['width'] ?: null,
        'height' => $image['height'] ?: null,
        'dateModified' => date('c', $image['modified']),
        'author' => [
            '@type' => 'Person',
            'name' => $authorName,
        ],
    ];
}

$breadcrumbItems = [];
foreach ($crumbs as $index => $crumb) {
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'name' => $crumb['label'],
        'item' => absolute_url(script_base_url() . '/' . basename($_SERVER['SCRIPT_NAME'] ?? 'index.php') . $crumb['url']),
    ];
}

$schemaGraph = [
    [
        '@type' => 'WebSite',
        '@id' => $siteRootUrl . '#website',
        'name' => $siteTitle,
        'url' => $siteRootUrl,
        'description' => $siteDescription,
        'inLanguage' => 'fr-FR',
    ],
    [
        '@type' => 'WebPage',
        '@id' => $currentUrl . '#webpage',
        'url' => $currentUrl,
        'name' => $pageTitle,
        'description' => $siteDescription,
        'isPartOf' => ['@id' => $siteRootUrl . '#website'],
        'inLanguage' => 'fr-FR',
    ],
    [
        '@type' => 'ImageGallery',
        '@id' => $currentUrl . '#gallery',
        'name' => $pageTitle,
        'description' => $siteDescription,
        'url' => $currentUrl,
        'image' => $imageObjects,
    ],
    [
        '@type' => 'LocalBusiness',
        '@id' => $siteRootUrl . '#localbusiness',
        'name' => $businessName,
        'url' => $businessUrl ?: $siteRootUrl,
        'email' => $contactEmail,
        'telephone' => $businessPhone,
        'areaServed' => $businessArea,
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $businessCity,
            'addressRegion' => $businessRegion,
            'addressCountry' => $businessCountry,
        ],
    ],
    [
        '@type' => 'BreadcrumbList',
        '@id' => $currentUrl . '#breadcrumb',
        'itemListElement' => $breadcrumbItems,
    ],
];

?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($siteDescription) ?>">
<meta name="keywords" content="<?= e($seoKeywords) ?>">
<meta name="robots" content="index,follow,max-image-preview:large">
<meta name="author" content="<?= e($authorName) ?>">
<meta name="generator" content="UpWebGallery <?= e($scriptVersion) ?>">
<meta name="geo.region" content="<?= e($businessCountry . '-' . $businessRegion) ?>">
<meta name="geo.placename" content="<?= e($businessCity) ?>">
<meta name="theme-color" content="#080807">
<link rel="canonical" href="<?= e($currentUrl) ?>">

<meta property="og:locale" content="fr_FR">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($siteDescription) ?>">
<meta property="og:url" content="<?= e($currentUrl) ?>">
<meta property="og:site_name" content="<?= e($siteTitle) ?>">
<?php if ($ogImage): ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta property="og:image:alt" content="<?= e($siteTitle) ?>">
<?php endif; ?>

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($siteDescription) ?>">
<?php if ($ogImage): ?>
<meta name="twitter:image" content="<?= e($ogImage) ?>">
<?php endif; ?>

<script type="application/ld+json">
<?= json_encode(['@context' => 'https://schema.org', '@graph' => $schemaGraph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>

<style>
:root {
    --bg: #f6f1e8;
    --bg-deep: #ede5d8;
    --surface: rgba(255, 252, 246, .76);
    --surface2: rgba(255, 252, 246, .94);
    --surface3: rgba(255, 255, 255, .5);
    --text: #171412;
    --muted: #766f67;
    --soft: #9a9186;
    --line: rgba(37, 30, 22, .11);
    --gold: #b78a42;
    --gold2: #e6c278;
    --accent: #d8b46a;
    --shadow: 0 26px 90px rgba(38, 29, 18, .15);
    --shadow2: 0 18px 60px rgba(38, 29, 18, .12);
    --radius: 30px;
    --radius2: 20px;
}

html[data-theme="dark"] {
    --bg: #080807;
    --bg-deep: #11100e;
    --surface: rgba(17, 16, 14, .70);
    --surface2: rgba(24, 23, 21, .92);
    --surface3: rgba(255, 255, 255, .045);
    --text: #f5efe4;
    --muted: #afa69a;
    --soft: #83796f;
    --line: rgba(255, 255, 255, .10);
    --gold: #c99a4a;
    --gold2: #e8c77d;
    --accent: #d8b46a;
    --shadow: 0 34px 110px rgba(0, 0, 0, .55);
    --shadow2: 0 18px 70px rgba(0, 0, 0, .40);
}

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    min-height: 100vh;
    color: var(--text);
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background:
        linear-gradient(180deg, var(--bg), var(--bg-deep));
    overflow-x: hidden;
}

body::before {
    content: "";
    position: fixed;
    inset: 0;
    z-index: -3;
    pointer-events: none;
    background:
        radial-gradient(circle at 48% -12%, rgba(216,180,106,.10), transparent 32%),
        linear-gradient(115deg, transparent 0 54%, rgba(216,180,106,.026) 55%, transparent 66%);
}

body::after {
    content: "";
    position: fixed;
    inset: 0;
    z-index: -2;
    pointer-events: none;
    opacity: .30;
    background-image:
        repeating-radial-gradient(circle at 17% 23%, rgba(255,255,255,.16) 0 1px, transparent 1px 4px),
        repeating-linear-gradient(0deg, rgba(255,255,255,.025) 0 1px, transparent 1px 3px);
    mix-blend-mode: overlay;
}


.read-progress {
    position: fixed;
    left: 0;
    top: 0;
    z-index: 200;
    height: 3px;
    width: 0;
    background: linear-gradient(90deg, var(--gold2), var(--gold));
    box-shadow: 0 0 22px rgba(216,180,106,.42);
}

.editorial-intro {
    display: grid;
    grid-template-columns: minmax(220px, .42fr) minmax(0, 1fr);
    gap: clamp(18px, 4vw, 54px);
    align-items: center;
    margin: 0 0 32px;
    padding: clamp(18px, 3vw, 30px);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background:
        linear-gradient(135deg, rgba(216,180,106,.08), transparent 42%),
        var(--surface);
    backdrop-filter: blur(20px);
    box-shadow: var(--shadow2);
}

.editorial-intro span {
    display: block;
    margin-bottom: 9px;
    color: var(--accent);
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
}

.editorial-intro strong {
    display: block;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(2rem, 3.8vw, 4rem);
    line-height: .92;
    font-weight: 500;
    letter-spacing: -.045em;
}

.editorial-intro p {
    margin: 0;
    max-width: 760px;
    color: var(--muted);
    font-size: clamp(1rem, 1.4vw, 1.18rem);
}


a {
    color: inherit;
    text-decoration: none;
}

img {
    display: block;
    max-width: 100%;
}

button {
    font: inherit;
}

.wrapper {
    width: min(1540px, calc(100% - 36px));
    margin: 0 auto;
    padding: 20px 0 72px;
}

.skip-link {
    position: absolute;
    left: -999px;
    top: 10px;
    background: var(--surface2);
    color: var(--text);
    padding: 10px 14px;
    border-radius: 999px;
    z-index: 1000;
}

.skip-link:focus {
    left: 12px;
}

.topbar {
    position: sticky;
    top: 12px;
    z-index: 60;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    min-height: 68px;
    margin-bottom: 34px;
    padding: 10px 12px 10px 16px;
    background: color-mix(in srgb, var(--surface) 88%, transparent);
    border: 1px solid var(--line);
    border-radius: 999px;
    backdrop-filter: blur(22px) saturate(1.15);
    box-shadow: var(--shadow2);
}

.brand {
    display: flex;
    align-items: center;
    gap: 13px;
    min-width: 0;
}

.logo {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    overflow: hidden;
    color: #15100a;
    font-size: .88rem;
    font-weight: 900;
    letter-spacing: .12em;
    background:
        linear-gradient(135deg, var(--gold2), var(--gold));
    box-shadow: inset 0 1px 0 rgba(255,255,255,.5), 0 14px 38px rgba(199,154,74,.22);
}

.logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.brand-copy {
    min-width: 0;
}

.brand-copy strong {
    display: block;
    font-size: .98rem;
    letter-spacing: .02em;
    white-space: nowrap;
}

.brand-copy span {
    display: block;
    color: var(--muted);
    font-size: .82rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.nav-actions {
    display: flex;
    align-items: center;
    gap: 7px;
}

.nav-link,
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0 15px;
    border-radius: 999px;
    border: 1px solid transparent;
    background: transparent;
    color: var(--muted);
    cursor: pointer;
    transition: transform .22s ease, background .22s ease, color .22s ease, border-color .22s ease, box-shadow .22s ease;
}

.nav-link:hover,
.btn:hover {
    transform: translateY(-1px);
    color: var(--text);
    background: var(--surface2);
    border-color: var(--line);
    box-shadow: var(--shadow2);
}

.theme-btn {
    min-width: 44px;
    padding: 0 13px;
    background: var(--surface2);
    border-color: var(--line);
    color: var(--text);
}

.hero {
    min-height: clamp(520px, 76vh, 820px);
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: center;
    position: relative;
    isolation: isolate;
    margin-bottom: 34px;
}

.hero-card {
    max-width: 980px;
    padding: clamp(22px, 6vw, 86px) 0;
}

.kicker {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 22px;
    color: var(--accent);
    font-size: .85rem;
    font-weight: 650;
    letter-spacing: .18em;
    text-transform: uppercase;
}

.kicker::before {
    content: "";
    width: 46px;
    height: 1px;
    background: linear-gradient(90deg, var(--gold), transparent);
}

h1 {
    margin: 0;
    max-width: 10ch;
    font-family: Georgia, "Times New Roman", serif;
    font-weight: 500;
    font-size: clamp(3.2rem, 9vw, 9.5rem);
    line-height: .86;
    letter-spacing: -.07em;
}

h2 {
    max-width: 720px;
    margin: 28px 0 0;
    color: var(--muted);
    font-weight: 350;
    font-size: clamp(1.15rem, 2.2vw, 1.85rem);
    line-height: 1.38;
}

.hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin-top: 34px;
}

.stat {
    display: inline-flex;
    align-items: center;
    min-height: 39px;
    padding: 0 14px;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: var(--surface);
    color: var(--muted);
    backdrop-filter: blur(16px);
}

.hero-panel {
    position: absolute;
    right: 0;
    bottom: 28px;
    width: min(430px, 44vw);
    display: grid;
    gap: 12px;
    padding: 18px;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--surface);
    box-shadow: var(--shadow2);
    backdrop-filter: blur(20px);
}

.hero-panel h3 {
    margin: 0;
    font-size: .92rem;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--accent);
}

.hero-panel p {
    margin: 0;
    color: var(--muted);
}

.socials,
.contact-links {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
}

.socials a,
.contact-links a,
.folder-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0 13px;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: var(--surface2);
    color: var(--text);
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
}

.socials a:hover,
.contact-links a:hover,
.folder-link:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow2);
    border-color: color-mix(in srgb, var(--gold) 45%, var(--line));
}

.breadcrumbs {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 28px;
    padding: 10px;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: var(--surface);
    backdrop-filter: blur(18px);
}

.breadcrumbs h3 {
    margin: 0 6px 0 6px;
    color: var(--soft);
    font-size: .82rem;
    font-weight: 500;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.crumb {
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    padding: 0 13px;
    border-radius: 999px;
    color: var(--muted);
    border: 1px solid transparent;
    transition: .22s ease;
}

.crumb:hover {
    color: var(--text);
    background: var(--surface2);
    border-color: var(--line);
}

.crumb.active {
    color: #16100a;
    background: linear-gradient(135deg, var(--gold2), var(--gold));
    border-color: transparent;
    font-weight: 700;
}

.section-title {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 18px;
    margin: 38px 0 18px;
}

.section-title h3 {
    margin: 0;
    font-family: Georgia, "Times New Roman", serif;
    font-weight: 500;
    font-size: clamp(2rem, 4vw, 4.2rem);
    line-height: .95;
    letter-spacing: -.045em;
}

.section-title p {
    margin: 8px 0 0;
    color: var(--muted);
    max-width: 620px;
}

.hint {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--soft);
    font-size: .92rem;
}

.hint::before {
    content: "↔";
    color: var(--accent);
}

.folder-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 18px;
}

.folder-card {
    min-height: min(520px, 58vh);
    position: relative;
    overflow: hidden;
    border-radius: var(--radius);
    border: 1px solid var(--line);
    background: var(--surface);
    box-shadow: var(--shadow2);
    isolation: isolate;
    transition: transform .34s ease, box-shadow .34s ease, border-color .34s ease;
}

.folder-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow);
    border-color: color-mix(in srgb, var(--gold) 45%, var(--line));
}

.folder-cover {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    transform: scale(1.02);
    filter: saturate(.92) contrast(1.04);
    transition: transform 1s ease, filter .6s ease;
}

.folder-card:hover .folder-cover {
    transform: scale(1.07);
    filter: saturate(1.02) contrast(1.06);
}

.folder-card::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: 1;
    background:
        linear-gradient(180deg, rgba(0,0,0,.02), rgba(0,0,0,.78)),
        linear-gradient(90deg, rgba(0,0,0,.42), transparent 62%);
}

.folder-content {
    position: absolute;
    z-index: 2;
    left: 22px;
    right: 22px;
    bottom: 22px;
    color: white;
}

.folder-content small {
    display: inline-flex;
    margin-bottom: 11px;
    color: rgba(255,255,255,.70);
    letter-spacing: .14em;
    text-transform: uppercase;
}

.folder-content strong {
    display: block;
    font-family: Georgia, "Times New Roman", serif;
    font-weight: 500;
    font-size: clamp(2rem, 4vw, 4rem);
    line-height: .9;
    letter-spacing: -.045em;
}

.folder-content span {
    display: block;
    margin-top: 16px;
    color: rgba(255,255,255,.76);
}

.gallery-box {
    position: relative;
    border-radius: calc(var(--radius) + 10px);
    border: 1px solid var(--line);
    background: var(--surface);
    box-shadow: var(--shadow2);
    backdrop-filter: blur(22px);
    padding: clamp(12px, 2vw, 22px);
    overflow: hidden;
}

.gallery-toolbar {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}

.folder-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
}

.carousel-wrap {
    position: relative;
}

.gallery-track {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: minmax(340px, 54vw);
    gap: clamp(14px, 2.5vw, 30px);
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scroll-behavior: smooth;
    padding: 10px clamp(12px, 7vw, 92px) 24px;
    scrollbar-width: thin;
    scrollbar-color: var(--gold) transparent;
}

.gallery-track::-webkit-scrollbar {
    height: 9px;
}

.gallery-track::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--gold2), var(--gold));
    border-radius: 999px;
}

.carousel-btn {
    position: absolute;
    top: 46%;
    z-index: 15;
    width: 56px;
    height: 56px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: 1px solid var(--line);
    background: color-mix(in srgb, var(--surface2) 92%, transparent);
    color: var(--text);
    box-shadow: var(--shadow2);
    backdrop-filter: blur(16px);
    cursor: pointer;
    font-size: 1.65rem;
    transition: transform .22s ease, background .22s ease, border-color .22s ease;
}

.carousel-btn:hover {
    transform: translateY(-1px) scale(1.04);
    border-color: color-mix(in srgb, var(--gold) 55%, var(--line));
}

.carousel-btn.prev {
    left: 18px;
}

.carousel-btn.next {
    right: 18px;
}

.photo-card {
    position: relative;
    scroll-snap-align: center;
    overflow: hidden;
    border-radius: calc(var(--radius) + 4px);
    background: var(--surface2);
    border: 1px solid var(--line);
    box-shadow: var(--shadow2);
    transition: transform .38s ease, box-shadow .38s ease, border-color .38s ease;
    animation: revealPhoto .75s ease both;
    animation-delay: calc(var(--i, 0) * 70ms);
}

.photo-card:hover {
    transform: translateY(-7px);
    box-shadow: var(--shadow);
    border-color: color-mix(in srgb, var(--gold) 38%, var(--line));
}

.photo-card::after {
    content: "";
    position: absolute;
    left: 18px;
    right: 18px;
    bottom: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(216,180,106,.48), transparent);
    opacity: 0;
    transition: opacity .28s ease;
}

.photo-card:hover::after {
    opacity: 1;
}

.photo-button {
    display: block;
    width: 100%;
    padding: 0;
    border: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
    text-align: left;
}

.photo-frame {
    position: relative;
    aspect-ratio: var(--ratio);
    display: grid;
    place-items: center;
    overflow: hidden;
    background:
        linear-gradient(135deg, rgba(255,255,255,.035), rgba(0,0,0,.16)),
        #111;
}

.photo-frame::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(115deg, transparent 0 40%, rgba(255,255,255,.10) 48%, transparent 56%);
    opacity: 0;
    transform: translateX(-40%);
    transition: opacity .45s ease, transform .7s ease;
    pointer-events: none;
    z-index: 2;
}

.photo-card:hover .photo-frame::before {
    opacity: .45;
    transform: translateX(40%);
}

.photo-frame img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    filter: blur(8px) saturate(.85);
    opacity: 0;
    transform: scale(1.018);
    transition: opacity .75s ease, filter .9s ease, transform .95s ease;
}

.photo-frame img.loaded {
    opacity: 1;
    filter: blur(0) saturate(1);
    transform: scale(1);
}

.photo-card:hover .photo-frame img.loaded {
    transform: scale(1.018);
}

.photo-caption {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: center;
    padding: 17px 18px;
}

.photo-caption strong {
    display: block;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 1.18rem;
    font-weight: 500;
    line-height: 1.05;
}

.photo-caption span {
    color: var(--muted);
    font-size: .88rem;
}

.empty {
    padding: 42px 24px;
    border-radius: var(--radius);
    background: var(--surface);
    border: 1px dashed var(--line);
    color: var(--muted);
    text-align: center;
}

.footer {
    margin-top: 42px;
    padding: 22px 0;
    color: var(--soft);
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    border-top: 1px solid var(--line);
}

.version-badge {
    font-size: .84rem;
}

.lightbox {
    position: fixed;
    inset: 0;
    z-index: 100;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background:
        radial-gradient(circle at top, rgba(216,180,106,.10), transparent 28%),
        rgba(0,0,0,.91);
    backdrop-filter: blur(16px);
}

.lightbox.open {
    display: flex;
}

.lightbox-inner {
    width: min(1420px, 100%);
    max-height: 100%;
    animation: lightboxIn .24s ease both;
}

.lightbox-top {
    color: white;
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    margin-bottom: 12px;
}

.lightbox-title strong {
    display: block;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(1.2rem, 2.5vw, 2.2rem);
    font-weight: 500;
}

.lightbox-title span {
    color: rgba(255,255,255,.62);
}

.lightbox-actions {
    display: flex;
    gap: 9px;
}

.lightbox-btn {
    min-width: 44px;
    min-height: 44px;
    border: 1px solid rgba(255,255,255,.16);
    background: rgba(255,255,255,.08);
    color: white;
    padding: 0 14px;
    border-radius: 999px;
    cursor: pointer;
    backdrop-filter: blur(14px);
    transition: .2s ease;
}

.lightbox-btn:hover {
    background: rgba(255,255,255,.14);
    border-color: rgba(232,199,125,.45);
}

.lightbox-stage {
    display: grid;
    place-items: center;
    max-height: 72vh;
    border-radius: 28px;
    overflow: hidden;
    background: rgba(255,255,255,.035);
    border: 1px solid rgba(255,255,255,.10);
}

.lightbox-stage img {
    max-width: 100%;
    max-height: 72vh;
    width: auto;
    height: auto;
    object-fit: contain;
    opacity: 0;
    transform: scale(.986);
    transition: opacity .28s ease, transform .35s ease;
}

.lightbox-stage img.visible {
    opacity: 1;
    transform: scale(1);
}

.lightbox-thumbs {
    margin-top: 12px;
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 8px 2px 12px;
}

.lightbox-thumb {
    flex: 0 0 auto;
    width: 82px;
    height: 58px;
    border-radius: 14px;
    overflow: hidden;
    border: 2px solid rgba(255,255,255,.13);
    background: rgba(255,255,255,.06);
    cursor: pointer;
    opacity: .56;
    padding: 0;
    transition: .2s ease;
}

.lightbox-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.lightbox-thumb.active {
    opacity: 1;
    border-color: var(--gold2);
    transform: translateY(-2px);
}

.back-to-top {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 70;
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: 1px solid var(--line);
    background: var(--surface2);
    color: var(--text);
    cursor: pointer;
    box-shadow: var(--shadow2);
    opacity: 0;
    pointer-events: none;
    transform: translateY(8px);
    transition: .22s ease;
}

.back-to-top.visible {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
}

@keyframes revealPhoto {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes lightboxIn {
    from {
        opacity: 0;
        transform: translateY(12px) scale(.99);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}


.burger-btn {
    display: none;
    width: 44px;
    height: 44px;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: var(--surface2);
    color: var(--text);
    cursor: pointer;
    place-items: center;
    padding: 0;
}

.burger-btn span {
    display: block;
    width: 18px;
    height: 2px;
    margin: 3px auto;
    border-radius: 99px;
    background: currentColor;
    transition: transform .22s ease, opacity .22s ease;
}

.burger-btn.open span:nth-child(1) {
    transform: translateY(5px) rotate(45deg);
}

.burger-btn.open span:nth-child(2) {
    opacity: 0;
}

.burger-btn.open span:nth-child(3) {
    transform: translateY(-5px) rotate(-45deg);
}

.mobile-appbar {
    display: none;
}

.lightbox-exif {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    color: rgba(255,255,255,.72);
}

.lightbox-exif span {
    display: inline-flex;
    align-items: center;
    min-height: 32px;
    padding: 0 11px;
    border-radius: 999px;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.11);
    font-size: .86rem;
}

.lightbox-btn.playing {
    color: #15100a;
    background: linear-gradient(135deg, var(--gold2), var(--gold));
    border-color: transparent;
}


@media (max-width: 1100px) {
    .hero {
        min-height: auto;
        padding: 64px 0 28px;
    }

    .hero-panel {
        position: relative;
        right: auto;
        bottom: auto;
        width: 100%;
        margin-top: 32px;
    }

    .gallery-track {
        grid-auto-columns: minmax(300px, 76vw);
        padding-left: 58px;
        padding-right: 58px;
    }
}

@media (max-width: 720px) {
    .wrapper {
        width: min(100% - 20px, 1540px);
        padding-top: 10px;
    }

    .topbar {
        align-items: center;
        flex-direction: row;
        flex-wrap: wrap;
        border-radius: 24px;
        padding: 12px;
    }

    .brand {
        flex: 1 1 auto;
        width: auto;
    }

    .burger-btn {
        display: grid;
    }

    .brand-copy span {
        max-width: 220px;
    }

    .nav-actions {
        width: 100%;
        display: none;
        grid-template-columns: repeat(4, 1fr);
        gap: 7px;
    }

    .nav-actions.open {
        display: grid;
    }

    .mobile-appbar {
        position: fixed;
        left: 10px;
        right: 10px;
        bottom: 10px;
        z-index: 65;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
        padding: 7px;
        border-radius: 22px;
        border: 1px solid var(--line);
        background: color-mix(in srgb, var(--surface2) 92%, transparent);
        box-shadow: var(--shadow2);
        backdrop-filter: blur(18px);
    }

    .mobile-appbar a {
        min-height: 40px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        color: var(--muted);
        font-size: .82rem;
    }

    .mobile-appbar a:hover {
        background: var(--surface3);
        color: var(--text);
    }

    .nav-link,
    .btn {
        min-height: 42px;
        padding: 0 10px;
        font-size: .9rem;
    }

    .theme-btn {
        min-width: 0;
    }

    .hero {
        padding-top: 48px;
    }

    .editorial-intro {
        grid-template-columns: 1fr;
        border-radius: 22px;
    }

    .kicker {
        letter-spacing: .12em;
    }

    .kicker::before {
        width: 28px;
    }

    h1 {
        max-width: none;
        font-size: clamp(3.4rem, 18vw, 6rem);
    }

    h2 {
        font-size: 1.1rem;
    }

    .hero-meta {
        margin-top: 24px;
    }

    .stat {
        min-height: 35px;
        font-size: .9rem;
    }

    .hero-panel {
        padding: 16px;
        border-radius: 22px;
    }

    .breadcrumbs {
        border-radius: 22px;
    }

    .section-title {
        align-items: flex-start;
        flex-direction: column;
    }

    .folder-grid {
        grid-template-columns: 1fr;
    }

    .folder-card {
        min-height: 420px;
    }

    .gallery-box {
        padding: 10px;
        border-radius: 24px;
    }

    .gallery-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .gallery-track {
        grid-auto-columns: 88%;
        gap: 14px;
        padding: 8px 48px 18px;
    }

    .carousel-btn {
        width: 42px;
        height: 42px;
        font-size: 1.3rem;
    }

    .carousel-btn.prev {
        left: 6px;
    }

    .carousel-btn.next {
        right: 6px;
    }

    .photo-card {
        border-radius: 22px;
    }

    .photo-caption {
        flex-direction: column;
        align-items: flex-start;
        padding: 15px;
    }

    .lightbox {
        padding: 10px;
    }

    .lightbox-top {
        align-items: flex-start;
        flex-direction: column;
    }

    .lightbox-actions {
        width: 100%;
    }

    .lightbox-btn {
        flex: 1;
    }

    .lightbox-stage {
        max-height: 62vh;
        border-radius: 20px;
    }

    .lightbox-stage img {
        max-height: 62vh;
    }

    .lightbox-thumb {
        width: 68px;
        height: 50px;
    }

    .footer {
        padding-bottom: 82px;
    }
}

@media (prefers-reduced-motion: reduce) {
    html {
        scroll-behavior: auto;
    }

    *,
    *::before,
    *::after {
        animation: none !important;
        transition: none !important;
    }
}
</style>
</head>

<body>
<div class="read-progress" id="readProgress" aria-hidden="true"></div>
<a class="skip-link" href="#galerie">Aller à la galerie</a>

<div class="wrapper" id="top">

<header class="topbar">
    <div class="brand">
        <div class="logo" aria-hidden="true">
            <?php if ($logoImage): ?>
                <img src="<?= e($logoImage) ?>" alt="">
            <?php else: ?>
                <?= e($logoText) ?>
            <?php endif; ?>
        </div>
        <div class="brand-copy">
            <strong><?= e($siteTitle) ?></strong>
            <span><?= e($siteSubtitle) ?></span>
        </div>
    </div>

    <button class="burger-btn" id="burgerBtn" type="button" aria-label="Ouvrir le menu" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>

    <nav class="nav-actions" id="mainNav" aria-label="Navigation principale">
        <a href="#galerie" class="nav-link">Galerie</a>
        <a href="#dossiers" class="nav-link">Dossiers</a>
        <a href="#contact" class="nav-link">Contact</a>
        <button class="btn theme-btn" id="themeToggle" type="button" aria-label="Changer le thème">☀</button>
    </nav>
</header>

<main>

<section class="hero" aria-labelledby="main-title">
    <div class="hero-card">
        <div class="kicker">UpWebGallery portfolio</div>
        <h1 id="main-title"><?= e($siteTitle) ?></h1>
        <h2><?= e($siteSubtitle) ?></h2>

        <div class="hero-meta">
            <div class="stat"><?= count($gallery['images']) ?> photo(s)</div>
            <div class="stat"><?= count($gallery['directories']) ?> dossier(s)</div>
            <div class="stat">Sans base de données</div>
            <div class="stat">v<?= e($scriptVersion) ?></div>
            <div class="stat"><?= $currentPath ? 'Collection : ' . e(human_title(basename($currentPath))) : 'Accueil' ?></div>
            <div class="stat"><?= !empty($gallery['from_cache']) ? 'Cache actif' : 'Scan direct' ?></div>
        </div>
    </div>

    <aside class="hero-panel" id="contact">
        <div>
            <h3>Contact</h3>
            <p><?= e($contactText) ?></p>
        </div>

        <div class="contact-links">
            <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>
        </div>

        <div>
            <h3>Réseaux</h3>
            <div class="socials">
                <?php foreach ($socialLinks as $social): ?>
                    <a href="<?= e($social['url']) ?>" target="_blank" rel="noopener noreferrer">
                        <?= e($social['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
</section>

<nav class="breadcrumbs" aria-label="Fil d'Ariane">
    <h3>Fil d’Ariane</h3>
    <?php foreach ($crumbs as $index => $crumb): ?>
        <?php $active = $index === count($crumbs) - 1; ?>
        <a class="crumb <?= $active ? 'active' : '' ?>" href="<?= e($crumb['url']) ?>">
            <?= e($crumb['label']) ?>
        </a>
    <?php endforeach; ?>
</nav>


<section class="editorial-intro" aria-label="Présentation de la galerie">
    <div>
        <span>Collection active</span>
        <strong><?= $currentPath ? e(human_title(basename($currentPath))) : 'Accueil' ?></strong>
    </div>
    <p>
        <?= $currentPath
            ? 'Vous consultez une collection précise. Les images restent accessibles en plein écran avec miniatures, diaporama et métadonnées si disponibles.'
            : 'Les collections sont générées automatiquement depuis le dossier /photos/. Le portfolio reste sans base de données, sans framework et compatible hébergement PHP classique.'
        ?>
    </p>
</section>

<?php if (!$gallery['valid']): ?>
    <div class="empty">
        Le dossier <strong>/photos/</strong> n’existe pas encore. Crée-le au même niveau que ce fichier PHP.
    </div>
<?php endif; ?>

<?php if (!empty($gallery['directories'])): ?>
<section id="dossiers">
    <div class="section-title">
        <div>
            <h3>Dossiers</h3>
            <p>Les collections sont détectées automatiquement depuis les sous-répertoires du dossier /photos/.</p>
        </div>
    </div>

    <div class="folder-grid">
        <?php foreach ($gallery['directories'] as $dir): ?>
            <a class="folder-card" href="<?= e($dir['url']) ?>">
                <?php if ($dir['cover']): ?>
                    <div class="folder-cover" style="background-image:url('<?= e($dir['cover']) ?>')"></div>
                <?php else: ?>
                    <div class="folder-cover" style="background:linear-gradient(135deg,#2a2926,#0c0b0a)"></div>
                <?php endif; ?>

                <div class="folder-content">
                    <small>Collection</small>
                    <strong><?= e($dir['title']) ?></strong>
                    <span><?= (int)$dir['count'] ?> photo(s)</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section id="galerie">
    <div class="section-title">
        <div>
            <h3>Galerie</h3>
            <p>Images affichées dans un carrousel horizontal, avec conservation du ratio original.</p>
        </div>
        <?php if (!empty($gallery['images'])): ?>
            <div class="hint">Glisser horizontalement</div>
        <?php endif; ?>
    </div>

    <?php if (!empty($gallery['images'])): ?>
        <div class="gallery-box">
            <div class="gallery-toolbar">
                <div class="folder-nav">
                    <a href="?" class="folder-link">Racine</a>
                    <?php foreach ($gallery['directories'] as $dir): ?>
                        <a href="<?= e($dir['url']) ?>" class="folder-link"><?= e($dir['title']) ?></a>
                    <?php endforeach; ?>
                </div>

                <div class="folder-link"><?= count($gallery['images']) ?> image(s)</div>
            </div>

            <div class="carousel-wrap">
                <button class="carousel-btn prev" type="button" id="carouselPrev" aria-label="Image précédente">‹</button>
                <button class="carousel-btn next" type="button" id="carouselNext" aria-label="Image suivante">›</button>

                <div class="gallery-track" id="galleryTrack">
                    <?php foreach ($gallery['images'] as $i => $image): ?>
                        <article class="photo-card" style="--ratio: <?= e($image['ratio']) ?>; --i: <?= (int)$i ?>;">
                            <button
                                class="photo-button"
                                type="button"
                                data-index="<?= (int)$i ?>"
                                data-src="<?= e($image['url']) ?>"
                                data-title="<?= e($image['title']) ?>"
                                data-exif="<?= e(json_encode($image['exif'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                            >
                                <div class="photo-frame">
                                    <img
                                        src="<?= e($image['url']) ?>"
                                        alt="<?= e($image['alt'] ?? $image['title']) ?>"
                                        loading="lazy"
                                        decoding="async"
                                        width="<?= (int)$image['width'] ?>"
                                        height="<?= (int)$image['height'] ?>"
                                    >
                                </div>

                                <div class="photo-caption">
                                    <div>
                                        <strong><?= e($image['title']) ?></strong>
                                        <span><?= e($image['name']) ?></span>
                                    </div>
                                    <?php if ($image['width'] && $image['height']): ?>
                                        <span><?= (int)$image['width'] ?>×<?= (int)$image['height'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </button>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="empty">
            Aucune image détectée dans ce dossier.
        </div>
    <?php endif; ?>
</section>

</main>

<footer class="footer">
    <span>© <?= date('Y') ?> <?= e($siteTitle) ?></span>
    <span class="version-badge">UpWebGallery v<?= e($scriptVersion) ?> — Premium UX stable</span>
</footer>

</div>

<nav class="mobile-appbar" aria-label="Navigation mobile">
    <a href="#top">Accueil</a>
    <a href="#galerie">Galerie</a>
    <a href="#dossiers">Dossiers</a>
    <a href="#contact">Contact</a>
</nav>

<button class="back-to-top" id="backToTop" type="button" aria-label="Retour en haut">↑</button>

<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Visualisation de l’image">
    <div class="lightbox-inner">
        <div class="lightbox-top">
            <div class="lightbox-title">
                <strong id="lightboxTitle">Image</strong>
                <span id="lightboxCounter"></span>
            </div>

            <div class="lightbox-actions">
                <button class="lightbox-btn" id="prevBtn" type="button">←</button>
                <button class="lightbox-btn" id="nextBtn" type="button">→</button>
                <button class="lightbox-btn" id="playBtn" type="button">Lecture</button>
                <button class="lightbox-btn" id="closeBtn" type="button">Fermer</button>
            </div>
        </div>

        <div class="lightbox-stage">
            <img id="lightboxImage" src="" alt="">
        </div>

        <div class="lightbox-exif" id="lightboxExif" aria-label="Métadonnées photo"></div>
        <div class="lightbox-thumbs" id="lightboxThumbs" aria-label="Miniatures de la galerie"></div>
    </div>
</div>

<script>
(function () {
    const slideshowDelay = <?= (int)$slideshowDelay ?>;
    const html = document.documentElement;
    const themeToggle = document.getElementById('themeToggle');

    const savedTheme = localStorage.getItem('portfolioTheme');
    if (savedTheme) {
        html.setAttribute('data-theme', savedTheme);
    }

    function updateThemeButton() {
        const dark = html.getAttribute('data-theme') === 'dark';
        themeToggle.textContent = dark ? '☀' : '☾';
        themeToggle.setAttribute('aria-label', dark ? 'Activer le mode clair' : 'Activer le mode sombre');
    }

    updateThemeButton();

    themeToggle.addEventListener('click', function () {
        const current = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', current);
        localStorage.setItem('portfolioTheme', current);
        updateThemeButton();
    });

    const burgerBtn = document.getElementById('burgerBtn');
    const mainNav = document.getElementById('mainNav');

    if (burgerBtn && mainNav) {
        burgerBtn.addEventListener('click', function () {
            const open = mainNav.classList.toggle('open');
            burgerBtn.classList.toggle('open', open);
            burgerBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        Array.from(mainNav.querySelectorAll('a')).forEach(function (link) {
            link.addEventListener('click', function () {
                mainNav.classList.remove('open');
                burgerBtn.classList.remove('open');
                burgerBtn.setAttribute('aria-expanded', 'false');
            });
        });
    }

    const lazyImages = Array.from(document.querySelectorAll('.photo-frame img'));

    function markLoaded(image) {
        image.classList.add('loaded');
    }

    lazyImages.forEach(function (image) {
        if (image.complete) {
            markLoaded(image);
        } else {
            image.addEventListener('load', function () {
                markLoaded(image);
            }, { once: true });
        }
    });

    const galleryTrack = document.getElementById('galleryTrack');
    const carouselPrev = document.getElementById('carouselPrev');
    const carouselNext = document.getElementById('carouselNext');

    function scrollCarousel(direction) {
        if (!galleryTrack) return;
        const amount = Math.max(320, galleryTrack.clientWidth * 0.78);
        galleryTrack.scrollBy({
            left: direction * amount,
            behavior: 'smooth'
        });
    }

    if (carouselPrev) {
        carouselPrev.addEventListener('click', function () {
            scrollCarousel(-1);
        });
    }

    if (carouselNext) {
        carouselNext.addEventListener('click', function () {
            scrollCarousel(1);
        });
    }

    const buttons = Array.from(document.querySelectorAll('.photo-button'));
    const lightbox = document.getElementById('lightbox');
    const img = document.getElementById('lightboxImage');
    const title = document.getElementById('lightboxTitle');
    const counter = document.getElementById('lightboxCounter');
    const closeBtn = document.getElementById('closeBtn');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const playBtn = document.getElementById('playBtn');
    const exifBox = document.getElementById('lightboxExif');
    const thumbs = document.getElementById('lightboxThumbs');

    let currentIndex = 0;
    let slideshowTimer = null;
    let touchStartX = 0;
    let touchEndX = 0;

    function buildThumbs() {
        if (!thumbs || thumbs.dataset.ready === '1') return;

        buttons.forEach(function (btn, index) {
            const thumb = document.createElement('button');
            thumb.type = 'button';
            thumb.className = 'lightbox-thumb';
            thumb.dataset.index = String(index);
            thumb.setAttribute('aria-label', 'Voir image ' + (index + 1));

            const thumbImg = document.createElement('img');
            thumbImg.src = btn.dataset.src;
            thumbImg.alt = btn.dataset.title || '';

            thumb.appendChild(thumbImg);
            thumb.addEventListener('click', function () {
                openLightbox(index);
            });

            thumbs.appendChild(thumb);
        });

        thumbs.dataset.ready = '1';
    }

    function syncThumbs() {
        if (!thumbs) return;
        const items = Array.from(thumbs.querySelectorAll('.lightbox-thumb'));

        items.forEach(function (item) {
            item.classList.toggle('active', Number(item.dataset.index) === currentIndex);
        });

        const active = thumbs.querySelector('.lightbox-thumb.active');
        if (active) {
            active.scrollIntoView({
                behavior: 'smooth',
                inline: 'center',
                block: 'nearest'
            });
        }
    }

    function renderExif(raw) {
        if (!exifBox) return;
        exifBox.innerHTML = '';

        let data = {};
        try {
            data = raw ? JSON.parse(raw) : {};
        } catch (e) {
            data = {};
        }

        const labels = {
            camera: 'Boîtier',
            lens: 'Objectif',
            aperture: 'Ouverture',
            speed: 'Vitesse',
            iso: 'ISO',
            focal: 'Focale',
            date: 'Date'
        };

        Object.keys(labels).forEach(function (key) {
            if (!data[key]) return;
            const item = document.createElement('span');
            item.textContent = labels[key] + ' : ' + data[key];
            exifBox.appendChild(item);
        });
    }

    function openLightbox(index) {
        if (!buttons[index]) return;

        buildThumbs();

        currentIndex = index;
        const btn = buttons[index];

        img.classList.remove('visible');
        img.src = btn.dataset.src;
        img.alt = btn.dataset.title || '';
        title.textContent = btn.dataset.title || 'Image';
        counter.textContent = 'Image ' + (index + 1) + ' / ' + buttons.length;
        renderExif(btn.dataset.exif || '{}');

        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';

        if (img.complete) {
            requestAnimationFrame(function () {
                img.classList.add('visible');
            });
        }

        syncThumbs();
    }

    img.addEventListener('load', function () {
        requestAnimationFrame(function () {
            img.classList.add('visible');
        });
    });

    function closeLightbox() {
        stopSlideshow();
        lightbox.classList.remove('open');
        document.body.style.overflow = '';
        img.src = '';
        img.classList.remove('visible');
    }

    function move(direction) {
        if (!buttons.length) return;
        currentIndex = (currentIndex + direction + buttons.length) % buttons.length;
        openLightbox(currentIndex);

        const card = buttons[currentIndex].closest('.photo-card');
        if (card) {
            card.scrollIntoView({
                behavior: 'smooth',
                inline: 'center',
                block: 'nearest'
            });
        }
    }

    function stopSlideshow() {
        if (slideshowTimer) {
            clearInterval(slideshowTimer);
            slideshowTimer = null;
        }

        if (playBtn) {
            playBtn.classList.remove('playing');
            playBtn.textContent = 'Lecture';
        }
    }

    function startSlideshow() {
        stopSlideshow();

        if (playBtn) {
            playBtn.classList.add('playing');
            playBtn.textContent = 'Pause';
        }

        slideshowTimer = setInterval(function () {
            move(1);
        }, slideshowDelay);
    }

    function toggleSlideshow() {
        if (slideshowTimer) {
            stopSlideshow();
        } else {
            startSlideshow();
        }
    }

    buttons.forEach(function (btn, index) {
        btn.addEventListener('click', function () {
            openLightbox(index);
        });
    });

    closeBtn.addEventListener('click', closeLightbox);
    prevBtn.addEventListener('click', function () { move(-1); });
    nextBtn.addEventListener('click', function () { move(1); });
    if (playBtn) {
        playBtn.addEventListener('click', toggleSlideshow);
    }

    lightbox.addEventListener('click', function (event) {
        if (event.target === lightbox) {
            closeLightbox();
        }
    });

    lightbox.addEventListener('touchstart', function (event) {
        touchStartX = event.changedTouches[0].screenX;
    }, { passive: true });

    lightbox.addEventListener('touchend', function (event) {
        touchEndX = event.changedTouches[0].screenX;
        const diff = touchEndX - touchStartX;

        if (Math.abs(diff) > 60) {
            move(diff > 0 ? -1 : 1);
        }
    }, { passive: true });

    lightbox.addEventListener('wheel', function (event) {
        if (!lightbox.classList.contains('open')) return;

        if (Math.abs(event.deltaY) > 25 || Math.abs(event.deltaX) > 25) {
            event.preventDefault();
            move((event.deltaY + event.deltaX) > 0 ? 1 : -1);
        }
    }, { passive: false });

    document.addEventListener('keydown', function (event) {
        if (lightbox.classList.contains('open')) {
            if (event.key === 'Escape') closeLightbox();
            if (event.key === 'ArrowLeft') move(-1);
            if (event.key === 'ArrowRight') move(1);
            return;
        }

        if (event.key === 'ArrowLeft') {
            scrollCarousel(-1);
        }

        if (event.key === 'ArrowRight') {
            scrollCarousel(1);
        }
    });

    const readProgress = document.getElementById('readProgress');
    const backToTop = document.getElementById('backToTop');

    function syncReadProgress() {
        if (!readProgress) return;

        const doc = document.documentElement;
        const scrollTop = window.scrollY || doc.scrollTop;
        const height = Math.max(1, doc.scrollHeight - window.innerHeight);
        const percent = Math.min(100, Math.max(0, (scrollTop / height) * 100));
        readProgress.style.width = percent + '%';
    }

    function syncBackToTop() {
        syncReadProgress();

        if (window.scrollY > 600) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    }

    window.addEventListener('scroll', syncBackToTop, { passive: true });
    syncBackToTop();
    syncReadProgress();

    backToTop.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();
</script>

</body>
</html>
