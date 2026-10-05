<?php
# ==========================================================
# ملف تنفيذ الحذف (Delete.php)
# لا يعرض صفحة، فقط يحذف السجل ثم يرجع للصفحة السابقة
# ==========================================================

# الاتصال بقاعدة البيانات + التأكد من تسجيل الدخول
include 'connect.php';
require_login();

# نسمح بالحذف فقط عن طريق POST (وليس بفتح رابط مباشر)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['ID'])) {
    header('Location: Delet.php');
    exit();
}

# التحقق من رمز الحماية CSRF
check_csrf();

# رقم السجل المراد حذفه (نحوله لرقم صحيح للأمان)
$ID = (int) $_POST['ID'];

# (السلامة) نقرأ بيانات القطعة قبل حذفها حتى نحفظها في سجل العمليات
$old = $database->prepare("SELECT * FROM estate WHERE ID = :ID");
$old->execute([':ID' => $ID]);
$oldRow = $old->fetch();

# تنفيذ الحذف باستعلام مُجهّز
$st = $database->prepare("DELETE FROM estate WHERE ID = :ID");
$st->bindParam(':ID', $ID, PDO::PARAM_INT);
$st->execute();

# rowCount يخبرنا كم سجل انحذف: إذا 1 نجح الحذف، إذا 0 السجل غير موجود
if ($st->rowCount() > 0) {
    # (السلامة) تسجيل البيانات المحذوفة، فلو حُذفت بالخطأ نستطيع معرفتها وإرجاعها
    log_action($database, 'DELETE', 'Deleted: ' . block_summary($oldRow));
    flash('success', 'Block deleted successfully.');
} else {
    flash('error', 'Block not found or already deleted.');
}

# الصفحة التي نرجع لها: نقبل فقط صفحاتنا (حماية من التحويل لموقع خارجي)
$back = $_POST['back'] ?? 'Delet.php';
if (!preg_match('/^(Delet|Show|Find)\.php(\?[\w=%&.+-]*)?$/', $back)) {
    $back = 'Delet.php';
}

# التحويل
header('Location: ' . $back);
exit();
