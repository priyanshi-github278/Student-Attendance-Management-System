<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';