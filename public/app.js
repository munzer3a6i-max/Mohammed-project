// ==========================================================
// ملف الجافاسكربت المشترك (app.js)
// ملاحظة: في الجافاسكربت نكتب الشرح بعد // وليس # لأن # يسبب خطأ هنا
// ==========================================================


// ---------- 1) حساب المبلغ المتبقي تلقائياً ----------
// نبحث عن حقول السعر والمدفوع (التي عليها data-calc) وحقل المتبقي
const calcInputs = document.querySelectorAll('[data-calc]');
const remainingInput = document.getElementById('RemainingAmount');

function calculateRemainingAmount() {
    // قراءة القيم وتحويلها لأرقام، وإذا كان الحقل فارغاً نعتبره صفر
    const total = parseFloat(document.getElementsByName('TotalCatchBlook')[0].value) || 0;
    const payment = parseFloat(document.getElementsByName('CustomerPayment')[0].value) || 0;

    // المتبقي = الإجمالي - المدفوع، مع إظهار رقمين عشريين
    const remaining = total - payment;
    remainingInput.value = remaining.toFixed(2);

    // تلوين الحقل بالأحمر إذا كان المدفوع أكبر من السعر
    remainingInput.classList.toggle('invalid', remaining < 0);
}

// ربط الدالة بحدث الكتابة في الحقلين، وتشغيلها مرة عند فتح الصفحة
if (calcInputs.length && remainingInput) {
    calcInputs.forEach(function (input) {
        input.addEventListener('input', calculateRemainingAmount);
    });
    calculateRemainingAmount();
}


// ---------- 2) رسالة تأكيد قبل الحذف ----------
// أي نموذج عليه data-confirm يسأل المستخدم قبل الإرسال
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        // إذا ضغط المستخدم "إلغاء" نوقف إرسال النموذج
        if (!confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});


// ---------- 3) فتح وإغلاق القائمة الجانبية في الجوال ----------
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const menuBtn = document.getElementById('menuBtn');

if (sidebar && menuBtn) {
    // الضغط على زر القائمة يفتحها أو يغلقها
    menuBtn.addEventListener('click', function () {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    });
    // الضغط على الخلفية الشفافة يغلق القائمة
    overlay.addEventListener('click', function () {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });
}


// ---------- 4) الفلتر السريع في جدول قائمة القطع ----------
const filterInput = document.getElementById('tableFilter');

if (filterInput) {
    filterInput.addEventListener('input', function () {
        // تحويل الكلمة لحروف صغيرة حتى لا يفرق بين الكبير والصغير
        const word = filterInput.value.toLowerCase();

        // نمر على كل صف: إذا كان نصه يحتوي الكلمة نظهره، وإلا نخفيه
        document.querySelectorAll('.table tbody tr').forEach(function (row) {
            row.style.display = row.textContent.toLowerCase().includes(word) ? '' : 'none';
        });
    });
}


// ---------- 5) إخفاء رسائل النجاح تلقائياً بعد 4 ثوانٍ ----------
document.querySelectorAll('.alert-success').forEach(function (alertBox) {
    setTimeout(function () {
        alertBox.classList.add('hide');
    }, 4000);
});
