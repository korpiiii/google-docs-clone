<?php
require_once '../includes/config.php';

session_start();
session_unset();
session_destroy();

redirect('users/login.php');
?>
