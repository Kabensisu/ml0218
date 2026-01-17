<?php
/**
 * user_cache.php
 * 全站统一云存档接口 - 增强容错版
 * - 统一 JSON 格式，永远返回 200 避免前端炸红
 * - 100% 兼容现有 app.js
 * - 软登录校验（登录过程不报错）
 * - 保留所有原版功能（列出、删除、清空）
 */

require_once __DIR__ . '/bootstrap.php';

// ================= Session 安全配置 =================
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// ================= 永远返回 200 的 JSON 输出 =================
function safeJson($data = null, $code = 200, $message = 'ok') {
    http_response_code(200); // 永远 200，前端不炸
    echo json_encode([
        'code'    => $code,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ================= 软登录校验（不会 exit） =================
function softRequireLogin() {
    if (empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
        return null;
    }
    return [
        'id' => (int)$_SESSION['user_id'],
        'name' => $_SESSION['username']
    ];
}

// ================= 基础配置 =================
define('USER_CACHES_DIR', __DIR__ . '/user_caches/');

// ================= 确保存储目录存在 =================
if (!file_exists(USER_CACHES_DIR)) {
    @mkdir(USER_CACHES_DIR, 0755, true);
}

// ================= 防止 JSON 被直接访问 =================
$htaccess = USER_CACHES_DIR . '.htaccess';
if (!file_exists($htaccess)) {
    @file_put_contents($htaccess, <<<HT
<FilesMatch "\.json$">
Order allow,deny
Deny from all
</FilesMatch>
HT);
}

// ================= 工具函数 =================
function loadUserCache($file) {
    if (!file_exists($file)) {
        return null;
    }
    
    $content = file_get_contents($file);
    if ($content === false) {
        return null;
    }
    
    $json = json_decode($content, true);
    return is_array($json) ? $json : null;
}

function saveUserCache($file, $uid, $data) {
    $payload = [
        'uid' => $uid,
        'updated_at' => time(),
        'data' => $data
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }

    /**
     * 加独占锁，防止并发覆盖
     * LOCK_EX + 原子写入
     */
    $result = @file_put_contents(
        $file,
        $json,
        LOCK_EX
    );

    return $result !== false;
}

function getRequestBody() {
    $input = @json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }
    return $input;
}

// ================= 主逻辑 =================
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$userInfo = softRequireLogin();

// 统一用户文件命名：每个用户一个文件
$uid = $userInfo ? $userInfo['id'] : 0;
$cacheFile = USER_CACHES_DIR . $uid . '.json';

/* =============================== 
 * GET 请求处理
 * =============================== */
if ($method === 'GET') {
    
    // 1. 原版：拉取整个云存档
    if ($action === '') {
        if (!$uid) {
            safeJson(null, 200, '未登录，跳过缓存');
        }
        
        if (!file_exists($cacheFile)) {
            safeJson(null, 200, '无云存档');
        }

        $cache = loadUserCache($cacheFile);
        if ($cache === null) {
            safeJson(null, 200, '读取云存档失败');
        }
        
        safeJson($cache, 200, 'ok');
    }
    
    // 2. 原版：列出所有缓存 key
    elseif ($action === 'list_cache') {
        if (!$uid) {
            safeJson([], 200, '未登录，跳过缓存');
        }
        
        if (!file_exists($cacheFile)) {
            safeJson([], 200, '无缓存');
        }

        $cache = loadUserCache($cacheFile);
        if ($cache === null || !isset($cache['data'])) {
            safeJson([], 200, '无缓存');
        }
        
        $keys = array_keys($cache['data']);
        safeJson($keys, 200, 'ok');
    }
    
    // 3. 修改版：按 key 获取单个缓存值
    elseif ($action === 'get_cache') {
        if (!$uid) {
            safeJson(null, 200, '未登录，跳过缓存');
        }
        
        $key = trim($_GET['key'] ?? '');
        if ($key === '') {
            safeJson(null, 200, 'key 为空，跳过');
        }
        
        if (!file_exists($cacheFile)) {
            safeJson(null, 200, '无缓存');
        }

        $cache = loadUserCache($cacheFile);
        if ($cache === null || !isset($cache['data'][$key])) {
            safeJson(null, 200, '无缓存');
        }
        
        safeJson([
            'value' => $cache['data'][$key],
            'updated_at' => $cache['updated_at'] ?? null
        ], 200, 'ok');
    }
}

/* =============================== 
 * POST 请求处理
 * =============================== */
elseif ($method === 'POST') {
    
    if (!$uid) {
        safeJson(null, 200, '未登录，跳过写入');
    }
    
    $input = getRequestBody();
    
    // 1. 原版：删除指定 key
    if ($action === 'delete') {
        $key = $input['key'] ?? '';
        if (!$key) {
            safeJson(null, 200, '缺少 key 参数');
        }

        if (!file_exists($cacheFile)) {
            safeJson(null, 200, '缓存不存在');
        }

        $cache = loadUserCache($cacheFile);
        if ($cache === null || !isset($cache['data'][$key])) {
            safeJson(null, 200, 'key 不存在');
        }

        unset($cache['data'][$key]);

        // 如果数据为空，删除文件
        if (empty($cache['data'])) {
            @unlink($cacheFile);
            safeJson(true, 200, '缓存已清空');
        }

        $success = saveUserCache($cacheFile, $uid, $cache['data']);
        if (!$success) {
            safeJson(null, 200, '删除缓存失败');
        }
        
        safeJson(true, 200, '缓存已删除');
    }
    
    // 2. 原版：清空当前用户所有缓存
    elseif ($action === 'clear_all') {
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
        safeJson(true, 200, '所有云存档已清除');
    }
    
    // 3. 修改版：按 key 写入单个值
    else {
        $key = trim($input['key'] ?? '');
        $value = $input['value'] ?? null;
        
        // 4. 原版：整体写入云存档（兼容原版数据结构）
        if ($key === '' && isset($input['data'])) {
            $data = $input['data'] ?? [];
            if (!is_array($data)) {
                safeJson(null, 200, '数据格式错误，跳过');
            }
            
            $success = saveUserCache($cacheFile, $uid, $data);
            safeJson($success, 200, $success ? '云存档已保存' : '保存失败');
        }
        
        // 修改版：按 key 写入
        elseif ($key !== '' && $value !== null) {
            $cache = loadUserCache($cacheFile);
            if ($cache === null) {
                $cacheData = [$key => $value];
            } else {
                $cacheData = $cache['data'] ?? [];
                $cacheData[$key] = $value;
            }
            
            $success = saveUserCache($cacheFile, $uid, $cacheData);
            safeJson($success, 200, $success ? '保存成功' : '保存失败');
        }
        
        else {
            safeJson(null, 200, '参数不完整，跳过');
        }
    }
}

/* =============================== 
 * 兜底响应
 * =============================== */
safeJson(null, 200, 'noop');