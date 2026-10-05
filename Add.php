<?php
# ==========================================================
# صفحة إضافة قطعة جديدة (Add.php)
# ==========================================================

# الاتصال بقاعدة البيانات + التأكد من تسجيل الدخول
include 'connect.php';
require_login();

# قيم مبدئية فارغة للنموذج، وقائمة فارغة للأخطاء
$data   = [];
$errors = [];

# عند الضغط على زر الإضافة
if (isset($_POST['Send'])) {

    # التحقق من رمز الحماية
    check_csrf();

    # التحقق من صحة البيانات وحساب المتبقي (الدالة في functions.php)
    [$data, $errors] = validate_block($_POST);

    # التأكد أن القطعة غير مسجلة من قبل (نفس رقم المخطط + نفس رقم القطعة)
    if (!$errors && block_exists($database, $data['AreaNumber'], $data['BlockNumber'])) {
        $errors[] = 'This block is already registered in this area.';
    }

    # إذا لا توجد أخطاء نحفظ البيانات
    if (!$errors) {

        # استعلام الإضافة باستخدام Prepared Statement (حماية من SQL Injection)
        $AddData = $database->prepare(
            "INSERT INTO estate (IDNumber, BlockNumber, UserName, AreaNumber, `Date`, TotalCatchBlook, CustomerPayment, RemainingAmount)
             VALUES (:IDNumber, :BlockNumber, :UserName, :AreaNumber, :Date, :TotalCatchBlook, :CustomerPayment, :RemainingAmount)"
        );

        # ربط القيم بالمتغيرات
        $AddData->bindParam(':IDNumber',        $data['IDNumber']);
        $AddData->bindParam(':BlockNumber',     $data['BlockNumber']);
        $AddData->bindParam(':UserName',        $data['UserName']);
        $AddData->bindParam(':AreaNumber',      $data['AreaNumber']);
        $AddData->bindParam(':Date',            $data['Date']);
        $AddData->bindParam(':TotalCatchBlook', $data['TotalCatchBlook']);
        $AddData->bindParam(':CustomerPayment', $data['CustomerPayment']);
        $AddData->bindParam(':RemainingAmount', $data['RemainingAmount']);

        # تنفيذ الاستعلام
        $AddData->execute();

        # (السلامة) تسجيل عملية الإضافة في سجل العمليات
        log_action($database, 'ADD', 'Added: ' . block_summary($data));

        # حفظ رسالة نجاح ثم التحويل لقائمة القطع
        # (التحويل يمنع تكرار الإضافة إذا ضغط المستخدم تحديث الصفحة)
        flash('success', 'Block added successfully.');
        header('Location: Show.php');
        exit();
    }
}

# إعدادات الصفحة
$pageTitle  = 'Add New Block';
$activePage = 'Add.php';

# إعدادات زر النموذج
$submitName  = 'Send';
$submitLabel = 'Add Block';

# عرض الصفحة: الجزء العلوي + النموذج + الجزء السفلي
include 'header.php';
include 'block_form.php';
include 'footer.php';
