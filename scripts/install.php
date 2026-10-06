<?php
error_reporting(0);

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$msg = '';

$is_mobile = preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|samsung|scp|wap|windows ce;iemobile|xhtml\\+xml)/i", $_SERVER["HTTP_USER_AGENT"] ?? '');
$video_url = $is_mobile ? 'https://videocloud.xn--tlq395o.top/wallpapermodCloud_4.mp4' : 'https://videocloud.xn--tlq395o.top/wallpaperPCCloud_4.mp4';

function check_env() {
    $r = [];
    $v = phpversion();
    $r[] = ['name'=>'PHP 版本','need'=>'≥ 7.2','val'=>$v,'ok'=>version_compare($v,'7.2.0','>=')];
    $r[] = ['name'=>'PDO MySQL','need'=>'必需','val'=>extension_loaded('pdo_mysql')?'已支持':'缺失','ok'=>extension_loaded('pdo_mysql')];
    $r[] = ['name'=>'目录权限','need'=>'可写','val'=>(is_writable(dirname(__DIR__).'/config/config.php')||is_writable(dirname(__DIR__).'/config'))?'正常':'受限','ok'=>is_writable(dirname(__DIR__).'/config/config.php')||is_writable(dirname(__DIR__).'/config')];
    $r[] = ['name'=>'JSON 扩展','need'=>'必需','val'=>function_exists('json_encode')?'已支持':'缺失','ok'=>function_exists('json_encode')];
    return $r;
}

// 接收全局保留的数据库参数
$db_host = trim($_POST['db_host'] ?? '127.0.0.1');
$db_name = trim($_POST['db_name'] ?? '');
$db_user = trim($_POST['db_user'] ?? '');
$db_pass = trim($_POST['db_pass'] ?? '');
$db_port = trim($_POST['db_port'] ?? '3306');

// 第 3 步：验证数据库连接，通过后才显示管理员设置页
if ($step == 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($db_host) || empty($db_name) || empty($db_user)) {
        $msg = '请完整填写数据库连接信息'; $step = 2;
    } else {
        try {
            $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
            new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
        } catch (PDOException $e) {
            $msg = "数据库连接失败：" . $e->getMessage(); $step = 2;
        }
    }
}

