<?php
/**
 * 分类管理API
 */
session_start();
require_once '../db/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                $stmt = $db->query("SELECT * FROM categories WHERE status = 1 ORDER BY sort ASC");
                $categories = $stmt->fetchAll();
                jsonResponse($categories);
            }
            break;
            
        case 'POST':
            checkAdmin();
            
            if ($action === 'add') {
                $input = json_decode(file_get_contents('php://input'), true);
                $name = trim($input['name'] ?? '');
                $sort = intval($input['sort'] ?? 0);
                $status = intval($input['status'] ?? 1);
                
                if (empty($name)) {
                    jsonResponse(null, 400, '分类名称不能为空');
                }
                
                // 检查是否已存在
                $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$name]);
                if ($stmt->fetch()) {
                    jsonResponse(null, 400, '分类名称已存在');
                }
                
                $stmt = $db->prepare("INSERT INTO categories (name, sort, status) VALUES (?, ?, ?)");
                $stmt->execute([$name, $sort, $status]);
                
                jsonResponse(['id' => $db->lastInsertId()], 200, '添加成功');
            }
            break;
            
        case 'PUT':
            checkAdmin();
            
            if ($action === 'update') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = intval($input['id'] ?? 0);
                $name = trim($input['name'] ?? '');
                $sort = intval($input['sort'] ?? 0);
                $status = intval($input['status'] ?? 1);
                
                if ($id <= 0 || empty($name)) {
                    jsonResponse(null, 400, '参数错误');
                }
                
                // 检查名称是否与其他分类重复
                $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
                $stmt->execute([$name, $id]);
                if ($stmt->fetch()) {
                    jsonResponse(null, 400, '分类名称已存在');
                }
                
                $stmt = $db->prepare("UPDATE categories SET name = ?, sort = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $sort, $status, $id]);
                
                jsonResponse(null, 200, '更新成功');
            }
            break;
            
        default:
            jsonResponse(null, 405, '方法不允许');
    }
    
} catch (Exception $e) {
    jsonResponse(null, 500, '服务器错误: ' . $e->getMessage());
}

