<?php
/**
 * 用户管理页面
 */
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../db/config.php';
$db = getDB();

// 获取用户列表
$page = intval($_GET['page'] ?? 1);
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

$total = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$users = $db->query("SELECT id, username, nickname, email, status, created_at FROM users WHERE role = 'user' ORDER BY id DESC LIMIT $pageSize OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - 后台管理</title>
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
                <a href="users.php" class="nav-item active">
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
                <h1>用户管理</h1>
            </header>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>昵称</th>
                            <th>邮箱</th>
                            <th>状态</th>
                            <th>注册时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['nickname'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                            <td>
                                <span class="status-badge <?php echo $user['status'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $user['status'] ? '正常' : '禁用'; ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button class="btn-edit" onclick="toggleUserStatus(<?php echo $user['id']; ?>, <?php echo $user['status']; ?>)">
                                    <?php echo $user['status'] ? '禁用' : '启用'; ?>
                                </button>
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

    <script>
        async function toggleUserStatus(userId, currentStatus) {
            const newStatus = currentStatus ? 0 : 1;
            const action = newStatus ? '启用' : '禁用';
            
            if (!confirm(`确定要${action}这个用户吗？`)) return;

            try {
                const response = await fetch('../api/users.php?action=update&id=' + userId, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: newStatus })
                });
                const result = await response.json();

                if (result.code === 200) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('操作失败：' + error.message);
            }
        }
    </script>
</body>
</html>

