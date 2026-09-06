<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title><?= htmlspecialchars($currentTitle) ?> - <?= htmlspecialchars($conf_site_title) ?></title>
<link rel="icon" href="<?= htmlspecialchars($conf_favicon) ?>">
<script src="https://cdn.jsdelivr.net/npm/@phosphor-icons/web"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="../assets/css/cards.css?v=<?= time() ?>" rel="stylesheet">
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
                <span style="font-size: 11px; color: var(--color-primary); font-family: 'Inter', monospace; font-weight: 600; margin-top: 2px; opacity: 0.8;">v2026.9.5</span>
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
            <?php if(!empty($msg)): ?><div id="sys-msg" data-msg="<?= $msg ?>" data-type="success" style="display:none;"></div><?php endif; ?>
            <?php if(!empty($errorMsg)): ?><div id="sys-msg" data-msg="<?= $errorMsg ?>" data-type="error" style="display:none;"></div><?php endif; ?>
