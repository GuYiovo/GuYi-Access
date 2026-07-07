<?php
// config.php
// 延长生命周期至30天（2592000秒），彻底解决后台操作缓慢导致掉线的痛点
ini_set('session.gc_maxlifetime', 2592000);
session_set_cookie_params(2592000);

if (!defined('DB_INSTALLED_CHECK')) {
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    if ($current_script !== 'install.php' && file_exists(__DIR__ . '/install.php')) {
        header('Location: install.php');
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
