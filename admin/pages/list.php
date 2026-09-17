<?php
// --- 确保存在 t() 翻译函数 (安全后备避免报错) ---
if (!function_exists('t')) { function t($str) { return $str; } }

$appList = $db->getApps();
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$statusFilter = null; $filterStr = $_GET['filter'] ?? 'all';
if ($filterStr === 'unused') $statusFilter = 0; elseif ($filterStr === 'active') $statusFilter = 1; elseif ($filterStr === 'banned') $statusFilter = 2;
$appFilter = isset($_GET['app_id']) && $_GET['app_id'] !== '' ? intval($_GET['app_id']) : null;
$typeFilter = ($appFilter !== null && isset($_GET['type']) && $_GET['type'] !== '') ? $_GET['type'] : null;
$sortFilter = $_GET['sort'] ?? 'create_desc';
$isSearching = isset($_GET['q']) && !empty($_GET['q']); $offset = ($page - 1) * $perPage;
$cardList = []; $totalCards = 0;

try {
    if ($isSearching) { $allResults = $db->searchCards($_GET['q']); $totalCards = count($allResults); $cardList = array_slice($allResults, $offset, $perPage); } 
    else { $totalCards = $db->getTotalCardCount($statusFilter, $appFilter, $typeFilter); $cardList = $db->getCardsPaginated($perPage, $offset, $statusFilter, $appFilter, $typeFilter, $sortFilter); }
} catch (Throwable $e) {}
$totalPages = ceil($totalCards / $perPage); if ($totalPages > 0 && $page > $totalPages) $page = $totalPages;
?>
<style>
@media (max-width: 768px) {
    .list-top-bar { flex-direction: column !important; align-items: stretch !important; gap: 16px !important; }
    .list-top-bar form { flex-direction: column !important; width: 100% !important; gap: 12px !important; }
    .list-top-bar .e-input, .list-top-bar .e-select { max-width: 100% !important; width: 100% !important; height: 48px !important; }
    .list-top-bar .e-btn { width: 100% !important; height: 48px !important; }
    
    .batch-actions { padding: 16px !important; gap: 12px !important; justify-content: space-between !important; }
    .batch-actions button { flex: 1 1 30% !important; height: 40px !important; font-size: 13px !important; padding: 0 !important; border-radius: 12px !important; }
    .batch-actions span { display: none; }
    
    .pagination-bar { flex-direction: column !important; align-items: stretch !important; gap: 16px !important; }
    .pagination-bar select { width: 100% !important; text-align: center; }
}
</style>

<h2 class="page-title"><?= t(htmlspecialchars($currentTitle)) ?></h2>
<p class="page-desc"><?= t('多项目与软件隔离授权管理') ?></p>

