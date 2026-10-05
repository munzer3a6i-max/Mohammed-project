<?php
# ==========================================================
# ملف الاتصال بقاعدة البيانات (connect.php)
# يتم استدعاء هذا الملف في بداية كل صفحة عن طريق require
# ==========================================================

# ضبط المنطقة الزمنية على توقيت السعودية حتى تكون أوقات سجل العمليات صحيحة
date_default_timezone_set('Asia/Riyadh');

# ---------- بيانات الطلب خلف Vercel ----------
# على Vercel يصل الطلب للـ PHP عن طريق وسيط (Proxy)، لذلك نأخذ
# عنوان جهاز المستخدم ونوع الاتصال (HTTPS) من الترويسات التي يضيفها Vercel
# (نثق بهذه الترويسات فقط على Vercel لأن أي شخص يستطيع إرسالها في مكان آخر)
if (getenv('VERCEL')) {
    $forwardedIp = $_SERVER['HTTP_X_REAL_IP'] ?? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')[0]);
    if ($forwardedIp !== '') {
        $_SERVER['REMOTE_ADDR'] = $forwardedIp;
    }
    if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
        $_SERVER['HTTPS'] = 'on';
    }
}

# ---------- ترويسات أمان يرسلها السيرفر للمتصفح ----------
header('X-Frame-Options: DENY');              # منع عرض الموقع داخل إطار في موقع آخر (Clickjacking)
header('X-Content-Type-Options: nosniff');    # منع المتصفح من تخمين نوع الملفات
header('Referrer-Policy: same-origin');       # عدم إرسال روابط صفحاتنا لمواقع خارجية

# ---------- بيانات الاتصال بقاعدة البيانات ----------
# تُقرأ من متغيرات البيئة (Environment Variables) وليس من الكود،
# حتى لا تُرفع كلمة المرور إلى GitHub. على Vercel تُضاف من:
# Project → Settings → Environment Variables
# إذا لم تُحدَّد نستخدم إعدادات XAMPP الافتراضية للتشغيل على الجهاز
function env($name, $default = '')
{
    $value = getenv($name);
    return ($value === false || $value === '') ? $default : $value;
}

$host     = env('DB_HOST', 'localhost');   # عنوان السيرفر
$port     = env('DB_PORT', '3306');        # المنفذ
$username = env('DB_USER', 'root');        # اسم مستخدم قاعدة البيانات
$password = env('DB_PASSWORD', '');        # كلمة مرور قاعدة البيانات (فارغة في XAMPP)
$db       = env('DB_NAME', 'realestate');  # اسم قاعدة البيانات

# بعض الخدمات تعطي رابطاً واحداً مثل: mysql://user:pass@host:3306/dbname
if ($url = env('DATABASE_URL')) {
    $parts    = parse_url($url);
    $host     = $parts['host'] ?? $host;
    $port     = $parts['port'] ?? $port;
    $username = isset($parts['user']) ? urldecode($parts['user']) : $username;
    $password = isset($parts['pass']) ? urldecode($parts['pass']) : $password;
    $db       = isset($parts['path']) ? ltrim($parts['path'], '/') : $db;
}

# خيارات الاتصال
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, # رمي استثناء (Exception) عند أي خطأ في الاستعلامات
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       # النتائج كمصفوفة بأسماء الأعمدة مثل $row['UserName']
    PDO::ATTR_EMULATE_PREPARES   => false,                  # تنفيذ الاستعلامات المُجهّزة فعلياً في السيرفر (حماية أكثر)
    PDO::ATTR_TIMEOUT            => 10,                     # مهلة الاتصال بالثواني
];

