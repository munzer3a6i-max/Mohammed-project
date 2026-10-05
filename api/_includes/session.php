<?php
# ==========================================================
# حفظ الجلسات في قاعدة البيانات (session.php)
# على Vercel كل طلب قد يعمل على سيرفر مختلف، والملفات المؤقتة لا تُشارك بينها
# لذلك لا يمكن حفظ الجلسة كملف (الطريقة الافتراضية في PHP)
# الحل: نحفظ الجلسة في جدول sessions داخل قاعدة البيانات
# ==========================================================

class DatabaseSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    private $database;

    public function __construct(PDO $database)
    {
        $this->database = $database;
    }

    public function open($path, $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    # قراءة بيانات الجلسة (ترجع نصاً فارغاً إذا لم توجد)
    public function read($id): string|false
    {
        $sql = $this->database->prepare("SELECT Data FROM sessions WHERE ID = :id");
        $sql->execute([':id' => $id]);
        $data = $sql->fetchColumn();
        return $data === false ? '' : $data;
    }

    # حفظ بيانات الجلسة (إضافة أو تحديث)
    public function write($id, $data): bool
    {
        $sql = $this->database->prepare(
            "INSERT INTO sessions (ID, Data, LastAccess) VALUES (:id, :data, :t)
             ON DUPLICATE KEY UPDATE Data = VALUES(Data), LastAccess = VALUES(LastAccess)"
        );
        return $sql->execute([':id' => $id, ':data' => $data, ':t' => time()]);
    }

    public function destroy($id): bool
    {
        $sql = $this->database->prepare("DELETE FROM sessions WHERE ID = :id");
        return $sql->execute([':id' => $id]);
    }

    # حذف الجلسات القديمة المنتهية
    public function gc($max_lifetime): int|false
    {
        $sql = $this->database->prepare("DELETE FROM sessions WHERE LastAccess < :t");
        $sql->execute([':t' => time() - $max_lifetime]);
        return $sql->rowCount();
    }

    # (السرية) use_strict_mode: نقبل فقط رقم جلسة موجود فعلاً في قاعدة البيانات
    public function validateId($id): bool
    {
        $sql = $this->database->prepare("SELECT 1 FROM sessions WHERE ID = :id");
        $sql->execute([':id' => $id]);
        return (bool) $sql->fetchColumn();
    }

    public function updateTimestamp($id, $data): bool
    {
        $sql = $this->database->prepare("UPDATE sessions SET LastAccess = :t WHERE ID = :id");
        return $sql->execute([':t' => time(), ':id' => $id]);
    }
}
