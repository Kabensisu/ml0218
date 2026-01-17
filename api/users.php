<?php
/**
 * 用户管理API
 */
session_start();
require_once '../db/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = getDB();
    
    switch ($method) {
        case 'PUT':
            checkAdmin();
            
            if ($action === 'update') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = intval($_GET['id'] ?? 0);
                $status = intval($input['status'] ?? 1);
                
                if ($id <= 0) {
                    jsonResponse(null, 400, '用户ID无效');
                }
                
                $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
                
                jsonResponse(null, 200, '更新成功');
            }
            break;
            
        default:
            jsonResponse(null, 405, '方法不允许');
    }
    
} catch (Exception $e) {
    jsonResponse(null, 500, '服务器错误: ' . $e->getMessage());
}

