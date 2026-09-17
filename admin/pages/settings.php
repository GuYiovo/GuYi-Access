<?php if (!defined('DB_INSTALLED_CHECK')) exit; ?>
<h2 class="page-title"><?= htmlspecialchars($currentTitle) ?></h2>
<p class="page-desc"><?= t('系统基础环境参数与安全设置') ?></p>

<style>
@media (max-width: 768px) {
    .settings-grid { grid-template-columns: 1fr !important; gap: 24px !important; }
}

/* 列表卡片基础样式 */
.setting-checkbox-card {
    display: flex; align-items: center; justify-content: space-between; gap: 16px; 
    cursor: pointer; padding: 16px; border-radius: 12px; background: var(--bg-layout); 
    border: 1px solid transparent; transition: all 0.2s ease;
}
.setting-checkbox-card:hover {
    border-color: rgba(59, 130, 246, 0.2);
    background: var(--color-primary-bg);
}

/* iOS 风格滑动开关 */
.ios-switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
.ios-switch input { opacity: 0; width: 0; height: 0; }
.ios-slider {
    position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
    background-color: var(--border-color-input); transition: .3s cubic-bezier(0.4, 0, 0.2, 1); 
    border-radius: 24px;
}
.ios-slider:before {
    position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
    background-color: white; transition: .3s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
}
.ios-switch input:checked + .ios-slider { background-color: var(--color-primary); }
.ios-switch input:checked + .ios-slider:before { transform: translateX(20px); }

/* 分段选项卡横向滚动适配移动端 */
.e-segmented-wrap {
    width: 100%; overflow-x: auto; margin-bottom: 24px;
    -webkit-overflow-scrolling: touch;
}
.e-segmented { display: inline-flex; min-width: max-content; }
</style>

<div class="e-segmented-wrap">
    <div class="e-segmented" id="setting_tabs">
        <div class="e-segmented-item active" onclick="switchSettingView('sys')"><?= t('系统与外观') ?></div>
        <div class="e-segmented-item" onclick="switchSettingView('admin')"><?= t('管理员资料') ?></div>
        <div class="e-segmented-item" onclick="switchSettingView('migrate')"><?= t('数据迁移') ?></div>
        <div class="e-segmented-item" onclick="switchSettingView('about')"><?= t('关于作者') ?></div>
    </div>
</div>

