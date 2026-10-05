<?php
# ==========================================================
# جدول عرض القطع المشترك (table.php)
# نستخدمه في أكثر من صفحة بدل تكرار نفس الجدول
# قبل استدعائه نحدد في الصفحة:
#   $rows       = السجلات القادمة من قاعدة البيانات
#   $showEdit   = true لإظهار زر التعديل
#   $showDelete = true لإظهار زر الحذف
#   $backTo     = الصفحة التي نرجع لها بعد الحذف
# ==========================================================

# قيم افتراضية إذا لم تُحدَّد في الصفحة
$showEdit   = $showEdit   ?? false;
$showDelete = $showDelete ?? false;
$backTo     = $backTo     ?? 'Show.php';
?>
<!-- حاوية الجدول: تسمح بالتمرير الأفقي في الشاشات الصغيرة -->
<div class="table-wrap">
    <table class="table">
        <!-- عناوين الأعمدة (بنفس ترتيب البيانات تحتها) -->
        <thead>
            <tr>
                <th>ID Number</th>
                <th>Customer Name</th>
                <th>Area No.</th>
                <th>Block No.</th>
                <th>Date</th>
                <th>Total Price</th>
                <th>Paid</th>
                <th>Remaining</th>
                <th>Status</th>
                <?php if ($showEdit || $showDelete): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <!-- نمر على كل سجل ونطبعه في صف، ونستخدم e() للحماية من XSS -->
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php echo e($row['IDNumber']); ?></td>
                    <td class="strong"><?php echo e($row['UserName']); ?></td>
                    <td><?php echo e($row['AreaNumber']); ?></td>
                    <td><?php echo e($row['BlockNumber']); ?></td>
                    <td><?php echo e($row['Date']); ?></td>
                    <td><?php echo money($row['TotalCatchBlook']); ?></td>
                    <td><?php echo money($row['CustomerPayment']); ?></td>
                    <td><?php echo money($row['RemainingAmount']); ?></td>
                    <td><?php echo status_badge($row['RemainingAmount']); ?></td>

                    <?php if ($showEdit || $showDelete): ?>
                        <td class="actions">
                            <!-- زر التعديل: يفتح صفحة Up.php مع رقم السجل -->
                            <?php if ($showEdit): ?>
                                <a class="icon-btn edit" href="Up.php?id=<?php echo (int) $row['ID']; ?>" title="Edit">
                                    <i class="bx bx-edit"></i>
                                </a>
                            <?php endif; ?>

                            <!-- زر الحذف: نموذج يرسل رقم السجل إلى Delete.php مع رسالة تأكيد قبل الحذف -->
                            <?php if ($showDelete): ?>
                                <form method="post" action="Delete.php" class="inline" data-confirm="Delete block <?php echo e($row['BlockNumber']); ?> for <?php echo e($row['UserName']); ?>?">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="ID" value="<?php echo (int) $row['ID']; ?>">
                                    <input type="hidden" name="back" value="<?php echo e($backTo); ?>">
                                    <button type="submit" class="icon-btn delete" title="Delete"><i class="bx bx-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
