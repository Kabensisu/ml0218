<?php
require_once __DIR__ . '/bootstrap.php';

// 只允许 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(null, 405, '只支持 POST 请求');
}

// 读取 JSON
$input = json_decode(file_get_contents('php://input'), true);

$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');
$nickname = trim($input['nickname'] ?? '');
$email    = trim($input['email'] ?? '');

// 基础校验
if ($username === '' || $password === '') {
    jsonResponse(null, 400, '用户名和密码不能为空');
}

if (strlen($username) < 3 || strlen($username) > 20) {
    jsonResponse(null, 400, '用户名长度应在3-20个字符之间');
}

if (strlen($password) < 6) {
    jsonResponse(null, 400, '密码长度不能少于6个字符');
}

// 邮箱格式校验
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(null, 400, '邮箱格式不正确');
}

try {
    $db = getDB();

    // 检查用户名是否已存在
    $stmt = $db->prepare('SELECT id FROM users WHERE username = :u');
    $stmt->execute(['u' => $username]);
    if ($stmt->fetch()) {
        jsonResponse(null, 409, '用户名已存在');
    }

    // 检查邮箱是否已存在（如果提供）
    if ($email !== '') {
        $stmt = $db->prepare('SELECT id FROM users WHERE email = :e');
        $stmt->execute(['e' => $email]);
        if ($stmt->fetch()) {
            jsonResponse(null, 409, '邮箱已被注册');
        }
    }

    // 加密密码
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 插入用户
    $stmt = $db->prepare(
        'INSERT INTO users (username, password, nickname, email)
         VALUES (:u, :p, :n, :e)'
    );
    $stmt->execute([
        'u' => $username,
        'p' => $hashedPassword,
        'n' => $nickname !== '' ? $nickname : $username,
        'e' => $email !== '' ? $email : null
    ]);

    $userId = $db->lastInsertId();

    jsonResponse([
        'id'       => $userId,
        'username' => $username,
        'nickname' => $nickname !== '' ? $nickname : $username
    ], 200, '注册成功');

} catch (Throwable $e) {
    jsonResponse(null, 500, '注册失败');
}