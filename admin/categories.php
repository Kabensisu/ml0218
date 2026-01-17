<?php
/**
 * 分类管理页面
 */
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../db/config.php';
$db = getDB();

// 获取分类列表
$categories = $db->query("SELECT * FROM categories ORDER BY sort ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理 - 后台管理</title>
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
                <a href="categories.php" class="nav-item active">
                    <span class="nav-icon">📁</span>
                    <span>分类管理</span>
                </a>
                <a href="users.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>用户管理</span>
                </a>
                <a href="ads.php" class="nav-item">
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
                <h1>分类管理</h1>
                <button class="btn-primary" onclick="showAddModal()">+ 添加分类</button>
            </header>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>分类名称</th>
                            <th>排序</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td><?php echo htmlspecialchars($cat['name']); ?></td>
                            <td><?php echo $cat['sort']; ?></td>
                            <td>
                                <span class="status-badge <?php echo $cat['status'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $cat['status'] ? '启用' : '禁用'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-edit" onclick="editCategory(<?php echo htmlspecialchars(json_encode($cat)); ?>)">编辑</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- 添加/编辑分类弹窗 -->
    <div id="category-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">添加分类</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="category-form" onsubmit="saveCategory(event)">
                <input type="hidden" id="category-id" name="id">
                <div class="form-group">
                    <label>分类名称 *</label>
                    <input type="text" id="category-name" name="name" required>
                </div>
                <div class="form-group">
                    <label>排序值</label>
                    <input type="number" id="category-sort" name="sort" value="0">
                </div>
                <div class="form-group">
                    <label>状态</label>
                    <select id="category-status" name="status">
                        <option value="1">启用</option>
                        <option value="0">禁用</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">取消</button>
                    <button type="submit" class="btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('modal-title').textContent = '添加分类';
            document.getElementById('category-form').reset();
            document.getElementById('category-id').value = '';
            document.getElementById('category-modal').classList.add('active');
        }

        function editCategory(cat) {
            document.getElementById('modal-title').textContent = '编辑分类';
            document.getElementById('category-id').value = cat.id;
            document.getElementById('category-name').value = cat.name;
            document.getElementById('category-sort').value = cat.sort || 0;
            document.getElementById('category-status').value = cat.status;
            document.getElementById('category-modal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('category-modal').classList.remove('active');
        }

        async function saveCategory(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            const isEdit = data.id !== '';

            try {
                const response = await fetch('../api/categories.php?action=' + (isEdit ? 'update' : 'add'), {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.code === 200) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('保存失败：' + error.message);
            }
        }
    </script>
</body>
</html>

