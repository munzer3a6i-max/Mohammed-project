<?php
# ==========================================================
# النسخ الاحتياطي (Backup.php) - مبدأ التوافر (Availability)
# ينزّل نسخة من كل بيانات القطع إلى جهاز المستخدم
# حتى لو تعطل السيرفر أو انحذفت البيانات، نستطيع استرجاعها
#   Backup.php?type=csv : ملف يفتح في Excel
#   Backup.php?type=sql : ملف يُستورد في phpMyAdmin لإرجاع البيانات
# ==========================================================

# الاتصال بقاعدة البيانات + التأكد من تسجيل الدخول (السرية: لا أحد ينزل البيانات بدون دخول)
include 'connect.php';
require_login();

# نوع النسخة المطلوب، ونقبل فقط csv أو sql
$type = ($_GET['type'] ?? 'csv') === 'sql' ? 'sql' : 'csv';

# جلب كل السجلات
$rows = $database->query("SELECT * FROM estate ORDER BY ID")->fetchAll();

# (السلامة) تسجيل عملية النسخ الاحتياطي في سجل العمليات
log_action($database, 'BACKUP', strtoupper($type) . ' backup downloaded (' . count($rows) . ' blocks)');

# اسم الملف يحتوي التاريخ والوقت، مثال: realestate_backup_2026-09-28_1430.csv
$fileName = 'realestate_backup_' . date('Y-m-d_Hi') . '.' . $type;

# ---------- نسخة CSV ----------
if ($type === 'csv') {

    # إخبار المتصفح أن هذا ملف للتنزيل وليس صفحة
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');

    # فتح مخرج الكتابة مباشرة إلى المتصفح
    $out = fopen('php://output', 'w');

    # علامة BOM حتى يعرض Excel الحروف العربية بشكل صحيح
    fwrite($out, "\xEF\xBB\xBF");

    # السطر الأول: أسماء الأعمدة
    fputcsv($out, ['ID', 'IDNumber', 'UserName', 'AreaNumber', 'BlockNumber', 'Date',
                   'TotalCatchBlook', 'CustomerPayment', 'RemainingAmount'], ',', '"', '\\');

    # كتابة كل سجل في سطر
    foreach ($rows as $r) {
        fputcsv($out, [$r['ID'], $r['IDNumber'], $r['UserName'], $r['AreaNumber'], $r['BlockNumber'],
                       $r['Date'], $r['TotalCatchBlook'], $r['CustomerPayment'], $r['RemainingAmount']], ',', '"', '\\');
    }

    fclose($out);
    exit();
}

# ---------- نسخة SQL ----------
header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');

# رأس الملف: معلومات النسخة
echo "-- Real Estate backup\n";
echo "-- Created: " . date('Y-m-d H:i:s') . " by " . $_SESSION['user'] . "\n";
echo "-- Blocks: " . count($rows) . "\n\n";
# اسم قاعدة البيانات يؤخذ من connect.php (يختلف بين XAMPP والاستضافة)
echo "USE `" . $db . "`;\n\n";

# كتابة أمر INSERT لكل سجل
# نستخدم $database->quote لحماية القيم (تضع علامات التنصيص وتعالج الرموز الخاصة)
foreach ($rows as $r) {
    $values = array_map(function ($v) use ($database) {
        return $v === null ? 'NULL' : $database->quote((string) $v);
    }, [$r['ID'], $r['IDNumber'], $r['UserName'], $r['AreaNumber'], $r['BlockNumber'],
        $r['Date'], $r['TotalCatchBlook'], $r['CustomerPayment'], $r['RemainingAmount']]);

    # REPLACE: إذا كان السجل موجوداً يستبدله، وإذا كان محذوفاً يرجعه
    echo "REPLACE INTO estate (ID, IDNumber, UserName, AreaNumber, BlockNumber, `Date`, TotalCatchBlook, CustomerPayment, RemainingAmount) VALUES ("
        . implode(', ', $values) . ");\n";
}
exit();