<!-- ================= Tab 1: 系统与外观 ================= -->
<div id="view_sys">
    <div class="e-card">
        <div class="e-card-header" style="font-weight: 600;"><i class="ph-fill ph-sliders-horizontal" style="color:var(--color-primary); margin-right:6px;"></i> <?= t('系统与外观') ?></div>
        <div class="e-card-body" style="padding: 24px;">
            <form method="POST" class="grid grid-cols-2 settings-grid" style="gap: 40px;">
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="update_settings" value="1">
                    
                    <div class="e-form-item" style="margin: 0;">
                        <label style="font-weight:600; font-size:14px; color:var(--text-secondary); display:flex; align-items:center; gap:6px; margin-bottom: 8px;">
                            <i class="ph ph-translate" style="font-size: 18px; color: var(--color-primary);"></i> <?= t('系统语言 / Language') ?>
                        </label>
                        <select class="e-select" style="height: 44px; font-size: 14px;" onchange="window.location.href='?tab=settings&lang='+this.value">
                            <option value="zh" <?= (isset($_COOKIE['sys_lang']) && $_COOKIE['sys_lang'] == 'en') ? '' : 'selected' ?>><?= t('简体中文 (Chinese)') ?></option>
                            <option value="en" <?= (isset($_COOKIE['sys_lang']) && $_COOKIE['sys_lang'] == 'en') ? 'selected' : '' ?>>English</option>
                        </select>
                        <div style="font-size:12px; color:var(--text-tertiary); margin-top:8px;">
                            <?= t('切换系统显示语言') ?>
                        </div>
                    </div>

                    <div style="height: 1px; background: var(--border-color-input); border-radius: 1px; margin: 4px 0; opacity: 0.5;"></div>

                    <label class="setting-checkbox-card">
                        <div style="flex: 1;">
                            <div style="font-weight:600; font-size:14px; color:var(--text-primary); margin-bottom: 4px;"><?= t('开启 API 接口通讯加密 (AES-256-GCM)') ?></div>
                            <div style="font-size:12px; color:var(--text-tertiary); line-height:1.5;"><?= t('强烈建议开启以防止被抓包破解。客户端需要对应解密逻辑。') ?></div>
                        </div>
                        <div class="ios-switch">
                            <input type="checkbox" name="api_encrypt" value="1" <?= $conf_api_encrypt=='1'?'checked':'' ?>>
                            <span class="ios-slider"></span>
                        </div>
                    </label>
                    
                    <label class="setting-checkbox-card">
                        <div style="flex: 1;">
                            <div style="font-weight:600; font-size:14px; color:var(--text-primary); margin-bottom: 4px;"><?= t('开启全局二次元背景图 (实验性)') ?></div>
                            <div style="font-size:12px; color:var(--text-tertiary); line-height:1.5;"><?= t('在右侧主区域显示淡淡的 ACG 背景图，并开启全站毛玻璃拟物风。') ?></div>
                        </div>
                        <div class="ios-switch">
                            <input type="checkbox" name="enable_bg_image" value="1" <?= $conf_enable_bg=='1'?'checked':'' ?>>
                            <span class="ios-slider"></span>
                        </div>
                    </label>
                </div>

                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div class="e-form-item" style="margin: 0;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:12px; align-items: center;">
                            <label style="font-weight:600; font-size:14px; color:var(--text-secondary);"><i class="ph ph-image" style="vertical-align: middle; margin-right: 4px;"></i> <?= t('背景图透明度') ?></label>
                            <span class="mono" id="bgImgOpVal" style="background: var(--color-primary-bg); color:var(--color-primary); font-size:12px; font-weight:700; padding: 4px 10px; border-radius: 12px;"><?= $conf_bg_img_opacity ?></span>
                        </div>
                        <input type="range" name="bg_img_opacity" min="0.05" max="1" step="0.05" value="<?= $conf_bg_img_opacity ?>" oninput="document.getElementById('bgImgOpVal').innerText=this.value">
                        <div style="font-size:12px; color:var(--text-tertiary); margin-top:8px;"><?= t('控制后面二次元壁纸的明显程度。') ?></div>
                    </div>
                    
                    <div class="e-form-item" style="margin: 0;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:12px; align-items: center;">
                            <label style="font-weight:600; font-size:14px; color:var(--text-secondary);"><i class="ph ph-drop" style="vertical-align: middle; margin-right: 4px;"></i> <?= t('面板白底透明度 (越低越透)') ?></label>
                            <span class="mono" id="cardOpVal" style="background: var(--color-primary-bg); color:var(--color-primary); font-size:12px; font-weight:700; padding: 4px 10px; border-radius: 12px;"><?= $conf_card_opacity ?></span>
                        </div>
                        <input type="range" name="card_opacity" min="0" max="1" step="0.05" value="<?= $conf_card_opacity ?>" oninput="document.getElementById('cardOpVal').innerText=this.value">
                        <div style="font-size:12px; color:var(--text-tertiary); margin-top:8px;"><?= t('控制毛玻璃卡片的透明度。建议保持在 0.4 ~ 0.8 之间以保证阅读性。') ?></div>
                    </div>
                    
                    <div style="margin-top: auto; padding-top: 20px;">
                        <button type="submit" data-no-ajax="true" class="e-btn e-btn-primary w-full" style="height:48px; font-size: 15px;"><i class="ph ph-floppy-disk"></i> <?= t('保存系统与外观修改') ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= Tab 2: 管理员资料 ================= -->
