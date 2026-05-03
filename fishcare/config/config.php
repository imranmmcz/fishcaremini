<?php
/**
 * Fish Care System - Main Configuration
 */

// Site Configuration
define('SITE_NAME', 'Fish Care System');
define('SITE_NAME_BN', 'ফিশ কেয়ার সিস্টেম');
define('SITE_URL', 'http://localhost/fishcare');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Timezone
date_default_timezone_set('Asia/Dhaka');

// Language
$current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'bn';
define('CURRENT_LANG', $current_lang);

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/database.php';

/**
 * Get translations
 */
function t($key) {
    $translations = [
        'bn' => [
            'home' => 'হোম',
            'login' => 'লগইন',
            'logout' => 'লগআউট',
            'register' => 'রেজিস্টার',
            'profile' => 'প্রোফাইল',
            'dashboard' => 'ড্যাশবোর্ড',
            'settings' => 'সেটিংস',
            'save' => 'সংরক্ষণ',
            'cancel' => 'বাতিল',
            'edit' => 'সম্পাদনা',
            'delete' => 'মুছুন',
            'add' => 'যোগ করুন',
            'search' => 'অনুসন্ধান',
            'filter' => 'ফিল্টার',
            'export' => 'রপ্তানি',
            'print' => 'প্রিন্ট',
            'loading' => 'লোড হচ্ছে...',
            'no_data' => 'কোনো তথ্য পাওয়া যায়নি',
            'success' => 'সফল',
            'error' => 'ত্রুটি',
            'warning' => 'সতর্কতা',
            'confirm' => 'নিশ্চিত করুন',
            'yes' => 'হ্যাঁ',
            'no' => 'না',
            'actions' => 'কার্যক্রম',
            'status' => 'স্ট্যাটাস',
            'date' => 'তারিখ',
            'amount' => 'পরিমাণ',
            'price' => 'দাম',
            'total' => 'মোট',
            'name' => 'নাম',
            'phone' => 'ফোন',
            'email' => 'ইমেইল',
            'address' => 'ঠিকানা',
            'description' => 'বিবরণ',
            'notes' => 'নোট',
            ' ponds' => 'পুকুর',
            'fish' => 'মাছ',
            'income' => 'আয়',
            'expense' => 'ব্যয়',
            'profit' => 'লাভ',
            'loss' => 'ক্ষতি',
            'sales' => 'বিক্রয়',
            'purchase' => 'ক্রয়',
            'stock' => 'স্টক',
            'customer' => 'গ্রাহক',
            'supplier' => 'সরবরাহকারী',
            'invoice' => 'চালান',
            'report' => 'রিপোর্ট',
            'admin' => 'অ্যাডমিন',
            'farmer' => 'চাষী',
            'seller' => 'বিক্রেতা',
            'wholesaler' => 'হোলসেলার',
            'user' => 'ব্যবহারকারী',
            'welcome' => 'স্বাগতম',
            'my_profile' => 'আমার প্রোফাইল',
            'edit_profile' => 'প্রোফাইল সম্পাদনা',
            'change_password' => 'পাসওয়ার্ড পরিবর্তন',
        ],
        'en' => [
            'home' => 'Home',
            'login' => 'Login',
            'logout' => 'Logout',
            'register' => 'Register',
            'profile' => 'Profile',
            'dashboard' => 'Dashboard',
            'settings' => 'Settings',
            'save' => 'Save',
            'cancel' => 'Cancel',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'add' => 'Add',
            'search' => 'Search',
            'filter' => 'Filter',
            'export' => 'Export',
            'print' => 'Print',
            'loading' => 'Loading...',
            'no_data' => 'No data found',
            'success' => 'Success',
            'error' => 'Error',
            'warning' => 'Warning',
            'confirm' => 'Confirm',
            'yes' => 'Yes',
            'no' => 'No',
            'actions' => 'Actions',
            'status' => 'Status',
            'date' => 'Date',
            'amount' => 'Amount',
            'price' => 'Price',
            'total' => 'Total',
            'name' => 'Name',
            'phone' => 'Phone',
            'email' => 'Email',
            'address' => 'Address',
            'description' => 'Description',
            'notes' => 'Notes',
            'ponds' => 'Ponds',
            'fish' => 'Fish',
            'income' => 'Income',
            'expense' => 'Expense',
            'profit' => 'Profit',
            'loss' => 'Loss',
            'sales' => 'Sales',
            'purchase' => 'Purchase',
            'stock' => 'Stock',
            'customer' => 'Customer',
            'supplier' => 'Supplier',
            'invoice' => 'Invoice',
            'report' => 'Report',
            'admin' => 'Admin',
            'farmer' => 'Farmer',
            'seller' => 'Seller',
            'wholesaler' => 'Wholesaler',
            'user' => 'User',
            'welcome' => 'Welcome',
            'my_profile' => 'My Profile',
            'edit_profile' => 'Edit Profile',
            'change_password' => 'Change Password',
        ]
    ];

    return isset($translations[$current_lang][$key]) ? $translations[$current_lang][$key] : $key;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Redirect to login page
 */
function redirectToLogin() {
    redirect(SITE_URL . '/pages/auth/login.php');
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirectToLogin();
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    $user = getCurrentUser();

    if ($user['role'] !== $role && $user['role'] !== 'admin') {
        redirect(SITE_URL . '/index.php');
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = '৳') {
    return $currency . number_format($amount, 2);
}

/**
 * Get user role display name
 */
function getRoleName($role) {
    $roles = [
        'admin' => 'অ্যাডমিন',
        'farmer' => 'চাষী',
        'wholesaler' => 'হোলসেলার',
        'seller' => 'বিক্রেতা',
        'customer' => 'গ্রাহক'
    ];
    return $roles[$role] ?? $role;
}

/**
 * Log system activity
 */
function logActivity($action, $description = '') {
    try {
        $pdo = getDBConnection();
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO activities (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $description, $ipAddress]);
    } catch (Exception $e) {
        error_log("Log Activity Error: " . $e->getMessage());
    }
}
