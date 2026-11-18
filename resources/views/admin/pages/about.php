<?php
$logo = 'https://pub-8a73d0f678a64dc9abb02ea54e4c44c5.r2.dev/logo.png';
$site_main = 'https://guyi-access-wed.pages.dev/';
$site_backup = 'https://guyiovo.github.io/GuYi-Access-wed/';
$site_personal = 'https://sad66.pages.dev/';
$tg = 'https://t.me/GuYi0u0';
$license_url = 'https://polyformproject.org/licenses/perimeter/1.0.1';
$github = 'https://github.com/GuYiovo/GuYi-Access';
?>
<div class="e-card" style="max-width: 520px; margin: 28px auto; text-align: center; padding: 40px 20px 32px; border:none; background:transparent; box-shadow:none;">
    <img src="<?= htmlspecialchars($logo) ?>" alt="Logo" style="width:88px; height:88px; border-radius:50%; object-fit:cover; margin-bottom:20px; box-shadow:0 12px 28px rgba(0,0,0,0.14); transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.06)'" onmouseout="this.style.transform='scale(1)'">
    <h2 style="font-size:26px; font-weight:700; margin:0 0 6px 0; color:var(--text-primary); letter-spacing:-0.4px;">GuYi Access Pro</h2>
    <p style="color:var(--text-secondary); margin:0 0 28px 0; font-size:14px; line-height:1.5;">工业级高可用 · 多应用验证分发架构</p>

    <div style="background: var(--bg-layout); border-radius: 18px; padding: 8px 0; border: 1px solid var(--border-color); text-align: left; width: 100%; max-width: 400px; margin: 0 auto; overflow: hidden;">
        <!-- Author header -->
        <div style="display:flex; align-items:center; gap:12px; padding: 16px 20px 14px;">
            <div style="width:40px; height:40px; border-radius:12px; background:var(--color-primary-bg); color:var(--color-primary); display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;"><i class="ph-fill ph-user-circle"></i></div>
            <div>
                <div style="font-size:11px; color:var(--text-tertiary); font-weight:600; text-transform:uppercase; letter-spacing: 0.6px;">AUTHOR</div>
                <div style="font-size:15px; font-weight:600; color:var(--text-primary); margin-top:1px;">核心架构设计 · GuYiovo</div>
            </div>
        </div>

        <div style="height:1px; background:var(--border-color); margin: 0 16px;"></div>

        <!-- Links -->
        <div style="display:flex; flex-direction:column; padding: 6px 8px 8px;">
            <a href="<?= htmlspecialchars($site_main) ?>" target="_blank" rel="noopener" style="display:flex; justify-content:space-between; align-items:center; padding: 12px 12px; border-radius:12px; text-decoration:none; color:inherit; transition: background .15s;" onmouseover="this.style.background='var(--color-primary-bg)'" onmouseout="this.style.background='transparent'">
                <span style="color:var(--text-secondary); font-weight:500; font-size:14px; display:flex; align-items:center; gap:8px;"><i class="ph ph-globe" style="font-size:18px;"></i> 官方网站</span>
                <span style="color:var(--color-primary); font-weight:600; font-size:13px; display:flex; align-items:center; gap:4px;">访问 <i class="ph ph-arrow-square-out"></i></span>
            </a>
            <a href="<?= htmlspecialchars($site_backup) ?>" target="_blank" rel="noopener" style="display:flex; justify-content:space-between; align-items:center; padding: 12px 12px; border-radius:12px; text-decoration:none; color:inherit; transition: background .15s;" onmouseover="this.style.background='var(--color-primary-bg)'" onmouseout="this.style.background='transparent'">
                <span style="color:var(--text-secondary); font-weight:500; font-size:14px; display:flex; align-items:center; gap:8px;"><i class="ph ph-link" style="font-size:18px;"></i> 备用官网</span>
                <span style="color:var(--color-primary); font-weight:600; font-size:13px; display:flex; align-items:center; gap:4px;">访问 <i class="ph ph-arrow-square-out"></i></span>
            </a>
            <a href="<?= htmlspecialchars($site_personal) ?>" target="_blank" rel="noopener" style="display:flex; justify-content:space-between; align-items:center; padding: 12px 12px; border-radius:12px; text-decoration:none; color:inherit; transition: background .15s;" onmouseover="this.style.background='var(--color-primary-bg)'" onmouseout="this.style.background='transparent'">
                <span style="color:var(--text-secondary); font-weight:500; font-size:14px; display:flex; align-items:center; gap:8px;"><i class="ph ph-user" style="font-size:18px;"></i> 作者个人站</span>
                <span style="color:var(--color-primary); font-weight:600; font-size:13px; display:flex; align-items:center; gap:4px;">访问 <i class="ph ph-arrow-square-out"></i></span>
            </a>
            <a href="<?= htmlspecialchars($tg) ?>" target="_blank" rel="noopener" style="display:flex; justify-content:space-between; align-items:center; padding: 12px 12px; border-radius:12px; text-decoration:none; color:inherit; transition: background .15s;" onmouseover="this.style.background='var(--color-primary-bg)'" onmouseout="this.style.background='transparent'">
                <span style="color:var(--text-secondary); font-weight:500; font-size:14px; display:flex; align-items:center; gap:8px;"><i class="ph ph-paper-plane-tilt" style="font-size:18px;"></i> Telegram 频道</span>
                <span style="color:var(--color-primary); font-weight:600; font-size:13px; display:flex; align-items:center; gap:4px;">@GuYi0u0 <i class="ph ph-arrow-square-out"></i></span>
            </a>
            <a href="<?= htmlspecialchars($github) ?>" target="_blank" rel="noopener" style="display:flex; justify-content:space-between; align-items:center; padding: 12px 12px; border-radius:12px; text-decoration:none; color:inherit; transition: background .15s;" onmouseover="this.style.background='var(--color-primary-bg)'" onmouseout="this.style.background='transparent'">
                <span style="color:var(--text-secondary); font-weight:500; font-size:14px; display:flex; align-items:center; gap:8px;"><i class="ph ph-github-logo" style="font-size:18px;"></i> GitHub</span>
                <span style="color:var(--color-primary); font-weight:600; font-size:13px; display:flex; align-items:center; gap:4px;">仓库 <i class="ph ph-arrow-square-out"></i></span>
            </a>
        </div>

        <div style="height:1px; background:var(--border-color); margin: 0 16px;"></div>

        <!-- License -->
        <div style="padding: 14px 20px 16px; display:flex; justify-content:space-between; align-items:center; gap:12px;">
            <span style="color:var(--text-secondary); font-weight:500; font-size:14px; display:flex; align-items:center; gap:8px;"><i class="ph ph-shield-check" style="font-size:18px;"></i> 许可证</span>
            <a href="<?= htmlspecialchars($license_url) ?>" target="_blank" rel="noopener" style="color:var(--text-tertiary); font-size:12px; text-decoration:none; text-align:right; line-height:1.35;" title="PolyForm Perimeter License 1.0.1">
                PolyForm Perimeter<br>1.0.1
            </a>
        </div>
    </div>
</div>
