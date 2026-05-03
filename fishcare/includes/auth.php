<?php
/**
 * Fish Care System - Authentication Functions
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Login user
 */
function loginUser($username, $password) {
    try {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'ইউজারনাম বা পাসওয়ার্ড ভুল'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'অ্যাকাউন্ট সক্রিয় নেই'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'ইউজারনাম বা পাসওয়ার্ড ভুল'];
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['name_bn'];

        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$user['id']]);

        // Log activity
        logActivity('login', 'ব্যবহারকারী লগইন করেছেন');

        return ['success' => true, 'message' => 'লগইন সফল', 'redirect' => getDashboardUrl($user['role'])];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'ত্রুটি: ' . $e->getMessage()];
    }
}

/**
 * Register new user
 */
function registerUser($data) {
    try {
        $pdo = getDBConnection();

        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'ইউজারনাম ইতিমধ্যে ব্যবহৃত'];
        }

        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'ইমেইল ইতিমধ্যে ব্যবহৃত'];
        }

        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role, name_bn, name_en, phone, division_id, district_id, upazila_id, address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['username'],
            $data['email'],
            $passwordHash,
            $data['role'] ?? 'customer',
            $data['name_bn'],
            $data['name_en'] ?? '',
            $data['phone'] ?? '',
            $data['division_id'] ?? null,
            $data['district_id'] ?? null,
            $data['upazila_id'] ?? null,
            $data['address'] ?? ''
        ]);

        logActivity('register', 'নতুন ব্যবহারকারী রেজিস্টার করেছেন');

        return ['success' => true, 'message' => 'রেজিস্ট্রেশন সফল'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'ত্রুটি: ' . $e->getMessage()];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    logActivity('logout', 'ব্যবহারকারী লগআউট করেছেন');

    session_unset();
    session_destroy();

    redirect(SITE_URL . '/pages/auth/login.php');
}

/**
 * Get dashboard URL based on role
 */
function getDashboardUrl($role) {
    $urls = [
        'admin' => SITE_URL . '/pages/dashboard/admin/index.php',
        'farmer' => SITE_URL . '/pages/dashboard/farmer/index.php',
        'wholesaler' => SITE_URL . '/pages/dashboard/admin/index.php',
        'seller' => SITE_URL . '/pages/dashboard/seller/index.php',
        'customer' => SITE_URL . '/pages/dashboard/customer/index.php'
    ];
    return $urls[$role] ?? SITE_URL . '/index.php';
}

/**
 * Update user profile
 */
function updateProfile($userId, $data) {
    try {
        $pdo = getDBConnection();

        $fields = [];
        $values = [];

        $allowedFields = ['name_bn', 'name_en', 'phone', 'address', 'division_id', 'district_id', 'upazila_id'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return ['success' => false, 'message' => 'কোনো তথ্য আপডেট করা হয়নি'];
        }

        $values[] = $userId;

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        logActivity('profile_update', 'প্রোফাইল আপডেট করেছেন');

        return ['success' => true, 'message' => 'প্রোফাইল আপডেট সফল'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'ত্রুটি: ' . $e->getMessage()];
    }
}

/**
 * Change password
 */
function changePassword($userId, $oldPassword, $newPassword) {
    try {
        $pdo = getDBConnection();

        // Get current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!password_verify($oldPassword, $user['password'])) {
            return ['success' => false, 'message' => 'পুরানো পাসওয়ার্ড ভুল'];
        }

        // Update password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newHash, $userId]);

        logActivity('password_change', 'পাসওয়ার্ড পরিবর্তন করেছেন');

        return ['success' => true, 'message' => 'পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'ত্রুটি: ' . $e->getMessage()];
    }
}
