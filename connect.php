<?php
# ==========================================================
# ملف الاتصال بقاعدة البيانات (connect.php)
# يتم استدعاء هذا الملف في بداية كل صفحة عن طريق include
# ==========================================================

# ضبط المنطقة الزمنية على توقيت السعودية حتى تكون أوقات سجل العمليات صحيحة
date_default_timezone_set('Asia/Riyadh');

# ---------- إعدادات أمان الجلسة (السرية - Confidentiality) ----------
# نضبطها قبل تشغيل الجلسة حتى يكون ملف تعريف الجلسة (Cookie) محمياً
if (session_status() === PHP_SESSION_NONE) {

    # use_strict_mode: يرفض أي رقم جلسة لم ينشئه السيرفر بنفسه
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'httponly' => true,     # الجافاسكربت لا يستطيع قراءة الكوكي (حماية من سرقتها عبر XSS)
        'samesite' => 'Strict', # المتصفح لا يرسل الكوكي مع طلبات قادمة من مواقع أخرى
        'secure'   => !empty($_SERVER['HTTPS']), # تُرسل فقط عبر HTTPS إذا كان الموقع يعمل عليه
    ]);

    # تشغيل الجلسة (Session) لحفظ بيانات المستخدم بعد تسجيل الدخول
    session_start();
}

# ---------- ترويسات أمان يرسلها السيرفر للمتصفح ----------
header('X-Frame-Options: DENY');              # منع عرض الموقع داخل إطار في موقع آخر (Clickjacking)
header('X-Content-Type-Options: nosniff');    # منع المتصفح من تخمين نوع الملفات
header('Referrer-Policy: same-origin');       # عدم إرسال روابط صفحاتنا لمواقع خارجية

# بيانات الاتصال بقاعدة البيانات
# عدّل هذه القيم عند رفع المشروع على استضافة حقيقية
$host     = "localhost";   # عنوان السيرفر
$username = "root";        # اسم مستخدم قاعدة البيانات
$password = "";            # كلمة مرور قاعدة البيانات (فارغة في XAMPP)
$db       = "realestate";  # اسم قاعدة البيانات

try {
    # إنشاء الاتصال باستخدام PDO مع دعم اللغة العربية (utf8mb4)
    $database = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $username, $password);

    # جعل PDO يرمي استثناء (Exception) عند حدوث أي خطأ في الاستعلامات
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    # جعل النتائج ترجع كمصفوفة بأسماء الأعمدة مثل $row['UserName']
    $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    # تعطيل المحاكاة حتى تُنفَّذ الاستعلامات المُجهّزة فعلياً في السيرفر (حماية أكثر)
    $database->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $error) {
    # (التوافر + السرية) نسجّل تفاصيل الخطأ في ملف السجل الخاص بالسيرفر فقط
    # ونعرض للمستخدم رسالة عامة، لأن رسالة الخطأ الأصلية قد تكشف اسم القاعدة أو المستخدم
    error_log('DB connection failed: ' . $error->getMessage());
    http_response_code(503);
    die('<h2 style="font-family:sans-serif;text-align:center;margin-top:60px">
         The system is temporarily unavailable. Please try again later.</h2>');
}

# استدعاء ملف الدوال المساعدة (الحماية، الرسائل، التحقق من البيانات)
require_once __DIR__ . '/functions.php';