<div class="e-card mb-4 flex items-center gap-4 flex-wrap list-top-bar" style="padding: 16px 24px;">
    <form method="GET" class="flex gap-3 items-center flex-1">
        <input type="hidden" name="tab" value="list">
        <?php if(isset($_GET['filter'])): ?><input type="hidden" name="filter" value="<?= $_GET['filter'] ?>"><?php endif; ?>
        <input type="text" name="q" placeholder="<?= t('搜索卡密/设备/备注') ?>" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="e-input" style="max-width:240px;">
        <select name="app_id" class="e-select" style="max-width:160px;" onchange="this.form.submit()">
            <option value=""><?= t('全部应用') ?></option>
            <?php foreach($appList as $app): ?><option value="<?= $app['id'] ?>" <?= ($appFilter === $app['id']) ? 'selected' : '' ?>><?= htmlspecialchars($app['app_name']) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="e-btn e-btn-primary"><?= t('查询') ?></button>
    </form>
    
    <?php 
    $bf = function($f) use($appFilter,$typeFilter,$sortFilter){
        $p=['tab'=>'list','filter'=>$f,'sort'=>$sortFilter];
        if($appFilter) $p['app_id']=$appFilter;
        return '?'.http_build_query($p);
    }; 
    $filter_options = ['all' => '全部', 'unused' => '未激活', 'active' => '使用中', 'banned' => '已封禁'];
    $current_filter_name = $filter_options[$filterStr] ?? '全部';
    ?>
    <div class="e-status-dropdown" id="statusDropdown">
        <div class="e-dropdown-trigger" id="dropdownTrigger">
            <span><?= t('状态') ?>: <b><?= t($current_filter_name) ?></b></span>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256">
                <path d="M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80A8,8,0,0,1,53.66,90.34L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z"></path>
            </svg>
        </div>
        <div class="e-dropdown-menu" id="dropdownMenu">
            <?php foreach ($filter_options as $val => $name): ?>
                <a href="<?= $bf($val) ?>" class="<?= $filterStr === $val ? 'active' : '' ?>">
                    <?= t($name) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="e-card">
    <form id="batchForm" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <div class="flex gap-2 items-center flex-wrap batch-actions" style="padding: 16px 24px; border-bottom: 1px solid var(--border-color); background: var(--bg-layout); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
            <button type="button" class="e-btn e-btn-default" onclick="submitBatch('batch_unbind')"><?= t('批量解绑') ?></button>
            <button type="button" class="e-btn e-btn-default" onclick="batchAddTime()"><?= t('加时') ?></button>
            <button type="button" class="e-btn e-btn-default" onclick="batchSubTime()"><?= t('扣时') ?></button>
            <button type="button" class="e-btn e-btn-default" onclick="globalCompensate()"><?= t('全局补偿') ?></button>
            <button type="submit" name="batch_export" value="1" data-no-ajax="true" class="e-btn e-btn-default"><?= t('导出 TXT') ?></button>
            <span style="flex:1"></span>
            <button type="button" class="e-btn e-btn-danger" onclick="if(confirm('<?= t('确定清理过期卡？') ?>')) singleActionForm('clean_expired', 1);"><?= t('清理过期') ?></button>
            <button type="button" class="e-btn e-btn-danger" onclick="submitBatch('batch_delete')"><?= t('批量删除') ?></button>
            <input type="hidden" name="add_hours" id="addHoursInput"><input type="hidden" name="sub_hours" id="subHoursInput">
        </div>

        <div class="e-table-wrap">
            <table class="e-table">
                <thead><tr>
                    <th style="width: 48px; text-align:center;"><input type="checkbox" onclick="toggleAllChecks(this)" style="accent-color: var(--color-primary);"></th>
                    <th><?= t('应用') ?></th><th><?= t('卡密') ?></th><th><?= t('状态') ?></th><th><?= t('激活时间') ?></th>
                    <?php $sp = $_GET; $sp['sort'] = ($sortFilter == 'expire_asc') ? 'expire_desc' : 'expire_asc'; $sUrl = '?'.http_build_query($sp); ?>
                    <th><a href="<?= $sUrl ?>" style="display:flex; align-items:center; gap:4px; color:inherit; transition: color 0.2s;"><?= t('到期时间') ?> <i class="ph <?= $sortFilter == 'expire_asc'?'ph-sort-ascending':'ph-sort-descending' ?>"></i></a></th>
                    <th><?= t('设备') ?></th><th><?= t('备注') ?></th><th><?= t('操作') ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($cardList as $card): ?>
                    <tr>
                        <td style="text-align:center;"><input type="checkbox" name="ids[]" value="<?= $card['id'] ?>" class="row-check" style="accent-color: var(--color-primary);"></td>
                        <td><?= $card['app_id']>0 ? "<span class='e-tag e-tag-blue'>".htmlspecialchars($card['app_name'])."</span>" : "-" ?></td>
                        <td class="mono font-medium" style="color:var(--color-primary); cursor:pointer;" onclick="copy('<?= $card['card_code'] ?>')"><?= $card['card_code'] ?></td>
                        <td>
                            <?php
                            if ($card['status'] == 2) echo '<span class="e-tag e-tag-red">' . t('封禁') . '</span>';
                            elseif ($card['status'] == 1) echo (strtotime($card['expire_time']) > time()) ? (empty($card['device_hash']) ? '<span class="e-tag e-tag-warning">' . t('待绑定') . '</span>' : '<span class="e-tag e-tag-green">' . t('使用中') . '</span>') : '<span class="e-tag">' . t('已过期') . '</span>';
                            else echo '<span class="e-tag">' . t('闲置') . '</span>';
                            ?>
                        </td>
                        <td class="mono" style="font-size:12px;"><?= !empty($card['used_time']) ? date('y-m-d H:i', strtotime($card['used_time'])) : '-' ?></td>
                        <td class="mono" style="font-size:12px; <?= ($card['status']==1 && strtotime($card['expire_time'])<time())?'color:var(--color-error)':'' ?>"><?= !empty($card['expire_time']) ? date('y-m-d H:i', strtotime($card['expire_time'])) : '-' ?></td>
                        <td class="mono" style="font-size:12px;"><?= ($card['status']==1 && !empty($card['device_hash'])) ? substr($card['device_hash'],0,8).'...' : '-' ?></td>
                        <td style="font-size:12px; max-width:80px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($card['notes'] ?? '') ?>"><?= !empty($card['notes']) ? htmlspecialchars($card['notes']) : '-' ?></td>
                        <td>
                            <?php if ($card['status'] == 1 && !empty($card['device_hash'])): ?><button type="button" class="e-btn e-btn-link" onclick="singleActionForm('unbind_card',<?= $card['id'] ?>)"><?= t('解绑') ?></button><?php endif; ?>
                            <?php if ($card['status'] != 2): ?><button type="button" class="e-btn e-btn-link" style="color:var(--color-warning);" onclick="singleActionForm('ban_card',<?= $card['id'] ?>)"><?= t('封禁') ?></button><?php else: ?><button type="button" class="e-btn e-btn-link" style="color:var(--color-success);" onclick="singleActionForm('unban_card',<?= $card['id'] ?>)"><?= t('解封') ?></button><?php endif; ?>
                            <button type="button" class="e-btn e-btn-link" style="color:var(--color-error);" onclick="singleActionForm('del_card',<?= $card['id'] ?>)"><?= t('删除') ?></button>
                        </td>
                    </tr>
                    <?php endforeach; if (empty($cardList)): ?><tr><td colspan="9" style="text-align:center; padding: 40px 0;"><?= t('暂无数据') ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="flex justify-between items-center flex-wrap gap-4 pagination-bar" style="padding: 16px 24px; border-top: 1px solid var(--border-color);">
            <?php $qp = ['tab'=>'list','filter'=>$filterStr,'sort'=>$sortFilter]; if(!empty($_GET['q'])) $qp['q']=$_GET['q']; if($appFilter!==null) $qp['app_id']=$appFilter; if($typeFilter!==null) $qp['type']=$typeFilter; $plu = $qp; $plu['page']=1; ?>
            <select class="e-select" style="width: auto; height: 36px; padding: 0 32px 0 12px;" onchange="window.location.href='?<?= http_build_query($plu) ?>&limit='+this.value">
                <option value="20" <?= $perPage==20?'selected':'' ?>>20 <?= t('条/页') ?></option>
                <option value="50" <?= $perPage==50?'selected':'' ?>>50 <?= t('条/页') ?></option>
                <option value="100" <?= $perPage==100?'selected':'' ?>>100 <?= t('条/页') ?></option>
            </select>
            <div class="flex items-center gap-2" style="justify-content: center;">
                <?php $qp['limit']=$perPage; $gu = function($p)use($qp){$qp['page']=$p;return '?'.http_build_query($qp);}; ?>
                <?php if($page>1): ?><a href="<?= $gu($page-1) ?>" class="e-btn e-btn-default"><i class="ph ph-caret-left"></i></a><?php endif; ?>
                <span style="margin: 0 12px; font-size:14px; color:var(--text-secondary); font-weight:500;"><?= $page ?> / <?= max(1, $totalPages) ?></span>
                <?php if($page<$totalPages): ?><a href="<?= $gu($page+1) ?>" class="e-btn e-btn-default"><i class="ph ph-caret-right"></i></a><?php endif; ?>
            </div>
        </div>
    </form>
