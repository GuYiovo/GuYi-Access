<?php
$dashboardData = $db->getDashboardData();
$logs = $db->getUsageLogs(20, 0); $activeDevices = $db->getActiveDevices(); $appList = $db->getApps();
$display_stats = [ 'total' => $dashboardData['stats']['total'], 'active' => $dashboardData['stats']['active'], 'apps' => count($appList), 'unused' => $dashboardData['stats']['unused'], 'online' => $dashboardData['stats']['online'] ];
?>
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
