<h2 class="page-title"><?= htmlspecialchars($currentTitle) ?></h2>
<p class="page-desc">恶意请求与违规设备管理</p>

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
