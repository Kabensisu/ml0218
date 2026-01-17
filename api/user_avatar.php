<?php
/**
 * 用户头像上传接口
 */

require_once __DIR__ . '/bootstrap.php';

// ===== 接口规范 =====
requireMethod(['POST']);
requireLogin();

// ===== 文件存在性校验 =====
if (
    !isset($_FILES['avatar']) ||
    $_FILES['avatar']['error'] !== UPLOAD_ERR_OK
) {
    jsonResponse(null, 400, '文件上传失败');
}

$file = $_FILES['avatar'];

// ===== 基础限制 =====
$maxSize = 2 * 1024 * 1024; // 2MB
$allowedMime = ['image/jpeg', 'image/png', 'image/gif'];
$allowedExt  = ['jpg', 'jpeg', 'png', 'gif'];

if ($file['size'] > $maxSize) {
    jsonResponse(null, 400, '文件大小不能超过 2MB');
}

// ===== MIME 校验（防伪造）=====
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$realMime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($realMime, $allowedMime)) {
    jsonResponse(null, 400, '非法图片类型');
}

// ===== 扩展名校验 =====
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt)) {
    jsonResponse(null, 400, '非法文件扩展名');
}

try {
    // ===== 上传目录 =====
    $uploadRoot = realpath(__DIR__ . '/../uploads');
    if ($uploadRoot === false) {
        jsonResponse(null, 500, '上传目录不存在');
    }

    $avatarDir = $uploadRoot . '/avatars';
    if (!is_dir($avatarDir)) {
        mkdir($avatarDir, 0755, true);
    }

    // ===== 生成安全文件名 =====
    $userId = intval($_SESSION['user_id']);
    $filename = sprintf(
        'avatar_%d_%s.%s',
        $userId,
        bin2hex(random_bytes(8)),
        $ext
    );

    $absolutePath = $avatarDir . '/' . $filename;
    $relativePath = 'uploads/avatars/' . $filename;

    // ===== 移动文件 =====
    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        jsonResponse(null, 500, '文件保存失败');
    }

    // ===== 数据库处理 =====
    $db = getDB();

    // 旧头像
    $stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $oldAvatar = $stmt->fetchColumn();

    // 更新新头像
    $stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt->execute([$relativePath, $userId]);

    // 删除旧头像（安全限制在 uploads 内）
    if ($oldAvatar) {
        $oldPath = realpath(__DIR__ . '/../' . $oldAvatar);
        if ($oldPath && strpos($oldPath, $uploadRoot) === 0 && file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }

    jsonResponse(['avatar' => $relativePath], 200, '头像上传成功');

} catch (Throwable $e) {
    jsonResponse(null, 500, '服务器错误');
}