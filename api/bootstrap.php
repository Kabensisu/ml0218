<?php
/**
 * API 统一引导文件（PHP 8.1 稳定版）
 */

// ================= 基础设置 =================
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

// ================= Session 统一配置 =================
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

session_start();

// ================= 统一 JSON 输出 =================
function jsonResponse($data = null, int $code = 200, string $message = 'ok'): never {
    http_response_code($code);
    echo json_encode([
        'code'    => $code,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ================= 登录校验 =================
function requireLogin(): int {
    if (empty($_SESSION['user_id'])) {
        jsonResponse(null, 401, '未登录');
    }
    return (int)$_SESSION['user_id'];
}

// ================= 数据库连接（单例） =================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=caqfmwxv;charset=utf8mb4',
            'caqfmwxv',
            'uV86oE3tu0',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        return $pdo;
    } catch (Throwable $e) {
        error_log($e->getMessage());
        jsonResponse(null, 500, '数据库连接失败');
    }
}

// ===================================================
//  权限与校验函数
// ===================================================

function requireMethod(array $methods) {
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods)) {
        jsonResponse(null, 405, '方法不允许');
    }
}

function requireAdmin() {
    $role = $_SESSION['role'] ?? '';
    if ($role !== 'admin') {
        jsonResponse(null, 403, '无权限');
    }
}

