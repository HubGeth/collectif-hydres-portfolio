<?php
declare(strict_types=1);

/*
 * API privée du Collectif Hydres.
 * La configuration et les données sont volontairement hors de /www. Voir
 * ADMINISTRATION.md avant la première mise en ligne.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$privateDir = dirname(__DIR__, 2) . '/private';
$configPath = $privateDir . '/admin-config.php';
if (!is_file($configPath)) {
    http_response_code(503);
    echo json_encode(['error' => 'Administration non configurée.']);
    exit;
}
$config = require $configPath;
if (!is_array($config) || empty($config['username']) || empty($config['password_hash'])) {
    http_response_code(503);
    echo json_encode(['error' => 'Configuration d’administration invalide.']);
    exit;
}

session_name('hydres_admin');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$dataPath = $privateDir . '/content.json';

function reply(array $body, int $status = 200): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function body(): array {
    $input = json_decode(file_get_contents('php://input'), true);
    return is_array($input) ? $input : [];
}
function clean(string $value, int $length = 500): string {
    return mb_substr(trim(strip_tags($value)), 0, $length);
}
function content(string $path): array {
    if (!is_file($path)) {
        $seed = __DIR__ . '/default-content.json';
        if (!is_dir(dirname($path)) || !copy($seed, $path)) reply(['error' => 'Stockage indisponible.'], 500);
    }
    $value = json_decode((string) file_get_contents($path), true);
    $defaults = json_decode((string) file_get_contents(__DIR__ . '/default-content.json'), true);
    $defaults = is_array($defaults) ? $defaults : ['events' => [], 'media' => [], 'creations' => [], 'collectivePhotos' => [], 'mediationHosts' => []];
    // Migration non destructive : les installations créées avant les nouveaux
    // modules reçoivent leurs contenus initiaux sans perdre agenda ou médias.
    return is_array($value) ? $value + $defaults : $defaults;
}
function saveContent(string $path, array $content): void {
    $tmp = $path . '.tmp';
    if (file_put_contents($tmp, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) === false || !rename($tmp, $path)) {
        reply(['error' => 'Impossible d’enregistrer les modifications.'], 500);
    }
}
function loggedIn(): bool { return !empty($_SESSION['admin']); }
function requireAdmin(): void {
    if (!loggedIn()) reply(['error' => 'Connexion requise.'], 401);
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($csrf) || !hash_equals($_SESSION['csrf'] ?? '', $csrf)) reply(['error' => 'Requête refusée.'], 403);
}
function event(array $input): array {
    $type = $input['type'] ?? 'diffusion';
    if (!in_array($type, ['diffusion', 'residence'], true)) $type = 'diffusion';
    $start = clean((string)($input['startDate'] ?? ''), 10);
    $end = clean((string)($input['endDate'] ?? ''), 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || ($end && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end))) reply(['error' => 'Date invalide.'], 422);
    $result = ['id' => clean((string)($input['id'] ?? ''), 64) ?: bin2hex(random_bytes(16)), 'startDate' => $start, 'endDate' => $end, 'type' => $type, 'featured' => !empty($input['featured'])];
    foreach (['titleFr','titleEn','detailsFr','detailsEn','placeFr','placeEn'] as $field) $result[$field] = clean((string)($input[$field] ?? ''));
    if (!$result['titleFr'] || !$result['detailsFr']) reply(['error' => 'Le titre et le détail français sont requis.'], 422);
    return $result;
}
function pageContent(array $input, array $current): array {
    $result = $current;
    $result['collectivePhotos'] = array_values(array_filter(array_map(fn($value) => clean((string)$value, 240), $input['collectivePhotos'] ?? $current['collectivePhotos'] ?? [])));
    $result['mediationHosts'] = array_values(array_filter(array_map(fn($value) => clean((string)$value, 500), $input['mediationHosts'] ?? $current['mediationHosts'] ?? [])));
    $result['creations'] = [];
    foreach (($input['creations'] ?? $current['creations'] ?? []) as $item) {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(clean((string)($item['slug'] ?? ''), 80)));
        if (!$slug) continue;
        $creation = ['id' => clean((string)($item['id'] ?? ''), 64) ?: bin2hex(random_bytes(12)), 'slug' => $slug, 'legacy' => !empty($item['legacy']) || in_array($slug, ['geschwister', 'torann'], true)];
        foreach (['titleFr','titleEn','metaFr','metaEn','introFr','introEn','bodyFr','bodyEn','hero'] as $field) $creation[$field] = clean((string)($item[$field] ?? ''), 5000);
        $creation['photos'] = array_values(array_filter(array_map(fn($url) => clean((string)$url, 240), $item['photos'] ?? [])));
        if ($creation['titleFr']) $result['creations'][] = $creation;
    }
    return $result;
}

if ($action === 'events' && $method === 'GET') {
    $items = content($dataPath)['events'];
    usort($items, fn($a, $b) => strcmp($a['startDate'], $b['startDate']));
    reply(['events' => $items]);
}
if ($action === 'public-content' && $method === 'GET') {
    $store = content($dataPath);
    reply(['creations' => $store['creations'] ?? [], 'collectivePhotos' => $store['collectivePhotos'] ?? [], 'mediationHosts' => $store['mediationHosts'] ?? []]);
}
if ($action === 'me' && $method === 'GET') reply(['authenticated' => loggedIn(), 'csrf' => loggedIn() ? $_SESSION['csrf'] : null]);
if ($action === 'login' && $method === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateFile = $privateDir . '/login-' . hash('sha256', $ip) . '.json';
    $rate = is_file($rateFile) ? json_decode((string)file_get_contents($rateFile), true) : ['attempts' => 0, 'until' => 0];
    if (($rate['until'] ?? 0) > time()) reply(['error' => 'Trop de tentatives. Réessayez dans quelques minutes.'], 429);
    $input = body();
    $valid = hash_equals((string)$config['username'], (string)($input['username'] ?? '')) && password_verify((string)($input['password'] ?? ''), (string)$config['password_hash']);
    if (!$valid) {
        $attempts = ($rate['attempts'] ?? 0) + 1;
        file_put_contents($rateFile, json_encode(['attempts' => $attempts, 'until' => $attempts >= 5 ? time() + 900 : 0]), LOCK_EX);
        reply(['error' => 'Identifiant ou mot de passe incorrect.'], 401);
    }
    @unlink($rateFile);
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    reply(['authenticated' => true, 'csrf' => $_SESSION['csrf']]);
}
if ($action === 'logout' && $method === 'POST') {
    requireAdmin(); session_unset(); session_destroy(); reply(['ok' => true]);
}
if ($action === 'admin-content' && $method === 'GET') { if (!loggedIn()) reply(['error' => 'Connexion requise.'], 401); reply(content($dataPath)); }
if ($action === 'page-content' && $method === 'PUT') {
    requireAdmin(); $store = content($dataPath); $store = pageContent(body(), $store); saveContent($dataPath, $store); reply($store);
}
if ($action === 'event' && in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    requireAdmin(); $store = content($dataPath); $input = body(); $id = clean((string)($input['id'] ?? ($_GET['id'] ?? '')), 64);
    if ($method === 'DELETE') {
        $store['events'] = array_values(array_filter($store['events'], fn($item) => $item['id'] !== $id)); saveContent($dataPath, $store); reply(['ok' => true]);
    }
    $item = event($input); $found = false;
    foreach ($store['events'] as $key => $existing) if ($existing['id'] === $item['id']) { $store['events'][$key] = $item; $found = true; }
    if (!$found) $store['events'][] = $item;
    saveContent($dataPath, $store); reply(['event' => $item]);
}
if ($action === 'upload' && $method === 'POST') {
    requireAdmin();
    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) reply(['error' => 'Image non reçue.'], 422);
    $file = $_FILES['image'];
    if ($file['size'] > 5 * 1024 * 1024) reply(['error' => 'L’image ne doit pas dépasser 5 Mo.'], 422);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime]) || !@getimagesize($file['tmp_name'])) reply(['error' => 'Format accepté : JPG, PNG ou WebP.'], 422);
    $dir = dirname(__DIR__) . '/uploads'; if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) reply(['error' => 'Impossible d’enregistrer l’image.'], 500);
    $store = content($dataPath); $media = ['id' => bin2hex(random_bytes(16)), 'url' => '/uploads/' . $name, 'name' => clean((string)($file['name'] ?? 'photo'), 120)];
    $store['media'][] = $media; saveContent($dataPath, $store); reply(['media' => $media], 201);
}
reply(['error' => 'Route inconnue.'], 404);
