<?php
session_start();
$_SESSION['authorized'] = null;
header('Location: ' . strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,strpos( $_SERVER["SERVER_PROTOCOL"],'/'))).'://' . $_SERVER['HTTP_HOST'] . '/login.php');
exit;