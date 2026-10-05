<?php
# ==========================================================
# الجزء العلوي المشترك لكل الصفحات (header.php)
# يحتوي على: رأس الصفحة + القائمة الجانبية + الشريط العلوي
# قبل استدعائه نحدد في الصفحة:
#   $pageTitle  = عنوان الصفحة
#   $activePage = اسم الصفحة الحالية لتلوين زرها في القائمة
# ==========================================================

# قائمة روابط القائمة الجانبية: [اسم الملف, النص, الأيقونة]
$menu = [
    ['Page.php',   'Dashboard',   'bx-grid-alt'],
    ['Add.php',    'Add Block',   'bx-plus-circle'],
    ['Update.php', 'Update Block','bx-edit'],
    ['Delet.php',  'Delete Block','bx-trash'],
    ['Find.php',   'Find Block',  'bx-search'],
    ['Show.php',   'Block List',  'bx-list-ul'],
    ['Security.php','Security (CIA)','bx-shield-quarter'],  # صفحة الأمان CIA
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ترميز الصفحة لدعم كل اللغات -->
    <meta charset="UTF-8">
    <!-- جعل الصفحة متجاوبة مع شاشات الجوال -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- عنوان الصفحة في تبويب المتصفح -->
    <title><?php echo e($pageTitle); ?> | Real Estate</title>
    <!-- خط Poppins من Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- مكتبة الأيقونات Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <!-- ملف التنسيق الرئيسي للمشروع -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- الغلاف الرئيسي: قائمة جانبية + محتوى -->
<div class="layout">

    <!-- ===== القائمة الجانبية ===== -->
    <aside class="sidebar" id="sidebar">

        <!-- شعار واسم النظام -->
        <div class="brand">
            <i class="bx bxs-buildings"></i>
            <span>Real Estate</span>
        </div>

        <!-- روابط الصفحات: نمر على المصفوفة ونطبع كل رابط -->
        <nav class="nav">
            <?php foreach ($menu as $item): ?>
                <!-- إذا كان الرابط هو الصفحة الحالية نضيف له كلاس active -->
                <a href="<?php echo $item[0]; ?>" class="<?php echo $activePage === $item[0] ? 'active' : ''; ?>">
                    <i class="bx <?php echo $item[2]; ?>"></i>
                    <span><?php echo $item[1]; ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- بيانات المستخدم الحالي + زر تسجيل الخروج -->
        <div class="user-box">
            <div class="avatar"><?php echo e(strtoupper(substr($_SESSION['user'], 0, 1))); ?></div>
            <div class="user-info">
                <strong><?php echo e($_SESSION['user']); ?></strong>
                <small>Administrator</small>
            </div>
            <a href="Exit.php" class="logout" title="Logout"><i class="bx bx-log-out"></i></a>
        </div>
    </aside>

    <!-- خلفية شفافة تظهر خلف القائمة في الجوال، الضغط عليها يغلق القائمة -->
    <div class="overlay" id="overlay"></div>

    <!-- ===== منطقة المحتوى ===== -->
    <main class="content">

        <!-- الشريط العلوي: زر القائمة للجوال + عنوان الصفحة + التاريخ -->
        <header class="topbar">
            <button class="menu-btn" id="menuBtn" type="button" aria-label="Menu"><i class="bx bx-menu"></i></button>
            <h1><?php echo e($pageTitle); ?></h1>
            <span class="today"><i class="bx bx-calendar"></i> <?php echo date('d M Y'); ?></span>
        </header>

        <!-- عرض رسالة النجاح أو الخطأ إن وجدت -->
        <?php echo show_flash(); ?>
