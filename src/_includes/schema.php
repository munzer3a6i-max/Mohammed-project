<?php
# ==========================================================
# إنشاء الجداول تلقائياً (schema.php)
# عند أول تشغيل على قاعدة بيانات فارغة ينشئ الموقع الجداول بنفسه،
# فلا حاجة لاستيراد ملف SQL يدوياً. (نفس محتوى database/database_hosting.sql)
# ==========================================================

function ensure_schema(PDO $database)
{
    # هل كل الجداول الأربعة موجودة؟ إذا نعم لا نفعل شيئاً
    $count = $database->query(
        "SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name IN ('estate', 'login', 'audit_log', 'sessions')"
    )->fetchColumn();
    if ((int) $count === 4) {
        return;
    }

    $database->exec("CREATE TABLE IF NOT EXISTS estate (
        ID              INT AUTO_INCREMENT PRIMARY KEY,
        IDNumber        VARCHAR(10)   NOT NULL,
        UserName        VARCHAR(100)  NOT NULL,
        AreaNumber      VARCHAR(50)   NOT NULL,
        BlockNumber     VARCHAR(50)   NOT NULL,
        `Date`          DATE          NOT NULL,
        TotalCatchBlook DECIMAL(12,2) NOT NULL,
        CustomerPayment DECIMAL(12,2) NOT NULL,
        RemainingAmount DECIMAL(12,2) NOT NULL,
        INDEX idx_idnumber (IDNumber),
        UNIQUE KEY uq_area_block (AreaNumber, BlockNumber),
        CONSTRAINT chk_total   CHECK (TotalCatchBlook > 0),
        CONSTRAINT chk_payment CHECK (CustomerPayment >= 0 AND CustomerPayment <= TotalCatchBlook),
        CONSTRAINT chk_remain  CHECK (RemainingAmount = TotalCatchBlook - CustomerPayment)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $database->exec("CREATE TABLE IF NOT EXISTS login (
        ID             INT AUTO_INCREMENT PRIMARY KEY,
        Name           VARCHAR(50)  NOT NULL UNIQUE,
        Password       VARCHAR(255) NOT NULL,
        FailedAttempts INT NOT NULL DEFAULT 0,
        LockedUntil    DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $database->exec("CREATE TABLE IF NOT EXISTS audit_log (
        ID        INT AUTO_INCREMENT PRIMARY KEY,
        UserName  VARCHAR(50)  NOT NULL,
        Action    VARCHAR(30)  NOT NULL,
        Details   VARCHAR(500) NOT NULL,
        IPAddress VARCHAR(45)  NOT NULL,
        CreatedAt DATETIME     NOT NULL,
        INDEX idx_created (CreatedAt)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $database->exec("CREATE TABLE IF NOT EXISTS sessions (
        ID         VARCHAR(128) NOT NULL PRIMARY KEY,
        Data       BLOB         NOT NULL,
        LastAccess INT UNSIGNED NOT NULL,
        INDEX idx_last_access (LastAccess)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    # المستخدم الافتراضي (majed / majed123) فقط إذا لم يوجد أي مستخدم
    if ((int) $database->query("SELECT COUNT(*) FROM login")->fetchColumn() === 0) {
        $database->exec("INSERT INTO login (Name, Password) VALUES
            ('majed', '\$2y\$12\$k0BXMKN29mcKs5kq1bFsgeZEhJUdVRQgLopIumZG/JqFX0jnOeBae')");
    }

    # بيانات تجريبية فقط إذا كان جدول القطع فارغاً
    if ((int) $database->query("SELECT COUNT(*) FROM estate")->fetchColumn() === 0) {
        $database->exec("INSERT INTO estate (IDNumber, UserName, AreaNumber, BlockNumber, `Date`, TotalCatchBlook, CustomerPayment, RemainingAmount) VALUES
            ('1023456789', 'Ahmed Alharbi',   'A-12', '105', '2026-01-15', 350000.00, 350000.00, 0.00),
            ('1098765432', 'Sara Alqahtani',  'A-12', '106', '2026-02-03', 420000.00, 200000.00, 220000.00),
            ('1034567890', 'Khalid Alotaibi', 'B-07', '31',  '2026-03-21', 275000.00, 100000.00, 175000.00),
            ('1023456789', 'Ahmed Alharbi',   'C-03', '12',  '2026-05-09', 510000.00, 300000.00, 210000.00)");
    }
}
