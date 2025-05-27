<?php
// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session start
session_start();

// Base URL
define('BASE_URL', 'http://localhost/google_docs_clone');

// Timezone
date_default_timezone_set('UTC');
?>
