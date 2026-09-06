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
            document.querySelectorAll('.menu-item, .sub-menu-item, .m-nav-item').forEach(el=>{ 
                if(el.href){ 
                    const u=new URL(el.href,window.location.href),c=new URL(url,window.location.href); 
                    el.classList.toggle('active', u.searchParams.get('tab')===c.searchParams.get('tab')); 
                } 
            });
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