# (السرية) الاتصال المشفّر SSL/TLS: مطلوب في أغلب قواعد البيانات السحابية
# مثل TiDB Cloud و Aiven. يُفعّل بوضع DB_SSL=true
if (filter_var(env('DB_SSL', 'false'), FILTER_VALIDATE_BOOLEAN)) {
    $ca = env('DB_SSL_CA');
    if ($ca === '') {
        # البحث عن شهادات النظام في المسارات المعروفة (Vercel يستخدم Amazon Linux)
        foreach (['/etc/pki/tls/certs/ca-bundle.crt', '/etc/ssl/certs/ca-certificates.crt', '/etc/ssl/cert.pem'] as $path) {
            if (is_readable($path)) { $ca = $path; break; }
        }
    }
    $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

try {
    # إنشاء الاتصال باستخدام PDO مع دعم اللغة العربية (utf8mb4)
    $database = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $error) {
    # (التوافر + السرية) نسجّل تفاصيل الخطأ في سجل السيرفر فقط (يظهر في Vercel → Logs)
    # ونعرض للمستخدم رسالة عامة، لأن رسالة الخطأ الأصلية قد تكشف اسم القاعدة أو المستخدم
    error_log('DB connection failed: ' . $error->getMessage());
    http_response_code(503);

    $box = '<div style="font-family:sans-serif;max-width:640px;margin:60px auto;padding:0 16px;line-height:1.6">';

    # على Vercel بدون أي إعداد لقاعدة البيانات: نوضح السبب مباشرة (لا يكشف أي بيانات سرية)
    if (getenv('VERCEL') && env('DB_HOST') === '' && env('DATABASE_URL') === '') {
        die($box . '<h2>Database is not configured</h2>
             <p>Add <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_NAME</code>, <code>DB_USER</code>,
             <code>DB_PASSWORD</code> and <code>DB_SSL</code> in Vercel → Project → Settings → Environment Variables,
             then <strong>redeploy</strong>.</p></div>');
    }

    # وضع التشخيص: DB_DEBUG=true يعرض سبب الخطأ (فعّله مؤقتاً فقط ثم احذفه)
    if (filter_var(env('DB_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN)) {
        $msg  = $error->getMessage();
        $hint = 'See the error message above.';
        if (stripos($msg, 'insecure transport') !== false || stripos($msg, 'SSL') !== false || stripos($msg, 'TLS') !== false) {
            $hint = 'The database requires an encrypted connection: set <code>DB_SSL=true</code>.';
        } elseif (strpos($msg, '[1045]') !== false) {
            $hint = 'Wrong <code>DB_USER</code> or <code>DB_PASSWORD</code>.';
        } elseif (strpos($msg, '[1049]') !== false || strpos($msg, '[1044]') !== false) {
            $hint = 'Check <code>DB_NAME</code>: the database must exist and this user must have access to it. Create it, then run <code>database/database_hosting.sql</code>.';
        } elseif (strpos($msg, '[2002]') !== false || strpos($msg, '[2005]') !== false || stripos($msg, 'timed out') !== false) {
            $hint = 'Cannot reach the server: check <code>DB_HOST</code> and <code>DB_PORT</code> (TiDB uses 4000). '
                  . 'Free hosts like InfinityFree do not allow outside connections, so they cannot be used with Vercel.';
        }
        die($box . '<h2>Database connection failed</h2><pre style="white-space:pre-wrap;background:#f3f4f6;padding:12px;border-radius:8px">'
            . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</pre><p><strong>Hint:</strong> ' . $hint
            . '</p><p style="color:#b45309">Remove <code>DB_DEBUG</code> once it works.</p></div>');
    }

    die('<h2 style="font-family:sans-serif;text-align:center;margin-top:60px">
         The system is temporarily unavailable. Please try again later.</h2>');
}

# ---------- إعدادات أمان الجلسة (السرية - Confidentiality) ----------
# نضبطها قبل تشغيل الجلسة حتى يكون ملف تعريف الجلسة (Cookie) محمياً
if (session_status() === PHP_SESSION_NONE) {

    # حفظ الجلسات في قاعدة البيانات بدل الملفات (ضروري على Vercel)
    require_once __DIR__ . '/session.php';
    session_set_save_handler(new DatabaseSessionHandler($database), true);

    # use_strict_mode: يرفض أي رقم جلسة لم ينشئه السيرفر بنفسه
    ini_set('session.use_strict_mode', '1');

    # حذف الجلسات القديمة من الجدول تلقائياً (احتمال 1% مع كل طلب)
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');

    session_set_cookie_params([
        'httponly' => true,     # الجافاسكربت لا يستطيع قراءة الكوكي (حماية من سرقتها عبر XSS)
        'samesite' => 'Strict', # المتصفح لا يرسل الكوكي مع طلبات قادمة من مواقع أخرى
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', # تُرسل فقط عبر HTTPS إذا كان الموقع يعمل عليه
    ]);

    # تشغيل الجلسة (Session) لحفظ بيانات المستخدم بعد تسجيل الدخول
    session_start();
}

# استدعاء ملف الدوال المساعدة (الحماية، الرسائل، التحقق من البيانات)
require_once __DIR__ . '/functions.php';
