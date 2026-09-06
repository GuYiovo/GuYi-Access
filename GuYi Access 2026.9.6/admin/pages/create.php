<?php $appList = $db->getApps(); ?>
<h2 class="page-title"><?= htmlspecialchars($currentTitle) ?></h2>
<p class="page-desc">多项目与软件隔离授权管理</p>

<div class="e-card" style="max-width: 800px;">
    <div class="e-card-header">批量生成授权卡密</div>
    <div class="e-card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="gen_cards" value="1">
            
            <div class="e-form-item" style="margin-bottom: 24px;">
                <label class="e-label">目标应用 <span style="color:var(--color-error)">*</span></label>
                <select name="app_id" class="e-select" required style="height: 44px; font-size: 14px;">
                    <option value="">请选择应用...</option>
                    <?php foreach($appList as $app): if($app['status']==0) continue; ?><option value="<?= $app['id'] ?>"><?= htmlspecialchars($app['app_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            
            <!-- 重点修改1：直接强制写死列间距为 32px -->
            <div class="grid grid-cols-2" style="column-gap: 32px; row-gap: 16px;">
                <div class="e-form-item" style="margin-bottom: 0;"><label class="e-label">生成数量</label><input type="number" name="num" class="e-input" value="10" min="1" max="1000"></div>
                <div class="e-form-item" style="margin-bottom: 0;">
                    <label class="e-label">卡密时长类型</label>
                    <select name="type" class="e-select" onchange="document.getElementById('c_time').style.display=(this.value==='custom'?'block':'none')">
                        <?php foreach (CARD_TYPES as $k => $v): ?><option value="<?= $k ?>"><?= $v['name'] ?> (<?= $v['duration'] >= 86400 ? ($v['duration']/86400).'天' : ($v['duration']/3600).'小时' ?>)</option><?php endforeach; ?>
                        <option value="custom" style="color:var(--color-primary);">自定义任意小时</option>
                    </select>
                </div>
                <!-- 自定义时长填充满一行 -->
                <div class="e-form-item" id="c_time" style="display:none; grid-column: 1 / -1; margin-bottom: 0; margin-top: 8px;"><label class="e-label" style="color:var(--color-primary);">输入自定义时长 (小时)</label><input type="number" name="custom_hours" class="e-input" value="24" min="1"></div>
            </div>
            
            <!-- 可折叠高级选项面板 -->
            <div class="mt-4" style="border: 1px solid var(--border-color-input); border-radius: var(--radius-base); overflow: hidden; transition: all 0.3s;">
                <div onclick="const c = document.getElementById('advOpts'); const i = document.getElementById('advIcon'); const t = document.getElementById('advText'); if(c.style.display==='none'){c.style.display='block';i.style.transform='rotate(180deg)';t.innerText='收起高级选项 (前缀、备注等)';}else{c.style.display='none';i.style.transform='rotate(0deg)';t.innerText='展开高级选项 (前缀、备注等)';}" 
                     style="padding: 14px 20px; background: var(--bg-layout); cursor: pointer; display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 500; color: var(--text-secondary); transition: background 0.2s; user-select: none;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="ph ph-sliders-horizontal" style="font-size: 16px; color: var(--color-primary);"></i> 
                        <span id="advText">展开高级选项 (前缀、备注等)</span>
                    </div>
                    <i class="ph ph-caret-down" id="advIcon" style="transition: transform 0.3s;"></i>
                </div>
                
                <div id="advOpts" style="display: none; padding: 24px 20px 28px 20px; border-top: 1px dashed var(--border-color-input); background: transparent;">
                    <!-- 重点修改2：同样强制写死列间距为 32px -->
                    <div class="grid grid-cols-2" style="column-gap: 32px;">
                        <div class="e-form-item" style="margin-bottom: 0;"><label class="e-label">卡密前缀 (可选)</label><input type="text" name="pre" class="e-input" placeholder="如 VIP-"></div>
                        <div class="e-form-item" style="margin-bottom: 0;"><label class="e-label">备注信息 (可选)</label><input type="text" name="note" class="e-input" placeholder="例如：代理A批次"></div>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-4 mt-8 pt-6" style="border-top:1px solid var(--border-color);">
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
