<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/functions.php';

if (is_logged_in()) {
    redirect(base_url('chat.php'));
}

redirect(base_url('login.php'));
