<?php
# ==========================================================
# صفحة البحث للتعديل (Update.php)
# المستخدم يبحث برقم الهوية، ثم يختار القطعة التي يريد تعديلها
# ==========================================================

# الاتصال بقاعدة البيانات + التأكد من تسجيل الدخول
require __DIR__ . '/_includes/connect.php';
require_login();

# قراءة رقم الهوية من الرابط (نستخدم GET حتى يبقى البحث ظاهراً في الرابط)
$search = trim($_GET['id_number'] ?? '');
$rows   = [];

# إذا أدخل المستخدم رقم هوية نبحث عنه
if ($search !== '') {
    # استعلام مُجهّز: القيمة تُمرَّر منفصلة عن الاستعلام (حماية من SQL Injection)
    $sth = $database->prepare("SELECT * FROM estate WHERE IDNumber = :IDNumber ORDER BY ID DESC");
    $sth->bindParam(':IDNumber', $search);
    $sth->execute();

    # fetchAll تجلب كل النتائج (العميل قد يملك أكثر من قطعة)
    $rows = $sth->fetchAll();
}

# إعدادات الصفحة
$pageTitle  = 'Update Block Info';
$activePage = 'Update.php';
include __DIR__ . '/_includes/header.php';
?>

<!-- ===== نموذج البحث ===== -->
<section class="card">
    <form method="get" class="search-bar">
        <i class="bx bx-id-card"></i>
        <input type="text" name="id_number" placeholder="Search by customer ID number..." required
               value="<?php echo e($search); ?>">
        <button type="submit" class="btn btn-primary"><i class="bx bx-search"></i> Search</button>
    </form>
</section>

<!-- ===== النتائج (تظهر فقط بعد البحث) ===== -->
<?php if ($search !== ''): ?>
    <section class="card">
        <?php if ($rows): ?>
            <!-- عدد النتائج -->
            <div class="card-head">
                <h2>Results</h2>
                <span class="muted"><?php echo count($rows); ?> block(s) found</span>
            </div>
            <!-- الجدول المشترك مع زر التعديل فقط -->
            <?php $showEdit = true; include __DIR__ . '/_includes/table.php'; ?>
        <?php else: ?>
            <!-- رسالة عدم وجود نتائج -->
            <div class="empty"><i class="bx bx-search-alt"></i><p>No blocks found for this ID number.</p></div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php include __DIR__ . '/_includes/footer.php'; ?>
