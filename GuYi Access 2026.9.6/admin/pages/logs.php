<?php $logs = $db->getUsageLogs(100, 0); ?>
<h2 class="page-title"><?= htmlspecialchars($currentTitle) ?></h2>
<p class="page-desc">客户端验证请求流日志审计</p>

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
