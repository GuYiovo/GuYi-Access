<h2 class="page-title"><?= htmlspecialchars($currentTitle) ?></h2>
<p class="page-desc">系统基础环境参数与安全设置</p>

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
