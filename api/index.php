<?php
# ==========================================================
# نقطة الدخول الوحيدة (api/index.php) - Front Controller
# خطة Vercel المجانية (Hobby) تسمح بـ 12 دالة (Function) فقط في كل نشر،
# لذلك بدل أن تكون كل صفحة دالة مستقلة، كل الطلبات تمر من هذا الملف
# وهو يستدعي الصفحة المطلوبة من مجلد src/
# مثال: /Page.php  →  src/Page.php
# ==========================================================

# الصفحات المسموح بها فقط (أي اسم آخر يرجع 404)
$pages = ['MinePage', 'Page', 'Add', 'Update', 'Up', 'Delet', 'Delete',
          'Find', 'Show', 'Security', 'Backup', 'Exit'];

# اسم الصفحة يأتي من vercel.json (?__page=Page)، أو من الرابط نفسه (/Page.php)
$page = $_GET['__page'] ?? basename(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '.php');
unset($_GET['__page']);

# الصفحة الرئيسية (/) أو index.php: التحويل لصفحة تسجيل الدخول
if ($page === '' || $page === 'index') {
    header('Location: MinePage.php');
    exit();
}

if (!in_array($page, $pages, true)) {
    http_response_code(404);
    echo 'Not Found';
    exit();
}

require dirname(__DIR__) . '/src/' . $page . '.php';
