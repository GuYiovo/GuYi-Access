<?php $appList = $db->getApps(); ?>
<h2 class="page-title"><?= htmlspecialchars($currentTitle) ?></h2>
<p class="page-desc">多项目与软件隔离授权管理</p>

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
                <!-- ⭐ 修复：准确指引至 /Verifyfile/api.php -->
                <?php $apiUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on'?"https":"http")."://".$_SERVER['HTTP_HOST'].rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])),'/')."/Verifyfile/api.php"; ?>
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