<div id="view_admin" style="display:none;">
    <div class="grid grid-cols-2 settings-grid" style="gap: 24px;">
        <div class="e-card" style="margin: 0;">
            <div class="e-card-header" style="font-weight: 600;"><i class="ph-fill ph-user-circle" style="color:var(--color-primary); margin-right:6px;"></i> <?= t('管理员资料设置') ?></div>
            <div class="e-card-body" style="padding: 24px;">
                <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 16px;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="update_profile" value="1">
                    <div class="e-form-item" style="margin: 0;">
                        <label class="e-label" style="font-size: 13px; margin-bottom: 8px;"><?= t('登录账号') ?></label>
                        <input type="text" name="admin_username" value="<?= htmlspecialchars($currentAdminUser) ?>" class="e-input" style="height: 44px; background: var(--bg-layout);" required>
                    </div>
                    <div class="e-form-item" style="margin: 0;">
                        <label class="e-label" style="font-size: 13px; margin-bottom: 8px;"><?= t('头像设置') ?></label>
                        <div style="display: flex; gap: 12px; margin-bottom: 12px; font-size: 13px; color: var(--text-secondary);">
                            <label style="cursor:pointer; display:flex; align-items:center; gap:6px;"><input type="radio" name="avatar_type" value="url" checked onchange="document.getElementById('set_avatar_url').style.display='block';document.getElementById('set_avatar_file').style.display='none';"> <?= t('图片链接') ?></label>
                            <label style="cursor:pointer; display:flex; align-items:center; gap:6px;"><input type="radio" name="avatar_type" value="upload" onchange="document.getElementById('set_avatar_url').style.display='none';document.getElementById('set_avatar_file').style.display='block';"> <?= t('本地上传') ?></label>
                        </div>
                        <div id="set_avatar_url">
                            <input type="text" name="admin_avatar_url" value="<?= htmlspecialchars($conf_avatar) ?>" class="e-input" style="height: 44px; background: var(--bg-layout);" placeholder="<?= t('输入图片链接') ?>">
                        </div>
                        <div id="set_avatar_file" style="display:none;">
                            <input type="file" name="admin_avatar_file" accept="image/*" class="e-input" style="padding: 9px 12px; background: var(--bg-layout); height: 44px;">
                        </div>
                    </div>
                    <button type="submit" data-no-ajax="true" class="e-btn e-btn-primary" style="height:44px; font-size: 14px; margin-top: 8px;"><i class="ph ph-user-circle-plus"></i> <?= t('保存资料修改') ?></button>
                </form>
            </div>
        </div>

        <div class="e-card" style="margin: 0;">
            <div class="e-card-header" style="font-weight: 600;"><i class="ph-fill ph-lock-key" style="color:var(--color-error); margin-right:6px;"></i> <?= t('修改管理员密码') ?></div>
            <div class="e-card-body" style="padding: 24px;">
                <form method="POST" style="display: flex; flex-direction: column; gap: 16px;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="update_pwd" value="1">
                    <div class="e-form-item" style="margin: 0;">
                        <label class="e-label" style="font-size: 13px; margin-bottom: 8px;"><?= t('新密码') ?></label>
                        <input type="password" name="new_pwd" class="e-input" style="height: 44px; background: var(--bg-layout);" required placeholder="<?= t('输入新密码') ?>">
                    </div>
                    <div class="e-form-item" style="margin: 0;">
                        <label class="e-label" style="font-size: 13px; margin-bottom: 8px;"><?= t('再次确认') ?></label>
                        <input type="password" name="confirm_pwd" class="e-input" style="height: 44px; background: var(--bg-layout);" required placeholder="<?= t('再次输入新密码') ?>">
                    </div>
                    <button type="submit" class="e-btn e-btn-danger" style="height:44px; font-size: 14px; margin-top: 8px;"><i class="ph ph-warning-circle"></i> <?= t('强制修改并退出') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ================= Tab 3: 数据迁移 ================= -->
