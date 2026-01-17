<?php
/**
 * 后台管理首页
 */
session_start();

// 检查登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../db/config.php';
$db = getDB();

// 获取统计数据
$stats = [
    'total_games' => $db->query("SELECT COUNT(*) FROM games WHERE status = 1")->fetchColumn(),
    'total_users' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'total_categories' => $db->query("SELECT COUNT(*) FROM categories WHERE status = 1")->fetchColumn(),
    'today_plays' => $db->query("SELECT SUM(play_count) FROM games")->fetchColumn() ?: 0
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - 游戏平台</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- 侧边栏 -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>后台管理</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item active">
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

        <!-- 主内容区 -->
        <main class="main-content">
            <header class="content-header">
                <h1>数据统计</h1>
                <div class="user-info">
                    欢迎，<?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🎮</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_games']; ?></div>
                        <div class="stat-label">游戏总数</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">用户总数</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📁</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_categories']; ?></div>
                        <div class="stat-label">分类总数</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['today_plays']); ?></div>
                        <div class="stat-label">总游玩次数</div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

