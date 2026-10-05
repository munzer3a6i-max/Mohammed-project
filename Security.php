<?php
# ==========================================================
# صفحة الأمان (Security.php) - مثلث الأمان CIA
#   C = Confidentiality (السرية)  : البيانات لا يراها إلا المصرّح له
#   I = Integrity       (السلامة) : البيانات صحيحة ولا تتغير بدون علمنا
#   A = Availability    (التوافر) : البيانات متاحة دائماً وقت الحاجة
# تحتوي الصفحة على: شرح ما تم تطبيقه + تغيير كلمة المرور + النسخ الاحتياطي + سجل العمليات
# ==========================================================

# الاتصال بقاعدة البيانات + التأكد من تسجيل الدخول
include 'connect.php';
require_login();

# أخطاء نموذج تغيير كلمة المرور
$pwErrors = [];

# ---------- (السرية) تغيير كلمة المرور ----------
if (isset($_POST['change_password'])) {

    # التحقق من رمز الحماية
    check_csrf();

    # قراءة الحقول الثلاثة
    $current = $_POST['current'] ?? '';
    $new     = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    # جلب كلمة المرور المشفّرة الحالية للمستخدم
    $u = $database->prepare("SELECT * FROM login WHERE Name = :n");
    $u->execute([':n' => $_SESSION['user']]);
    $user = $u->fetch();

    # 1) يجب إدخال كلمة المرور الحالية بشكل صحيح (حتى لا يغيرها شخص وجد الجهاز مفتوحاً)
    if (!$user || !password_verify($current, $user['Password'])) {
        $pwErrors[] = 'Current password is incorrect.';
    }

    # 2) كلمة المرور الجديدة يجب أن تكون قوية
    $pwErrors = array_merge($pwErrors, validate_password($new));

    # 3) تأكيد كلمة المرور يجب أن يطابق الجديدة
    if ($new !== $confirm) {
        $pwErrors[] = 'New password and confirmation do not match.';
    }

    # إذا لا توجد أخطاء: نشفّر كلمة المرور الجديدة ونحفظها
    if (!$pwErrors) {
        $save = $database->prepare("UPDATE login SET Password = :p WHERE ID = :id");
        $save->execute([':p' => password_hash($new, PASSWORD_DEFAULT), ':id' => $user['ID']]);

        # تجديد رقم الجلسة بعد تغيير كلمة المرور
        session_regenerate_id(true);

        # (السلامة) تسجيل العملية
        log_action($database, 'PASSWORD_CHANGE', 'Password changed');

        flash('success', 'Password changed successfully.');
        header('Location: Security.php');
        exit();
    }
}

# ---------- (السلامة) قراءة آخر 100 عملية من سجل العمليات ----------
$logs = $database->query("SELECT * FROM audit_log ORDER BY ID DESC LIMIT 100")->fetchAll();

# ألوان شارات أنواع العمليات
$actionColors = [
    'ADD' => 'badge-paid', 'UPDATE' => 'badge-info', 'DELETE' => 'badge-danger',
    'LOGIN' => 'badge-muted', 'LOGOUT' => 'badge-muted', 'LOGIN_FAILED' => 'badge-pending',
    'BACKUP' => 'badge-info', 'PASSWORD_CHANGE' => 'badge-pending',
];

# إعدادات الصفحة
$pageTitle  = 'Security (CIA)';
$activePage = 'Security.php';
include 'header.php';
?>

<!-- ===== بطاقات مثلث الأمان CIA ===== -->
<section class="cia">

    <!-- C: السرية -->
    <div class="cia-card">
        <div class="cia-letter c">C</div>
        <h2>Confidentiality</h2>
        <p class="muted">Only authorized users can see the data.</p>
        <ul class="checks">
            <li><i class="bx bx-check"></i> Login required on every page</li>
            <li><i class="bx bx-check"></i> Passwords hashed (bcrypt)</li>
            <li><i class="bx bx-check"></i> Account lock after <?php echo MAX_ATTEMPTS; ?> failed logins</li>
            <li><i class="bx bx-check"></i> Auto logout after <?php echo SESSION_TIMEOUT / 60; ?> min idle</li>
            <li><i class="bx bx-check"></i> Secure cookies (HttpOnly, SameSite)</li>
            <li><i class="bx bx-check"></i> XSS output escaping</li>
        </ul>
    </div>

    <!-- I: السلامة -->
    <div class="cia-card">
        <div class="cia-letter i">I</div>
        <h2>Integrity</h2>
        <p class="muted">Data stays correct and every change is tracked.</p>
        <ul class="checks">
            <li><i class="bx bx-check"></i> Prepared statements (no SQL Injection)</li>
            <li><i class="bx bx-check"></i> CSRF tokens on every form</li>
            <li><i class="bx bx-check"></i> Server-side validation</li>
            <li><i class="bx bx-check"></i> Remaining amount calculated by server</li>
            <li><i class="bx bx-check"></i> Duplicate block prevention</li>
            <li><i class="bx bx-check"></i> Audit log of every action</li>
        </ul>
    </div>

    <!-- A: التوافر -->
    <div class="cia-card">
        <div class="cia-letter a">A</div>
        <h2>Availability</h2>
        <p class="muted">Data is always accessible when needed.</p>
        <ul class="checks">
            <li><i class="bx bx-check"></i> One-click backup (CSV / SQL)</li>
            <li><i class="bx bx-check"></i> Restore by importing SQL backup</li>
            <li><i class="bx bx-check"></i> Friendly error pages, errors logged</li>
            <li><i class="bx bx-check"></i> Database indexes for fast search</li>
            <li><i class="bx bx-check"></i> Lockout is temporary (no permanent denial)</li>
        </ul>
    </div>
