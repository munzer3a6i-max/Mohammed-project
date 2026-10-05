<?php
# ==========================================================
# تشغيل المشروع على الجهاز بنفس توجيه Vercel (للتطوير فقط)
#   php -S localhost:8000 dev-router.php
# ثم افتح http://localhost:8000
# الملفات الثابتة من public/ والصفحات من src/ عبر api/index.php
# ==========================================================
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/favicon.ico') {
    $path = '/favicon.svg';
}

if ($path === '/') {
    $path = '/index.php';
}

# الملفات الثابتة (CSS / JS / الصور)
$static = __DIR__ . '/public' . $path;
if ($path !== '/' && is_file($static)) {
    $types = ['css' => 'text/css', 'js' => 'application/javascript', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'svg' => 'image/svg+xml'];
    $ext = strtolower(pathinfo($static, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    readfile($static);
    return;
}

# كل صفحات PHP تمر من api/index.php (نفس vercel.json)
if ($path === '/index.php' || preg_match('#^/[A-Za-z]+\.php$#', $path)) {
    $_GET['__page'] = basename($path, '.php');
    require __DIR__ . '/api/index.php';
    return;
}

http_response_code(404);
echo 'Not Found';
