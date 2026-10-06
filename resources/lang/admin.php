<?php
// admin/includes/lang.php
$sys_lang = $_COOKIE['sys_lang'] ?? 'zh';

// 全局海量翻译词库 (覆盖整个后台所有的边角文字)
$i18n_dict = [
    // 菜单与大标题
    '系统概览' => 'SYSTEM OVERVIEW', '核心业务' => 'CORE BUSINESS', '卡密管理' => 'CARD MANAGEMENT', '防御与监控' => 'SECURITY & MONITOR', '系统设置' => 'SYSTEM SETTINGS',
    '数据总览' => 'Dashboard', '应用管理' => 'App Management', '卡密库存' => 'Card Inventory', '批量制卡' => 'Batch Create', '防御与拉黑' => 'Defense & Blacklist', '访问日志' => 'Access Logs', '全局配置' => 'Global Config', '关于作者' => 'About Author', '安全退出' => 'Logout', '控制台' => 'Console', '检查更新' => 'Check Update', '检测中...' => 'Checking...',
    
    // 移动端底部导航与面板短词
    '概览' => 'Dash', '制卡' => 'Create', '设置' => 'Settings', '库存' => 'Stock', '更多' => 'More', '防御拉黑' => 'Blacklist', '全局设置' => 'Global Settings', '点击复制' => 'Click to Copy',

    // 页面头部描述
    '多项目与软件隔离授权管理' => 'Multi-project isolated authorization management',
    '系统基础环境参数与安全设置' => 'System environment and security settings',
    '恶意请求与违规设备管理' => 'Malicious Request & Block Management',
    '批量生成授权卡密' => 'Batch Generate Cards',
    '客户端验证请求流日志审计' => 'Client Request Logs Audit',
    
    // 卡密列表页
    '搜索卡密/设备/备注' => 'Search code / device / notes',
    '全部应用' => 'All Apps',
    '查询' => 'Search',
    '全部' => 'All', '未激活' => 'Inactive', '使用中' => 'In Use', '已封禁' => 'Banned', '待绑定' => 'Pending', '已过期' => 'Expired', '闲置' => 'Idle',
    '批量解绑' => 'Batch Unbind', '加时' => 'Add Time', '扣时' => 'Sub Time', '全局补偿' => 'Global Compensate', '导出 TXT' => 'Export TXT', '清理过期' => 'Clean Expired', '批量删除' => 'Batch Delete',
    '卡密' => 'Card Code', '应用' => 'App', '状态' => 'Status', '激活时间' => 'Activated Time', '到期时间' => 'Expire Time', '设备' => 'Device', '备注' => 'Notes', '操作' => 'Actions',
    '解绑' => 'Unbind', '解封' => 'Unban', '封禁' => 'Ban', '删除' => 'Delete', '编辑' => 'Edit', '正常' => 'Normal', '禁用' => 'Disabled', '启用' => 'Enable', '换盐' => 'Reset Secret',
    '暂无数据' => 'No Data Found', '条/页' => ' rows/page', '20 条/页' => '20 rows/page', '50 条/页' => '50 rows/page', '100 条/页' => '100 rows/page',
    '确定清理过期卡？' => 'Confirm to clean expired cards?',

    // 应用管理页
    '应用列表' => 'App List', '云端变量' => 'Cloud Variables', '应用名称' => 'App Name', '版本/备注' => 'Version / Notes', '库存统计' => 'Inventory',
    '警告：重置后客户端必须同步更换新的 Secret 才能通讯解密！确定重置？' => 'WARNING: Clients must update Secret to communicate! Confirm reset?',
    '需先清空卡密' => 'Must empty cards first', '暂无应用' => 'No Apps', '创建新应用' => 'Create New App', '例如：Android 客户端' => 'e.g. Android Client', '版本号' => 'Version', '例如：v1.0.0' => 'e.g. v1.0.0', '备注信息' => 'Notes Info', '立即创建' => 'Create Now',
    '接口与安全提示' => 'API & Security Tips', '客户端 API 接口通讯地址：' => 'Client API Address:', '安全建议：' => 'Security Advice:', '请求时使用' => 'Use', '作为身份标识；' => 'as identity;', '并将后台对应分配的' => 'and embed the allocated', '写死在客户端源码深处，专门用于接收响应数据的 AES 解密。' => 'deep into client source code for AES decryption of response data.',
    '所属应用' => 'Belongs to App', '变量 Key' => 'Variable Key', '变量 Value' => 'Variable Value', '权限' => 'Permission', '公开' => 'Public', '私有' => 'Private', '暂无变量' => 'No Variables', '添加变量' => 'Add Variable', '-- 请选择 --' => '-- Select --', '键名 (Key)' => 'Key Name', '变量值' => 'Variable Value', '设为公开变量 (客户端可见)' => 'Set as Public (Visible to Client)', '保存变量' => 'Save Variable', '应用设置' => 'App Settings', '更新链接' => 'Update URL', '更新日志' => 'Update Log', '强制更新拦截' => 'Force Update Block', '确定' => 'Confirm', '取消' => 'Cancel', '编辑变量' => 'Edit Variable',

    // 批量制卡页
    '目标应用' => 'Target App', '请选择应用...' => 'Select App...', '生成数量' => 'Amount', '卡密时长类型' => 'Card Duration Type', '自定义任意小时' => 'Custom Hours', '输入自定义时长 (小时)' => 'Input Custom Duration (Hours)', '展开高级选项 (前缀、备注等)' => 'Advanced Options (Prefix, Notes...)', '收起高级选项 (前缀、备注等)' => 'Hide Advanced Options', '卡密前缀 (可选)' => 'Prefix (Optional)', '如 VIP-' => 'e.g. VIP-', '例如：代理A批次' => 'e.g. Agent A Batch', '立即生成' => 'Generate Now', '生成并下载 TXT' => 'Generate & Download TXT', '制卡成功 - 直接输出' => 'Generation Successful', '一键复制全部卡密' => 'Copy All Cards', '天卡' => 'Day Card', '周卡' => 'Week Card', '月卡' => 'Month Card', '季卡' => 'Season Card', '年卡' => 'Year Card', '永久卡' => 'Lifetime Card', '小时' => 'Hours', '天' => 'Days',

    // 防御与日志
    '新增黑名单' => 'Add Blacklist', '类型' => 'Type', '设备特征码' => 'Device Hash', 'IP 地址' => 'IP Address', '拦截目标 (需准确无空格)' => 'Target (Exact, no spaces)', '拦截原因备注' => 'Block Reason', '强制拉黑' => 'Force Block', '拦截目标特征 (Value)' => 'Blocked Target (Value)', '添加时间' => 'Added Time', '暂无黑名单数据' => 'No Blacklist Data',
    '发生时间' => 'Time Occurred', '目标应用' => 'Target App', '记录动作' => 'Action Logged', '参数/详情' => 'Details / Params', '来源 IP' => 'Source IP', '无日志记录' => 'No Logs', '拦截' => 'Blocked',

    // 数据总览首页
    '总库存量' => 'Total Cards', '活跃设备' => 'Active Devices', '接入应用' => 'Connected Apps', '待售库存' => 'Unsold Cards', '在线' => 'Online', '实时活跃设备' => 'Real-time Active Devices', '当前宇宙非常安静，暂无活跃设备' => 'No active devices at the moment.', '最新审计日志' => 'Latest Logs', '查看全部' => 'View All', '动作记录' => 'Action Record', '详情 / 参数' => 'Details / Params', '系统公告' => 'System Announcements', '正在同步云端信息...' => 'Syncing cloud info...', '同步失败，请检查网络' => 'Sync failed, check network', '卡密类型分布' => 'Card Type Stats', '暂无分布数据' => 'No Data', '凌晨好！夜深了，注意休息哦' => 'Good late night! Take a rest.', '早上好！今天也要全力以赴' => 'Good morning! Give it your all today.', '上午好！新的一天元气满满' => 'Good morning! Full of energy.', '中午好！记得按时吃午饭哦' => 'Good afternoon! Remember to eat lunch.', '下午好！喝杯茶，继续努力吧' => 'Good afternoon! Keep up the good work.', '晚上好！愿你度过一个轻松的夜晚' => 'Good evening! Have a relaxing night.', '欢迎回来' => 'Welcome Back', '未分类' => 'Uncategorized',

    // 系统设置与相关新标签
    '安全与个性化设置' => 'Security & Personalization', '系统语言 / Language' => 'System Language', '切换系统显示语言' => 'Switch system display language', '开启 API 接口通讯加密 (AES-256-GCM)' => 'Enable API Encryption (AES-256-GCM)', '开启全局二次元背景图 (实验性)' => 'Enable Anime Background', '背景图透明度' => 'Background Opacity', '控制后面二次元壁纸的明显程度。' => 'Adjust background wallpaper visibility.', '面板白底透明度 (越低越透)' => 'Panel Opacity (Lower is more transparent)', '控制毛玻璃卡片的透明度。建议保持在 0.4 ~ 0.8 之间以保证阅读性。' => 'Control frosted glass panel opacity.', '强烈建议开启以防止被抓包破解。客户端需要对应解密逻辑。' => 'Highly recommended to prevent packet sniffing.', '在右侧主区域显示淡淡的 ACG 背景图，并开启全站毛玻璃拟物风。' => 'Show a faint ACG background image.', '保存修改并刷新' => 'Save Changes', '修改管理员密码' => 'Change Admin Password', '新密码' => 'New Password', '再次确认' => 'Confirm Password', '强制修改并退出' => 'Force Change & Logout', '系统数据迁移工具' => 'System Data Migration', '导出当前数据' => 'Export Current Data', '将当前所有应用、卡密、变量打包下载备份，防患于未然。' => 'Download all apps, cards, and variables as a backup.', '一键下载 JSON 备份' => 'Download JSON Backup', '导入恢复数据' => 'Import & Restore Data', '(危险: 将覆盖当前一切)' => '(Danger: Overwrites everything)', '上传备份 JSON 文件，彻底恢复历史数据，请谨慎操作。' => 'Upload backup JSON to completely restore historical data.', '执行恢复' => 'Execute Restore', '警告：该操作将彻底清空当前系统，恢复不可逆！' => 'WARNING: Irreversible restore operation!',
    '系统与外观' => 'System & Appearance', '管理员资料' => 'Admin Profile', '数据迁移' => 'Data Migration', 
    '保存系统与外观修改' => 'Save System Changes', '管理员资料设置' => 'Admin Profile Settings', 
    '登录账号' => 'Username', '头像设置' => 'Avatar Settings', '图片链接' => 'Image URL', 
    '本地上传' => 'Local Upload', '输入图片链接' => 'Enter Image URL', '保存资料修改' => 'Save Profile',
    '工业级高可用 · 多应用验证分发架构' => 'High Availability & Multi-App Verification Architecture',

    // 弹窗与系统提示动作 (actions.php 隐藏在后端的提示)
    '复制成功' => 'Copied!', '复制失败' => 'Copy Failed', '确定执行此操作？' => 'Confirm action?', '请先勾选目标' => 'Please select targets first', '确定批量执行？' => 'Confirm batch execution?', '增加小时数' => 'Add hours', '扣除小时数' => 'Subtract hours', '统一补偿小时数(应用于在用卡密):' => 'Global compensate hours:', '网络异常，正在刷新...' => 'Network error, refreshing...', '处理中...' => 'Processing...', '操作成功' => 'Operation successful!', '操作失败' => 'Operation failed', '保存成功' => 'Saved successfully', '删除成功' => 'Deleted successfully', '创建成功' => 'Created successfully', '修改成功' => 'Modified successfully', '密码修改成功，请重新登录' => 'Password changed, please login again', '密码不一致' => 'Passwords do not match', '恢复成功' => 'Restored successfully', '系统维护中，无法连接数据库。' => 'System maintenance, database connection failed.', '缺少参数' => 'Missing parameters', '找不到该卡密' => 'Card not found', '找不到应用' => 'App not found', '简体中文 (Chinese)' => 'Simplified Chinese'
];

// 供菜单与标题手动调用的基础函数
function t($text) {
    global $sys_lang, $i18n_dict;
    if ($sys_lang === 'en' && isset($i18n_dict[$text])) {
        return $i18n_dict[$text];
    }
    return $text;
}

// 核心：全局输出翻译拦截引擎 (自动拦截替换所有角落里的中文)
function translate_output_buffer($buffer) {
    global $i18n_dict;
    $keys = array_keys($i18n_dict);
    // 按文本长度排序，防止部分短词被错误截断
    usort($keys, function($a, $b) {
        return strlen($b) - strlen($a);
    });
    
    $replace_arr = [];
    foreach ($keys as $k) {
        $replace_arr[$k] = $i18n_dict[$k];
    }
    // 强制将捕获到的输出 HTML 全部替换为英文
    return strtr($buffer, $replace_arr);
}
