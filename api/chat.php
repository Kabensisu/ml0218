<?php
/**
 * 聊天接口
 */

require_once __DIR__ . '/bootstrap.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {

    // ================== GET ==================
    if ($method === 'GET') {

        requireMethod(['GET']);

        // 获取聊天记录
        $limit = intval($_GET['limit'] ?? 50);
        if ($limit <= 0 || $limit > 200) {
            $limit = 50;
        }

        $stmt = $db->prepare("
            SELECT 
                c.*,
                u.username,
                u.nickname,
                u.avatar
            FROM chat_messages c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.game_id = 0
            ORDER BY c.created_at ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $messages = $stmt->fetchAll();

        jsonResponse($messages);
    }

    // ================== POST ==================
    if ($method === 'POST') {

        requireMethod(['POST']);
        requireLogin();

        if ($action !== 'send') {
            jsonResponse(null, 400, '非法操作');
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $message = trim($input['message'] ?? '');

        if ($message === '') {
            jsonResponse(null, 400, '消息内容不能为空');
        }

        if (mb_strlen($message) > 500) {
            jsonResponse(null, 400, '消息不能超过 500 字');
        }

        $userId = $_SESSION['user_id'];
        $username = $_SESSION['username'] ?? '游客';

        $stmt = $db->prepare("
            INSERT INTO chat_messages (game_id, user_id, username, message, created_at)
            VALUES (0, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $username, $message]);

        $messageId = $db->lastInsertId();

        $stmt = $db->prepare("
            SELECT 
                c.*,
                u.username,
                u.nickname,
                u.avatar
            FROM chat_messages c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$messageId]);
        $newMessage = $stmt->fetch();

        jsonResponse($newMessage, 200, '发送成功');
    }

    jsonResponse(null, 405, '方法不允许');

} catch (Throwable $e) {
    jsonResponse(null, 500, '服务器错误');
}