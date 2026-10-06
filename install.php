<?php
// Compatibility installer entry point.
// The canonical installer lives in scripts/install.php.
$installer = __DIR__ . '/scripts/install.php';
if (is_file($installer)) {
    require $installer;
    exit;
}
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
echo "安装程序已移除或已完成安装。";
