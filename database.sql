-- ==========================================================
-- ملف قاعدة البيانات (database.sql)
-- طريقة الاستخدام: افتح phpMyAdmin > تبويب Import > اختر هذا الملف > Go
-- أو الصق محتواه في تبويب SQL
-- ملاحظة: في SQL نكتب الشرح بعد -- وليس #
-- ==========================================================

-- إنشاء قاعدة البيانات إذا لم تكن موجودة، مع دعم اللغة العربية
CREATE DATABASE IF NOT EXISTS realestate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- اختيار قاعدة البيانات للعمل عليها
USE realestate;


-- ----------------------------------------------------------
-- جدول القطع (estate)
-- IF NOT EXISTS: إذا كان الجدول موجوداً مسبقاً لا يتم حذفه ولا تضيع بياناتك
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS estate (
    ID              INT AUTO_INCREMENT PRIMARY KEY,  -- رقم السجل (يزيد تلقائياً)
    IDNumber        VARCHAR(10)   NOT NULL,          -- رقم هوية العميل
    UserName        VARCHAR(100)  NOT NULL,          -- اسم العميل
    AreaNumber      VARCHAR(50)   NOT NULL,          -- رقم المخطط
    BlockNumber     VARCHAR(50)   NOT NULL,          -- رقم القطعة
    `Date`          DATE          NOT NULL,          -- تاريخ البيع
    TotalCatchBlook DECIMAL(12,2) NOT NULL,          -- السعر الإجمالي للقطعة
    CustomerPayment DECIMAL(12,2) NOT NULL,          -- المبلغ المدفوع
    RemainingAmount DECIMAL(12,2) NOT NULL,          -- المبلغ المتبقي
    INDEX idx_idnumber (IDNumber),                   -- (التوافر) فهرس لتسريع البحث برقم الهوية
    UNIQUE KEY uq_area_block (AreaNumber, BlockNumber),  -- (السلامة) قاعدة البيانات نفسها تمنع تكرار القطعة
    -- (السلامة) قيود تمنع حفظ أرقام غير منطقية حتى لو تجاوز أحد صفحات الموقع
    CONSTRAINT chk_total   CHECK (TotalCatchBlook > 0),
    CONSTRAINT chk_payment CHECK (CustomerPayment >= 0 AND CustomerPayment <= TotalCatchBlook),
    CONSTRAINT chk_remain  CHECK (RemainingAmount = TotalCatchBlook - CustomerPayment)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ----------------------------------------------------------
-- جدول المستخدمين (login)
-- نحذفه ونعيد إنشاءه لأنه أضيفت له أعمدة جديدة (كلمة المرور + قفل الحساب)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS login;

CREATE TABLE login (
    ID             INT AUTO_INCREMENT PRIMARY KEY,  -- رقم المستخدم
    Name           VARCHAR(50)  NOT NULL UNIQUE,    -- اسم الدخول (لا يتكرر)
    Password       VARCHAR(255) NOT NULL,           -- (السرية) كلمة المرور مشفّرة (وليست نصاً عادياً)
    FailedAttempts INT NOT NULL DEFAULT 0,          -- (السرية) عدد محاولات الدخول الخاطئة المتتالية
    LockedUntil    DATETIME NULL                    -- (السرية) الحساب مقفل حتى هذا الوقت
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- إضافة المستخدم الافتراضي
-- اسم الدخول: majed
-- كلمة المرور: majed123
-- كلمة المرور محفوظة مشفّرة بدالة password_hash في PHP
INSERT INTO login (Name, Password) VALUES
('majed', '$2y$12$k0BXMKN29mcKs5kq1bFsgeZEhJUdVRQgLopIumZG/JqFX0jnOeBae');


-- ----------------------------------------------------------
-- (السلامة) جدول سجل العمليات (audit_log)
-- يحفظ كل عملية: من؟ ماذا؟ متى؟ ومن أي جهاز؟
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log (
    ID        INT AUTO_INCREMENT PRIMARY KEY,  -- رقم العملية
    UserName  VARCHAR(50)  NOT NULL,           -- اسم المستخدم الذي قام بالعملية
    Action    VARCHAR(30)  NOT NULL,           -- نوع العملية: ADD, UPDATE, DELETE, LOGIN ...
    Details   VARCHAR(500) NOT NULL,           -- تفاصيل العملية (مثلاً القيم قبل وبعد التعديل)
    IPAddress VARCHAR(45)  NOT NULL,           -- عنوان جهاز المستخدم
    CreatedAt DATETIME     NOT NULL,           -- تاريخ ووقت العملية
    INDEX idx_created (CreatedAt)              -- فهرس لتسريع عرض آخر العمليات
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ----------------------------------------------------------
-- بيانات تجريبية (اختيارية) لعرض المشروع
-- إذا لا تريدها احذف هذا الجزء قبل الاستيراد
-- ----------------------------------------------------------
INSERT INTO estate (IDNumber, UserName, AreaNumber, BlockNumber, `Date`, TotalCatchBlook, CustomerPayment, RemainingAmount)
SELECT * FROM (
    SELECT '1023456789' AS a, 'Ahmed Alharbi' AS b, 'A-12' AS c, '105' AS d, '2026-01-15' AS e, 350000.00 AS f, 350000.00 AS g, 0.00 AS h UNION ALL
    SELECT '1098765432', 'Sara Alqahtani', 'A-12', '106', '2026-02-03', 420000.00, 200000.00, 220000.00 UNION ALL
    SELECT '1034567890', 'Khalid Alotaibi', 'B-07', '31',  '2026-03-21', 275000.00, 100000.00, 175000.00 UNION ALL
    SELECT '1023456789', 'Ahmed Alharbi', 'C-03', '12',  '2026-05-09', 510000.00, 300000.00, 210000.00
) AS demo
WHERE NOT EXISTS (SELECT 1 FROM estate);   -- تضاف فقط إذا كان الجدول فارغاً
