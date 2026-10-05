<?php
# ==========================================================
# ملف الدوال المساعدة (functions.php)
# يحتوي على دوال نستخدمها في أكثر من صفحة حتى لا نكرر الكود
# ==========================================================


# ----------------------------------------------------------
# إعدادات الأمان العامة (نغيرها من هنا فقط)
# ----------------------------------------------------------
const SESSION_TIMEOUT = 900;  # (السرية) مدة عدم النشاط قبل الخروج التلقائي بالثواني = 15 دقيقة
const MAX_ATTEMPTS    = 5;    # (السرية) عدد محاولات الدخول الخاطئة المسموحة
const LOCK_MINUTES    = 5;    # (السرية) مدة قفل الحساب بالدقائق بعد تجاوز المحاولات


# ----------------------------------------------------------
# (التوافر - Availability) معالج الأخطاء العام
# إذا حدث أي خطأ غير متوقع في أي صفحة، بدل ما تظهر شاشة بيضاء
# أو رسالة تقنية تكشف تفاصيل النظام، نسجّل الخطأ ونعرض صفحة مرتبة
# ----------------------------------------------------------
set_exception_handler(function ($ex) {
    # تسجيل تفاصيل الخطأ في سجل السيرفر للمبرمج فقط
    error_log('Unhandled error: ' . $ex->getMessage() . ' in ' . $ex->getFile() . ':' . $ex->getLine());
    http_response_code(500);
    echo '<div style="font-family:sans-serif;text-align:center;margin-top:60px">
            <h2>Something went wrong</h2>
            <p>Your data is safe. Please go back and try again.</p>
            <a href="Page.php">Back to Dashboard</a>
          </div>';
});


# ----------------------------------------------------------
# دالة e: تحمي من ثغرة XSS
# تحوّل الرموز الخاصة مثل < > إلى نص عادي قبل عرضها في الصفحة
# حتى لا يستطيع أحد إدخال كود JavaScript داخل البيانات
# ----------------------------------------------------------
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}


# ----------------------------------------------------------
# دالة money: تنسيق المبالغ المالية بفاصلة الآلاف ورقمين عشريين
# مثال: 150000 تصبح 150,000.00
# ----------------------------------------------------------
function money($number)
{
    return number_format((float) $number, 2);
}


# ----------------------------------------------------------
# دالة require_login: حماية الصفحات
# إذا لم يسجّل المستخدم دخوله يتم تحويله إلى صفحة تسجيل الدخول
# نستدعيها في بداية كل صفحة داخل النظام
# ----------------------------------------------------------
function require_login()
{
    if (empty($_SESSION['user'])) {
        header('Location: MinePage.php');
        exit();
    }

    # (السرية) الخروج التلقائي: إذا ترك المستخدم الجهاز بدون استخدام 15 دقيقة
    # ننهي الجلسة حتى لا يستطيع شخص آخر استخدام الجهاز المفتوح
    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('error', 'Your session expired due to inactivity. Please login again.');
        header('Location: MinePage.php');
        exit();
    }

    # تحديث وقت آخر نشاط مع كل صفحة يفتحها المستخدم
    $_SESSION['last_activity'] = time();
}


# ----------------------------------------------------------
# (السلامة - Integrity) دالة log_action: سجل العمليات (Audit Log)
# تحفظ كل عملية مهمة: من قام بها؟ ماذا فعل؟ متى؟ ومن أي جهاز (IP)؟
# فائدتها: إذا تغيرت بيانات نعرف من غيّرها ومتى، ولا يستطيع أحد الإنكار
# ----------------------------------------------------------
function log_action($database, $action, $details = '')
{
    $sql = $database->prepare(
        "INSERT INTO audit_log (UserName, Action, Details, IPAddress, CreatedAt)
         VALUES (:user, :action, :details, :ip, :at)"
    );
    $sql->execute([
        ':user'    => $_SESSION['user'] ?? 'guest',       # اسم المستخدم (أو guest إذا لم يسجل دخول)
        ':action'  => $action,                            # نوع العملية مثل ADD / UPDATE / DELETE
        ':details' => mb_substr($details, 0, 500),        # وصف العملية (بحد أقصى 500 حرف)
        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '',      # عنوان جهاز المستخدم
        ':at'      => date('Y-m-d H:i:s'),                # التاريخ والوقت
    ]);
}


# ----------------------------------------------------------
# (السلامة) دالة block_summary: وصف مختصر للقطعة نستخدمه في سجل العمليات
# ----------------------------------------------------------
function block_summary($b)
{
    return "Area {$b['AreaNumber']} / Block {$b['BlockNumber']} - {$b['UserName']} (ID {$b['IDNumber']}), "
         . "Total {$b['TotalCatchBlook']}, Paid {$b['CustomerPayment']}, Remaining {$b['RemainingAmount']}";
}


# ----------------------------------------------------------
# (السرية) دالة validate_password: شروط كلمة المرور القوية
# 8 أحرف على الأقل + حرف + رقم
# ----------------------------------------------------------
function validate_password($pass)
{
    $errors = [];
    if (strlen($pass) < 8)              $errors[] = 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Za-z]/', $pass)) $errors[] = 'Password must contain at least one letter.';
    if (!preg_match('/\d/', $pass))       $errors[] = 'Password must contain at least one number.';
    return $errors;
}


