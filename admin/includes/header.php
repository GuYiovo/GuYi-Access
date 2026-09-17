<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">

<!-- 视口配置：确保移动端响应式、禁用缩放，适配全面屏 -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">

<!-- iOS 全屏模式配置 -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($conf_site_title) ?>">

<!-- Android 全屏模式配置 -->
<meta name="mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#ffffff">

<title><?= htmlspecialchars($currentTitle) ?> - <?= htmlspecialchars($conf_site_title) ?></title>

<!-- 图标设置：覆盖多设备尺寸（iOS主屏图标、通用favicon） -->
<!-- 智能调用：直接读取后台设置中的头像/图标，更改头像后桌面图标自动更新 -->
<link rel="apple-touch-icon" href="<?= htmlspecialchars($conf_avatar) ?>">
<link rel="icon" type="image/jpeg" href="<?= htmlspecialchars($conf_favicon) ?>">
<link rel="icon" href="<?= htmlspecialchars($conf_favicon) ?>">

<script src="https://cdn.jsdelivr.net/npm/@phosphor-icons/web"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="../assets/css/cards.css?v=<?= time() ?>" rel="stylesheet">
<style>
    /* ================= 原生 App 物理引擎级重写 ================= */
    * { -webkit-tap-highlight-color: transparent; }
    body { overscroll-behavior-y: none; /* 彻底阻断 iOS 上下拉回弹白边 */ }
    
    input[type=range] { -webkit-appearance: none; width: 100%; height: 6px; background: var(--border-color-input); border-radius: 3px; outline: none; transition: background 0.2s; }
    input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 18px; height: 18px; border-radius: 50%; background: var(--color-primary); cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.2); transition: transform 0.1s; }
    input[type=range]::-webkit-slider-thumb:active { transform: scale(1.2); }
    
    .admin-sider { box-shadow: 4px 0 24px rgba(0, 0, 0, 0.06) !important; border-right: 1px solid var(--border-color) !important; z-index: 50; }
    .admin-layout.sider-collapsed .sider-logo .logo-info { display: none !important; }
    
    /* ================= 移动端沉浸式原生 App 级别优化 ================= */
    @media (max-width: 768px) {
        body { padding-bottom: 90px !important; }
        
        /* ======== 终极修复：彻底隐藏无用的PC端侧边栏和顶部栏，释放全屏空间 ======== */
        .admin-sider { display: none !important; }
        .admin-header { display: none !important; }
        .admin-main { margin-left: 0 !important; width: 100% !important; }
        
        /* ======== 刘海屏安全区适配：给内容区顶部下压，完美避开刘海/灵动岛 ======== */
        .admin-content { 
            padding-top: calc(20px + env(safe-area-inset-top)) !important; 
        }
        
        /* 核心导航条：iOS 毛玻璃、浮动效果 */
        .m-nav { 
            bottom: max(16px, env(safe-area-inset-bottom)) !important; left: 20px !important; right: 20px !important; 
            width: auto !important; border-radius: 28px !important; border-top: none !important; 
            border: 1px solid rgba(255, 255, 255, 0.4) !important; box-shadow: 0 12px 40px rgba(0,0,0,0.12) !important; 
            padding: 6px 12px !important; z-index: 1000;
            background: rgba(255, 255, 255, 0.8) !important; backdrop-filter: saturate(200%) blur(30px); -webkit-backdrop-filter: saturate(200%) blur(30px);
        }
        
        /* 全局卡片 App 化：无边框、大圆角、弥散阴影 */
        .e-card { border-radius: 24px !important; border: none !important; box-shadow: 0 8px 32px rgba(0,0,0,0.05) !important; margin-bottom: 24px !important; overflow: hidden !important; }
        .e-card-header { padding: 24px 24px 12px 24px !important; border-bottom: none !important; font-size: 17px !important; font-weight: 700 !important; }
        .e-card-body { padding: 0 24px 24px 24px !important; }
        
        /* 页面标题大字重 App 风格 */
        .page-title { font-size: 30px !important; font-weight: 800 !important; margin-top: 8px !important; letter-spacing: -0.8px; margin-bottom: 8px !important; }
        .page-desc { font-size: 15px !important; color: var(--text-tertiary) !important; margin-bottom: 28px !important; font-weight: 500; }
        
        /* 底部操作面板 Action Sheet 化 */
        .modal-overlay { align-items: flex-end !important; padding: 0 !important; background: rgba(0,0,0,0.4) !important; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); }
        .modal-content { 
            width: 100% !important; max-width: 100% !important; border-radius: 32px 32px 0 0 !important; margin: 0 !important; 
            padding: 24px 24px calc(24px + env(safe-area-inset-bottom)) 24px !important; 
            animation: modalSlideUp 0.35s cubic-bezier(0.2, 1, 0.3, 1) !important; 
        }
        .modal-header { font-size: 18px !important; font-weight: 700 !important; padding: 0 0 20px 0 !important; border-bottom: none !important; text-align: center; }
        @keyframes modalSlideUp { from { transform: translateY(100%); opacity: 0.8; } to { transform: translateY(0); opacity: 1; } }
        
        /* 交互控件高度与文字垂直居中修复 */
        .e-input, .e-textarea { border-radius: 16px !important; padding: 12px 16px !important; font-size: 15px !important; line-height: 1.5 !important; }
        .e-select { 
            border-radius: 16px !important; height: 48px !important; padding: 0 36px 0 16px !important; font-size: 15px !important; 
            line-height: normal !important; appearance: none; -webkit-appearance: none; 
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") !important; 
            background-repeat: no-repeat !important; background-position: right 12px center !important; background-size: 16px !important; 
        }
        
        .e-btn { border-radius: 16px !important; font-weight: 600 !important; }
        
        /* 分段按钮文字居中 */
        .e-segmented { border-radius: 14px !important; padding: 4px !important; display: flex !important; }
        .e-segmented-item { border-radius: 10px !important; font-size: 15px !important; padding: 8px 16px !important; flex: 1; text-align: center; }
        
        /* 移动端表格允许滑动，不撑破屏幕 */
        .e-table-wrap { 
            width: 100% !important; display: block !important; overflow-x: auto !important; 
            -webkit-overflow-scrolling: touch !important; border: none !important; 
            border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; 
        }
        .e-table { width: 100% !important; min-width: max-content !important; }
        .e-table th { background: transparent !important; border-bottom: 1px solid rgba(0,0,0,0.05) !important; padding: 12px 16px !important; white-space: nowrap !important; }
        .e-table td { border-bottom: 1px solid rgba(0,0,0,0.03) !important; padding: 16px 16px !important; white-space: nowrap !important; }
        
        /* 更多功能面板的悬浮定位与隐藏状态 */
        .m-nav-more-overlay {
            display: block; position: fixed; 
            bottom: max(90px, calc(74px + env(safe-area-inset-bottom))); right: 20px;
            background: rgba(255, 255, 255, 0.85); backdrop-filter: saturate(200%) blur(30px); -webkit-backdrop-filter: saturate(200%) blur(30px);
            border-radius: 24px; padding: 20px; 
            width: calc(100vw - 40px); max-width: 300px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.12);
            border: 1px solid rgba(255, 255, 255, 0.5);
            opacity: 0; visibility: hidden;
            transform: translateY(15px) scale(0.95); transform-origin: bottom right;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); z-index: 999;
        }
        .m-nav-more-overlay.show { opacity: 1; visibility: visible; transform: translateY(0) scale(1); }
        .m-nav-more-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .m-nav-more-item {
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
            padding: 16px 8px; border-radius: 16px;
            color: var(--text-secondary, #475569); text-decoration: none; font-size: 14px;
            background: rgba(0, 0, 0, 0.03); transition: all 0.2s;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .m-nav-more-item i { font-size: 28px; margin-bottom: 2px; }
        .m-nav-more-item:hover, .m-nav-more-item.active { background: var(--color-primary-bg, #eff6ff); color: var(--color-primary, #3b82f6); }
    }
    @media (min-width: 769px) { .m-nav-more-overlay { display: none !important; } }
</style>
<?php if ($conf_enable_bg == '1'): ?>
<style>
    .admin-main { position: relative; z-index: 1; }
    /* 默认 PC 端横屏背景图 */
    .admin-main::before { content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('https://www.loliapi.com/acg/pc/') center/cover fixed; opacity: <?= htmlspecialchars($conf_bg_img_opacity) ?>; pointer-events: none; z-index: -1; }
    .admin-header { background: rgba(255, 255, 255, <?= htmlspecialchars($conf_card_opacity) ?>) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255, 255, 255, 0.4) !important; }
    .e-card, .pro-stat-card { background: rgba(255, 255, 255, <?= htmlspecialchars($conf_card_opacity) ?>) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.6) !important; box-shadow: 0 8px 32px rgba(31, 38, 135, 0.05) !important; }
    .e-table thead, .e-table th, thead[style*="var(--bg-layout)"], div[style*="var(--bg-layout)"] { background: rgba(255, 255, 255, <?= max(0, $conf_card_opacity - 0.3) ?>) !important; }
    .e-input, .e-select, .e-textarea { background: rgba(255, 255, 255, <?= max(0.1, $conf_card_opacity - 0.1) ?>) !important; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
    
    @media (max-width: 768px) { 
        /* 手机端竖屏背景图完美适配 */
        .admin-main::before { background-image: url('https://www.loliapi.com/acg/pe/'); }
        .m-nav, .m-nav-more-overlay { background: rgba(255, 255, 255, <?= htmlspecialchars($conf_card_opacity) ?>) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.5) !important; }
        .m-nav-more-item { background: rgba(255, 255, 255, <?= max(0.1, $conf_card_opacity - 0.1) ?>) !important; }
    }
</style>
<?php endif; ?>

<script>
// 移动端菜单交互控制逻辑
document.addEventListener('DOMContentLoaded', function() {
    var mToggle = document.getElementById('mNavMoreToggle');
    var mMenu = document.getElementById('mNavMoreMenu');
    if (mToggle && mMenu) {
        mToggle.addEventListener('click', function(e) {
            e.preventDefault(); e.stopPropagation();
            mMenu.classList.toggle('show');
        });
        document.addEventListener('click', function(e) {
            if (!mMenu.contains(e.target) && !mToggle.contains(e.target)) { mMenu.classList.remove('show'); }
            // 点击面板里的按键自动收起菜单
            if (e.target.closest('.m-nav-more-item')) { mMenu.classList.remove('show'); }
        });
    }
});
</script>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sider">
        <div class="sider-logo">
            <img src="<?= htmlspecialchars($conf_avatar) ?>" alt="Logo">
            <div class="logo-info" style="display: flex; flex-direction: column; justify-content: center; overflow: hidden; white-space: nowrap; transition: all 0.3s;">
                <span class="logo-text" style="line-height: 1.2;"><?= htmlspecialchars(mb_strimwidth($conf_site_title, 0, 10, '..')) ?></span>
                <span style="font-size: 11px; color: var(--color-primary); font-family: 'Inter', monospace; font-weight: 600; margin-top: 2px; opacity: 0.8;">v2026.9.17</span>
            </div>
        </div>
        <div class="sider-menu">
            <div class="menu-group"><span><?= t('系统概览') ?></span></div>
            <a href="?tab=dashboard" class="menu-item <?= $tab == 'dashboard' ? 'active' : '' ?>"><i class="ph ph-squares-four"></i> <span><?= t('数据总览') ?></span></a>
            <div class="menu-group"><span><?= t('核心业务') ?></span></div>
            <a href="?tab=apps" class="menu-item <?= $tab == 'apps' ? 'active' : '' ?>"><i class="ph ph-app-window"></i> <span><?= t('应用管理') ?></span></a>
            <?php $isCardMenu = in_array($tab, ['list', 'create']); ?>
            <div class="menu-item has-submenu <?= $isCardMenu ? 'submenu-open active-parent' : '' ?>" onclick="toggleSubMenu(this)"><i class="ph ph-database"></i> <span><?= t('卡密管理') ?></span><i class="ph ph-caret-down sub-arrow"></i></div>
            <div class="sub-menu" style="<?= $isCardMenu ? 'display:block;' : 'display:none;' ?>"><a href="?tab=list" class="sub-menu-item <?= $tab == 'list' ? 'active' : '' ?>"><?= t('卡密库存') ?></a><a href="?tab=create" class="sub-menu-item <?= $tab == 'create' ? 'active' : '' ?>"><?= t('批量制卡') ?></a></div>
            <?php $isSecMenu = in_array($tab, ['blacklist', 'logs']); ?>
            <div class="menu-item has-submenu <?= $isSecMenu ? 'submenu-open active-parent' : '' ?>" onclick="toggleSubMenu(this)"><i class="ph ph-shield-check"></i> <span><?= t('防御与监控') ?></span><i class="ph ph-caret-down sub-arrow"></i></div>
            <div class="sub-menu" style="<?= $isSecMenu ? 'display:block;' : 'display:none;' ?>"><a href="?tab=blacklist" class="sub-menu-item <?= $tab == 'blacklist' ? 'active' : '' ?>"><?= t('防御与拉黑') ?></a><a href="?tab=logs" class="sub-menu-item <?= $tab == 'logs' ? 'active' : '' ?>"><?= t('访问日志') ?></a></div>
            <div style="margin-top: auto;"></div>
            <div class="menu-group"><span><?= t('系统设置') ?></span></div>
            <a href="?tab=settings" class="menu-item <?= $tab == 'settings' ? 'active' : '' ?>"><i class="ph ph-gear"></i> <span><?= t('全局配置') ?></span></a>
            <a href="javascript:void(0);" onclick="checkSystemUpdate(true, this)" class="menu-item data-no-ajax"><i class="ph ph-rocket-launch"></i> <span><?= t('检查更新') ?></span></a>
            <a href="?logout=1" class="menu-item menu-item-danger data-no-ajax"><i class="ph ph-sign-out"></i> <span><?= t('安全退出') ?></span></a>
        </div>
    </aside>
    
    <!-- 移动端底部导航与折叠面板 -->
    <nav class="m-nav" id="mNav">
        <a href="?tab=dashboard" class="m-nav-item <?= $tab == 'dashboard' ? 'active' : '' ?>"><i class="ph ph-squares-four"></i><span><?= t('概览') ?></span></a>
        <a href="?tab=apps" class="m-nav-item <?= $tab == 'apps' ? 'active' : '' ?>"><i class="ph ph-app-window"></i><span><?= t('应用') ?></span></a>
        <a href="?tab=list" class="m-nav-item <?= $tab == 'list' ? 'active' : '' ?>"><i class="ph ph-database"></i><span><?= t('库存') ?></span></a>
        <a href="?tab=create" class="m-nav-item <?= $tab == 'create' ? 'active' : '' ?>"><i class="ph ph-magic-wand"></i><span><?= t('制卡') ?></span></a>
        
        <!-- 更多触发按钮 -->
        <a href="javascript:void(0);" class="m-nav-item <?= in_array($tab, ['settings','blacklist','logs']) ? 'active' : '' ?>" id="mNavMoreToggle">
            <i class="ph ph-list"></i><span><?= t('更多') ?></span>
        </a>
    </nav>

    <!-- 手机端更多功能弹出面板 -->
    <div class="m-nav-more-overlay" id="mNavMoreMenu">
        <div class="m-nav-more-grid">
            <a href="?tab=blacklist" class="m-nav-more-item <?= $tab == 'blacklist' ? 'active' : '' ?>"><i class="ph ph-shield-check"></i><span><?= t('防御拉黑') ?></span></a>
            <a href="?tab=logs" class="m-nav-more-item <?= $tab == 'logs' ? 'active' : '' ?>"><i class="ph ph-clock-counter-clockwise"></i><span><?= t('访问日志') ?></span></a>
            <a href="?tab=settings" class="m-nav-more-item <?= $tab == 'settings' ? 'active' : '' ?>"><i class="ph ph-gear"></i><span><?= t('全局设置') ?></span></a>
            <a href="javascript:void(0);" onclick="checkSystemUpdate(true, this)" class="m-nav-more-item data-no-ajax"><i class="ph ph-rocket-launch"></i><span><?= t('检查更新') ?></span></a>
            
            <a href="?logout=1" class="m-nav-more-item data-no-ajax" style="color:var(--color-error); background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2);">
                <i class="ph ph-sign-out"></i><span><?= t('安全退出') ?></span>
            </a>
        </div>
    </div>

    <main class="admin-main">
        <header class="admin-header">
            <div class="header-left"><button class="sider-toggle" id="siderToggle"><i class="ph ph-list"></i></button></div>
            <div class="header-user"><span><?= htmlspecialchars($currentAdminUser) ?></span><img src="<?= htmlspecialchars($conf_avatar) ?>" class="header-avatar" alt="Avatar"></div>
        </header>
        <div class="admin-content" id="main" style="will-change: transform, opacity;">
            <?php if(!empty($msg)): ?><div id="sys-msg" data-msg="<?= $msg ?>" data-type="success" style="display:none;"></div><?php endif; ?>
            <?php if(!empty($errorMsg)): ?><div id="sys-msg" data-msg="<?= $errorMsg ?>" data-type="error" style="display:none;"></div><?php endif; ?>
