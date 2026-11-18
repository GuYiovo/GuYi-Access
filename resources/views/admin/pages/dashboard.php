<?php
$dashboardData = $db->getDashboardData();
$logs = $db->getUsageLogs(20, 0); $activeDevices = $db->getActiveDevices(); $appList = $db->getApps();
$display_stats = [ 'total' => $dashboardData['stats']['total'], 'active' => $dashboardData['stats']['active'], 'apps' => count($appList), 'unused' => $dashboardData['stats']['unused'], 'online' => $dashboardData['stats']['online'] ];
?>
<style>
    /* 全新响应式布局 */
    .dashboard-top { display: flex; flex-direction: column; gap: 24px; margin-bottom: 24px; }
    .dashboard-main { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start; }
    
    /* 头部欢迎区 */
    .app-greeting-box { display: flex; align-items: center; gap: 16px; padding: 4px 0 8px 0; }
    .app-greeting-avatar { 
        width: 52px; height: 52px; border-radius: 14px; object-fit: cover; 
        border: 1px solid var(--border-color); 
        background: var(--bg-layout);
    }
    .app-greeting-info { display: flex; flex-direction: column; justify-content: center; gap: 4px; }
    .app-greeting-title { 
        font-size: 20px; font-weight: 700; color: var(--text-primary); letter-spacing: -0.3px; 
        margin: 0; line-height: 1.25;
    }
    .app-greeting-sub { font-size: 13px; color: var(--text-tertiary); font-weight: 500; }
    
    @media (max-width: 1100px) { .dashboard-main { grid-template-columns: 1fr; } }
    @media (max-width: 992px) { .grid-cols-4 { grid-template-columns: repeat(2, 1fr) !important; } }
    
    @media (max-width: 768px) { 
        .dashboard-top { gap: 16px; margin-bottom: 16px; }
        .dashboard-main { gap: 16px; }
        .grid-cols-4 { grid-template-columns: repeat(2, 1fr) !important; gap: 12px !important; }
        
        .app-greeting-box { gap: 12px; padding: 0 0 4px 0; }
        .app-greeting-avatar { width: 44px; height: 44px; border-radius: 12px; }
        .app-greeting-title { font-size: 17px; }
        .app-greeting-sub { font-size: 12px; }
        
        .pro-stat-card { padding: 16px; gap: 10px; border-radius: 14px; }
        .pro-stat-header { font-size: 12px; }
        .pro-stat-icon-wrap { width: 28px; height: 28px; font-size: 15px; border-radius: 8px; }
        .pro-stat-value { font-size: 24px; }
        .pro-stat-value span { font-size: 12px !important; }
        
        .e-card-header { padding: 16px 16px 0 16px !important; font-size: 15px !important; }
        .dash-table-wrap { padding: 12px 16px 16px 16px !important; }
        #cloud-notice { padding: 16px !important; }
    }
    
    .dash-notice-card {
        border: 1px solid var(--border-color); box-shadow: none; border-radius: 14px; 
        background: var(--bg-container);
    }

    /* 核心数据卡片 — 简约 */
    .pro-stat-card { 
        position: relative; background: var(--bg-container); border: 1px solid var(--border-color); border-radius: 14px; 
        padding: 20px; overflow: hidden; display: flex; flex-direction: column; gap: 12px; 
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        box-shadow: none;
    }
    .pro-stat-card:hover { 
        border-color: rgba(0,0,0,0.08);
        box-shadow: 0 4px 16px rgba(0,0,0,0.04); 
    }
    
    .pro-stat-header { display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: 500; color: var(--text-secondary); z-index: 2; }
    .pro-stat-icon-wrap { 
        width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; 
        font-size: 16px; background: var(--bg-layout); color: var(--text-secondary);
    }
    .pro-stat-value { font-size: 32px; font-weight: 700; color: var(--text-primary); line-height: 1; font-family: 'Inter', system-ui, sans-serif; letter-spacing: -0.5px; z-index: 2; }
    
    

    /* 表格 */
    .dash-table-wrap { width: 100%; overflow-x: auto; }
    .dash-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
    .dash-table th { padding: 14px 16px; font-weight: 600; color: var(--text-secondary); border-bottom: 2px solid var(--bg-layout); }
    .dash-table td { padding: 14px 16px; border-bottom: 1px solid var(--border-color-input); color: var(--text-primary); }
    .dash-table tbody tr:hover td { background: var(--bg-layout); }
    .dash-table tbody tr:last-child td { border-bottom: none; }
    
    /* 自然撑开的空状态容器 */
    .dash-empty-state {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 16px; padding: 56px 20px; text-align: center;
    }
