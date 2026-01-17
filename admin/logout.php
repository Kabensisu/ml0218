<?php
/**
 * 后台退出登录
 */
session_start();
session_destroy();
header('Location: login.php');
exit;

