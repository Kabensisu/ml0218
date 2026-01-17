<?php
/**
 * 修改用户密码接口
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
$old_password = $input['old_password'] ?? '';
$new_password = $input['new_password'] ?? '';

if (empty($old_password) || empty($new_password)) {
    jsonResponse(null, 400, '密码不能为空');
}

if (strlen($new_password) < 6) {
    jsonResponse(null, 400, '新密码长度不能少于6个字符');
}

try {
    $db = getDB();
    
    // 验证旧密码
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($old_password, $user['password'])) {
        jsonResponse(null, 401, '当前密码错误');
    }
    
    // 更新密码
    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
    
    jsonResponse(null, 200, '密码修改成功');
    
} catch (Exception $e) {
    jsonResponse(null, 500, '服务器错误: ' . $e->getMessage());
}

