<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/view.php';

require_login();
update_last_seen();

$viewer = current_user(true);
$profileId = isset($_GET['id']) ? (int) $_GET['id'] : (int) $viewer['id'];
$profileUser = fetch_user_by_id($profileId);

if (!$profileUser) {
    flash('error', 'المستخدم غير موجود.');
    redirect(base_url('chat.php'));
}

$isOwner = (int) $profileUser['id'] === (int) $viewer['id'];
$stats = conversation_stats((int) $profileUser['id']);
$media = recent_media((int) $profileUser['id']);

$pageTitle = 'الملف الشخصي';
require_once __DIR__ . '/partials/header.php';
?>
<div class="profile-shell">
    <div class="top-nav">
        <div class="brand">
            <span class="brand-badge">MP</span>
            <div>
                <strong>الملف الشخصي</strong>
                <div class="small">واجهة جاهزة وعصرية</div>
            </div>
        </div>
        <div class="profile-actions">
            <?php if ($isOwner): ?>
                <a class="ghost-btn" href="<?= e(base_url('chat.php')) ?>">العودة للشات</a>
                <a class="btn secondary" href="<?= e(base_url('settings.php')) ?>">تعديل الحساب</a>
            <?php else: ?>
                <a class="ghost-btn" href="<?= e(base_url('chat.php?user=' . (int) $profileUser['id'])) ?>">فتح المحادثة</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-cover">
        <img src="<?= e(cover_url($profileUser['cover_image'] ?? null)) ?>" alt="<?= e($profileUser['full_name']) ?>">
    </div>

    <section class="profile-card">
        <div class="profile-header">
            <div class="avatar-xl">
                <?php if (!empty($profileUser['avatar']) && file_exists(public_path($profileUser['avatar']))): ?>
                    <img src="<?= e(base_url($profileUser['avatar'])) ?>" alt="<?= e($profileUser['full_name']) ?>">
                <?php else: ?>
                    <span><?= e(initials($profileUser['full_name'])) ?></span>
                <?php endif; ?>
            </div>
            <div style="flex:1; min-width:0;">
                <div class="profile-summary">
                    <div>
                        <h1 class="profile-name"><?= e($profileUser['full_name']) ?></h1>
                        <div class="user-handle">@<?= e($profileUser['username']) ?></div>
                    </div>
                    <div class="tags">
                        <span class="tag"><?= user_is_online($profileUser['last_seen']) ? 'متصل الآن' : 'غير متصل' ?></span>
                        <span class="tag"><?= e($profileUser['status_message'] ?: 'بدون حالة') ?></span>
                    </div>
                </div>
                <p class="muted" style="margin-top:14px;"><?= e($profileUser['bio'] ?: 'لا توجد نبذة مضافة بعد.') ?></p>
            </div>
        </div>
    </section>

    <div class="profile-grid">
        <section class="profile-panel">
            <div class="section-head">
                <div>
                    <h2 class="section-title">تحليل الحساب</h2>
                    <div class="small">إحصاءات سريعة عن النشاط</div>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="small">الرسائل المرسلة</div>
                    <div class="stat-value"><?= (int) $stats['sent_count'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="small">الرسائل المستلمة</div>
                    <div class="stat-value"><?= (int) $stats['received_count'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="small">المرفقات المرسلة</div>
                    <div class="stat-value"><?= (int) $stats['media_sent_count'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="small">عدد المحادثات</div>
                    <div class="stat-value"><?= (int) $stats['chat_count'] ?></div>
                </div>
            </div>

            <div class="hr"></div>

            <h2 class="section-title">آخر الصور المرسلة</h2>
            <?php if (!$media): ?>
                <div class="helper-note">لا توجد صور مرسلة حتى الآن. عند إرسال صور داخل الشات ستظهر هنا تلقائياً.</div>
            <?php else: ?>
                <div class="media-grid">
                    <?php foreach ($media as $image): ?>
                        <a class="media-thumb" href="<?= e(base_url($image['attachment_path'])) ?>" target="_blank">
                            <img src="<?= e(base_url($image['attachment_path'])) ?>" alt="<?= e($image['attachment_name']) ?>">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <aside class="profile-panel">
            <h2 class="section-title">معلومات الحساب</h2>
            <div class="info-list">
                <div class="info-row"><span class="small">الاسم</span><strong><?= e($profileUser['full_name']) ?></strong></div>
                <div class="info-row"><span class="small">اسم المستخدم</span><strong>@<?= e($profileUser['username']) ?></strong></div>
                <div class="info-row"><span class="small">البريد</span><strong><?= $isOwner ? e($profileUser['email']) : 'خاص' ?></strong></div>
                <div class="info-row"><span class="small">تاريخ الإنشاء</span><strong><?= e(format_full_date($profileUser['created_at'])) ?></strong></div>
                <div class="info-row"><span class="small">آخر ظهور</span><strong><?= e(format_full_date($profileUser['last_seen'])) ?></strong></div>
            </div>

            <div class="hr"></div>
            <div class="helper-note">هذه الصفحة مصممة لتظهر بشكل احترافي فور التشغيل، ويمكنك تعديل الألوان والخطوط والنصوص بسهولة من ملفات CSS وPHP.</div>
        </aside>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
