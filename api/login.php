<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(null, 405, '只支持 POST');
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

if ($username === '' || $password === '') {
    jsonResponse(null, 400, '用户名或密码不能为空');
}

$pdo = getDB();

$stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    jsonResponse(null, 401, '用户名或密码错误');
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];

jsonResponse([
    'user_id' => $user['id'],
    'username' => $user['username']
], 200, '登录成功');