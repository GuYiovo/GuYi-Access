<?php
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
<h2 class="page-title"><?= htmlspecialchars($currentTitle) ?></h2>
<p class="page-desc">多项目与软件隔离授权管理</p>

<div class="e-card mb-4 flex items-center gap-4 flex-wrap" style="padding: 16px 24px;">
    <form method="GET" class="flex gap-3 items-center flex-1">
        <input type="hidden" name="tab" value="list">
        <?php if(isset($_GET['filter'])): ?><input type="hidden" name="filter" value="<?= $_GET['filter'] ?>"><?php endif; ?>
        <input type="text" name="q" placeholder="搜索卡密/设备/备注" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="e-input" style="max-width:240px;">
        <select name="app_id" class="e-select" style="max-width:160px;" onchange="this.form.submit()">
            <option value="">全部应用</option>
            <?php foreach($appList as $app): ?><option value="<?= $app['id'] ?>" <?= ($appFilter === $app['id']) ? 'selected' : '' ?>><?= htmlspecialchars($app['app_name']) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="e-btn e-btn-primary">查询</button>
    </form>
    
    <div class="e-segmented hidden md:flex">
        <?php $bf = function($f) use($appFilter,$typeFilter,$sortFilter){$p=['tab'=>'list','filter'=>$f,'sort'=>$sortFilter];if($appFilter) $p['app_id']=$appFilter;return '?'.http_build_query($p);}; ?>
        <a href="<?= $bf('all') ?>" class="e-segmented-item <?= $filterStr=='all'?'active':'' ?>">全部</a>
        <a href="<?= $bf('unused') ?>" class="e-segmented-item <?= $filterStr=='unused'?'active':'' ?>">未激活</a>
        <a href="<?= $bf('active') ?>" class="e-segmented-item <?= $filterStr=='active'?'active':'' ?>">使用中</a>
        <a href="<?= $bf('banned') ?>" class="e-segmented-item <?= $filterStr=='banned'?'active':'' ?>">已封禁</a>
    </div>
</div>

