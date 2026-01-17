<?php
/**
 * 更新用户信息接口
 */
session_start();
require_once '../db/config.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(null, 401, '未登录');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(null, 405, '方法不允许');
}

$input = json_decode(file_get_contents('php://input'), true);
$nickname = trim($input['nickname'] ?? '');
$email = trim($input['email'] ?? '');

try {
    $db = getDB();
    
    // 检查邮箱是否已被其他用户使用
    if (!empty($email)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            jsonResponse(null, 400, '邮箱已被使用');
        }
    }
    
    // 更新用户信息
    $stmt = $db->prepare("UPDATE users SET nickname = ?, email = ? WHERE id = ?");
    $stmt->execute([$nickname ?: null, $email ?: null, $_SESSION['user_id']]);
    
    jsonResponse(null, 200, '更新成功');
    
} catch (Exception $e) {
    jsonResponse(null, 500, '服务器错误: ' . $e->getMessage());
}