# ----------------------------------------------------------
# دوال CSRF: حماية النماذج من الإرسال من مواقع خارجية
# نولّد رمزاً سرياً عشوائياً ونضعه في كل نموذج (form)
# وعند الإرسال نتأكد أن الرمز المرسل يطابق الرمز المحفوظ في الجلسة
# ----------------------------------------------------------
function csrf_token()
{
    # إنشاء الرمز مرة واحدة فقط وحفظه في الجلسة
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

# طباعة حقل مخفي يحتوي الرمز داخل النموذج
function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

# التحقق من الرمز عند استقبال النموذج، وإيقاف الطلب إذا كان غير صحيح
function check_csrf()
{
    $sent = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        die('Invalid request, please go back and try again.');
    }
}


# ----------------------------------------------------------
# دوال الرسائل (Flash Messages)
# نحفظ الرسالة في الجلسة ثم نعرضها مرة واحدة في الصفحة التالية
# مثل: "تمت الإضافة بنجاح" بعد التحويل لصفحة أخرى
# ----------------------------------------------------------
function flash($type, $message)
{
    # النوع يكون success (نجاح) أو error (خطأ)
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function show_flash()
{
    # إذا وجدت رسالة نعرضها ثم نحذفها من الجلسة حتى لا تتكرر
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $icon = $f['type'] === 'success' ? 'bx-check-circle' : 'bx-error-circle';
        return '<div class="alert alert-' . e($f['type']) . '"><i class="bx ' . $icon . '"></i>' . e($f['message']) . '</div>';
    }
    return '';
}


# ----------------------------------------------------------
# دالة validate_block: التحقق من بيانات القطعة قبل الحفظ
# تستخدم في صفحة الإضافة وصفحة التعديل
# ترجع مصفوفتين: البيانات بعد التنظيف + قائمة الأخطاء
# ----------------------------------------------------------
function validate_block($input)
{
    $errors = [];

    # قراءة الحقول وحذف المسافات الزائدة من البداية والنهاية
    $data = [
        'IDNumber'        => trim($input['IDNumber'] ?? ''),
        'UserName'        => trim($input['UserName'] ?? ''),
        'AreaNumber'      => trim($input['AreaNumber'] ?? ''),
        'BlockNumber'     => trim($input['BlockNumber'] ?? ''),
        'Date'            => trim($input['Date'] ?? ''),
        'TotalCatchBlook' => trim($input['TotalCatchBlook'] ?? ''),
        'CustomerPayment' => trim($input['CustomerPayment'] ?? ''),
    ];

    # رقم الهوية: أرقام فقط وبحد أقصى 10 خانات
    if (!preg_match('/^\d{1,10}$/', $data['IDNumber'])) {
        $errors[] = 'ID Number must contain digits only (max 10).';
    }

    # اسم العميل: مطلوب
    if ($data['UserName'] === '') {
        $errors[] = 'Customer name is required.';
    }

    # رقم المخطط ورقم القطعة: مطلوبان
    if ($data['AreaNumber'] === '' || $data['BlockNumber'] === '') {
        $errors[] = 'Area number and block number are required.';
    }

    # التاريخ: يجب أن يكون بصيغة صحيحة سنة-شهر-يوم
    $d = DateTime::createFromFormat('Y-m-d', $data['Date']);
    if (!$d || $d->format('Y-m-d') !== $data['Date']) {
        $errors[] = 'Please enter a valid date.';
    }

    # شكل المبلغ الصحيح: أرقام فقط، وبحد أقصى رقمين بعد الفاصلة (مثل 1500 أو 1500.75)
    $moneyPattern = '/^\d{1,10}(\.\d{1,2})?$/';

    # السعر الإجمالي: رقم صحيح الشكل وأكبر من صفر
    if (!preg_match($moneyPattern, $data['TotalCatchBlook']) || $data['TotalCatchBlook'] <= 0) {
        $errors[] = 'Total price must be a number greater than 0 (max 2 decimals).';
    }

    # المبلغ المدفوع: رقم صحيح الشكل ولا يقل عن صفر
    if (!preg_match($moneyPattern, $data['CustomerPayment'])) {
        $errors[] = 'Customer payment must be 0 or more (max 2 decimals).';
    }

    # المبلغ المدفوع لا يمكن أن يكون أكبر من السعر الإجمالي
    if (!$errors && $data['CustomerPayment'] > $data['TotalCatchBlook']) {
        $errors[] = 'Customer payment cannot be greater than the total price.';
    }

    # حساب المبلغ المتبقي داخل السيرفر (وليس الاعتماد على المتصفح)
    # لأن المستخدم يستطيع تعديل القيمة في المتصفح قبل الإرسال
    if (!$errors) {
        $data['RemainingAmount'] = round($data['TotalCatchBlook'] - $data['CustomerPayment'], 2);
    }

    return [$data, $errors];
}


# ----------------------------------------------------------
# دالة block_exists: تمنع بيع نفس القطعة مرتين
# تبحث عن نفس رقم المخطط + رقم القطعة في قاعدة البيانات
# المتغير $exceptId يستخدم في التعديل حتى لا يعتبر السجل الحالي مكرراً
# ----------------------------------------------------------
function block_exists($database, $area, $block, $exceptId = 0)
{
    $sql = $database->prepare(
        "SELECT COUNT(*) FROM estate WHERE AreaNumber = :area AND BlockNumber = :block AND ID <> :id"
    );
    $sql->execute([':area' => $area, ':block' => $block, ':id' => $exceptId]);
    return $sql->fetchColumn() > 0;
}


# ----------------------------------------------------------
# دالة status_badge: تعرض حالة الدفع بشكل ملوّن
# إذا المتبقي صفر = مدفوع بالكامل (أخضر)، غير ذلك = متبقي مبلغ (برتقالي)
# ----------------------------------------------------------
function status_badge($remaining)
{
    if ((float) $remaining <= 0) {
        return '<span class="badge badge-paid">Paid</span>';
    }
    return '<span class="badge badge-pending">Pending</span>';
}
