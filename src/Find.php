<?php
# ==========================================================
# صفحة البحث (Find.php)
# البحث باسم العميل أو رقم المخطط، ويقبل جزء من الاسم
# ==========================================================

# الاتصال بقاعدة البيانات + التأكد من تسجيل الدخول
require __DIR__ . '/_includes/connect.php';
require_login();

# قراءة كلمة البحث من الرابط
$search = trim($_GET['search'] ?? '');
$rows   = [];

if ($search !== '') {
    # نضيف % قبل وبعد الكلمة حتى يصير البحث جزئياً
    # مثال: البحث عن "ali" يجد "Ali Ahmed" و "Khalid Ali"
    $like = '%' . $search . '%';

    # استعلام مُجهّز: الكلمة تُمرَّر كقيمة منفصلة (هنا كانت ثغرة SQL Injection في النسخة القديمة)
    $sth = $database->prepare(
        "SELECT * FROM estate WHERE UserName LIKE :s1 OR AreaNumber LIKE :s2 ORDER BY ID DESC"
    );
    $sth->bindParam(':s1', $like);
    $sth->bindParam(':s2', $like);
    $sth->execute();

    # جلب كل النتائج (وليس أول نتيجة فقط)
    $rows = $sth->fetchAll();
}

# إعدادات الصفحة
$pageTitle  = 'Find Block';
$activePage = 'Find.php';
include __DIR__ . '/_includes/header.php';
?>

<!-- ===== نموذج البحث ===== -->
<section class="card">
    <form method="get" class="search-bar">
        <i class="bx bx-search"></i>
        <input type="text" name="search" placeholder="Search by customer name or area number..." required
               value="<?php echo e($search); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</section>

<!-- ===== النتائج ===== -->
<?php if ($search !== ''): ?>
    <section class="card">
        <?php if ($rows): ?>
            <div class="card-head">
                <h2>Results for "<?php echo e($search); ?>"</h2>
                <span class="muted"><?php echo count($rows); ?> block(s) found</span>
            </div>
            <!-- الجدول المشترك مع أزرار التعديل والحذف -->
            <?php
            $showEdit   = true;
            $showDelete = true;
            $backTo     = 'Find.php?search=' . urlencode($search);
            include __DIR__ . '/_includes/table.php';
            ?>
        <?php else: ?>
            <div class="empty"><i class="bx bx-search-alt"></i><p>No results match your search.</p></div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php include __DIR__ . '/_includes/footer.php'; ?>
