<?php
/**
 * 游戏管理API
 */
session_start();
require_once '../db/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            // 获取游戏列表
            if ($action === 'list') {
                $category = $_GET['category'] ?? '';
                $search = $_GET['search'] ?? '';
                $page = intval($_GET['page'] ?? 1);
                $pageSize = intval($_GET['pageSize'] ?? 20);
                $sort = $_GET['sort'] ?? 'default';
                $offset = ($page - 1) * $pageSize;
                
                $where = ["status = 1"];
                $params = [];
                
                if (!empty($category) && $category !== '首页' && $category !== '全部') {
                    $where[] = "category = ?";
                    $params[] = $category;
                }
                
                if (!empty($search)) {
                    $where[] = "(name LIKE ? OR hint LIKE ?)";
                    $searchParam = "%{$search}%";
                    $params[] = $searchParam;
                    $params[] = $searchParam;
                }
                
                $whereClause = implode(' AND ', $where);
                
                // 获取总数
                $countSql = "SELECT COUNT(*) as total FROM games WHERE {$whereClause}";
                $countStmt = $db->prepare($countSql);
                $countStmt->execute($params);
                $total = $countStmt->fetch()['total'];
                
                // 根据排序参数确定排序方式
                $orderBy = "ORDER BY sort DESC, id DESC"; // 默认排序
                if ($sort === 'hot') {
                    // 按热度排序：游玩次数降序，然后按排序字段，最后按ID
                    $orderBy = "ORDER BY COALESCE(play_count, 0) DESC, sort DESC, id DESC";
                } elseif ($sort === 'new') {
                    // 按添加时间排序：添加时间越新，排名越靠前
                    $orderBy = "ORDER BY created_at DESC, id DESC";
                }
                
                // 获取列表
                $sql = "SELECT * FROM games WHERE {$whereClause} {$orderBy} LIMIT ? OFFSET ?";
                $params[] = $pageSize;
                $params[] = $offset;
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $games = $stmt->fetchAll();
                
                jsonResponse([
                    'list' => $games,
                    'total' => $total,
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'totalPages' => ceil($total / $pageSize)
                ]);
            }
            
            // 获取单个游戏
            elseif ($action === 'get') {
                $id = intval($_GET['id'] ?? 0);
                if ($id <= 0) {
                    jsonResponse(null, 400, '游戏ID无效');
                }
                
                $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
                $stmt->execute([$id]);
                $game = $stmt->fetch();
                
                if (!$game) {
                    jsonResponse(null, 404, '游戏不存在');
                }
                
                jsonResponse($game);
            }
            
            // 获取分类列表
            elseif ($action === 'categories') {
                $stmt = $db->query("SELECT * FROM categories WHERE status = 1 ORDER BY sort ASC");
                $categories = $stmt->fetchAll();
                jsonResponse($categories);
            }
            
            break;
            
        case 'POST':
            // 需要管理员权限
            checkAdmin();
            
            // 添加游戏
            if ($action === 'add') {
                $input = json_decode(file_get_contents('php://input'), true);
                
                $name = trim($input['name'] ?? '');
                $category = trim($input['category'] ?? '');
                $path = trim($input['path'] ?? '');
                $preview = trim($input['preview'] ?? '');
                $hint = trim($input['hint'] ?? '');
                $orientation = intval($input['orientation'] ?? 0);
                $sort = intval($input['sort'] ?? 0);
                
                if (empty($name) || empty($path)) {
                    jsonResponse(null, 400, '游戏名称和路径不能为空');
                }
                
                $stmt = $db->prepare("INSERT INTO games (name, category, path, preview, hint, orientation, sort) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $category, $path, $preview, $hint, $orientation, $sort]);
                
                $gameId = $db->lastInsertId();
                jsonResponse(['id' => $gameId], 200, '添加成功');
            }
            
            break;
            
        case 'PUT':
            // 需要管理员权限
            checkAdmin();
            
            // 更新游戏
            if ($action === 'update') {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = intval($input['id'] ?? 0);
                
                if ($id <= 0) {
                    jsonResponse(null, 400, '游戏ID无效');
                }
                
                $name = trim($input['name'] ?? '');
                $category = trim($input['category'] ?? '');
                $path = trim($input['path'] ?? '');
                $preview = trim($input['preview'] ?? '');
                $hint = trim($input['hint'] ?? '');
                $orientation = intval($input['orientation'] ?? 0);
                $sort = intval($input['sort'] ?? 0);
                $status = intval($input['status'] ?? 1);
                
                if (empty($name) || empty($path)) {
                    jsonResponse(null, 400, '游戏名称和路径不能为空');
                }
                
                $stmt = $db->prepare("UPDATE games SET name = ?, category = ?, path = ?, preview = ?, hint = ?, orientation = ?, sort = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $category, $path, $preview, $hint, $orientation, $sort, $status, $id]);
                
                jsonResponse(null, 200, '更新成功');
            }
            
            break;
            
        case 'DELETE':
            // 需要管理员权限
            checkAdmin();
            
            // 删除游戏
            if ($action === 'delete') {
                $id = intval($_GET['id'] ?? 0);
                
                if ($id <= 0) {
                    jsonResponse(null, 400, '游戏ID无效');
                }
                
                // 软删除（更新状态）
                $stmt = $db->prepare("UPDATE games SET status = 0 WHERE id = ?");
                $stmt->execute([$id]);
                
                jsonResponse(null, 200, '删除成功');
            }
            
            break;
            
        default:
            jsonResponse(null, 405, '方法不允许');
    }
    
} catch (Exception $e) {
    jsonResponse(null, 500, '服务器错误: ' . $e->getMessage());
}