</style>
<?php
    $h = (int)date('H');
    if ($h < 6) { $greeting = '凌晨好，夜深了注意休息'; }
    elseif ($h < 9) { $greeting = '早上好'; }
    elseif ($h < 12) { $greeting = '上午好'; }
    elseif ($h < 14) { $greeting = '中午好'; }
    elseif ($h < 18) { $greeting = '下午好'; }
    else { $greeting = '晚上好'; }
?>

<div class="dashboard-top">
    <div class="app-greeting-box">
        <img src="<?= htmlspecialchars($conf_avatar) ?>" alt="Avatar" class="app-greeting-avatar">
        <div class="app-greeting-info">
            <h2 class="app-greeting-title">欢迎回来, <?= htmlspecialchars($currentAdminUser) ?></h2>
            <div class="app-greeting-sub"><?= $greeting ?></div>
        </div>
    </div>

    <div class="grid grid-cols-4" style="gap: 16px;">
        <div class="pro-stat-card">
            <div class="pro-stat-header">
                <span>总库存量</span>
                <div class="pro-stat-icon-wrap"><i class="ph ph-stack"></i></div>
            </div>
            <div class="pro-stat-value"><?= number_format($display_stats['total']) ?></div>
        </div>
        <div class="pro-stat-card">
            <div class="pro-stat-header">
                <span>活跃设备</span>
                <div class="pro-stat-icon-wrap"><i class="ph ph-device-mobile"></i></div>
            </div>
            <div class="pro-stat-value"><?= number_format($display_stats['active']) ?> <span style="font-size: 14px; color: var(--text-tertiary); font-weight: 500; margin-left: 4px;">/ <?= number_format($display_stats['online'] ?? 0) ?> 在线</span></div>
        </div>
        <div class="pro-stat-card">
            <div class="pro-stat-header">
                <span>接入应用</span>
                <div class="pro-stat-icon-wrap"><i class="ph ph-squares-four"></i></div>
            </div>
            <div class="pro-stat-value"><?= number_format($display_stats['apps']) ?></div>
        </div>
        <div class="pro-stat-card">
            <div class="pro-stat-header">
                <span>待售库存</span>
                <div class="pro-stat-icon-wrap"><i class="ph ph-ticket"></i></div>
            </div>
            <div class="pro-stat-value"><?= number_format($display_stats['unused']) ?></div>
        </div>
    </div>
</div>

