<?php
# ==========================================================
# صفحة عرض كل القطع (Show.php)
# تعرض جميع السجلات + مجموع المبالغ + فلتر سريع للبحث داخل الجدول
# ==========================================================

# الاتصال بقاعدة البيانات + التأكد من تسجيل الدخول
require __DIR__ . '/_includes/connect.php';
require_login();

# جلب كل السجلات من الأحدث للأقدم
$rows = $database->query("SELECT * FROM estate ORDER BY ID DESC")->fetchAll();

# حساب المجاميع من النتائج باستخدام array_column و array_sum
$sumTotal     = array_sum(array_column($rows, 'TotalCatchBlook'));
$sumPaid      = array_sum(array_column($rows, 'CustomerPayment'));
$sumRemaining = array_sum(array_column($rows, 'RemainingAmount'));

# إعدادات الصفحة
$pageTitle  = 'Block List';
$activePage = 'Show.php';
include __DIR__ . '/_includes/header.php';
?>

<!-- ===== ملخص المجاميع ===== -->
<section class="summary">
    <div><small>Blocks</small><strong><?php echo count($rows); ?></strong></div>
    <div><small>Total Value</small><strong><?php echo money($sumTotal); ?></strong></div>
    <div><small>Paid</small><strong class="text-green"><?php echo money($sumPaid); ?></strong></div>
    <div><small>Remaining</small><strong class="text-orange"><?php echo money($sumRemaining); ?></strong></div>
</section>

<section class="card">
    <div class="card-head">
        <h2>All Blocks</h2>
        <div class="head-actions">
            <!-- فلتر سريع: يخفي الصفوف التي لا تحتوي الكلمة (يعمل بالجافاسكربت بدون تحديث الصفحة) -->
            <div class="mini-search">
                <i class="bx bx-filter-alt"></i>
                <input type="text" id="tableFilter" placeholder="Quick filter...">
            </div>
            <!-- زر إضافة قطعة جديدة -->
            <a href="Add.php" class="btn btn-primary btn-sm"><i class="bx bx-plus"></i> Add</a>
        </div>
    </div>

    <?php if ($rows): ?>
        <!-- الجدول المشترك مع أزرار التعديل والحذف -->
        <?php
        $showEdit   = true;
        $showDelete = true;
        $backTo     = 'Show.php';
        include __DIR__ . '/_includes/table.php';
        ?>
    <?php else: ?>
        <div class="empty"><i class="bx bx-folder-open"></i><p>No blocks yet.</p></div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/_includes/footer.php'; ?>
