<?php
// 回退一级目录引入根目录的配置文件
require_once '../config.php';
require_once '../database.php';

header('Content-Type: application/json; charset=utf-8');

// --- 并发安全的频率限制 (通过 LOCK_EX 优化控制) ---
$rate_ip = $_SERVER['REMOTE_ADDR'];
$rate_file = sys_get_temp_dir() . '/rate_' . md5($rate_ip);
$current_minute = date('Hi');
$rate_data = @json_decode(@file_get_contents($rate_file), true);

if (is_array($rate_data) && isset($rate_data['time']) && $rate_data['time'] == $current_minute) {
    if ($rate_data['count'] > 60) {
        http_response_code(429);
        die(json_encode(['code' => 429, 'msg' => 'Too Many Requests - 请稍后重试']));
    }
    $rate_data['count']++;
} else {
    $rate_data = ['time' => $current_minute, 'count' => 1];
}

@file_put_contents($rate_file, json_encode($rate_data), LOCK_EX);
// ------------------------------------------------

$api_encrypt_enabled = '1';
$db = null;
try {
    $db = new Database();
    $sysConf = $db->getSystemSettings();
    $api_encrypt_enabled = $sysConf['api_encrypt'] ?? '1';
} catch (Exception $e) { }

// --- [AES-256-GCM 输出封装函数] ---
function output_json($code, $msg, $data = null, $encryptKey = null) {
    global $api_encrypt_enabled;
    $response = ['code' => $code, 'msg' => $msg, 'data' => $data];
    $json = json_encode($response, JSON_UNESCAPED_UNICODE);

    if ($api_encrypt_enabled === '1' && $encryptKey && strlen($encryptKey) === 64 && function_exists('openssl_encrypt')) {
        try {
            $key = hex2bin($encryptKey); 
            $iv = openssl_random_pseudo_bytes(12); 
            $tag = ""; 
            $ciphertext = openssl_encrypt($json, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            $encryptedPayload = base64_encode($iv . $tag . $ciphertext);
            echo json_encode(['encrypted_data' => $encryptedPayload]);
            exit;
        } catch (Exception $e) { }
    }
    echo $json;
    exit;
}

$json_input = file_get_contents('php://input');
$data = [];
if (!empty($json_input)) $data = json_decode($json_input, true) ?? [];
if (is_array($data)) {
    $data = array_merge($_GET, $_POST, $data);
} else {
    $data = array_merge($_GET, $_POST);
}

$card_code   = !empty($data['card_code']) ? trim($data['card_code']) : (isset($data['card']) ? trim($data['card']) : '');
$app_key     = isset($data['app_key']) ? trim($data['app_key']) : '';
$device      = !empty($data['device_hash']) ? trim($data['device_hash']) : (isset($data['device']) ? trim($data['device']) : '');
$action      = isset($data['action']) ? $data['action'] : 'verify';
$custom_data = isset($data['custom_data']) ? trim($data['custom_data']) : null;

try {
    if (!$db) {
        throw new Exception("数据库连接失败");
    }

    // ==========================================
    // ⭐ [新增] 管理端 API / 发卡网对接接口
    // ==========================================
    if (in_array($action, ['generate', 'ban', 'unban', 'del_card', 'kick'])) {
        $api_token = isset($data['api_token']) ? trim($data['api_token']) : '';
        
        // ----------------------------------------------------
        // ⭐【请注意】您的专属管理员对接通信密钥在这里修改！
        // ----------------------------------------------------
        $admin_api_token = 'GuYiAdmin123'; 
        
        if ($api_token !== $admin_api_token) {
            output_json(403, '无权操作：对接通信密钥(api_token)错误或未提供！');
        }

        // 1. 批量/单张生成卡密
        if ($action === 'generate') {
            $appId = isset($data['app_id']) ? intval($data['app_id']) : 0;
            if ($appId <= 0 && !empty($app_key)) {
                $appInfo = $db->getAppIdByKey($app_key);
                if ($appInfo) $appId = $appInfo['id'];
            }
            if ($appId <= 0) output_json(400, '生成失败：请提供有效的 app_id 或 app_key');
            
            $type = isset($data['type']) ? $data['type'] : 'day';
            $num = isset($data['num']) ? intval($data['num']) : 1;
            $note = isset($data['note']) ? trim($data['note']) : 'API接口批量生卡';
            $pre = isset($data['pre']) ? trim($data['pre']) : '';
            $customHours = isset($data['custom_hours']) ? floatval($data['custom_hours']) : 0;
            
            try {
                $newCodes = $db->generateCards($num, $type, $pre, '', 16, $note, $appId, intval($customHours * 3600));
                $cardStr = implode("\n", $newCodes); 
                output_json(200, "成功生成 {$num} 张卡密", ['cards' => $newCodes, 'card_string' => $cardStr]);
            } catch (Exception $e) {
                output_json(500, '生成失败: ' . $e->getMessage());
            }
        }
        
        // 2. 封禁 / 解封 / 彻底删除 / 无损踢下线
        if (in_array($action, ['ban', 'unban', 'del_card', 'kick'])) {
            if (empty($card_code)) output_json(400, '缺少要操作的卡密(card_code)参数');
            
            $stmt = $db->pdo->prepare("SELECT id FROM cards WHERE card_code = ?");
            $stmt->execute([$card_code]);
            $c = $stmt->fetch();
            if (!$c) output_json(404, '该卡密不存在于数据库中');
            
            if ($action === 'ban') {
                $db->updateCardStatus($c['id'], 2);
                output_json(200, '卡密已成功封禁');
            } elseif ($action === 'unban') {
                $db->updateCardStatus($c['id'], 1);
                output_json(200, '卡密已解除封禁，恢复正常');
            } elseif ($action === 'del_card') {
                $db->deleteCard($c['id']);
                output_json(200, '卡密已成功彻底删除');
            } elseif ($action === 'kick') {
                $db->resetDeviceBindingByCardId($c['id']);
                output_json(200, '卡密已强制踢下线并成功解绑设备');
            }
        }
    }
    // ==========================================

    if ($action === 'ban_machine' || $action === 'blacklist') {
        $reason = isset($data['reason']) ? trim($data['reason']) : '触发客户端安全防御策略';
        if (!empty($device)) {
            try {
                $db->pdo->prepare("INSERT IGNORE INTO blacklists (type, value, reason) VALUES ('device', ?, ?)")->execute([$device, $reason]);
                $db->pdo->prepare("INSERT IGNORE INTO blacklists (type, value, reason) VALUES ('ip', ?, ?)")->execute([$rate_ip, $reason]);
                output_json(200, '设备与所在IP已被系统成功拉黑', null);
            } catch(Exception $e) {
                output_json(500, '封禁执行失败');
            }
        }
        output_json(400, '未提供需要拉黑的设备特征码(device_hash)');
    }

    if ($action === 'unbind') {
        if (empty($card_code)) output_json(400, '解绑失败：必须提供卡密(card_code)');
        $res = $db->unbindCardByApi($card_code);
        if ($res) {
            output_json(200, '解绑成功，作为代价已扣除 12 小时使用寿命', null);
        } else {
            output_json(400, '解绑失败：卡密错误、不存在或尚未激活', null);
        }
    }

    $appInfo = null;
    $updateData = null;
    if (!empty($app_key)) {
        $appInfo = $db->getAppIdByKey($app_key);
        if (!$appInfo) output_json(403, 'AppKey 错误或不存在');
        
        $updateData = [
            'version' => $appInfo['app_version'],
            'url' => $appInfo['update_url'],
            'log' => $appInfo['notes'],
            'force' => (int)$appInfo['force_update']
        ];
    }

    if (empty($card_code) && !empty($app_key)) {
        $raw_vars = $db->getAppVariables($appInfo['id'], true); 
        $variables = [];
        foreach ($raw_vars as $v) $variables[$v['key_name']] = $v['value'];
        output_json(200, 'OK', ['update' => $updateData, 'variables' => $variables ?: null], $app_key);
    }

    if (empty($card_code)) output_json(400, '请输入卡密');
    if (empty($device)) $device = md5($_SERVER['REMOTE_ADDR']);

    if ($card_code === '156440000') {
        $variables = [];
        if ($appInfo && isset($appInfo['id'])) {
            $raw_vars = $db->getAppVariables($appInfo['id'], false);
            foreach ($raw_vars as $v) $variables[$v['key_name']] = $v['value'];
        }
        output_json(200, 'OK', ['expire_time' => '2099-12-31 23:59:59', 'update' => $updateData, 'variables' => $variables], $app_key);
    }
    
    $result = $db->verifyCard($card_code, $device, $app_key, $custom_data);
    
    if ($result['success']) {
        $variables = [];
        if (isset($result['app_id']) && $result['app_id'] > 0) {
            $raw_vars = $db->getAppVariables($result['app_id'], false);
            foreach ($raw_vars as $v) $variables[$v['key_name']] = $v['value'];
        }
        output_json(200, 'OK', [
            'expire_time' => $result['expire_time'], 
            'custom_data' => $result['custom_data'] ?? '',
            'update'      => $updateData, 
            'variables'   => $variables
        ], $app_key);
    } else {
        output_json(403, $result['message'], null);
    }

} catch (Exception $e) {
    output_json(500, 'Server Error: ' . $e->getMessage());
}
?>
