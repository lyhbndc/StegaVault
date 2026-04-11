<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'setup';
$_SESSION['user_id'] = 3; // admin id
$_SESSION['email'] = 'test@test.com';

require_once 'api/mfa.php';
