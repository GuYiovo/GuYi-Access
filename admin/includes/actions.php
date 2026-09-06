<?php
if (isset($_GET['action']) && $_GET['action'] === 'export_system') {
    ini_set('memory_limit', '512M'); set_time_limit(240);
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="System_Migrate_'.date('YmdHis').'.json"');
    $out = fopen('php://output', 'w'); $db->exportAllDataStream($out); fclose($out); exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_export'])) {
    verifyCSRF(); $ids = $_POST['ids'] ?? [];
    if (empty($ids)) { echo "<script>alert('请先勾选需要导出的卡密'); history.back();</script>"; exit; }
    $data = $db->getCardsByIds($ids);
    header('Content-Type: text/plain'); header('Content-Disposition: attachment; filename="cards_export_'.date('YmdHis').'.txt"');
    foreach ($data as $row) { echo "{$row['card_code']}\r\n"; } exit;
}

$msg = ''; $errorMsg = ''; $generatedCardsText = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();
    try {
        if (isset($_POST['create_app'])) {
            $appName = trim($_POST['app_name']); if (empty($appName)) throw new Exception("应用名称不能为空");
            $db->createApp(htmlspecialchars($appName), htmlspecialchars($_POST['app_version'] ?? ''), htmlspecialchars($_POST['app_notes']));
            $msg = "应用创建成功";
        } elseif (isset($_POST['toggle_app'])) { $db->toggleAppStatus(intval($_POST['app_id'])); $msg = "状态已更新";
        } elseif (isset($_POST['delete_app'])) { $db->deleteApp(intval($_POST['app_id'])); $msg = "应用已删除";
        } elseif (isset($_POST['edit_app'])) { 
            $appName = trim($_POST['app_name']); if (empty($appName)) throw new Exception("应用名称不能为空");
            $db->updateApp(intval($_POST['app_id']), htmlspecialchars($appName), htmlspecialchars($_POST['app_version'] ?? ''), htmlspecialchars($_POST['app_notes']), htmlspecialchars($_POST['update_url'] ?? ''), isset($_POST['force_update']) ? 1 : 0);
            $msg = "信息已更新";
        } elseif (isset($_POST['reset_secret'])) {
            $db->resetAppSecret(intval($_POST['app_id']));
            $msg = "通讯加密密钥 (API Secret) 已重置";
        } elseif (isset($_POST['add_var'])) {
            $varKey = trim($_POST['var_key']); if (empty($varKey)) throw new Exception("变量名不能为空");
            $db->addAppVariable(intval($_POST['var_app_id']), htmlspecialchars($varKey), htmlspecialchars(trim($_POST['var_value'])), isset($_POST['var_public']) ? 1 : 0);
            $msg = "变量添加成功";
        } elseif (isset($_POST['edit_var'])) {
            $varKey = trim($_POST['var_key']); if (empty($varKey)) throw new Exception("变量名不能为空");
            $db->updateAppVariable(intval($_POST['var_id']), htmlspecialchars($varKey), htmlspecialchars(trim($_POST['var_value'])), isset($_POST['var_public']) ? 1 : 0);
            $msg = "变量更新成功";
        } elseif (isset($_POST['del_var'])) { $db->deleteAppVariable(intval($_POST['var_id'])); $msg = "变量已删除";
        } elseif (isset($_POST['batch_delete'])) { $count = $db->batchDeleteCards($_POST['ids'] ?? []); $msg = "已删除 {$count} 张";
        } elseif (isset($_POST['batch_unbind'])) { $count = $db->batchUnbindCards($_POST['ids'] ?? []); $msg = "已解绑 {$count} 个";
        } elseif (isset($_POST['batch_add_time'])) { $hours = floatval($_POST['add_hours']); $count = $db->batchAddTime($_POST['ids'] ?? [], $hours); $msg = "已加时 {$count} 张";
        } elseif (isset($_POST['batch_sub_time'])) { $hours = floatval($_POST['sub_hours']); $count = $db->batchSubTime($_POST['ids'] ?? [], $hours); $msg = "已扣时 {$count} 张";
        } elseif (isset($_POST['global_compensate'])) {
            $hours = floatval($_POST['comp_hours']); $targetApp = isset($_GET['app_id']) && $_GET['app_id'] !== '' ? intval($_GET['app_id']) : null;
            $db->globalCompensate($hours, $targetApp); $msg = "已统一补偿 {$hours} 小时";
        } elseif (isset($_POST['gen_cards'])) {
            $type = $_POST['type']; $customHours = ($type === 'custom') ? floatval($_POST['custom_hours']) : 0;
            if ($type === 'custom' && $customHours <= 0) throw new Exception("时间需大于0");
            $newCodes = $db->generateCards($_POST['num'], $type, $_POST['pre'], '',16, htmlspecialchars($_POST['note']), intval($_POST['app_id']), intval($customHours * 3600));
            $generatedCardsText = implode("\r\n", $newCodes);
            if (isset($_POST['auto_export']) && $_POST['auto_export'] == '1' && !empty($newCodes)) {
                if (ob_get_level()) ob_end_clean(); header('Content-Type: text/plain'); header('Content-Disposition: attachment; filename="new_cards_'.date('YmdHis').'.txt"');
                echo $generatedCardsText; exit;
            }
            $msg = "成功生成 {$_POST['num']} 张";
        } elseif (isset($_POST['add_blacklist'])) {
            $bl_value = preg_replace('/\s+/', '', trim($_POST['bl_value'])); if (empty($bl_value)) throw new Exception("目标不能为空");
            $db->pdo->prepare("INSERT IGNORE INTO blacklists (type, value, reason) VALUES (?, ?, ?)")->execute([$_POST['bl_type'], $bl_value, htmlspecialchars($_POST['bl_reason']??'')]);
            $msg = "记录已添加";
        } elseif (isset($_POST['del_blacklist'])) { $db->pdo->prepare("DELETE FROM blacklists WHERE id=?")->execute([intval($_POST['id'])]); $msg = "记录已删除";
        } elseif (isset($_POST['del_card'])) { $db->deleteCard(intval($_POST['id'])); $msg = "已删除";
        } elseif (isset($_POST['unbind_card'])) { $db->resetDeviceBindingByCardId(intval($_POST['id'])); $msg = "已解绑";
        } elseif (isset($_POST['update_pwd'])) {
            $pwd1 = $_POST['new_pwd'] ?? ''; $pwd2 = $_POST['confirm_pwd'] ?? '';
            if (empty($pwd1)) throw new Exception("密码不能为空"); if ($pwd1 !== $pwd2) throw new Exception("两次密码不一致");
            $db->updateAdminPassword($pwd1); setcookie('admin_trust', '', time() - 3600, '/'); session_destroy(); header('Location: ../login/login.php'); exit;
        } elseif (isset($_POST['update_settings'])) {
            $db->saveSystemSettings([
                'site_title' => $conf_site_title, 'favicon' => $conf_favicon, 'admin_avatar' => $conf_avatar, 
                'api_encrypt' => isset($_POST['api_encrypt']) ? '1' : '0', 'enable_bg_image' => isset($_POST['enable_bg_image']) ? '1' : '0',
                'bg_img_opacity' => floatval($_POST['bg_img_opacity'] ?? 0.2), 'card_opacity' => floatval($_POST['card_opacity'] ?? 0.7)
            ]);
            $msg = "配置已保存"; echo "<script>alert('$msg');location.href='cards.php?tab=settings';</script>"; exit;
        } elseif (isset($_POST['ban_card'])) { $db->updateCardStatus(intval($_POST['id']), 2); $msg = "已封禁";
        } elseif (isset($_POST['unban_card'])) { $db->updateCardStatus(intval($_POST['id']), 1); $msg = "已解封";
        } elseif (isset($_POST['clean_expired'])) { $count = $db->cleanupExpiredCards(); $msg = "已清理 {$count} 张";
        } elseif (isset($_POST['import_system'])) {
            ini_set('memory_limit', '512M'); set_time_limit(240);
            if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] == UPLOAD_ERR_OK) {
                $content = file_get_contents($_FILES['backup_file']['tmp_name']); $data = json_decode($content, true);
                if (is_array($data)) { $db->importAllData($data); echo "<script>alert('数据已恢复，需重新登录');location.href='cards.php?logout=1';</script>"; exit; } else throw new Exception("无效的格式");
            } else throw new Exception("文件上传失败");
        }
    } catch(Exception $e) { $errorMsg = $e->getMessage(); }
}
?>
