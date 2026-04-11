<?php

/**
 * StegaVault - Collaborator Logout
 * File: collaborator/logout.php
 */

session_start();
session_destroy();

header('Location: login.php');
exit;
