<?php
# ==========================================================
# نموذج بيانات القطعة المشترك (block_form.php)
# نستخدمه في صفحة الإضافة Add.php وصفحة التعديل Up.php
# قبل استدعائه نحدد في الصفحة:
#   $data        = قيم الحقول (فارغة في الإضافة، بيانات السجل في التعديل)
#   $errors      = قائمة الأخطاء إن وجدت
#   $submitName  = اسم زر الإرسال
#   $submitLabel = النص المكتوب على الزر
# ==========================================================
?>

<!-- عرض أخطاء التحقق من البيانات إن وجدت -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <i class="bx bx-error-circle"></i>
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- النموذج: يرسل البيانات لنفس الصفحة بطريقة POST -->
<form method="post" class="form-grid">
    <!-- رمز الحماية CSRF -->
    <?php echo csrf_field(); ?>

    <!-- ===== القسم الأول: بيانات العميل والقطعة ===== -->
    <div class="card">
        <div class="card-head"><h2><i class="bx bx-user"></i> Customer & Block Info</h2></div>

        <div class="fields">
            <!-- رقم الهوية: أرقام فقط، 10 خانات كحد أقصى -->
            <label class="field">
                <span>ID Number</span>
                <input type="text" name="IDNumber" maxlength="10" inputmode="numeric" pattern="\d{1,10}"
                       placeholder="e.g. 1023456789" required value="<?php echo e($data['IDNumber'] ?? ''); ?>">
            </label>

            <!-- اسم العميل -->
            <label class="field">
                <span>Customer Name</span>
                <input type="text" name="UserName" placeholder="Enter customer name" required
                       value="<?php echo e($data['UserName'] ?? ''); ?>">
            </label>

            <!-- رقم المخطط -->
            <label class="field">
                <span>Area Number</span>
                <input type="text" name="AreaNumber" placeholder="Enter area number" required
                       value="<?php echo e($data['AreaNumber'] ?? ''); ?>">
            </label>

            <!-- رقم القطعة -->
            <label class="field">
                <span>Block Number</span>
                <input type="text" name="BlockNumber" placeholder="Enter block number" required
                       value="<?php echo e($data['BlockNumber'] ?? ''); ?>">
            </label>

            <!-- تاريخ البيع -->
            <label class="field">
                <span>Date</span>
                <input type="date" name="Date" required value="<?php echo e($data['Date'] ?? ''); ?>">
            </label>
        </div>
    </div>

    <!-- ===== القسم الثاني: البيانات المالية ===== -->
    <div class="card">
        <div class="card-head"><h2><i class="bx bx-money"></i> Payment</h2></div>

        <div class="fields">
            <!-- السعر الإجمالي: عند الكتابة يتم حساب المتبقي تلقائياً (data-calc) -->
            <label class="field">
                <span>Total Block Price</span>
                <input type="number" name="TotalCatchBlook" min="0.01" step="0.01" placeholder="0.00" required data-calc
                       value="<?php echo e($data['TotalCatchBlook'] ?? ''); ?>">
            </label>

            <!-- المبلغ المدفوع من العميل -->
            <label class="field">
                <span>Customer Payment</span>
                <input type="number" name="CustomerPayment" min="0" step="0.01" placeholder="0.00" required data-calc
                       value="<?php echo e($data['CustomerPayment'] ?? ''); ?>">
            </label>

            <!-- المبلغ المتبقي: للعرض فقط، ويحسبه السيرفر من جديد عند الحفظ -->
            <label class="field">
                <span>Remaining Amount</span>
                <input type="text" name="RemainingAmount" id="RemainingAmount" placeholder="0.00" readonly
                       value="<?php echo e($data['RemainingAmount'] ?? ''); ?>">
            </label>
        </div>

        <!-- زر الحفظ + زر الإلغاء -->
        <div class="form-actions">
            <a href="Page.php" class="btn btn-light">Cancel</a>
            <button type="submit" name="<?php echo e($submitName); ?>" class="btn btn-primary">
                <i class="bx bx-save"></i> <?php echo e($submitLabel); ?>
            </button>
        </div>
    </div>
</form>
