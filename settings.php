<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/view.php';

require_login();
update_last_seen();

$user = current_user(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $bio = trim((string) ($_POST['bio'] ?? ''));
    $statusMessage = trim((string) ($_POST['status_message'] ?? ''));
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $username === '' || $email === '') {
        flash('error', 'الاسم واسم المستخدم والبريد حقول مطلوبة.');
        redirect(base_url('settings.php'));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'البريد الإلكتروني غير صالح.');
        redirect(base_url('settings.php'));
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? LIMIT 1');
    $stmt->execute([$username, $email, (int) $user['id']]);
    if ($stmt->fetch()) {
        flash('error', 'اسم المستخدم أو البريد مستخدم من حساب آخر.');
        redirect(base_url('settings.php'));
    }

    if ($newPassword !== '' && strlen($newPassword) < 6) {
        flash('error', 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل.');
        redirect(base_url('settings.php'));
    }

    if ($newPassword !== '' && $newPassword !== $confirmPassword) {
        flash('error', 'تأكيد كلمة المرور الجديدة غير مطابق.');
        redirect(base_url('settings.php'));
    }

    try {
        $avatar = isset($_FILES['avatar']) ? store_uploaded_file($_FILES['avatar'], 'uploads/avatars', ALLOWED_IMAGE_EXTENSIONS) : null;
        $cover = isset($_FILES['cover_image']) ? store_uploaded_file($_FILES['cover_image'], 'uploads/covers', ALLOWED_IMAGE_EXTENSIONS) : null;

        $fields = 'full_name = ?, username = ?, email = ?, bio = ?, status_message = ?, updated_at = NOW()';
        $params = [$fullName, $username, $email, $bio, $statusMessage];

        if ($avatar) {
            $fields .= ', avatar = ?';
            $params[] = $avatar['path'];
        }

        if ($cover) {
            $fields .= ', cover_image = ?';
            $params[] = $cover['path'];
        }

        if ($newPassword !== '') {
            $fields .= ', password_hash = ?';
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $params[] = (int) $user['id'];
        $update = db()->prepare('UPDATE users SET ' . $fields . ' WHERE id = ?');
        $update->execute($params);

        flash('success', 'تم تحديث الحساب بنجاح.');
        redirect(base_url('profile.php'));
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect(base_url('settings.php'));
    }
}

$pageTitle = 'إعدادات الحساب';
require_once __DIR__ . '/partials/header.php';
?>
<div class="profile-shell">
    <div class="top-nav">
        <div class="brand">
            <span class="brand-badge">⚙</span>
            <div>
                <strong>إعدادات الحساب</strong>
                <div class="small">تعديل الملف الشخصي والمظهر</div>
            </div>
        </div>
        <div class="profile-actions">
            <a class="ghost-btn" href="<?= e(base_url('chat.php')) ?>">العودة للشات</a>
            <a class="btn secondary" href="<?= e(base_url('profile.php')) ?>">عرض البروفايل</a>
        </div>
    </div>

    <section class="settings-card">
        <h2 class="section-title">تحديث البيانات</h2>
        <p class="muted">يمكنك تغيير اسمك وصورتك الشخصية وصورة الغلاف وكلمة المرور من هنا.</p>

        <form method="post" enctype="multipart/form-data" class="form-grid">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

            <div class="form-row">
                <label class="field">
                    <span>الاسم الكامل</span>
                    <input class="input" type="text" name="full_name" value="<?= e($user['full_name']) ?>" required>
                </label>
                <label class="field">
                    <span>اسم المستخدم</span>
                    <input class="input" type="text" name="username" value="<?= e($user['username']) ?>" required>
                </label>
            </div>

            <label class="field">
                <span>البريد الإلكتروني</span>
                <input class="input" type="email" name="email" value="<?= e($user['email']) ?>" required>
            </label>

            <div class="form-row">
                <label class="field">
                    <span>الحالة المختصرة</span>
                    <input class="input" type="text" name="status_message" value="<?= e($user['status_message']) ?>" placeholder="مثال: متاح للعمل">
                </label>
                <label class="field">
                    <span>الصورة الشخصية</span>
                    <input class="file-input" type="file" name="avatar" accept="image/*">
                </label>
            </div>

            <label class="field">
                <span>صورة الغلاف</span>
                <input class="file-input" type="file" name="cover_image" accept="image/*">
            </label>

            <label class="field">
                <span>نبذة مختصرة</span>
                <textarea class="textarea" name="bio" rows="5" placeholder="اكتب نبذة قصيرة عنك أو عن نشاط الحساب"><?= e($user['bio']) ?></textarea>
            </label>

            <div class="hr"></div>

            <h2 class="section-title">تغيير كلمة المرور</h2>
            <div class="form-row">
                <label class="field">
                    <span>كلمة المرور الجديدة</span>
                    <input class="input" type="password" name="new_password" placeholder="اتركها فارغة إن لم ترد التغيير">
                </label>
                <label class="field">
                    <span>تأكيد كلمة المرور الجديدة</span>
                    <input class="input" type="password" name="confirm_password" placeholder="أعد كتابة الكلمة الجديدة">
                </label>
            </div>

            <div class="helper-note">بعد الحفظ ستنعكس التغييرات مباشرة في صفحة البروفايل والشات.</div>
            <button class="btn" type="submit">حفظ التعديلات</button>
        </form>
    </section>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
