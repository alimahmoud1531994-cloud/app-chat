<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/functions.php';

require_guest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $username === '' || $email === '' || $password === '') {
        flash('error', 'يرجى تعبئة جميع الحقول المطلوبة.');
        redirect(base_url('register.php'));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'صيغة البريد الإلكتروني غير صحيحة.');
        redirect(base_url('register.php'));
    }

    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
        flash('error', 'اسم المستخدم يجب أن يكون بين 3 و30 حرفاً ويحتوي على أحرف وأرقام فقط.');
        redirect(base_url('register.php'));
    }

    if (strlen($password) < 6) {
        flash('error', 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.');
        redirect(base_url('register.php'));
    }

    if ($password !== $confirmPassword) {
        flash('error', 'تأكيد كلمة المرور غير مطابق.');
        redirect(base_url('register.php'));
    }

    $check = db()->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $check->execute([$username, $email]);

    if ($check->fetch()) {
        flash('error', 'اسم المستخدم أو البريد مستخدم بالفعل.');
        redirect(base_url('register.php'));
    }

    $stmt = db()->prepare(
        'INSERT INTO users (full_name, username, email, password_hash, bio, status_message, created_at, updated_at, last_seen)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())'
    );

    $stmt->execute([
        $fullName,
        $username,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        'مرحباً، هذا حساب جديد في Messenger Pro.',
        'جاهز للدردشة',
    ]);

    $newUserId = (int) db()->lastInsertId();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $newUserId;
    update_last_seen($newUserId);

    flash('success', 'تم إنشاء الحساب بنجاح. أكمل الآن ملفك الشخصي.');
    redirect(base_url('chat.php'));
}

$pageTitle = 'إنشاء حساب';
require_once __DIR__ . '/partials/header.php';
?>
<div class="auth-wrap">
    <section class="auth-card">
        <h2>إنشاء حساب جديد</h2>
        <p class="muted">ابدأ خلال ثوانٍ، ثم أضف صورتك وغطاء البروفايل من صفحة الإعدادات.</p>

        <form method="post" class="form-grid">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

            <div class="form-row">
                <label class="field">
                    <span>الاسم الكامل</span>
                    <input class="input" type="text" name="full_name" placeholder="مثال: أحمد محمد" required>
                </label>
                <label class="field">
                    <span>اسم المستخدم</span>
                    <input class="input" type="text" name="username" placeholder="ahmed_dev" required>
                </label>
            </div>

            <label class="field">
                <span>البريد الإلكتروني</span>
                <input class="input" type="email" name="email" placeholder="name@example.com" required>
            </label>

            <div class="form-row">
                <label class="field">
                    <span>كلمة المرور</span>
                    <input class="input" type="password" name="password" placeholder="6 أحرف أو أكثر" required>
                </label>
                <label class="field">
                    <span>تأكيد كلمة المرور</span>
                    <input class="input" type="password" name="confirm_password" placeholder="أعد كتابة كلمة المرور" required>
                </label>
            </div>

            <button class="btn" type="submit">إنشاء الحساب</button>
        </form>

        <div class="auth-switch">
            لديك حساب بالفعل؟ <a href="<?= e(base_url('login.php')) ?>">سجل الدخول</a>
        </div>
    </section>

    <section class="auth-hero">
        <div class="brand">
            <span class="brand-badge">UI</span>
            <div>
                <strong>شكل احترافي</strong>
                <div class="small">تصميم عصري داكن مع عناصر زجاجية</div>
            </div>
        </div>
        <h1 class="hero-title">بروفايل جاهز، واجهة أنيقة، وتجربة استخدام قريبة من تطبيقات المحادثة الحديثة.</h1>
        <p class="hero-text">بعد التسجيل ستجد صفحة محادثات رئيسية، شريطاً جانبياً للمستخدمين، إمكانية البحث، وبروفايل فيه إحصاءات وآخر الصور المرسلة.</p>
        <div class="feature-list">
            <div class="feature-card"><strong>غلاف وصورة شخصية</strong><div class="small">ارفع صورة أفاتار وصورة كفر للبروفايل.</div></div>
            <div class="feature-card"><strong>إدارة الحساب</strong><div class="small">تعديل الاسم واليوزر والبريد والنبذة والحالة.</div></div>
            <div class="feature-card"><strong>محادثة مباشرة</strong><div class="small">افتح أي مستخدم وابدأ الشات فوراً.</div></div>
            <div class="feature-card"><strong>إحصاءات مصغرة</strong><div class="small">عدد الرسائل والمرفقات والمحادثات.</div></div>
        </div>
    </section>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
