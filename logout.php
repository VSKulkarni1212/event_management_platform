<?php
require 'includes/config.php';
require 'includes/session.php';

// Initialize session first
initSecureSession();

// Destroy session
destroySession();

// Redirect to login
header("Location: index.php");
exit;
?>