<div id="view_migrate" style="display:none;">
    <div class="e-card">
        <div class="e-card-header" style="font-weight: 600;"><i class="ph-fill ph-database" style="color:#8b5cf6; margin-right:6px;"></i> <?= t('系统数据迁移工具') ?></div>
        <div class="e-card-body grid grid-cols-2 settings-grid" style="gap: 24px; padding: 24px;">
            <div style="background:var(--bg-layout); border:1px solid var(--border-color); padding: 24px; border-radius: 16px; display: flex; flex-direction: column; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.04)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: var(--color-primary-bg); color: var(--color-primary); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        <i class="ph-fill ph-export"></i>
                    </div>
                    <h4 style="margin:0; font-weight:700; font-size:16px; color:var(--text-primary);"><?= t('导出当前数据') ?></h4>
                </div>
                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:24px; line-height:1.6; flex-grow: 1;"><?= t('将当前所有应用、卡密、变量打包下载备份，防患于未然。建议定期备份并妥善保管您的 JSON 文件。') ?></p>
                <a href="?action=export_system" class="e-btn e-btn-default w-full" style="justify-content:center; height:44px; font-size: 14px; font-weight: 500; border-color: var(--color-primary); color: var(--color-primary); background: transparent;"><i class="ph ph-download-simple"></i> <?= t('一键下载 JSON 备份') ?></a>
            </div>
            
            <div style="background: rgba(239, 68, 68, 0.02); border:1px dashed rgba(239, 68, 68, 0.3); padding: 24px; border-radius: 16px; display: flex; flex-direction: column; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(239,68,68,0.06)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(239, 68, 68, 0.1); color: var(--color-error); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        <i class="ph-fill ph-warning-octagon"></i>
                    </div>
                    <h4 style="margin:0; font-weight:700; font-size:16px; color:var(--color-error);"><?= t('导入恢复数据') ?> <span style="font-size:12px; font-weight:normal; opacity: 0.8; margin-left: 4px;"><?= t('(危险: 将覆盖当前一切)') ?></span></h4>
                </div>
                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:24px; line-height:1.6; flex-grow: 1;"><?= t('上传备份 JSON 文件，彻底恢复历史数据，请谨慎操作。') ?></p>
                <form method="POST" enctype="multipart/form-data" data-no-ajax="true" style="display: flex; gap: 8px;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="import_system" value="1">
                    <input type="file" name="backup_file" accept=".json" required class="e-input" style="padding:7px 10px; flex:1; background:var(--bg-container); height: 44px; border-color: rgba(239, 68, 68, 0.2);">
                    <button type="submit" onclick="return confirm('<?= t('警告：该操作将彻底清空当前系统，恢复不可逆！') ?>')" class="e-btn e-btn-danger" style="height:44px; padding:0 24px; font-weight: 500; flex-shrink: 0;"><i class="ph ph-upload-simple"></i> <?= t('执行恢复') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ================= Tab 4: 关于作者 ================= -->
<div id="view_about" style="display:none;">
    <?php
    $a_q = base64_decode('MTU2N'.'DQwMD'.'Aw');
    $a_u = base64_decode('aHR0cHM6Ly9'.'4bi0tanBy'.'MDcxZS5'.'0b3Av');
    $a_a = base64_decode('aHR0cHM6Ly9'.'xMi5xbG9nby5j'.'bi9oZWFkaW1n'.'X2RsP2RzdF9'.'1aW49MTU2NDQw'.'MDAwJnNwZWM'.'9NjQw');
    ?>
    <div class="e-card" style="max-width: 600px; margin: 20px auto; text-align: center; padding: 48px 24px; border:none; background:transparent; box-shadow:none;">
        <img src="<?= htmlspecialchars($a_a) ?>" style="width:100px; height:100px; border-radius:50%; margin-bottom:24px; box-shadow:0 12px 24px rgba(0,0,0,0.12); transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05) rotate(5deg)'" onmouseout="this.style.transform='scale(1) rotate(0)'">
        <h2 style="font-size:28px; font-weight:700; margin:0 0 8px 0; color:var(--text-primary); letter-spacing:-0.5px;"><?= base64_decode('R3VZaSBBY2Nlc3MgUHJv') ?></h2>
        <p style="color:var(--text-secondary); margin:0 0 32px 0; font-size:15px;"><?= t('工业级高可用 · 多应用验证分发架构') ?></p>
        
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
</div>

<script>
function switchSettingView(v) {
    document.querySelectorAll('#setting_tabs .e-segmented-item').forEach(el=>el.classList.remove('active'));
    event.target.classList.add('active');
    document.getElementById('view_sys').style.display = v==='sys' ? 'block' : 'none';
    document.getElementById('view_admin').style.display = v==='admin' ? 'block' : 'none';
    document.getElementById('view_migrate').style.display = v==='migrate' ? 'block' : 'none';
    document.getElementById('view_about').style.display = v==='about' ? 'block' : 'none';
}
</script>
