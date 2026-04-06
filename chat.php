<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/view.php';

require_login();
update_last_seen();

$currentUser = current_user(true);
$search = trim((string) ($_GET['search'] ?? ''));
$sidebarUsers = fetch_users_for_sidebar((int) $currentUser['id'], $search);
$selectedUserId = isset($_GET['user']) ? (int) $_GET['user'] : 0;

$activeUser = null;
if ($selectedUserId > 0 && $selectedUserId !== (int) $currentUser['id']) {
    $activeUser = fetch_user_by_id($selectedUserId);
}
if (!$activeUser && $sidebarUsers) {
    $activeUser = fetch_user_by_id((int) $sidebarUsers[0]['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (!$activeUser) {
        flash('error', 'اختر مستخدماً لبدء المحادثة.');
        redirect(base_url('chat.php'));
    }

    try {
        $attachment = isset($_FILES['attachment']) ? store_uploaded_file($_FILES['attachment'], 'uploads/chat') : null;
        send_message((int) $currentUser['id'], (int) $activeUser['id'], (string) ($_POST['body'] ?? ''), $attachment);
        redirect(base_url('chat.php?user=' . (int) $activeUser['id']));
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect(base_url('chat.php?user=' . (int) $activeUser['id']));
    }
}

$messages = [];
if ($activeUser) {
    mark_messages_as_read((int) $currentUser['id'], (int) $activeUser['id']);
    $messages = fetch_messages((int) $currentUser['id'], (int) $activeUser['id']);
}

$pageTitle = 'المحادثات';
require_once __DIR__ . '/partials/header.php';
?>
<div class="chat-layout">
    <aside class="sidebar">
        <div class="current-user-card">
            <div class="user-mini">
                <a class="avatar-lg" href="<?= e(base_url('profile.php')) ?>">
                    <?php if (!empty($currentUser['avatar']) && file_exists(public_path($currentUser['avatar']))): ?>
                        <img src="<?= e(base_url($currentUser['avatar'])) ?>" alt="<?= e($currentUser['full_name']) ?>">
                    <?php else: ?>
                        <span><?= e(initials($currentUser['full_name'])) ?></span>
                    <?php endif; ?>
                </a>
                <div>
                    <div class="user-name"><?= e($currentUser['full_name']) ?></div>
                    <div class="user-handle">@<?= e($currentUser['username']) ?></div>
                </div>
            </div>
            <div class="hr"></div>
            <div class="profile-actions">
                <a class="ghost-btn" href="<?= e(base_url('profile.php')) ?>">البروفايل</a>
                <a class="ghost-btn" href="<?= e(base_url('settings.php')) ?>">الإعدادات</a>
                <a class="ghost-btn" href="<?= e(base_url('logout.php')) ?>">خروج</a>
            </div>
        </div>

        <form method="get" class="search-box">
            <?php if ($activeUser): ?>
                <input type="hidden" name="user" value="<?= (int) $activeUser['id'] ?>">
            <?php endif; ?>
            <input class="search-input" type="search" name="search" value="<?= e($search) ?>" placeholder="ابحث عن مستخدم بالاسم أو اليوزر أو البريد">
        </form>

        <div class="section-head">
            <div>
                <div class="section-title">المستخدمون</div>
                <div class="small"><?= count($sidebarUsers) ?> نتيجة</div>
            </div>
            <?php if ($search !== ''): ?>
                <a class="ghost-btn" href="<?= e(base_url('chat.php')) ?>">مسح البحث</a>
            <?php endif; ?>
        </div>

        <div class="user-list">
            <?php if (!$sidebarUsers): ?>
                <div class="empty-state" style="padding:24px;">
                    <h3 class="section-title">لا توجد نتائج</h3>
                    <p class="muted">جرّب البحث باسم آخر أو أنشئ مستخدمين إضافيين للتجربة.</p>
                </div>
            <?php else: ?>
                <?php foreach ($sidebarUsers as $sidebarUser): ?>
                    <?= render_sidebar_item($sidebarUser, $activeUser ? (int) $activeUser['id'] : null) ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

    <main class="chat-board">
        <?php if (!$activeUser): ?>
            <div class="empty-state">
                <div class="brand" style="margin-inline:auto;">
                    <span class="brand-badge">💬</span>
                    <span>Messenger Pro</span>
                </div>
                <h2 class="section-title" style="margin-top:18px;">ابدأ باختيار مستخدم من الشريط الجانبي</h2>
                <p class="muted">ستظهر المحادثة هنا مع دعم الصور والملفات داخل الشات.</p>
            </div>
        <?php else: ?>
            <div class="chat-header">
                <div class="chat-user">
                    <div class="avatar-lg">
                        <?php if (!empty($activeUser['avatar']) && file_exists(public_path($activeUser['avatar']))): ?>
                            <img src="<?= e(base_url($activeUser['avatar'])) ?>" alt="<?= e($activeUser['full_name']) ?>">
                        <?php else: ?>
                            <span><?= e(initials($activeUser['full_name'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="user-title"><?= e($activeUser['full_name']) ?></div>
                        <div class="small">
                            @<?= e($activeUser['username']) ?>
                            <?= user_is_online($activeUser['last_seen']) ? '• متصل الآن' : '• آخر ظهور: ' . e(format_full_date($activeUser['last_seen'])) ?>
                        </div>
                    </div>
                </div>
                <div class="profile-actions">
                    <a class="ghost-btn" href="<?= e(base_url('profile.php?id=' . (int) $activeUser['id'])) ?>">عرض البروفايل</a>
                </div>
            </div>

            <div class="messages-area" data-messages-area>
                <?= render_messages_html($messages, (int) $currentUser['id'], $activeUser) ?>
            </div>

            <div class="message-compose">
                <form method="post" enctype="multipart/form-data" class="form-grid">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <div class="compose-grid">
                        <label class="field" style="margin:0;">
                            <span class="hidden">الرسالة</span>
                            <textarea class="textarea" name="body" placeholder="اكتب رسالتك هنا..." rows="3"></textarea>
                        </label>
                        <button class="btn" type="submit">إرسال الرسالة</button>
                    </div>

                    <div class="compose-row">
                        <label class="file-label" for="chat-attachment">📎 أرفق صورة أو ملف</label>
                        <input id="chat-attachment" class="hidden-file" type="file" name="attachment" data-chat-file>
                        <div class="small">الأنواع المدعومة: صور، PDF، Word، ZIP، TXT</div>
                    </div>

                    <div class="preview-box" data-file-preview>
                        <img class="preview-thumb" data-preview-thumb alt="preview">
                        <div class="preview-meta">
                            <div class="user-title">معاينة المرفق</div>
                            <div class="preview-note" data-preview-name></div>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php if ($activeUser): ?>
<script>
(function () {
    const area = document.querySelector('[data-messages-area]');
    if (!area) return;

    let lastHtml = area.innerHTML;
    const url = <?= json_encode(base_url('api/get_messages.php?user=' . (int) $activeUser['id'])) ?>;

    async function refreshMessages() {
        try {
            const response = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
            if (!response.ok) return;
            const html = await response.text();
            if (html !== lastHtml) {
                const nearBottom = area.scrollHeight - area.scrollTop - area.clientHeight < 120;
                area.innerHTML = html;
                lastHtml = html;
                if (nearBottom || area.dataset.forceScroll !== '0') {
                    area.scrollTop = area.scrollHeight;
                }
            }
        } catch (error) {
            console.error(error);
        }
    }

    setInterval(refreshMessages, 4000);
})();
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