</section>

<!-- ===== صف: تغيير كلمة المرور + النسخ الاحتياطي ===== -->
<section class="two-cols">

    <!-- (السرية) نموذج تغيير كلمة المرور -->
    <div class="card">
        <div class="card-head"><h2><i class="bx bx-lock-alt"></i> Change Password</h2></div>

        <!-- عرض أخطاء كلمة المرور إن وجدت -->
        <?php if ($pwErrors): ?>
            <div class="alert alert-error"><i class="bx bx-error-circle"></i>
                <ul><?php foreach ($pwErrors as $er): ?><li><?php echo e($er); ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="post" class="stack">
            <?php echo csrf_field(); ?>
            <label class="field"><span>Current Password</span>
                <input type="password" name="current" required autocomplete="current-password"></label>
            <label class="field"><span>New Password</span>
                <input type="password" name="new" required minlength="8" autocomplete="new-password"
                       placeholder="At least 8 characters, letters and numbers"></label>
            <label class="field"><span>Confirm New Password</span>
                <input type="password" name="confirm" required minlength="8" autocomplete="new-password"></label>
            <div class="form-actions">
                <button type="submit" name="change_password" class="btn btn-primary"><i class="bx bx-key"></i> Update Password</button>
            </div>
        </form>
    </div>

    <!-- (التوافر) النسخ الاحتياطي -->
    <div class="card">
        <div class="card-head"><h2><i class="bx bx-cloud-download"></i> Backup</h2></div>
        <p class="muted">Download a copy of all block data. Keep it safe so the data can be restored if the server fails or records are lost.</p>

        <div class="backup-btns">
            <!-- نسخة Excel -->
            <a href="Backup.php?type=csv" class="backup-btn">
                <i class="bx bx-spreadsheet"></i>
                <div><strong>Excel (CSV)</strong><small>Open and read in Excel</small></div>
            </a>
            <!-- نسخة SQL للاسترجاع -->
            <a href="Backup.php?type=sql" class="backup-btn">
                <i class="bx bx-data"></i>
                <div><strong>Database (SQL)</strong><small>Restore via phpMyAdmin → Import</small></div>
            </a>
        </div>
    </div>
</section>

<!-- ===== (السلامة) سجل العمليات ===== -->
<section class="card">
    <div class="card-head">
        <h2><i class="bx bx-history"></i> Audit Log</h2>
        <div class="head-actions">
            <!-- الفلتر السريع (نفس الموجود في صفحة القائمة) -->
            <div class="mini-search">
                <i class="bx bx-filter-alt"></i>
                <input type="text" id="tableFilter" placeholder="Filter log...">
            </div>
        </div>
    </div>

    <?php if ($logs): ?>
        <div class="table-wrap">
            <table class="table log-table">
                <thead>
                    <tr><th>Date & Time</th><th>User</th><th>Action</th><th>Details</th><th>IP Address</th></tr>
                </thead>
                <tbody>
                    <!-- كل عملية في صف، مع e() للحماية من XSS -->
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo e($log['CreatedAt']); ?></td>
                            <td class="strong"><?php echo e($log['UserName']); ?></td>
                            <td><span class="badge <?php echo $actionColors[$log['Action']] ?? 'badge-muted'; ?>"><?php echo e($log['Action']); ?></span></td>
                            <td class="wrap"><?php echo e($log['Details']); ?></td>
                            <td><?php echo e($log['IPAddress']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty"><i class="bx bx-history"></i><p>No activity recorded yet.</p></div>
    <?php endif; ?>
</section>

<?php include 'footer.php'; ?>