// 第 4 步：执行最终安装写入
if ($step == 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_username = trim($_POST['admin_username'] ?? '');
    $admin_pwd = $_POST['admin_password'] ?? '';
    $admin_pwd2 = $_POST['admin_password_confirm'] ?? '';

    if (empty($admin_username)) {
        $msg = '管理员账号不能为空'; $step = 3;
    } elseif (mb_strlen($admin_pwd) < 6) {
        $msg = '管理员密码长度不能少于 6 位'; $step = 3;
    } elseif ($admin_pwd !== $admin_pwd2) {
        $msg = '两次输入的管理员密码不一致'; $step = 3;
    } else {
        try {
            $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
            new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $secret = 'SYS_' . md5(uniqid('', true));
            $sh=addslashes($db_host);$sn=addslashes($db_name);$su=addslashes($db_user);$sp=addslashes($db_pass);$so=addslashes($db_port);
            
            $cfg = "<?php\n"
                 . "define('DB_INSTALLED_CHECK', true);\n"
                 . "header(\"X-Frame-Options: SAMEORIGIN\");\n"
                 . "header(\"X-XSS-Protection: 1; mode=block\");\n"
                 . "header(\"X-Content-Type-Options: nosniff\");\n"
                 . "define('SYS_SECRET', '$secret');\n"
                 . "define('DB_HOST', '$sh');\n"
                 . "define('DB_NAME', '$sn');\n"
                 . "define('DB_USER', '$su');\n"
                 . "define('DB_PASS', '$sp');\n"
                 . "define('DB_PORT', '$so');\n"
                 . "error_reporting(0);\n"
                 . "ini_set('display_errors', 0);\n\n"
                 . "ini_set('session.gc_maxlifetime', 315360000);\n"
                 . "session_set_cookie_params(315360000);\n\n"
                 . "if (!defined('DB_INSTALLED_CHECK')) {\n"
                 . "    \$current_script = basename(\$_SERVER['SCRIPT_NAME']);\n"
                 . "    if (\$current_script !== 'install.php' && file_exists(__DIR__ . '/install.php')) {\n"
                 . "        \$is_sub_dir = (strpos(\$_SERVER['REQUEST_URI'], '/admin/') !== false || strpos(\$_SERVER['REQUEST_URI'], '/login/') !== false);\n"
                 . "        header('Location: ' . (\$is_sub_dir ? '../install.php' : 'install.php'));\n"
                 . "        exit();\n"
                 . "    }\n"
                 . "}\n\n"
                 . "define('CARD_TYPES', [\n"
                 . "    'hour'=>['name'=>'小时卡','duration'=>3600],\n"
                 . "    'day'=>['name'=>'天卡','duration'=>86400],\n"
                 . "    'week'=>['name'=>'周卡','duration'=>604800],\n"
                 . "    'month'=>['name'=>'月卡','duration'=>2592000],\n"
                 . "    'season'=>['name'=>'季卡','duration'=>7776000],\n"
                 . "    'year'=>['name'=>'年卡','duration'=>31536000],\n"
                 . "]);\n"
                 . "?>";

            if (file_put_contents(dirname(__DIR__) . '/config/config.php', $cfg)) {
                
                // 处理头像（本地上传或直链）
                $avatar_val = '';
                if (isset($_POST['avatar_type']) && $_POST['avatar_type'] === 'upload' && isset($_FILES['admin_avatar_file']) && $_FILES['admin_avatar_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/assets/ResourceStorage/';
                    if (!is_dir($upload_dir)) {
                        @mkdir($upload_dir, 0755, true);
                    }
                    $ext = strtolower(pathinfo($_FILES['admin_avatar_file']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                        $filename = 'avatar_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                        if (move_uploaded_file($_FILES['admin_avatar_file']['tmp_name'], $upload_dir . $filename)) {
                            $avatar_val = '../assets/ResourceStorage/' . $filename;
                        }
                    }
                }
                if (empty($avatar_val)) { $avatar_val = trim($_POST['admin_avatar_url'] ?? ''); }
                if (empty($avatar_val)) { $avatar_val = 'https://q1.qlogo.cn/g?b=qq&nk=156440000&s=640'; }

                require_once dirname(__DIR__) . '/config/config.php';
                require_once dirname(__DIR__) . '/app/Infrastructure/Database/Database.php';
                
                $db = new Database();
                $db->updateAdminUsername($admin_username);
                $db->updateAdminPassword($admin_pwd);
                $db->saveSystemSettings(['admin_avatar' => $avatar_val]);
                
                $deleted = @unlink(__FILE__);
                $step = 4;
            } else { $msg = '配置文件写入失败，请检查目录权限'; $step = 3; }
        } catch (PDOException $e) {
            $msg = "安装过程中出现数据库错误：" . $e->getMessage(); $step = 3;
        }
    }
}

$env = check_env();
$env_ok = !in_array(false, array_column($env, 'ok'));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>系统部署与安装</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            var(--glass-bg): rgba(255, 255, 255, 0.02);
            var(--glass-border): rgba(255, 255, 255, 0.08);
            --text-main: rgba(255, 255, 255, 0.95);
            --text-dim: rgba(255, 255, 255, 0.5);
            --success: #34d399;
            --error: #f87171;
            --accent: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "PingFang SC", "Microsoft YaHei", sans-serif;
            background: #000;
            color: var(--text-main);
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            padding: 20px 0;
            -webkit-font-smoothing: antialiased;
        }

        .bg-layer {
            position: fixed; inset: 0; width: 100vw; height: 100vh; object-fit: cover;
            z-index: -1; transform: scale(1.05); 
        }
        
        .bg-overlay {
            position: fixed; inset: 0; z-index: -1;
            background: radial-gradient(circle at center, rgba(0,0,0,0) 0%, rgba(0,0,0,0.15) 100%);
        }

        /* iOS 同款进出场弹性动画 */
        .glass-card {
            width: 420px;
            max-width: 90%;
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(8px) saturate(120%);
            -webkit-backdrop-filter: blur(8px) saturate(120%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 28px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            padding: 36px;
            position: relative;
            z-index: 10;
            animation: iosEntry 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            transform-origin: center;
        }
        .glass-card.leave {
            animation: iosLeave 0.4s cubic-bezier(0.4, 0, 1, 1) forwards;
        }

        @keyframes iosEntry {
            0% { opacity: 0; transform: scale(0.92) translateY(15px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes iosLeave {
            0% { opacity: 1; transform: scale(1) translateY(0); }
            100% { opacity: 0; transform: scale(0.96) translateY(-15px); }
        }

        header { text-align: center; margin-bottom: 28px; }
        .logo { font-size: 22px; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 8px; }
        .step-indicator { display: flex; justify-content: center; gap: 10px; margin-top: 14px; }
        .dot {
            width: 5px; height: 5px; border-radius: 50%;
            background: rgba(255,255,255,0.2);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .dot.active { background: var(--accent); transform: scale(1.6); box-shadow: 0 0 8px rgba(255,255,255,0.4); }
        .dot.done { background: rgba(255,255,255,0.6); }

        h1 { font-size: 18px; font-weight: 600; text-align: center; margin-bottom: 6px; letter-spacing: 0.5px; }
        .subtitle { font-size: 12px; color: var(--text-dim); text-align: center; margin-bottom: 24px; }

        .form-group { margin-bottom: 14px; position: relative; }
        .input-row { display: flex; gap: 10px; }
        
        input[type="text"], input[type="password"] {
            width: 100%;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 13px 16px;
            color: #fff;
            font-size: 14px;
            outline: none;
            transition: all 0.25s ease;
            font-family: inherit;
        }
        input:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.05);
        }
        input::placeholder { color: rgba(255, 255, 255, 0.25); font-size: 13px; }

        .toggle-password {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3); cursor: pointer; transition: color 0.3s; font-size: 14px;
        }
        .toggle-password:hover { color: rgba(255, 255, 255, 0.8); }
        .has-eye { padding-right: 40px; }

        .env-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 16px; background: rgba(255, 255, 255, 0.02);
            border-radius: 14px; margin-bottom: 8px; border: 1px solid rgba(255, 255, 255, 0.04);
            transition: background 0.2s;
        }
        .env-item:hover { background: rgba(255, 255, 255, 0.04); }
        .env-name { font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.8); }
        .env-status { font-size: 12px; font-weight: 500; display: flex; align-items: center; gap: 6px; }
        .status-ok { color: var(--success); }
        .status-no { color: var(--error); }

        .btn {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
            color: #fff; border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 14px; padding: 14px; font-size: 14px; font-weight: 600;
            letter-spacing: 1px; cursor: pointer;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
            margin-top: 18px; display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn:hover { 
            background: rgba(255, 255, 255, 0.15); border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); 
        }
        .btn:active { transform: translateY(1px); }
        .btn:disabled { background: rgba(255,255,255,0.03); border-color: transparent; color: rgba(255,255,255,0.2); cursor: not-allowed; transform: none; box-shadow: none; }
        
        .btn-alt {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.7);
            margin-top: 10px;
            font-size: 13px;
        }
        .btn-alt:hover { background: rgba(255, 255, 255, 0.08); color: rgba(255, 255, 255, 0.9); }

        .alert {
            background: rgba(248, 113, 113, 0.08); border: 1px solid rgba(248, 113, 113, 0.15);
            color: rgba(248, 113, 113, 0.9); padding: 10px 12px; border-radius: 10px;
            font-size: 12px; text-align: center; margin-bottom: 20px;
        }

        .success-icon {
            width: 56px; height: 56px; background: rgba(52, 211, 153, 0.1);
            border: 1px solid rgba(52, 211, 153, 0.2); color: var(--success);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 24px; margin: 0 auto 18px;
        }
        .credential-box {
            background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 14px; padding: 16px; margin-top: 16px; font-size: 12px; line-height: 1.8; color: rgba(255,255,255,0.7);
        }
        .credential-box i { width: 16px; text-align: center; margin-right: 6px; opacity: 0.7; }
        .credential-box code { color: #fff; background: rgba(255,255,255,0.08); padding: 3px 6px; border-radius: 4px; font-family: monospace; letter-spacing: 0.5px; }

        @media (max-width: 480px) {
            .glass-card { padding: 30px 20px; width: 92%; border-radius: 24px; }
            .input-row { flex-direction: column; gap: 14px; }
        }
    </style>
</head>
<body>

<video class="bg-layer" src="<?php echo $video_url; ?>" autoplay loop muted playsinline></video>
<div class="bg-overlay"></div>

<div class="glass-card" id="main_card">
    <header>
        <div class="logo">GuYi System</div>
        <div class="step-indicator">
            <div class="dot <?php echo $step==1?'active':($step>1?'done':''); ?>"></div>
            <div class="dot <?php echo $step==2?'active':($step>2?'done':''); ?>"></div>
            <div class="dot <?php echo $step==3?'active':($step>3?'done':''); ?>"></div>
            <div class="dot <?php echo $step==4?'active':''; ?>"></div>
        </div>
    </header>

    <?php if($msg): ?>
        <div class="alert"><i class="fas fa-circle-exclamation"></i> <?php echo $msg; ?></div>
    <?php endif; ?>

    <?php if($step == 1): ?>
        <!-- =================== 第一页：环境检测 =================== -->
        <h1>环境检测</h1>
        <p class="subtitle">检查服务器运行环境是否就绪</p>
        
        <div class="env-list">
            <?php foreach($env as $it): ?>
            <div class="env-item">
                <span class="env-name"><?php echo $it['name']; ?></span>
                <span class="env-status <?php echo $it['ok']?'status-ok':'status-no'; ?>">
                    <?php echo $it['ok'] ? '通过 <i class="fas fa-check"></i>' : '异常 <i class="fas fa-times"></i>'; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>

        <button class="btn" <?php echo $env_ok?'':'disabled'; ?> onclick="smoothNavigate('?step=2')">下一步</button>

    <?php elseif($step == 2): ?>
        <!-- =================== 第二页：数据库配置 =================== -->
        <h1>配置数据库</h1>
        <p class="subtitle">填写您的 MySQL 数据库连接信息</p>
        
        <form class="smooth-form" method="POST" action="?step=3">
            <div class="form-group">
                <input type="text" name="db_host" placeholder="数据库主机 (例: 127.0.0.1)" value="<?php echo htmlspecialchars($db_host); ?>">
            </div>
            
            <div class="input-row">
                <div class="form-group" style="flex: 2;">
                    <input type="text" name="db_name" placeholder="数据库名称" value="<?php echo htmlspecialchars($db_name); ?>" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <input type="text" name="db_port" placeholder="端口" value="<?php echo htmlspecialchars($db_port); ?>">
                </div>
            </div>

            <div class="input-row">
                <div class="form-group">
                    <input type="text" name="db_user" placeholder="数据库用户名" value="<?php echo htmlspecialchars($db_user); ?>" required>
                </div>
                <div class="form-group">
                    <input type="password" name="db_pass" class="has-eye" placeholder="数据库密码">
                    <i class="fas fa-eye-slash toggle-password"></i>
                </div>
            </div>

            <button type="submit" class="btn">下一步</button>
        </form>

    <?php elseif($step == 3): ?>
        <!-- =================== 第三页：管理员账号资料 =================== -->
        <h1>管理员配置</h1>
        <p class="subtitle">设置系统后台超级管理员账户</p>
        
        <form id="admin_form" class="smooth-form" method="POST" action="?step=4" enctype="multipart/form-data">
            <!-- 携带前一页的数据库配置 -->
            <input type="hidden" name="db_host" value="<?php echo htmlspecialchars($db_host); ?>">
            <input type="hidden" name="db_name" value="<?php echo htmlspecialchars($db_name); ?>">
            <input type="hidden" name="db_user" value="<?php echo htmlspecialchars($db_user); ?>">
            <input type="hidden" name="db_pass" value="<?php echo htmlspecialchars($db_pass); ?>">
            <input type="hidden" name="db_port" value="<?php echo htmlspecialchars($db_port); ?>">

            <div class="form-group">
                <input type="text" id="a_user" name="admin_username" placeholder="设置管理员账号 (登录用)" required>
            </div>
            
            <div class="form-group">
                <input type="password" id="a_pwd1" name="admin_password" class="has-eye" placeholder="设置管理员密码 (至少 6 位)" minlength="6" required>
                <i class="fas fa-eye-slash toggle-password"></i>
            </div>
            <div class="form-group">
                <input type="password" id="a_pwd2" name="admin_password_confirm" class="has-eye" placeholder="再次确认管理员密码" minlength="6" required>
                <i class="fas fa-eye-slash toggle-password"></i>
            </div>

            <div class="form-group" style="background: rgba(255,255,255,0.03); padding: 14px 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.06); margin-top: 16px;">
                <p style="font-size: 13px; color: rgba(255,255,255,0.8); margin-bottom: 12px;">设置管理员头像</p>
                <div style="display: flex; gap: 16px; margin-bottom: 12px; font-size: 13px; color: rgba(255,255,255,0.7);">
                    <label style="cursor:pointer; display:flex; align-items:center; gap:6px;">
                        <input type="radio" name="avatar_type" value="url" checked onchange="document.getElementById('avatar_url_wrap').style.display='block';document.getElementById('avatar_file_wrap').style.display='none';"> 图片链接
                    </label>
                    <label style="cursor:pointer; display:flex; align-items:center; gap:6px;">
                        <input type="radio" name="avatar_type" value="upload" onchange="document.getElementById('avatar_url_wrap').style.display='none';document.getElementById('avatar_file_wrap').style.display='block';"> 本地上传
                    </label>
                </div>
                <div id="avatar_url_wrap">
                    <input type="text" id="a_avatar" name="admin_avatar_url" placeholder="头像图片链接 (留空使用默认头像)" style="padding: 10px 14px; font-size: 13px; background: rgba(0,0,0,0.2);">
                </div>
                <div id="avatar_file_wrap" style="display:none;">
                    <input type="file" name="admin_avatar_file" accept="image/*" style="padding: 9px 12px; font-size: 13px; background: rgba(0,0,0,0.2); width: 100%; border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; color: #fff;">
                </div>
            </div>

            <button type="submit" class="btn">完成安装</button>
            <button type="button" class="btn btn-alt skip-btn" onclick="skipAdminSetup(event)">
                <i class="fas fa-bolt"></i> 一键跳过 (自动生成并下载账密)
            </button>
        </form>

    <?php elseif($step == 4): ?>
        <!-- =================== 第四页：安装成功 =================== -->
        <div class="success-icon"><i class="fas fa-check"></i></div>
        <h1>安装完成</h1>
        <p class="subtitle">系统已成功部署并就绪</p>
        
        <div class="credential-box">
            <p><i class="fas fa-user"></i> 管理员账号：<code><?php echo htmlspecialchars($_POST['admin_username'] ?? ''); ?></code></p>
            <p><i class="fas fa-lock"></i> 登录密码：<code>********</code> (您刚刚设置或生成的密码)</p>
            <p style="margin-top: 12px; font-size: 11px; opacity: 0.5; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 8px;">
                <i class="fas fa-shield-halved"></i> 安全提示：安装文件 install.php 已自动销毁。
            </p>
        </div>

        <button class="btn" onclick="smoothNavigate('login/login.php')">进入管理后台</button>
    <?php endif; ?>
</div>

<script>
// 控制密码眼睛图标显示隐藏
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-password').forEach(function(icon) {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text'; this.classList.replace('fa-eye-slash', 'fa-eye');
            } else {
                input.type = 'password'; this.classList.replace('fa-eye', 'fa-eye-slash');
            }
        });
    });

    // 拦截所有的平滑表单提交，附加转场动画
    document.querySelectorAll('form.smooth-form').forEach(f => {
        f.addEventListener('submit', function(e) {
            if (!this.dataset.submitting) {
                e.preventDefault();
                this.dataset.submitting = 'true';
                
                // 将提交按钮变为 Loading 状态
                const btn = this.querySelector('button[type="submit"]');
                if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 处理中...';
                
                document.getElementById('main_card').classList.add('leave');
                setTimeout(() => this.submit(), 350);
            }
        });
    });
});

