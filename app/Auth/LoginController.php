<?php
ini_set('display_errors', 0); 
error_reporting(0);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/app/Infrastructure/Database/Database.php';
session_start();

try { $db = new Database(); } catch (Throwable $e) {
    die("系统维护中，请稍后重试。");
}

if (empty($_SESSION['csrf_token'])) {
    try {
        if (function_exists('random_bytes')) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } 
        else { $_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true)); }
    } catch (Exception $e) { $_SESSION['csrf_token'] = md5(uniqid()); }
}
$csrf_token = $_SESSION['csrf_token'];

$is_trusted = false;
try {
    $adminHashFingerprint = md5((string)$db->getAdminHash());
    if (isset($_COOKIE['admin_trust'])) {
        $parts = explode('|', $_COOKIE['admin_trust']);
        if (count($parts) === 2) {
            list($payload, $sign) = $parts;
            if (hash_equals(hash_hmac('sha256', $payload, SYS_SECRET), $sign)) {
                $data = json_decode(base64_decode($payload), true);
                if ($data && isset($data['exp'], $data['ua'], $data['ph']) && 
                    $data['exp'] > time() && 
                    $data['ua'] === md5($_SERVER['HTTP_USER_AGENT']) && 
                    hash_equals($data['ph'], $adminHashFingerprint)) {
                    $is_trusted = true;
                    $_SESSION['admin_logged_in'] = true; 
                    session_regenerate_id(true); 
                    $_SESSION['last_ip'] = $_SERVER['REMOTE_ADDR'];
                }
            }
        }
    }
} catch (Exception $e) { }

if (isset($_SESSION['admin_logged_in'])) { header('Location: ../admin/cards.php'); exit; }

$sysConf = $db->getSystemSettings();
$conf_site_title = $sysConf['site_title'] ?? 'GuYi Access';
$conf_favicon = !empty($sysConf['favicon']) ? $sysConf['favicon'] : 'https://q1.qlogo.cn/g?b=qq&nk=156440000&s=640';
$conf_avatar = !empty($sysConf['admin_avatar']) ? $sysConf['admin_avatar'] : 'https://q1.qlogo.cn/g?b=qq&nk=156440000&s=640';
$conf_bg_pc = $sysConf['bg_pc'] ?? 'https://www.loliapi.com/acg/pc/';
$conf_bg_mobile = $sysConf['bg_mobile'] ?? 'https://www.loliapi.com/acg/pe/';
$conf_bg_blur = $sysConf['bg_blur'] ?? '1';

$login_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && isset($_POST['username'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
         $login_error = "页面已过期，请刷新";
    } else {
        $username = trim($_POST['username']);
        $real_username = $db->getAdminUsername();
        $hash = $db->getAdminHash();
        
        // 【新增验证逻辑】: 同时校验账号和密码
        if ($username === $real_username && !empty($hash) && password_verify($_POST['password'], $hash)) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['last_ip'] = $_SERVER['REMOTE_ADDR'];
            
            $cookieData = ['exp' => time() + 315360000, 'ua' => md5($_SERVER['HTTP_USER_AGENT']), 'ph' => md5($hash)];
            $payload = base64_encode(json_encode($cookieData));
            $sign = hash_hmac('sha256', $payload, SYS_SECRET);
            setcookie('admin_trust', "$payload|$sign", time() + 315360000, '/', '', false, true);
            
            header('Location: ../admin/cards.php'); exit;
        } else {
            $login_error = "账号或密钥无效";
            usleep(500000);
        }
    }
}

require_once dirname(__DIR__, 2) . '/resources/views/auth/login.php';
