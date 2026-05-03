<?php
/**
 * Fish Care System - Logout Script
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Destroy session and logout
session_unset();
session_destroy();

// Redirect to login page
redirect(SITE_URL . '/pages/auth/login.php');
