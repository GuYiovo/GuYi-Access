<?php
// 核心信息防二改保护 (Base64碎片化重组)
$a_q = base64_decode('MTU2N'.'DQwMD'.'Aw');
$a_u = base64_decode('aHR0cHM6Ly9'.'4bi0tanBy'.'MDcxZS5'.'0b3Av');
$a_a = base64_decode('aHR0cHM6Ly9'.'xMi5xbG9nby5j'.'bi9oZWFkaW1n'.'X2RsP2RzdF9'.'1aW49MTU2NDQw'.'MDAwJnNwZWM'.'9NjQw');
?>
<div class="e-card" style="max-width: 600px; margin: 40px auto; text-align: center; padding: 48px 24px; border:none; background:transparent; box-shadow:none;">
    <img src="<?= htmlspecialchars($a_a) ?>" style="width:100px; height:100px; border-radius:50%; margin-bottom:24px; box-shadow:0 12px 24px rgba(0,0,0,0.12); transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05) rotate(5deg)'" onmouseout="this.style.transform='scale(1) rotate(0)'">
    <h2 style="font-size:28px; font-weight:700; margin:0 0 8px 0; color:var(--text-primary); letter-spacing:-0.5px;"><?= base64_decode('R3VZaSBBY2Nlc3MgUHJv') ?></h2>
    <p style="color:var(--text-secondary); margin:0 0 32px 0; font-size:15px;">工业级高可用 · 多应用验证分发架构</p>
    
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
