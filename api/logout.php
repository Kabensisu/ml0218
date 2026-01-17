<?php
/**
 * 用户登出接口
 */
require_once __DIR__ . '/bootstrap.php';

session_destroy();
jsonResponse(null, 200, '已退出登录');

