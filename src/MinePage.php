<?php
# ==========================================================
# صفحة تسجيل الدخول (MinePage.php)
# أول صفحة تظهر للمستخدم، يدخل فيها الاسم وكلمة المرور
# ==========================================================

# استدعاء ملف الاتصال بقاعدة البيانات
require __DIR__ . '/_includes/connect.php';

# إذا كان المستخدم مسجّل دخوله مسبقاً نحوله مباشرة للوحة التحكم
if (!empty($_SESSION['user'])) {
    header('Location: Page.php');
    exit();
}

# متغير لحفظ رسالة الخطأ إن وجدت
$error = '';

# عند الضغط على زر تسجيل الدخول
if (isset($_POST['login'])) {

    # التحقق من رمز الحماية CSRF
    check_csrf();

    # قراءة الاسم وكلمة المرور من النموذج
    $name = trim($_POST['name'] ?? '');
    $pass = $_POST['password'] ?? '';

    # البحث عن المستخدم في جدول login باستعلام مُجهّز (حماية من SQL Injection)
    $login = $database->prepare("SELECT * FROM login WHERE Name = :name");
    $login->bindParam(':name', $name);
    $login->execute();
    $user = $login->fetch();

    # (السرية) هل الحساب مقفل حالياً بسبب محاولات خاطئة كثيرة؟
    # LockedUntil يحفظ وقت انتهاء القفل، إذا كان في المستقبل فالحساب مقفل
    if ($user && $user['LockedUntil'] && strtotime($user['LockedUntil']) > time()) {

        # حساب الدقائق المتبقية على فك القفل
        $minutes = ceil((strtotime($user['LockedUntil']) - time()) / 60);
        $error   = "Too many failed attempts. Account locked, try again in $minutes minute(s).";

    # password_verify تقارن كلمة المرور المدخلة مع كلمة المرور المشفّرة في قاعدة البيانات
    } elseif ($user && password_verify($pass, $user['Password'])) {

        # دخول صحيح: نصفّر عداد المحاولات الخاطئة ونلغي القفل
        $reset = $database->prepare("UPDATE login SET FailedAttempts = 0, LockedUntil = NULL WHERE ID = :id");
        $reset->execute([':id' => $user['ID']]);

        # تجديد رقم الجلسة لمنع سرقتها (Session Fixation)
        session_regenerate_id(true);

        # حفظ اسم المستخدم ووقت آخر نشاط في الجلسة، ومن خلالهما نعرف أنه مسجل دخول
        $_SESSION['user']          = $user['Name'];
        $_SESSION['last_activity'] = time();

        # (السلامة) تسجيل عملية الدخول في سجل العمليات
        log_action($database, 'LOGIN', 'Successful login');

        # التحويل إلى لوحة التحكم
        header('Location: Page.php');
        exit();

    } else {
        # دخول خاطئ: إذا كان الاسم موجوداً نزيد عداد المحاولات الخاطئة
        if ($user) {
            $attempts = $user['FailedAttempts'] + 1;

            # إذا وصل العداد للحد الأقصى نقفل الحساب لمدة LOCK_MINUTES ونصفّر العداد
            # (هذا يمنع هجوم التخمين Brute Force الذي يجرب آلاف كلمات المرور)
            $lockedUntil = null;
            if ($attempts >= MAX_ATTEMPTS) {
                $lockedUntil = date('Y-m-d H:i:s', time() + LOCK_MINUTES * 60);
                $attempts    = 0;
            }

            $fail = $database->prepare("UPDATE login SET FailedAttempts = :a, LockedUntil = :l WHERE ID = :id");
            $fail->execute([':a' => $attempts, ':l' => $lockedUntil, ':id' => $user['ID']]);
        }

        # (السلامة) تسجيل المحاولة الفاشلة في سجل العمليات مع الاسم المستخدم
        log_action($database, 'LOGIN_FAILED', 'Failed login for name: ' . $name);

        # إذا كان الاسم أو كلمة المرور غير صحيحة
        # (لا نحدد أيهما الخطأ حتى لا نساعد من يحاول التخمين)
        $error = 'Invalid name or password!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ترميز الصفحة + التجاوب مع الجوال -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Real Estate</title>
    <!-- الخط + الأيقونات + ملف التنسيق -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="style.css">
</head>

<!-- كلاس login-page يعطي الصفحة خلفية الصورة -->
<body class="login-page">

    <!-- بطاقة تسجيل الدخول -->
    <div class="login-card">

        <!-- الشعار والعنوان -->
        <div class="login-logo"><i class="bx bxs-buildings"></i></div>
        <h1>Welcome Back</h1>
        <p class="muted">Sign in to the Real Estate Management System</p>

        <!-- رسالة من صفحة أخرى، مثل: انتهت الجلسة بسبب عدم النشاط -->
        <?php echo show_flash(); ?>

        <!-- رسالة الخطأ تظهر فقط إذا كانت البيانات غير صحيحة -->
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="bx bx-error-circle"></i><?php echo e($error); ?></div>
        <?php endif; ?>

        <!-- نموذج تسجيل الدخول -->
        <form method="post" autocomplete="off">
            <!-- رمز الحماية المخفي -->
            <?php echo csrf_field(); ?>

            <!-- حقل الاسم -->
            <div class="field icon-field">
                <i class="bx bx-user"></i>
                <input type="text" name="name" placeholder="Username" required
                       value="<?php echo e($_POST['name'] ?? ''); ?>">
            </div>

            <!-- حقل كلمة المرور -->
            <div class="field icon-field">
                <i class="bx bx-lock-alt"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <!-- زر الدخول -->
            <button type="submit" name="login" class="btn btn-primary btn-block">
                Login <i class="bx bx-right-arrow-alt"></i>
            </button>
        </form>
    </div>

</body>
</html>
