<?php
// /admin/cards.php
ini_set('display_errors', 0);
error_reporting(0);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/app/Infrastructure/Database/Database.php';
session_start();

try { $db = new Database(); } catch (Throwable $e) { die("系统维护中，无法连接数据库。"); }

if (!isset($_SESSION['admin_logged_in']) && isset($_COOKIE['admin_trust'])) {
    try {
        $adminHashFingerprint = md5((string)$db->getAdminHash());
        $parts = explode('|', $_COOKIE['admin_trust']);
        if (count($parts) === 2) {
            list($payload, $sign) = $parts;
            if (hash_equals(hash_hmac('sha256', $payload, SYS_SECRET), $sign)) {
                $data = json_decode(base64_decode($payload), true);
                if ($data && isset($data['exp'], $data['ua'], $data['ph']) && 
                    $data['exp'] > time() && 
                    $data['ua'] === md5($_SERVER['HTTP_USER_AGENT']) && 
                    hash_equals($data['ph'], $adminHashFingerprint)) {
                    $_SESSION['admin_logged_in'] = true; 
                    $_SESSION['last_ip'] = $_SERVER['REMOTE_ADDR'];
                }
            }
        }
    } catch (Exception $e) { }
}

if (!isset($_SESSION['admin_logged_in'])) { header('Location: ../login/login.php'); exit; }
if (isset($_SESSION['last_ip']) && $_SESSION['last_ip'] !== $_SERVER['REMOTE_ADDR']) { session_unset(); session_destroy(); header('Location: ../login/login.php'); exit; }
if (isset($_GET['logout'])) { session_destroy(); setcookie('admin_trust', '', time() - 3600, '/'); header('Location: ../login/login.php'); exit; }

if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } 
    catch (Exception $e) { $_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true)); }
}
$csrf_token = $_SESSION['csrf_token'];

function verifyCSRF() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        header('HTTP/1.1 403 Forbidden'); die('Security Alert: CSRF 校验失败。');
    }
}

// 语言切换逻辑
if (isset($_GET['lang']) && in_array($_GET['lang'], ['zh', 'en'])) {
    setcookie('sys_lang', $_GET['lang'], time() + 86400 * 365, '/');
    $tab = $_GET['tab'] ?? 'dashboard';
    header("Location: cards.php?tab={$tab}");
    exit;
}

require_once dirname(__DIR__, 2) . '/resources/lang/admin.php';

// 【核心拦截器】：如果是英文环境，启动底层输出拦截翻译引擎
if ($sys_lang === 'en') {
    ob_start('translate_output_buffer');
}

require_once dirname(__DIR__, 2) . '/app/Admin/Actions.php';

$sysConf = $db->getSystemSettings();
$currentAdminUser = $db->getAdminUsername();

$conf_site_title = !empty($sysConf['site_title']) ? $sysConf['site_title'] : 'GuYi Access';
$conf_favicon = !empty($sysConf['favicon']) ? $sysConf['favicon'] : base64_decode('aHR0cHM6Ly9xMS5xbG9nby5jbi9nP2I9cXEmbms9MTU2NDQwMDAwJnM9NjQw');
$conf_avatar = !empty($sysConf['admin_avatar']) ? $sysConf['admin_avatar'] : base64_decode('aHR0cHM6Ly9xMS5xbG9nby5jbi9nP2I9cXEmbms9MTU2NDQwMDAwJnM9NjQw');
$conf_api_encrypt = $sysConf['api_encrypt'] ?? '1';
$conf_enable_bg = $sysConf['enable_bg_image'] ?? '0';

$conf_bg_img_opacity = isset($sysConf['bg_img_opacity']) ? floatval($sysConf['bg_img_opacity']) : 0.2;
$conf_card_opacity = isset($sysConf['card_opacity']) ? floatval($sysConf['card_opacity']) : 0.7;

$tab = $_GET['tab'] ?? 'dashboard';

// 这里拿掉了关于作者的独立页面路由
$pageTitles = ['dashboard'=>t('数据总览'),'apps'=>t('应用管理'),'list'=>t('卡密库存'),'create'=>t('批量制卡'),'blacklist'=>t('防御与拉黑'),'logs'=>t('访问日志'),'settings'=>t('系统配置')];
$currentTitle = $pageTitles[$tab] ?? t('控制台');
$allowed_tabs = array_keys($pageTitles);

require_once dirname(__DIR__, 2) . '/resources/views/admin/layout/header.php';

if (in_array($tab, $allowed_tabs)) {
    require_once dirname(__DIR__, 2) . "/resources/views/admin/pages/{$tab}.php";
} else {
    require_once dirname(__DIR__, 2) . "/resources/views/admin/pages/dashboard.php";
}

require_once dirname(__DIR__, 2) . '/resources/views/admin/layout/footer.php';