</div>

<style>
.e-status-dropdown { position: relative; display: inline-block; font-size: 14px; z-index: 99; }
.e-dropdown-trigger { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 16px; background: rgba(255, 255, 255, 0.7); border: 1px solid rgba(226, 232, 240, 0.8); border-radius: 12px; cursor: pointer; color: var(--text-primary, #1e293b); user-select: none; transition: all 0.3s ease; min-width: 140px; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
.e-dropdown-trigger:hover { border-color: var(--color-primary, #3b82f6); background: rgba(255, 255, 255, 0.9); }
.e-dropdown-menu { position: absolute; top: 100%; right: 0; margin-top: 8px; background: rgba(255, 255, 255, 0.85); border: 1px solid rgba(226, 232, 240, 0.8); border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15); min-width: 100%; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); overflow: hidden; }
@media (max-width: 767px) { .e-dropdown-menu { left: 0; right: auto; width: 100%; } }
.e-dropdown-menu a { display: block; padding: 12px 16px; color: var(--text-secondary, #475569); text-decoration: none; transition: background 0.2s, color 0.2s; border-bottom: 1px solid rgba(241, 245, 249, 0.5); }
.e-dropdown-menu a:last-child { border-bottom: none; }
.e-dropdown-menu a:hover, .e-dropdown-menu a.active { background: rgba(59, 130, 246, 0.1); color: var(--color-primary, #3b82f6); font-weight: 600; }
@media (min-width: 768px) { .e-status-dropdown:hover .e-dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); } }
.e-dropdown-menu.show-mobile { opacity: 1; visibility: visible; transform: translateY(0); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var trigger = document.getElementById('dropdownTrigger');
    var menu = document.getElementById('dropdownMenu');
    if (trigger && menu) {
        trigger.addEventListener('click', function(e) { if (window.innerWidth < 768) { e.stopPropagation(); menu.classList.toggle('show-mobile'); } });
        document.addEventListener('click', function(e) { if (window.innerWidth < 768) { if (!menu.contains(e.target) && !trigger.contains(e.target)) { menu.classList.remove('show-mobile'); } } });
    }
});
</script>