<div class="dashboard-main">
    <!-- 下方左侧：宽屏内容区域 (占 2/3 宽度) -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="e-card" style="margin-bottom: 0; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border-radius: 16px;">
            <div class="e-card-header" style="font-weight: 700; border-bottom: none; padding: 20px 24px 0 24px; font-size: 16px;">
                <div style="display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-pulse" style="color: var(--color-success); font-size: 20px;"></i> 实时活跃设备</div>
            </div>
            
            <?php if (empty($activeDevices)): ?>
                <div class="dash-empty-state">
                    <div style="width:64px; height:64px; border-radius:18px; background:var(--bg-layout); display:flex; align-items:center; justify-content:center;">
                        <i class="ph-fill ph-ghost" style="font-size: 32px; color: var(--text-tertiary);"></i>
                    </div>
                    <span style="color: var(--text-secondary); font-size: 14px; font-weight:500;">当前宇宙非常安静，暂无活跃设备</span>
                </div>
            <?php else: ?>
                <div class="dash-table-wrap" style="padding: 12px 24px 24px 24px;">
                    <table class="dash-table">
                        <thead><tr><th>所属应用</th><th>卡密</th><th>到期时间</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($activeDevices, 0, 5) as $dev): ?>
                            <tr>
                                <td><span class="e-tag e-tag-blue" style="border-radius: 6px;"><?= htmlspecialchars($dev['app_name'] ?? '未分类') ?></span></td>
                                <td class="mono font-medium" style="color:var(--text-primary); font-size: 14px;"><?= htmlspecialchars($dev['card_code']) ?></td>
                                <td><span class="e-tag e-tag-green" style="border-radius: 6px;"><?= date('m-d H:i', strtotime($dev['expire_time'])) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="e-card" style="margin-bottom: 0; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border-radius: 16px;">
            <div class="e-card-header" style="font-weight: 700; display:flex; align-items:center; justify-content:space-between; border-bottom: none; padding: 20px 24px 0 24px; font-size: 16px;">
                <div style="display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-clock-counter-clockwise" style="color: var(--color-primary); font-size: 20px;"></i> 最新审计日志</div>
                <a href="?tab=logs" style="font-size: 13px; color: var(--color-primary); font-weight: 500; text-decoration: none; display:flex; align-items:center; gap:4px; padding: 6px 12px; background: var(--color-primary-bg); border-radius: 20px; transition: all 0.2s;">查看全部 <i class="ph ph-arrow-right"></i></a>
            </div>
            
            <?php if(empty($logs)): ?>
                <div class="dash-empty-state">
                    <div style="width:64px; height:64px; border-radius:18px; background:var(--bg-layout); display:flex; align-items:center; justify-content:center;">
                        <i class="ph-fill ph-wind" style="font-size: 32px; color: var(--text-tertiary);"></i>
                    </div>
                    <span style="color: var(--text-secondary); font-size: 14px; font-weight:500;">暂无日志记录</span>
                </div>
            <?php else: ?>
                <div class="dash-table-wrap" style="padding: 12px 24px 24px 24px;">
                    <table class="dash-table">
                        <thead><tr><th>发生时间</th><th>目标应用</th><th>动作记录</th><th>详情 / 参数</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($logs, 0, 6) as $log): ?>
                            <tr>
                                <td class="mono" style="font-size:12px; color:var(--text-tertiary);"><?= date('m-d H:i', strtotime($log['access_time'] ?? $log['log_time'] ?? 'now')) ?></td>
                                <td><span class="e-tag e-tag-blue" style="border-radius: 6px;"><?= htmlspecialchars($log['app_name'] ?? 'Sys') ?></span></td>
                                <td><?php $act=$log['result']??$log['action']??''; echo (strpos($act,'拦截')!==false||strpos($act,'封禁')!==false)?"<span style='color:var(--color-error); font-weight:600;'>{$act}</span>":"<span style='color:var(--text-primary); font-weight:500;'>{$act}</span>"; ?></td>
                                <td class="mono" style="font-size:12px; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($log['card_code'] ?? $log['details'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 下方右侧：侧边栏辅助功能区域 (占 1/3 宽度) -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- 系统公告挪到右侧 -->
        <div class="dash-notice-card e-card" style="margin-bottom: 0;">
            <div class="e-card-header" style="font-weight: 700; display:flex; align-items:center; gap:8px; border-bottom: none; padding: 20px 24px 0 24px; font-size: 16px;"><i class="ph-fill ph-megaphone" style="color: var(--color-warning); font-size: 20px;"></i> 系统公告</div>
            <div id="cloud-notice" style="padding: 16px 24px 24px 24px; white-space: pre-wrap; line-height: 1.8; color: var(--text-secondary); font-size: 14px; height: auto;">
                <div style="display:flex; align-items:center; justify-content:center; gap:8px; color:var(--text-tertiary); padding: 20px 0;"><i class="ph ph-spinner-gap" style="animation:spin 1s linear infinite;"></i> 正在同步云端信息...</div>
            </div>
        </div>

    </div>
</div>
