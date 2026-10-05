<?php
# ==========================================================
# تشغيل المشروع على الجهاز بنفس توجيه Vercel (للتطوير فقط)
#   php -S localhost:8000 dev-router.php
# ثم افتح http://localhost:8000
# الملفات الثابتة من public/ والصفحات من api/
# ==========================================================
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

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

# صفحات PHP (نفس القائمة الموجودة في vercel.json)
if (preg_match('#^/(index|MinePage|Page|Add|Update|Up|Delet|Delete|Find|Show|Security|Backup|Exit)\.php$#', $path, $m)) {
    chdir(__DIR__ . '/api');
    require __DIR__ . '/api/' . $m[1] . '.php';
    return;
}

http_response_code(404);
echo 'Not Found';