<div class="e-card">
    <form id="batchForm" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <div class="flex gap-2 items-center flex-wrap" style="padding: 16px 24px; border-bottom: 1px solid var(--border-color); background: var(--bg-layout); border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
            <button type="button" class="e-btn e-btn-default" onclick="submitBatch('batch_unbind')">批量解绑</button>
            <button type="button" class="e-btn e-btn-default" onclick="batchAddTime()">加时</button>
            <button type="button" class="e-btn e-btn-default" onclick="batchSubTime()">扣时</button>
            <button type="button" class="e-btn e-btn-default" onclick="globalCompensate()">全局补偿</button>
            <button type="submit" name="batch_export" value="1" data-no-ajax="true" class="e-btn e-btn-default">导出 TXT</button>
            <span style="flex:1"></span>
            <button type="button" class="e-btn e-btn-danger" onclick="if(confirm('确定清理过期卡？')) singleActionForm('clean_expired', 1);">清理过期</button>
            <button type="button" class="e-btn e-btn-danger" onclick="submitBatch('batch_delete')">批量删除</button>
            <input type="hidden" name="add_hours" id="addHoursInput"><input type="hidden" name="sub_hours" id="subHoursInput">
        </div>

        <div class="e-table-wrap">
            <table class="e-table">
                <thead><tr>
                    <th style="width: 48px; text-align:center;"><input type="checkbox" onclick="toggleAllChecks(this)" style="accent-color: var(--color-primary);"></th>
                    <th>应用</th><th>卡密</th><th>状态</th><th>激活时间</th>
                    <?php $sp = $_GET; $sp['sort'] = ($sortFilter == 'expire_asc') ? 'expire_desc' : 'expire_asc'; $sUrl = '?'.http_build_query($sp); ?>
                    <th><a href="<?= $sUrl ?>" style="display:flex; align-items:center; gap:4px; color:inherit; transition: color 0.2s;">到期时间 <i class="ph <?= $sortFilter == 'expire_asc'?'ph-sort-ascending':'ph-sort-descending' ?>"></i></a></th>
                    <th>设备</th><th>备注</th><th>操作</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($cardList as $card): ?>
                    <tr>
                        <td style="text-align:center;"><input type="checkbox" name="ids[]" value="<?= $card['id'] ?>" class="row-check" style="accent-color: var(--color-primary);"></td>
                        <td><?= $card['app_id']>0 ? "<span class='e-tag e-tag-blue'>".htmlspecialchars($card['app_name'])."</span>" : "-" ?></td>
                        <td class="mono font-medium" style="color:var(--color-primary); cursor:pointer;" onclick="copy('<?= $card['card_code'] ?>')"><?= $card['card_code'] ?></td>
                        <td>
                            <?php
                            if ($card['status'] == 2) echo '<span class="e-tag e-tag-red">封禁</span>';
                            elseif ($card['status'] == 1) echo (strtotime($card['expire_time']) > time()) ? (empty($card['device_hash']) ? '<span class="e-tag e-tag-warning">待绑定</span>' : '<span class="e-tag e-tag-green">使用中</span>') : '<span class="e-tag">已过期</span>';
                            else echo '<span class="e-tag">闲置</span>';
                            ?>
                        </td>
                        <td class="mono" style="font-size:12px;"><?= !empty($card['used_time']) ? date('y-m-d H:i', strtotime($card['used_time'])) : '-' ?></td>
                        <td class="mono" style="font-size:12px; <?= ($card['status']==1 && strtotime($card['expire_time'])<time())?'color:var(--color-error)':'' ?>"><?= !empty($card['expire_time']) ? date('y-m-d H:i', strtotime($card['expire_time'])) : '-' ?></td>
                        <td class="mono" style="font-size:12px;"><?= ($card['status']==1 && !empty($card['device_hash'])) ? substr($card['device_hash'],0,8).'...' : '-' ?></td>
                        <td style="font-size:12px; max-width:80px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($card['notes'] ?? '') ?>"><?= !empty($card['notes']) ? htmlspecialchars($card['notes']) : '-' ?></td>
                        <td>
                            <?php if ($card['status'] == 1 && !empty($card['device_hash'])): ?><button type="button" class="e-btn e-btn-link" onclick="singleActionForm('unbind_card',<?= $card['id'] ?>)">解绑</button><?php endif; ?>
                            <?php if ($card['status'] != 2): ?><button type="button" class="e-btn e-btn-link" style="color:var(--color-warning);" onclick="singleActionForm('ban_card',<?= $card['id'] ?>)">封禁</button><?php else: ?><button type="button" class="e-btn e-btn-link" style="color:var(--color-success);" onclick="singleActionForm('unban_card',<?= $card['id'] ?>)">解封</button><?php endif; ?>
                            <button type="button" class="e-btn e-btn-link" style="color:var(--color-error);" onclick="singleActionForm('del_card',<?= $card['id'] ?>)">删除</button>
                        </td>
                    </tr>
                    <?php endforeach; if (empty($cardList)): ?><tr><td colspan="9" style="text-align:center; padding: 40px 0;">暂无数据</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="flex justify-between items-center flex-wrap gap-4" style="padding: 16px 24px; border-top: 1px solid var(--border-color);">
            <?php $qp = ['tab'=>'list','filter'=>$filterStr,'sort'=>$sortFilter]; if(!empty($_GET['q'])) $qp['q']=$_GET['q']; if($appFilter!==null) $qp['app_id']=$appFilter; if($typeFilter!==null) $qp['type']=$typeFilter; $plu = $qp; $plu['page']=1; ?>
            <select class="e-select" style="width: auto; height: 36px; padding: 0 32px 0 12px;" onchange="window.location.href='?<?= http_build_query($plu) ?>&limit='+this.value">
                <option value="20" <?= $perPage==20?'selected':'' ?>>20 条/页</option>
                <option value="50" <?= $perPage==50?'selected':'' ?>>50 条/页</option>
                <option value="100" <?= $perPage==100?'selected':'' ?>>100 条/页</option>
            </select>
            <div class="flex items-center gap-2">
                <?php $qp['limit']=$perPage; $gu = function($p)use($qp){$qp['page']=$p;return '?'.http_build_query($qp);}; ?>
                <?php if($page>1): ?><a href="<?= $gu($page-1) ?>" class="e-btn e-btn-default"><i class="ph ph-caret-left"></i></a><?php endif; ?>
                <span style="margin: 0 12px; font-size:14px; color:var(--text-secondary); font-weight:500;"><?= $page ?> / <?= max(1, $totalPages) ?></span>
                <?php if($page<$totalPages): ?><a href="<?= $gu($page+1) ?>" class="e-btn e-btn-default"><i class="ph ph-caret-right"></i></a><?php endif; ?>
            </div>
        </div>
    </form>
</div>
