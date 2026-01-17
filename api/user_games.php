<?php
/**
 * 用户游戏记录接口
 */
session_start();
require_once '../db/config.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(null, 401, '未登录');
}

$action = $_GET['action'] ?? 'list';

try {
    $db = getDB();
    
    if ($action === 'list') {
        // 获取用户玩过的游戏
        $stmt = $db->prepare("
            SELECT g.*, ug.play_count, ug.last_play_time 
            FROM user_games ug
            INNER JOIN games g ON ug.game_id = g.id
            WHERE ug.user_id = ? AND g.status = 1
            ORDER BY ug.last_play_time DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $games = $stmt->fetchAll();
        
        jsonResponse($games);
    } elseif ($action === 'add') {
        // 记录游戏游玩
        $input = json_decode(file_get_contents('php://input'), true);
        $gameId = intval($input['game_id'] ?? 0);
        
        if ($gameId <= 0) {
            jsonResponse(null, 400, '游戏ID无效');
        }
        
        // 检查是否已存在记录
        $stmt = $db->prepare("SELECT id, play_count FROM user_games WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$_SESSION['user_id'], $gameId]);
        $record = $stmt->fetch();
        
        if ($record) {
            // 更新游玩次数
            $stmt = $db->prepare("UPDATE user_games SET play_count = play_count + 1, last_play_time = NOW() WHERE id = ?");
            $stmt->execute([$record['id']]);
        } else {
            // 创建新记录
            $stmt = $db->prepare("INSERT INTO user_games (user_id, game_id, play_count) VALUES (?, ?, 1)");
            $stmt->execute([$_SESSION['user_id'], $gameId]);
        }
        
        // 更新游戏总游玩次数
        $stmt = $db->prepare("UPDATE games SET play_count = play_count + 1 WHERE id = ?");
        $stmt->execute([$gameId]);
        
        jsonResponse(null, 200, '记录成功');
    }
    
} catch (Exception $e) {
    jsonResponse(null, 500, '服务器错误: ' . $e->getMessage());
}

