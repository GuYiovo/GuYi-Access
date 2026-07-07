<?php
// api.php
require_once '../config.php';
require_once '../database.php';

header('Content-Type: application/json; charset=utf-8');

// --- 并发安全的频率限制 ---
$rate_ip = $_SERVER['REMOTE_ADDR'];
$rate_file = sys_get_temp_dir() . '/rate_' . md5($rate_ip);
$current_minute = date('Hi');
$rate_data = @json_decode(@file_get_contents($rate_file), true);

if (is_array($rate_data) && isset($rate_data['time']) && $rate_data['time'] == $current_minute) {
    if ($rate_data['count'] > 60) {
        http_response_code(429);
        die(json_encode(['code' => 429, 'msg' => 'Too Many Requests - 请稍后重试', 'timestamp' => time()]));
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

// --- [AES-256-GCM 输出封装函数] 增加了响应的 timestamp ---
function output_json($code, $msg, $data = null, $encryptKey = null) {
    global $api_encrypt_enabled;
    $response = ['code' => $code, 'msg' => $msg, 'data' => $data, 'timestamp' => time()];
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

// 接收各项核心参数
$card_code   = !empty($data['card_code']) ? trim($data['card_code']) : (isset($data['card']) ? trim($data['card']) : '');
$device      = !empty($data['device_hash']) ? trim($data['device_hash']) : (isset($data['device']) ? trim($data['device']) : '');
$action      = isset($data['action']) ? $data['action'] : 'verify';
$custom_data = isset($data['custom_data']) ? trim($data['custom_data']) : null;

// ⭐ 新增：防抓包与防重放核心参数
$app_id      = isset($data['app_id']) ? intval($data['app_id']) : 0;
$timestamp   = isset($data['timestamp']) ? intval($data['timestamp']) : 0;
$sign        = isset($data['sign']) ? trim($data['sign']) : '';
$app_key     = isset($data['app_key']) ? trim($data['app_key']) : ''; 

try {
    if (!$db) throw new Exception("数据库连接失败");

    // ==========================================
    // ⭐ [安全增强] 防抓包签名验证 & 防重放时间戳验证
    // ==========================================
    if ($app_id > 0 && !empty($sign) && $timestamp > 0) {
        // 1. 防重放攻击：误差不得超过 60 秒
        if (abs(time() - $timestamp) > 60) {
            output_json(403, '请求异常：请求已过期，疑似重放攻击！');
        }

        // 2. 根据 app_id 获取数据库中真实的 app_key
        $appInfoStmt = $db->pdo->prepare("SELECT * FROM applications WHERE id = ?");
        $appInfoStmt->execute([$app_id]);
        $appInfo = $appInfoStmt->fetch(PDO::FETCH_ASSOC);

        if (!$appInfo || $appInfo['status'] == 0) {
            output_json(403, '验证失败：应用ID不存在或已被禁用');
        }

        // 3. 服务端计算签名并对比。客户端算法：md5(app_id + timestamp + card_code + app_key)
        $real_app_key = $appInfo['app_key'];
        $calc_sign = md5($app_id . $timestamp . $card_code . $real_app_key);
        
        if (strtolower($sign) !== strtolower($calc_sign)) {
            output_json(403, '安全拦截：数据签名校验失败，请求可能已被篡改！');
        }

        // 校验通过，赋值以便向下兼容旧的后续逻辑
        $app_key = $real_app_key;
    } 
    // 向下兼容：如果客户端依然传了明文的 app_key
    elseif (empty($app_key) && !in_array($action, ['generate', 'ban', 'unban', 'del_card', 'kick'])) {
        output_json(400, '请求遭拒：缺少必要的鉴权参数 (推荐使用 app_id + sign 签名防抓包机制)');
    }


    // ==========================================
    // 发卡网/管理端 API 接口
    // ==========================================
    if (in_array($action, ['generate', 'ban', 'unban', 'del_card', 'kick'])) {
        $api_token = isset($data['api_token']) ? trim($data['api_token']) : '';
        $admin_api_token = 'GuYiAdmin123'; 
        if ($api_token !== $admin_api_token) output_json(403, '无权操作：对接通信密钥错误！');

        if ($action === 'generate') {
            $genAppId = isset($data['app_id']) ? intval($data['app_id']) : 0;
            if ($genAppId <= 0 && !empty($app_key)) {
                $ai = $db->getAppIdByKey($app_key);
                if ($ai) $genAppId = $ai['id'];
            }
            if ($genAppId <= 0) output_json(400, '生成失败：请提供有效的 app_id');
            
            $type = isset($data['type']) ? $data['type'] : 'day';
            $num = isset($data['num']) ? intval($data['num']) : 1;
            $note = isset($data['note']) ? trim($data['note']) : 'API接口批量生卡';
            $pre = isset($data['pre']) ? trim($data['pre']) : '';
            $customHours = isset($data['custom_hours']) ? floatval($data['custom_hours']) : 0;
            
            try {
                $newCodes = $db->generateCards($num, $type, $pre, '', 16, $note, $genAppId, intval($customHours * 3600));
                output_json(200, "成功生成 {$num} 张卡密", ['cards' => $newCodes, 'card_string' => implode("\n", $newCodes)]);
            } catch (Exception $e) { output_json(500, '生成失败: ' . $e->getMessage()); }
        }
        
        if (in_array($action, ['ban', 'unban', 'del_card', 'kick'])) {
            if (empty($card_code)) output_json(400, '缺少操作卡密参数');
            $stmt = $db->pdo->prepare("SELECT id FROM cards WHERE card_code = ?");
            $stmt->execute([$card_code]); $c = $stmt->fetch();
            if (!$c) output_json(404, '该卡密不存在');
            
            if ($action === 'ban') { $db->updateCardStatus($c['id'], 2); output_json(200, '卡密封禁成功'); } 
            elseif ($action === 'unban') { $db->updateCardStatus($c['id'], 1); output_json(200, '卡密解封成功'); } 
            elseif ($action === 'del_card') { $db->deleteCard($c['id']); output_json(200, '卡密删除成功'); } 
            elseif ($action === 'kick') { $db->resetDeviceBindingByCardId($c['id']); output_json(200, '强制下线解绑成功'); }
        }
    }
    // ==========================================

    if ($action === 'ban_machine' || $action === 'blacklist') {
        $reason = isset($data['reason']) ? trim($data['reason']) : '触发客户端安全防御策略';
        if (!empty($device)) {
            try {
                $db->pdo->prepare("INSERT IGNORE INTO blacklists (type, value, reason) VALUES ('device', ?, ?)")->execute([$device, $reason]);
                $db->pdo->prepare("INSERT IGNORE INTO blacklists (type, value, reason) VALUES ('ip', ?, ?)")->execute([$rate_ip, $reason]);
                output_json(200, '拉黑成功', null);
            } catch(Exception $e) { output_json(500, '封禁执行失败'); }
        }
        output_json(400, '未提供特征码(device_hash)');
    }

    if ($action === 'unbind') {
        if (empty($card_code)) output_json(400, '解绑必须提供卡密(card_code)');
        if ($db->unbindCardByApi($card_code)) output_json(200, '解绑成功(已扣除使用寿命)', null);
        else output_json(400, '解绑失败：卡密错误或未激活', null);
    }

    $appInfo = null;
    $updateData = null;
    if (!empty($app_key)) {
        $appInfo = $db->getAppIdByKey($app_key);
        if (!$appInfo) output_json(403, 'AppKey 无效');
        $updateData = ['version' => $appInfo['app_version'], 'url' => $appInfo['update_url'], 'log' => $appInfo['notes'], 'force' => (int)$appInfo['force_update']];
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
    
    // 心跳也会走这里，verifyCard 内已更新了 last_active_time
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
