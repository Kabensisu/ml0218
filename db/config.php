<?php

/**
 * config.php
 *
 * 仅负责：
 * - 数据库配置
 * - 数据库连接（PDO）
 *
 * 不处理 session
 * 不处理权限
 * 不输出 JSON
 */

/**
 * 数据库配置文件
 */

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'caqfmwxv');
define('DB_USER', 'caqfmwxv');
define('DB_PASS', 'uV86oE3tu0'); // 根据你的phpstudy配置修改
define('DB_CHARSET', 'utf8mb4');

// 时区设置
date_default_timezone_set('Asia/Shanghai');

/**
 * 获取数据库连接
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

/**
 * JSON响应
 */
function jsonResponse($data, $code = 200, $message = 'success') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 检查用户是否登录
 */
function checkLogin() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(null, 401, '未登录');
    }
    return $_SESSION['user_id'];
}

/**
 * 检查是否为管理员
 */
function checkAdmin() {
    session_start();
    // 支持两种session变量名（前台和后台可能使用不同的变量名）
    $userId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
    $role = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? null;
    
    if (!$userId || $role !== 'admin') {
        jsonResponse(null, 403, '无权限');
    }
    return $userId;
}

