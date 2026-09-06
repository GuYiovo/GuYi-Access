<?php
// config.php
ini_set('session.gc_maxlifetime', 315360000);
session_set_cookie_params(315360000);

if (!defined('DB_INSTALLED_CHECK')) {
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    if ($current_script !== 'install.php' && file_exists(__DIR__ . '/install.php')) {
        // 智能判断当前所处层级进行跳转
        $is_sub_dir = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || strpos($_SERVER['REQUEST_URI'], '/login/') !== false);
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
?>
