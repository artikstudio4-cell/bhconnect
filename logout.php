<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/AuthModel.php';

session_start();

$auth = new AuthModel();
$auth->logout();

header('Location: login.php');
exit;
