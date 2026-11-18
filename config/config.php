<?php
// config/config.php
// Central application configuration. Installer populates the database credentials and SYS_SECRET.

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

ini_set('session.gc_maxlifetime', 315360000);
// 明确指定 path=/ 与 SameSite，避免子目录 session 丢失导致登录 CSRF 失败
$__secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
         || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 315360000,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $__secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
} else {
    session_set_cookie_params(315360000, '/', '', $__secure, true);
}
unset($__secure);

// If installation has not completed, guide users to the installer.
// The root install.php remains as a compatibility entry point.
if (!defined('DB_INSTALLED_CHECK')) {
    $current_script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $installer_exists = file_exists(BASE_PATH . '/scripts/install.php') || file_exists(BASE_PATH . '/install.php');

    if ($current_script !== 'install.php' && $installer_exists) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $is_sub_dir = (strpos($uri, '/admin/') !== false || strpos($uri, '/login/') !== false);
        header('Location: ' . ($is_sub_dir ? '../install.php' : 'install.php'));
        exit();
    }
}

if (!defined('CARD_TYPES')) {
    define('CARD_TYPES', [
        'hour' => ['name' => '小时卡', 'duration' => 3600],
        'day' => ['name' => '天卡', 'duration' => 86400],
        'week' => ['name' => '周卡', 'duration' => 604800],
        'month' => ['name' => '月卡', 'duration' => 2592000],
        'season' => ['name' => '季卡', 'duration' => 7776000],
        'year' => ['name' => '年卡', 'duration' => 31536000],
    ]);
}
