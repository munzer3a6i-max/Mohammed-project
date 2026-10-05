<?php
# ==========================================================
# صفحة البحث للحذف (Delet.php)
# المستخدم يبحث برقم الهوية، ثم يضغط زر الحذف بجانب القطعة
# عملية الحذف نفسها تتم في ملف Delete.php
# ==========================================================

# الاتصال بقاعدة البيانات + التأكد من تسجيل الدخول
include 'connect.php';
require_login();

# قراءة رقم الهوية من الرابط
$search = trim($_GET['id_number'] ?? '');
$rows   = [];

# البحث عن كل القطع التابعة لرقم الهوية
if ($search !== '') {
    $sth = $database->prepare("SELECT * FROM estate WHERE IDNumber = :IDNumber ORDER BY ID DESC");
    $sth->bindParam(':IDNumber', $search, PDO::PARAM_STR);
    $sth->execute();
    $rows = $sth->fetchAll();
}

# إعدادات الصفحة
$pageTitle  = 'Delete Block';
$activePage = 'Delet.php';
include 'header.php';
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

<!-- ===== النتائج ===== -->
<?php if ($search !== ''): ?>
    <section class="card">
        <?php if ($rows): ?>
            <div class="card-head">
                <h2>Results</h2>
                <span class="muted"><?php echo count($rows); ?> block(s) found</span>
            </div>
            <!-- الجدول المشترك مع زر الحذف فقط، وبعد الحذف نرجع لنفس نتيجة البحث -->
            <?php
            $showDelete = true;
            $backTo     = 'Delet.php?id_number=' . urlencode($search);
            include 'table.php';
            ?>
        <?php else: ?>
            <div class="empty"><i class="bx bx-search-alt"></i><p>No blocks found for this ID number.</p></div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php include 'footer.php'; ?>
