<?php
# ==========================================================
# لوحة التحكم / القائمة الرئيسية (Page.php)
# تعرض إحصائيات سريعة + أزرار الوصول للعمليات + آخر القطع المضافة
# ==========================================================

# الاتصال بقاعدة البيانات
require __DIR__ . '/_includes/connect.php';

# منع الدخول لهذه الصفحة بدون تسجيل دخول
require_login();

# ---------- الإحصائيات ----------
# نحسب في استعلام واحد: عدد القطع، مجموع الأسعار، مجموع المدفوع، مجموع المتبقي
# COALESCE تجعل النتيجة 0 بدل NULL إذا كان الجدول فارغاً
$stats = $database->query(
    "SELECT COUNT(*) AS blocks,
            COALESCE(SUM(TotalCatchBlook), 0) AS total,
            COALESCE(SUM(CustomerPayment), 0) AS paid,
            COALESCE(SUM(RemainingAmount), 0) AS remaining
     FROM estate"
)->fetch();

# نسبة التحصيل: كم بالمئة من إجمالي المبالغ تم دفعه
$percent = $stats['total'] > 0 ? round($stats['paid'] / $stats['total'] * 100) : 0;

# ---------- آخر 5 قطع مضافة ----------
# نرتبها تنازلياً حسب رقم السجل (الأحدث أولاً)
$rows = $database->query("SELECT * FROM estate ORDER BY ID DESC LIMIT 5")->fetchAll();

# إعدادات الصفحة ثم استدعاء الجزء العلوي
$pageTitle  = 'Dashboard';
$activePage = 'Page.php';
include __DIR__ . '/_includes/header.php';
?>

<!-- ===== بطاقات الإحصائيات ===== -->
<section class="stats">

    <!-- عدد القطع -->
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bx bx-map-alt"></i></div>
        <div>
            <small>Total Blocks</small>
            <strong><?php echo (int) $stats['blocks']; ?></strong>
        </div>
    </div>

    <!-- إجمالي قيمة القطع -->
    <div class="stat-card">
        <div class="stat-icon purple"><i class="bx bx-wallet"></i></div>
        <div>
            <small>Total Value</small>
            <strong><?php echo money($stats['total']); ?></strong>
        </div>
    </div>

    <!-- إجمالي المدفوع -->
    <div class="stat-card">
        <div class="stat-icon green"><i class="bx bx-check-shield"></i></div>
        <div>
            <small>Total Paid</small>
            <strong><?php echo money($stats['paid']); ?></strong>
        </div>
    </div>

    <!-- إجمالي المتبقي -->
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bx bx-time-five"></i></div>
        <div>
            <small>Total Remaining</small>
            <strong><?php echo money($stats['remaining']); ?></strong>
        </div>
    </div>
</section>

<!-- ===== شريط نسبة التحصيل ===== -->
<section class="card">
    <div class="card-head">
        <h2>Collection Progress</h2>
        <span class="muted"><?php echo $percent; ?>% collected</span>
    </div>
    <!-- عرض الشريط الداخلي يساوي النسبة المئوية -->
    <div class="progress"><div class="progress-bar" style="width: <?php echo $percent; ?>%"></div></div>
</section>

<!-- ===== أزرار الوصول السريع للعمليات ===== -->
<section class="quick">
    <a href="Add.php" class="quick-card">
        <i class="bx bx-plus-circle"></i><span>Add New Block</span>
    </a>
    <a href="Update.php" class="quick-card">
        <i class="bx bx-edit"></i><span>Update Block Info</span>
    </a>
    <a href="Delet.php" class="quick-card">
        <i class="bx bx-trash"></i><span>Delete Blocks</span>
    </a>
    <a href="Find.php" class="quick-card">
        <i class="bx bx-search"></i><span>Find Block</span>
    </a>
    <a href="Show.php" class="quick-card">
        <i class="bx bx-list-ul"></i><span>Show Block List</span>
    </a>
</section>

<!-- ===== جدول آخر القطع المضافة ===== -->
<section class="card">
    <div class="card-head">
        <h2>Recently Added</h2>
        <a href="Show.php" class="link">View all <i class="bx bx-right-arrow-alt"></i></a>
    </div>

    <?php if ($rows): ?>
        <!-- استدعاء الجدول المشترك بدون أزرار تعديل وحذف -->
        <?php include __DIR__ . '/_includes/table.php'; ?>
    <?php else: ?>
        <!-- رسالة تظهر إذا لا توجد بيانات -->
        <div class="empty"><i class="bx bx-folder-open"></i><p>No blocks yet. Start by adding a new block.</p></div>
    <?php endif; ?>
</section>

<?php
# استدعاء الجزء السفلي للصفحة
include __DIR__ . '/_includes/footer.php';
?>
