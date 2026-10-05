<?php
# ==========================================================
# تسجيل الخروج (Exit.php)
# يحذف بيانات الجلسة ثم يرجع المستخدم لصفحة تسجيل الدخول
# ==========================================================

# الاتصال بقاعدة البيانات (ويقوم أيضاً بتشغيل الجلسة)
require __DIR__ . '/_includes/connect.php';

# (السلامة) تسجيل عملية الخروج قبل حذف الجلسة
if (!empty($_SESSION['user'])) {
    log_action($database, 'LOGOUT', 'User logged out');
}

# تفريغ كل متغيرات الجلسة
$_SESSION = [];

# حذف ملف تعريف الجلسة (Cookie) من المتصفح
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

# إنهاء الجلسة نهائياً
session_destroy();

# التحويل لصفحة تسجيل الدخول
header('Location: MinePage.php');
exit();
