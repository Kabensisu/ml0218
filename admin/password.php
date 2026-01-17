<?php
/**
 * 修改密码页面
 */
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../db/config.php';
    
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = '所有字段都不能为空';
    } elseif ($new_password !== $confirm_password) {
        $error = '两次输入的新密码不一致';
    } elseif (strlen($new_password) < 6) {
        $error = '新密码长度不能少于6个字符';
    } else {
        try {
            $db = getDB();
            
            // 验证旧密码
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($old_password, $user['password'])) {
                $error = '旧密码错误';
            } else {
                // 更新密码
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['admin_id']]);
                
                $success = '密码修改成功！';
            }
        } catch (Exception $e) {
            $error = '修改失败：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密码 - 后台管理</title>
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
                <a href="ads.php" class="nav-item">
                    <span class="nav-icon">📢</span>
                    <span>广告管理</span>
                </a>
                <a href="password.php" class="nav-item active">
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
                <h1>修改密码</h1>
            </header>

            <div class="form-container">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" class="password-form">
                    <div class="form-group">
                        <label>当前密码 *</label>
                        <input type="password" name="old_password" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label>新密码 *</label>
                        <input type="password" name="new_password" required minlength="6" placeholder="至少6个字符">
                        <small class="form-hint">密码长度不能少于6个字符</small>
                    </div>
                    
                    <div class="form-group">
                        <label>确认新密码 *</label>
                        <input type="password" name="confirm_password" required minlength="6" placeholder="再次输入新密码">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">修改密码</button>
                        <a href="index.php" class="btn-cancel">取消</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>

