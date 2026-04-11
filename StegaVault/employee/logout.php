<?php

/**
 * StegaVault - Employee Logout
 * File: employee/logout.php
 */

session_start();
session_destroy();

header('Location: ../index.html');
exit;
