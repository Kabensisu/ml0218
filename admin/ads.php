<?php
/**
 * 广告管理页面
 */
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../db/config.php';
$db = getDB();

// 获取广告列表
$ads = $db->query("SELECT * FROM ads ORDER BY position, sort_order ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>广告管理 - 后台管理</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>后台管理</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>数据统计</span>
                </a>
                <a href="games.php" class="nav-item">
                    <span class="nav-icon">🎮</span>
                    <span>游戏管理</span>
                </a>
                <a href="categories.php" class="nav-item">
                    <span class="nav-icon">📁</span>
                    <span>分类管理</span>
                </a>
                <a href="users.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>用户管理</span>
                </a>
                <a href="ads.php" class="nav-item active">
                    <span class="nav-icon">📢</span>
                    <span>广告管理</span>
                </a>
                <a href="password.php" class="nav-item">
                    <span class="nav-icon">🔒</span>
                    <span>修改密码</span>
                </a>
                <a href="../index.html" class="nav-item" target="_blank">
                    <span class="nav-icon">🏠</span>
                    <span>返回前台</span>
                </a>
                <a href="logout.php" class="nav-item">
                    <span class="nav-icon">🚪</span>
                    <span>退出登录</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>广告管理</h1>
                <button class="btn-primary" onclick="showAddModal()">+ 添加广告</button>
            </header>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>位置</th>
                            <th>图片地址</th>
                            <th>跳转地址</th>
                            <th>状态</th>
                            <th>排序</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ads as $ad): ?>
                        <tr>
                            <td><?php echo $ad['id']; ?></td>
                            <td>
                                <?php 
                                $positionNames = [
                                    'ad_nav_left' => '导航栏左侧',
                                    'ad_nav_right' => '导航栏右侧'
                                ];
                                echo $positionNames[$ad['position']] ?? $ad['position'];
                                ?>
                            </td>
                            <td class="path-cell">
                                <a href="<?php echo htmlspecialchars($ad['image_url']); ?>" target="_blank">
                                    <?php echo htmlspecialchars(mb_substr($ad['image_url'], 0, 50)); ?>
                                </a>
                            </td>
                            <td class="path-cell">
                                <?php if ($ad['link_url']): ?>
                                    <a href="<?php echo htmlspecialchars($ad['link_url']); ?>" target="_blank">
                                        <?php echo htmlspecialchars(mb_substr($ad['link_url'], 0, 50)); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999;">无</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $ad['status'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $ad['status'] ? '启用' : '禁用'; ?>
                                </span>
                            </td>
                            <td><?php echo $ad['sort_order']; ?></td>
                            <td>
                                <button class="btn-edit" onclick="editAd(<?php echo htmlspecialchars(json_encode($ad)); ?>)">编辑</button>
                                <button class="btn-delete" onclick="deleteAd(<?php echo $ad['id']; ?>)">删除</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ads)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                暂无广告，点击"添加广告"按钮创建
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- 添加/编辑广告弹窗 -->
    <div id="ad-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">添加广告</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="ad-form" onsubmit="saveAd(event)">
                <input type="hidden" id="ad-id" name="id">
                <div class="form-group">
                    <label>广告位置 *</label>
                    <select id="ad-position" name="position" required>
                        <option value="">请选择</option>
                        <option value="ad_nav_left">导航栏左侧</option>
                        <option value="ad_nav_right">导航栏右侧</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>广告图片地址 *</label>
                    <input type="url" id="ad-image-url" name="image_url" required 
                           placeholder="https://example.com/image.jpg">
                </div>
                <div class="form-group">
                    <label>跳转地址</label>
                    <input type="url" id="ad-link-url" name="link_url" 
                           placeholder="https://example.com (可选)">
                </div>
                <div class="form-group">
                    <label>状态</label>
                    <select id="ad-status" name="status">
                        <option value="1">启用</option>
                        <option value="0">禁用</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>排序</label>
                    <input type="number" id="ad-sort-order" name="sort_order" value="0" min="0">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">取消</button>
                    <button type="submit" class="btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('modal-title').textContent = '添加广告';
            document.getElementById('ad-form').reset();
            document.getElementById('ad-id').value = '';
            document.getElementById('ad-modal').classList.add('active');
        }

        function editAd(ad) {
            document.getElementById('modal-title').textContent = '编辑广告';
            document.getElementById('ad-id').value = ad.id;
            document.getElementById('ad-position').value = ad.position;
            document.getElementById('ad-image-url').value = ad.image_url;
            document.getElementById('ad-link-url').value = ad.link_url || '';
            document.getElementById('ad-status').value = ad.status;
            document.getElementById('ad-sort-order').value = ad.sort_order;
            document.getElementById('ad-modal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('ad-modal').classList.remove('active');
        }

        async function saveAd(event) {
            event.preventDefault();
            
            const formData = {
                id: document.getElementById('ad-id').value || null,
                position: document.getElementById('ad-position').value,
                image_url: document.getElementById('ad-image-url').value,
                link_url: document.getElementById('ad-link-url').value,
                status: parseInt(document.getElementById('ad-status').value),
                sort_order: parseInt(document.getElementById('ad-sort-order').value),
                action: 'save'
            };

            try {
                const response = await fetch('../api/ads.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.code === 200) {
                    alert('保存成功');
                    location.reload();
                } else {
                    alert('保存失败: ' + result.message);
                }
            } catch (error) {
                alert('保存失败: ' + error.message);
            }
        }

        async function deleteAd(id) {
            if (!confirm('确定要删除这条广告吗？')) {
                return;
            }

            try {
                const response = await fetch('../api/ads.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id,
                        action: 'delete'
                    })
                });

                const result = await response.json();

                if (result.code === 200) {
                    alert('删除成功');
                    location.reload();
                } else {
                    alert('删除失败: ' + result.message);
                }
            } catch (error) {
                alert('删除失败: ' + error.message);
            }
        }

        // 点击模态框外部关闭
        document.getElementById('ad-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>