// A标签和普通按钮的丝滑过渡
function smoothNavigate(url) {
    document.getElementById('main_card').classList.add('leave');
    setTimeout(() => location.href = url, 350);
}

// “跳过设置”按钮逻辑：生成密码 -> 下载TXT -> 自动提交表单
function skipAdminSetup(e) {
    e.preventDefault();
    const btn = e.currentTarget;
    if (btn.dataset.clicked) return;
    btn.dataset.clicked = 'true';
    
    // 生成16位高强度随机密码 (去除容易混淆的字符如 0,O,l,I)
    const chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%^&*';
    let pass = '';
    for(let i=0; i<16; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
    const user = 'GuYi';
    
    // 自动填充表单
    document.getElementById('a_user').value = user;
    document.getElementById('a_pwd1').value = pass;
    document.getElementById('a_pwd2').value = pass;
    document.getElementById('a_avatar').value = ''; // 留空则使用默认头像
    
    // 触发 TXT 文件下载
    const text = `==== GuYi Access 安装凭证 ====\n\n管理员账号: ${user}\n管理员密码: ${pass}\n安装时间: ${new Date().toLocaleString()}\n\n*请妥善保管此文件！系统默认头像已自动配置。`;
    const blob = new Blob([text], { type: 'text/plain' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'GuYi_Admin_Credentials.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    
    // 触发丝滑提交流程
    const form = document.getElementById('admin_form');
    form.dataset.submitting = 'true';
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 正在配置并跳转...';
    document.getElementById('main_card').classList.add('leave');
    
    setTimeout(() => { form.submit(); }, 400);
}
</script>
</body>
</html>
