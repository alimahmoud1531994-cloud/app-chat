<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/functions.php';

require_guest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($identity === '' || $password === '') {
        flash('error', 'يرجى إدخال البريد أو اسم المستخدم وكلمة المرور.');
        redirect(base_url('login.php'));
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        flash('error', 'بيانات الدخول غير صحيحة.');
        redirect(base_url('login.php'));
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    update_last_seen((int) $user['id']);

    flash('success', 'مرحباً بعودتك ' . $user['full_name']);
    redirect(base_url('chat.php'));
}

$pageTitle = 'تسجيل الدخول';
require_once __DIR__ . '/partials/header.php';
?>
<div class="auth-wrap">
    <section class="auth-hero">
        <div class="brand">
            <span class="brand-badge">MP</span>
            <div>
                <strong><?= e(APP_NAME) ?></strong>
                <div class="small">واجهة احترافية شبيهة بالماسنجر</div>
            </div>
        </div>
        <h1 class="hero-title">تواصل أسرع، ملفات مرفقة، وصور تظهر داخل الشات مباشرة.</h1>
        <p class="hero-text">مشروع PHP + MySQL جاهز بواجهة حديثة: محادثات فردية، بحث عن المستخدمين، بروفايل أنيق، وإحصاءات بسيطة داخل الحساب.</p>
        <div class="feature-list">
            <div class="feature-card"><strong>شات منظم</strong><div class="small">فقاعات رسائل، وقت الإرسال، وحالة المستخدم.</div></div>
            <div class="feature-card"><strong>رفع ملفات</strong><div class="small">الصور تُعرض داخل المحادثة والملفات الأخرى للتحميل.</div></div>
            <div class="feature-card"><strong>بحث سريع</strong><div class="small">ابحث بالاسم أو اليوزر أو البريد.</div></div>
            <div class="feature-card"><strong>بروفايل احترافي</strong><div class="small">صورة، غلاف، نبذة، وإحصاءات الحساب.</div></div>
        </div>
    </section>

    <section class="auth-card">
        <h2>تسجيل الدخول</h2>
        <p class="muted">أدخل بياناتك للانتقال مباشرة إلى واجهة الشات.</p>

        <form method="post" class="form-grid">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

            <label class="field">
                <span>البريد الإلكتروني أو اسم المستخدم</span>
                <input class="input" type="text" name="identity" placeholder="example@email.com أو username" required>
            </label>

            <label class="field">
                <span>كلمة المرور</span>
                <input class="input" type="password" name="password" placeholder="********" required>
            </label>

            <button class="btn" type="submit">دخول إلى الشات</button>
        </form>

        <div class="auth-switch">
            ليس لديك حساب؟ <a href="<?= e(base_url('register.php')) ?>">أنشئ حساباً جديداً</a>
        </div>
    </section>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
