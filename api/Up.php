<?php
# ==========================================================
# صفحة تعديل بيانات قطعة (Up.php)
# تفتح من صفحة البحث Update.php عن طريق الرابط Up.php?id=رقم_السجل
# ==========================================================

# الاتصال بقاعدة البيانات + التأكد من تسجيل الدخول
require __DIR__ . '/_includes/connect.php';
require_login();

# قراءة رقم السجل من الرابط وتحويله لرقم صحيح
$ID = (int) ($_GET['id'] ?? 0);

# جلب بيانات السجل من قاعدة البيانات
$sth = $database->prepare("SELECT * FROM estate WHERE ID = :id");
$sth->bindParam(':id', $ID, PDO::PARAM_INT);
$sth->execute();
$row = $sth->fetch();

# إذا لم يوجد السجل نرجع لصفحة البحث مع رسالة خطأ
if (!$row) {
    flash('error', 'Block not found!');
    header('Location: Update.php');
    exit();
}

# نملأ النموذج ببيانات السجل الحالية
$data   = $row;
$errors = [];

# عند الضغط على زر التعديل
if (isset($_POST['UpdateBtn'])) {

    # التحقق من رمز الحماية
    check_csrf();

    # التحقق من البيانات وحساب المتبقي من جديد
    [$data, $errors] = validate_block($_POST);

    # التأكد أن رقم القطعة الجديد لا يتكرر مع قطعة أخرى (نستثني السجل الحالي)
    if (!$errors && block_exists($database, $data['AreaNumber'], $data['BlockNumber'], $ID)) {
        $errors[] = 'Another record already uses this area and block number.';
    }

    # إذا لا توجد أخطاء نحدّث السجل
    if (!$errors) {
        $update = $database->prepare(
            "UPDATE estate SET IDNumber = :IDNumber, UserName = :UserName, BlockNumber = :BlockNumber,
                    AreaNumber = :AreaNumber, TotalCatchBlook = :TotalCatchBlook,
                    CustomerPayment = :CustomerPayment, RemainingAmount = :RemainingAmount, `Date` = :Date
             WHERE ID = :ID"
        );

        # ربط القيم
        $update->bindParam(':ID',              $ID, PDO::PARAM_INT);
        $update->bindParam(':IDNumber',        $data['IDNumber']);
        $update->bindParam(':UserName',        $data['UserName']);
        $update->bindParam(':BlockNumber',     $data['BlockNumber']);
        $update->bindParam(':AreaNumber',      $data['AreaNumber']);
        $update->bindParam(':TotalCatchBlook', $data['TotalCatchBlook']);
        $update->bindParam(':CustomerPayment', $data['CustomerPayment']);
        $update->bindParam(':RemainingAmount', $data['RemainingAmount']);
        $update->bindParam(':Date',            $data['Date']);

        # تنفيذ التعديل
        $update->execute();

        # (السلامة) تسجيل القيم قبل التعديل وبعده، حتى نعرف بالضبط ماذا تغيّر
        log_action($database, 'UPDATE', 'Before: ' . block_summary($row) . ' | After: ' . block_summary($data));

        # رسالة نجاح والتحويل لقائمة القطع
        flash('success', 'Block updated successfully.');
        header('Location: Show.php');
        exit();
    }
}

# إعدادات الصفحة
$pageTitle  = 'Edit Block #' . $row['BlockNumber'];
$activePage = 'Update.php';

# إعدادات زر النموذج
$submitName  = 'UpdateBtn';
$submitLabel = 'Save Changes';

# عرض الصفحة
include __DIR__ . '/_includes/header.php';
include __DIR__ . '/_includes/block_form.php';
include __DIR__ . '/_includes/footer.php';
