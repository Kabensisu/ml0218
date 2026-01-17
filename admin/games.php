<?php
/**
 * 游戏管理页面
 */
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../db/config.php';
$db = getDB();

// 获取分类列表
$categories = $db->query("SELECT * FROM categories WHERE status = 1 ORDER BY sort ASC")->fetchAll();

// 获取游戏列表
$page = intval($_GET['page'] ?? 1);
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

$total = $db->query("SELECT COUNT(*) FROM games")->fetchColumn();
$games = $db->query("SELECT * FROM games ORDER BY id DESC LIMIT $pageSize OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>游戏管理 - 后台管理</title>
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
                <a href="games.php" class="nav-item active">
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
                <h1>游戏管理</h1>
                <button class="btn-primary" onclick="showAddModal()">+ 添加游戏</button>
            </header>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>游戏名称</th>
                            <th>分类</th>
                            <th>路径</th>
                            <th>排序</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($games as $game): ?>
                        <tr>
                            <td><?php echo $game['id']; ?></td>
                            <td><?php echo htmlspecialchars($game['name']); ?></td>
                            <td><?php echo htmlspecialchars($game['category']); ?></td>
                            <td class="path-cell"><?php echo htmlspecialchars($game['path']); ?></td>
                            <td><?php echo $game['sort']; ?></td>
                            <td>
                                <span class="status-badge <?php echo $game['status'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $game['status'] ? '启用' : '禁用'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-edit" onclick="editGame(<?php echo htmlspecialchars(json_encode($game)); ?>)">编辑</button>
                                <button class="btn-delete" onclick="deleteGame(<?php echo $game['id']; ?>)">删除</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- 分页 -->
            <div class="pagination">
                <?php
                $totalPages = ceil($total / $pageSize);
                if ($page > 1) {
                    echo '<a href="?page=' . ($page - 1) . '" class="page-btn">上一页</a>';
                }
                for ($i = 1; $i <= $totalPages; $i++) {
                    $active = $i === $page ? 'active' : '';
                    echo '<a href="?page=' . $i . '" class="page-btn ' . $active . '">' . $i . '</a>';
                }
                if ($page < $totalPages) {
                    echo '<a href="?page=' . ($page + 1) . '" class="page-btn">下一页</a>';
                }
                ?>
            </div>
        </main>
    </div>

    <!-- 添加/编辑游戏弹窗 -->
    <div id="game-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">添加游戏</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="game-form" onsubmit="saveGame(event)">
                <input type="hidden" id="game-id" name="id">
                <div class="form-group">
                    <label>游戏名称 *</label>
                    <input type="text" id="game-name" name="name" required>
                </div>
                <div class="form-group">
                    <label>分类</label>
                    <select id="game-category" name="category">
                        <option value="">请选择</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['name']); ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>游戏路径 *</label>
                    <input type="text" id="game-path" name="path" required placeholder="game/example/index.html">
                </div>
                <div class="form-group">
                    <label>预览图路径</label>
                    <input type="text" id="game-preview" name="preview" placeholder="game/example/preview.jpg">
                </div>
                <div class="form-group">
                    <label>游戏描述</label>
                    <textarea id="game-hint" name="hint" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>屏幕方向</label>
                    <select id="game-orientation" name="orientation">
                        <option value="0">横屏</option>
                        <option value="1">竖屏</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>排序值</label>
                    <input type="number" id="game-sort" name="sort" value="0">
                    <small>999=热门，998=最新</small>
                </div>
                <div class="form-group">
                    <label>状态</label>
                    <select id="game-status" name="status">
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
            document.getElementById('modal-title').textContent = '添加游戏';
            document.getElementById('game-form').reset();
            document.getElementById('game-id').value = '';
            document.getElementById('game-modal').classList.add('active');
        }

        function editGame(game) {
            document.getElementById('modal-title').textContent = '编辑游戏';
            document.getElementById('game-id').value = game.id;
            document.getElementById('game-name').value = game.name;
            document.getElementById('game-category').value = game.category || '';
            document.getElementById('game-path').value = game.path;
            document.getElementById('game-preview').value = game.preview || '';
            document.getElementById('game-hint').value = game.hint || '';
            document.getElementById('game-orientation').value = game.orientation || 0;
            document.getElementById('game-sort').value = game.sort || 0;
            document.getElementById('game-status').value = game.status;
            document.getElementById('game-modal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('game-modal').classList.remove('active');
        }

        async function saveGame(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            const isEdit = data.id !== '';

            try {
                const response = await fetch('../api/games.php?action=' + (isEdit ? 'update' : 'add'), {
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

        async function deleteGame(id) {
            if (!confirm('确定要删除这个游戏吗？')) return;

            try {
                const response = await fetch('../api/games.php?action=delete&id=' + id, {
                    method: 'DELETE'
                });
                const result = await response.json();

                if (result.code === 200) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('删除失败：' + error.message);
            }
        }
    </script>
</body>
</html>

