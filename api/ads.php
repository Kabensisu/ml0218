<?php
session_start();

// 添加详细的错误报告用于调试
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // 引入公共配置和函数 - 确保路径正确
    $bootstrapPath = __DIR__ . '/bootstrap.php';
    if (!file_exists($bootstrapPath)) {
        throw new Exception("bootstrap.php 文件不存在于路径: " . $bootstrapPath);
    }
    
    require_once $bootstrapPath;

    // 获取请求方法和操作
    $method = $_SERVER['REQUEST_METHOD'];
    $action = trim($_GET['action'] ?? '');

    /* =======================
     * POST 请求处理
     * ======================= */
    if ($method === 'POST') {
        // 统一解析JSON/表单输入
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        // 新增/更新广告（需要管理员权限）
        if ($action === 'save') {
            requireAdmin();
            
            $id = intval($input['id'] ?? 0);
            $position = trim($input['position'] ?? '');
            $imageUrl = trim($input['image_url'] ?? '');
            $linkUrl = trim($input['link_url'] ?? '');
            $status = isset($input['status']) ? intval($input['status']) : 1;
            $sortOrder = isset($input['sort_order']) ? intval($input['sort_order']) : 0;

            // 参数校验
            if ($position === '' || $imageUrl === '') {
                jsonResponse(null, 400, '位置和图片地址不能为空');
            }

            $pdo = getDB();
            if ($id > 0) {
                // 更新广告
                $stmt = $pdo->prepare(
                    "UPDATE ads SET position = ?, image_url = ?, link_url = ?, status = ?, sort_order = ?, updated_at = NOW() WHERE id = ?"
                );
                $stmt->execute([$position, $imageUrl, $linkUrl, $status, $sortOrder, $id]);
                jsonResponse(['id' => $id], 200, '更新成功');
            } else {
                // 新增广告
                $stmt = $pdo->prepare(
                    "INSERT INTO ads (position, image_url, link_url, status, sort_order, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
                );
                $stmt->execute([$position, $imageUrl, $linkUrl, $status, $sortOrder]);
                $id = $pdo->lastInsertId();
                jsonResponse(['id' => $id], 200, '创建成功');
            }
        }

        // 删除广告（需要管理员权限）
        elseif ($action === 'delete') {
            requireAdmin();
            
            $id = intval($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(null, 400, 'ID参数无效');
            }

            $pdo = getDB();
            $stmt = $pdo->prepare("DELETE FROM ads WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(null, 200, '删除成功');
        }

        else {
            jsonResponse(null, 400, '无效的操作');
        }
    }

    /* =======================
     * GET 请求处理
     * ======================= */
    elseif ($method === 'GET') {
        // 获取所有广告（需要管理员权限）
        if ($action === 'list') {
            requireAdmin();
            
            $pdo = getDB();
            $stmt = $pdo->query("SELECT * FROM ads ORDER BY position ASC, sort_order ASC");
            $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse($ads);
        }

        // 获取指定位置的广告（前台可用）
        elseif ($action === 'get') {
            $position = trim($_GET['position'] ?? '');
            if ($position === '') {
                jsonResponse(null, 400, 'position参数缺失');
            }

            $pdo = getDB();
            $stmt = $pdo->prepare(
                "SELECT * FROM ads WHERE position = ? AND status = 1 ORDER BY sort_order ASC LIMIT 1"
            );
            $stmt->execute([$position]);
            $ad = $stmt->fetch(PDO::FETCH_ASSOC);
            jsonResponse($ad ?: null);
        }

        else {
            jsonResponse(null, 400, '无效的操作');
        }
    }

    else {
        jsonResponse(null, 405, '不支持的请求方法');
    }
}

catch (Exception $e) {
    error_log('广告系统错误: ' . $e->getMessage());
    error_log('堆栈跟踪: ' . $e->getTraceAsString());
    
    // 返回详细的错误信息以便调试
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'message' => '服务器内部错误: ' . $e->getMessage(),
        'data' => null,
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>