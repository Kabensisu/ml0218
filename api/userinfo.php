<?php
/**
 * 获取当前用户信息
 */
require_once '../db/config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    jsonResponse(null, 401, '未登录');
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, nickname, email, avatar, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(null, 404, '用户不存在');
    }
    
    jsonResponse($user);
    
} catch (Exception $e) {
    jsonResponse(null, 500, '服务器错误: ' . $e->getMessage());
}

