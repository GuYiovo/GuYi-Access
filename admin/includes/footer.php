</div>
    </main>

    <div id="toast-root" class="toast-container"></div>
</div>

<script>
    let _t;
    function toast(m, t='ok'){
        const r = document.getElementById('toast-root');
        const d = document.createElement('div');
        d.className = 'toast';
        d.innerHTML = t==='error' ? '<i class="ph-fill ph-warning-circle" style="color:var(--color-error);font-size:20px;"></i> '+m : '<i class="ph-fill ph-check-circle" style="color:var(--color-success);font-size:20px;"></i> '+m;
        r.appendChild(d);
        setTimeout(() => { d.style.opacity = '0'; d.style.transform = 'translateY(-20px) scale(0.95)'; setTimeout(()=>d.remove(), 300); }, 3000);
    }
    
    function copy(t){ if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(t).then(()=>toast('复制成功')).catch(()=>fallbackCopy(t)); } else fallbackCopy(t); }
    function fallbackCopy(text) { const ta = document.createElement("textarea"); ta.value = text; ta.style.position="fixed"; ta.style.opacity="0"; document.body.appendChild(ta); ta.focus(); ta.select(); try{ document.execCommand('copy'); toast('复制成功'); }catch(e){ toast('复制失败','error'); } document.body.removeChild(ta); }
    
    function toggleAllChecks(el){document.querySelectorAll('.row-check').forEach(c=>c.checked=el.checked)}
    function singleActionForm(a, id, k='id'){if(!confirm('确定执行此操作？'))return;const f=document.createElement('form');f.method='POST';f.style.display='none';f.innerHTML=`<input name="${a}" value="1"><input name="${k}" value="${id}"><input name="csrf_token" value="<?= $csrf_token ?>">`;document.body.appendChild(f);f.submit()}
    function submitBatch(a){if(document.querySelectorAll('.row-check:checked').length===0){toast('请先勾选目标','error');return}if(!confirm('确定批量执行？'))return;const f=document.getElementById('batchForm');f.insertAdjacentHTML('beforeend',`<input type="hidden" name="${a}" value="1">`);f.submit()}
    function batchAddTime(){if(document.querySelectorAll('.row-check:checked').length===0){toast('请先勾选','error');return}const h=prompt("增加小时数","24");if(h&&!isNaN(h)){document.getElementById('addHoursInput').value=h;submitBatch('batch_add_time')}}
    function batchSubTime(){if(document.querySelectorAll('.row-check:checked').length===0){toast('请先勾选','error');return}const h=prompt("扣除小时数","24");if(h&&!isNaN(h)){document.getElementById('subHoursInput').value=h;submitBatch('batch_sub_time')}}
    function globalCompensate(){const h=prompt("统一补偿小时数(应用于在用卡密):","12");if(h&&!isNaN(h)){const f=document.getElementById('batchForm');f.insertAdjacentHTML('beforeend',`<input type="hidden" name="global_compensate" value="1"><input type="hidden" name="comp_hours" value="${h}">`);f.submit();}}

    window.switchAppView = function(v) {
        document.querySelectorAll('#app_tabs .e-segmented-item').forEach(el=>el.classList.remove('active'));
        event.target.classList.add('active');
        document.getElementById('view_apps').style.display = v==='apps' ? 'block' : 'none';
        document.getElementById('view_vars').style.display = v==='vars' ? 'block' : 'none';
    };
    
    window.openAppModal = function(id, n, v, no, url, force) {
        document.getElementById('e_app_id').value = id; document.getElementById('e_app_name').value = n;
        document.getElementById('e_app_ver').value = v; document.getElementById('e_app_note').value = no;
        document.getElementById('e_app_url').value = url; document.getElementById('e_app_force').checked = (force==1);
        document.getElementById('appModal').style.display = 'flex';
    };
    window.openVarModal = function(id, k, v, p) {
        document.getElementById('e_var_id').value = id; document.getElementById('e_var_key').value = k;
        document.getElementById('e_var_val').value = v; document.getElementById('e_var_pub').checked = (p==1);
        document.getElementById('varModal').style.display = 'flex';
    };

    window.toggleSubMenu = function(el) {
        const layout = document.querySelector('.admin-layout');
        if (layout.classList.contains('sider-collapsed')) return;
        el.classList.toggle('submenu-open');
        const subMenu = el.nextElementSibling;
        subMenu.style.display = subMenu.style.display === 'block' ? 'none' : 'block';
    };

    let isNavigating = false;
    async function loadTab(url, isForm = false, formData = null, formMethod = 'POST') {
        if (isNavigating && !isForm) return;
        isNavigating = true;

        if(!isForm) {
            // 使高亮代码支持最新的更多网格面板
            document.querySelectorAll('.menu-item, .sub-menu-item, .m-nav-item, .m-nav-more-item').forEach(el=>{ 
                if(el.href && !el.href.startsWith('javascript:')){ 
                    const u=new URL(el.href,window.location.href),c=new URL(url,window.location.href); 
                    el.classList.toggle('active', u.searchParams.get('tab')===c.searchParams.get('tab')); 
                } 
            });
            // 同步使底部“更多”图标根据当前路径高亮
            const currentTab = new URL(url, window.location.href).searchParams.get('tab');
            const mMoreBtn = document.getElementById('mNavMoreToggle');
            if(mMoreBtn) {
                mMoreBtn.classList.toggle('active', ['settings', 'blacklist', 'logs'].includes(currentTab));
            }
        }

        const m = document.getElementById('main');
        m.style.transition = 'opacity 0.2s cubic-bezier(0.4, 0, 1, 1), transform 0.2s cubic-bezier(0.4, 0, 1, 1)';
        m.style.opacity = '0'; m.style.transform = 'scale(0.98)';

        try {
            const fetchOpts = { headers: {'X-Requested-With': 'XMLHttpRequest'} };
            if(isForm) { fetchOpts.method = formMethod; fetchOpts.body = formData; }
            
            const [res] = await Promise.all([ fetch(url, fetchOpts), new Promise(r => setTimeout(r, 150)) ]);
            if(res.redirected) { window.location.href = res.url; return; }
            
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            
            m.style.transition = 'none';
            m.innerHTML = doc.getElementById('main').innerHTML;
            m.style.opacity = '0';
            m.style.transform = 'translateY(12px) scale(1)'; 
            
            if(!isForm) window.history.pushState({}, '', url); 
            initPage();
            
            void m.offsetHeight; 
            m.style.transition = 'opacity 0.4s cubic-bezier(0.2, 0.8, 0.2, 1), transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1)';
            m.style.opacity = '1'; m.style.transform = 'translateY(0) scale(1)';
        } catch(e) {
            toast('网络异常，正在刷新...', 'error'); window.location.href = url;
        } finally { setTimeout(() => { isNavigating = false; }, 400); }
    }
    
    document.addEventListener('click', e => {
        const link = e.target.closest('a');
        if(link && link.href && link.href.includes('?tab=') && !link.hasAttribute('target') && !link.hasAttribute('download') && !link.classList.contains('data-no-ajax')) {
            e.preventDefault(); loadTab(link.href);
        }
    });
    
    document.addEventListener('submit', async e => {
        if(e.target.tagName === 'FORM') {
            const s = e.submitter; 
            if(s && (s.name==='batch_export' || s.name==='auto_export' || s.hasAttribute('data-no-ajax')) || e.target.hasAttribute('data-no-ajax')) return;
            e.preventDefault(); 
            const btn = s || e.target.querySelector('button[type="submit"]'); let oT='';
            if(btn) { oT = btn.innerHTML; btn.innerHTML = '<i class="ph ph-spinner-gap" style="animation:spin 1s linear infinite;"></i> 处理中...'; btn.style.pointerEvents = 'none'; btn.style.opacity = '0.8'; }
            
            const fd = new FormData(e.target); if(s && s.name && !fd.has(s.name)) fd.append(s.name, s.value);
            await loadTab(e.target.action || window.location.href, true, fd, e.target.method || 'POST');
            
            if(btn) { btn.innerHTML = oT; btn.style.pointerEvents = 'auto'; btn.style.opacity = '1'; }
        }
    });

    // ================= 新增云端更新检测逻辑与封装 =================
    window.checkSystemUpdate = function(isManual = false, btnEl = null) {
        // 自动检测时，防止单页面切换重复触发
        if (!isManual && window.sysUpdateChecked) return;
        if (!isManual) window.sysUpdateChecked = true;

        // 自动检测时，判断 2 天的免打扰期
        if (!isManual) {
            const hideUntil = localStorage.getItem('hideUpdateUntil');
            if (hideUntil && Date.now() < parseInt(hideUntil)) return;
        }

        let oldBtnHtml = '';
        if (isManual && btnEl) {
            oldBtnHtml = btnEl.innerHTML;
            btnEl.innerHTML = '<i class="ph ph-spinner-gap" style="animation:spin 1s linear infinite;"></i> <span><?= t('检测中...') ?></span>';
            btnEl.style.pointerEvents = 'none';
        }

        const verElement = document.querySelector('.sider-logo .logo-info span:nth-child(2)');
        const localVer = verElement ? verElement.innerText.replace(/^v/i, '').trim() : '2026.9.17';
        
        fetch('https://cloudupdate.xn--tlq395o.top/GuYiAccessversionnumbervariable.txt?t=' + Date.now())
            .then(r => r.text())
            .then(cloudVer => {
                if (isManual && btnEl) { btnEl.innerHTML = oldBtnHtml; btnEl.style.pointerEvents = 'auto'; }
                
                cloudVer = cloudVer.trim();
                if (!cloudVer) {
                    if (isManual) toast('获取云端版本失败', 'error');
                    return;
                }
                
                const lParts = localVer.split('.').map(Number);
                const cParts = cloudVer.split('.').map(Number);
                let hasUpdate = false;
                
                for (let i = 0; i < Math.max(lParts.length, cParts.length); i++) {
                    const l = lParts[i] || 0;
                    const c = cParts[i] || 0;
                    if (c > l) { hasUpdate = true; break; }
                    else if (c < l) { break; }
                }
                
                if (hasUpdate) {
                    const existing = document.getElementById('sys-update-modal');
                    if(existing) existing.remove();

                    const overlay = document.createElement('div');
                    overlay.id = 'sys-update-modal';
                    overlay.className = 'modal-overlay';
                    overlay.style.display = 'flex';
                    overlay.style.zIndex = '9999';
                    
                    overlay.innerHTML = `
                        <div class="modal-content" style="max-width: 380px; margin: auto; animation: modalSlideUp 0.35s cubic-bezier(0.2, 1, 0.3, 1);">
                            <div class="modal-header" style="border-bottom: none; text-align: center; padding-top: 24px; font-size: 20px;">
                                <i class="ph-fill ph-rocket-launch" style="color: var(--color-primary); margin-right: 8px;"></i>发现新版本
                            </div>
                            <div class="modal-body" style="padding: 0 24px 24px;">
                                <p style="text-align: center; color: var(--text-secondary); margin-bottom: 20px; font-size: 14px;">检测到系统有可用更新，建议立即升级</p>
                                
                                <div style="display: flex; align-items: center; justify-content: center; gap: 16px; margin-bottom: 24px; background: var(--bg-layout); padding: 16px; border-radius: 12px;">
                                    <div style="text-align: center; flex: 1;">
                                        <div style="font-size: 12px; color: var(--text-tertiary); margin-bottom: 4px;">当前版本</div>
                                        <div class="mono" style="color: var(--text-secondary); text-decoration: line-through;">${localVer}</div>
                                    </div>
                                    <i class="ph ph-arrow-right" style="color: var(--color-primary); font-size: 20px;"></i>
                                    <div style="text-align: center; flex: 1;">
                                        <div style="font-size: 12px; color: var(--color-success); margin-bottom: 4px; font-weight: 600;">最新版本</div>
                                        <div class="mono" style="color: var(--color-success); font-weight: bold; font-size: 16px;">${cloudVer}</div>
                                    </div>
                                </div>
                                
                                <div style="background: rgba(59, 130, 246, 0.05); border: 1px dashed rgba(59, 130, 246, 0.2); padding: 14px; border-radius: 12px; margin-bottom: 24px; text-align: left;">
                                    <div style="font-size: 13px; color: var(--color-primary); font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; gap: 4px;"><i class="ph-fill ph-lightbulb"></i> 小白更新指南</div>
                                    <div style="font-size: 12px; color: var(--text-secondary); line-height: 1.6;">
                                        直接将当前所有目录删除，重新上传新版本的压缩包解压安装即可。<br><span style="color:var(--color-success); font-weight:500;">系统数据全部存放在数据库中，完全不会丢失，请放心更新。</span>
                                    </div>
                                </div>

                                <div style="display: flex; gap: 12px;">
                                    <button type="button" class="e-btn e-btn-default" style="flex: 1; padding: 0;" onclick="localStorage.setItem('hideUpdateUntil', Date.now() + 2 * 24 * 60 * 60 * 1000); this.closest('.modal-overlay').remove();">暂不更新</button>
                                    <a href="https://github.com/GuYiovo/GuYi-Access/archive/refs/heads/main.zip" target="_blank" class="e-btn e-btn-primary" style="flex: 1; padding: 0; text-decoration: none; justify-content: center;" onclick="this.closest('.modal-overlay').remove()">立即更新</a>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(overlay);
                } else if (isManual) {
                    toast('当前已是最新版本，无需更新', 'ok');
                }
            }).catch(e => {
                if (isManual && btnEl) { btnEl.innerHTML = oldBtnHtml; btnEl.style.pointerEvents = 'auto'; }
                if (isManual) toast('检测失败，请检查网络连接', 'error');
            });
    };
    // =============================================================

    function initPage() {
        const msgEl = document.getElementById('sys-msg'); if(msgEl) { toast(msgEl.dataset.msg, msgEl.dataset.type); msgEl.remove(); }
        const chartEl = document.getElementById('cM');
        if(chartEl && typeof Chart !== 'undefined'){
            const ctx = chartEl.getContext('2d'), tData = JSON.parse(chartEl.dataset.chart), cTypes = JSON.parse(chartEl.dataset.types), labels = Object.keys(tData).map(k=>cTypes[k]?.name||k), data = Object.values(tData);
            new Chart(ctx, {type:'doughnut', data:{labels:labels, datasets:[{data:data, backgroundColor:['#3b82f6','#10b981','#f59e0b','#a855f7','#06b6d4'], borderWidth:0, hoverOffset:4}]}, options:{cutout:'75%', plugins:{legend:{position:'bottom', labels:{usePointStyle:true, boxWidth:8, font:{family:'Inter',size:13,color:'#64748b'}, padding:20}}}, animation:false}});
        }
        const nC = document.getElementById('cloud-notice');
        if (nC && !nC.dataset.loaded) {
            let _u = atob(['aHR0cHM6L','y9jbG91ZHVwZGF0','ZS54bi0tanB','yMDcxZS50b','3AvR3VZaSU','yMEFjY2Vzc','yUyMG5vd','GljZS50eHQ='].join(''));
            fetch(_u+'?t='+Date.now()).then(r=>r.text()).then(t=>{nC.innerHTML=t;nC.dataset.loaded='1'}).catch(()=>nC.innerHTML='同步失败，请检查网络');
        }

        // 调用刚才封装好的检测逻辑
        checkSystemUpdate(false);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const adminLayout = document.querySelector('.admin-layout');
        const siderToggle = document.getElementById('siderToggle');
        if(localStorage.getItem('siderCollapsed') === '1') { adminLayout.classList.add('sider-collapsed'); }
        if(siderToggle) {
            siderToggle.addEventListener('click', () => {
                adminLayout.classList.toggle('sider-collapsed');
                localStorage.setItem('siderCollapsed', adminLayout.classList.contains('sider-collapsed') ? '1' : '0');
            });
        }
        const m = document.getElementById('main');
        m.style.opacity = '0'; m.style.transform = 'translateY(12px) scale(1)';
        requestAnimationFrame(() => {
            m.style.transition = 'opacity 0.5s cubic-bezier(0.2, 0.8, 0.2, 1), transform 0.5s cubic-bezier(0.2, 0.8, 0.2, 1)';
            m.style.opacity = '1'; m.style.transform = 'translateY(0) scale(1)';
        });
        initPage();
    });
    
    window.addEventListener('popstate', () => loadTab(window.location.href));
</script>
<style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>
</body>
</html>
