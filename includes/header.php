<?php
/**
 * Fish Care System - Global Header
 * Includes navigation and profile dropdown
 */

require_once __DIR__ . '/../config/config.php';

// Check login status
$isLoggedIn = isLoggedIn();
$currentUser = $isLoggedIn ? getCurrentUser() : null;
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME_BN; ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive.css">

    <style>
        :root {
            --primary-color: #00BCD4;
            --secondary-color: #4CAF50;
            --accent-color: #FF9800;
            --danger-color: #f44336;
            --dark-bg: #0f172a;
            --card-bg: rgba(255, 255, 255, 0.08);
            --border-color: rgba(255, 255, 255, 0.15);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
        }

        body {
            font-family: 'Hind Siliguri', 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            color: var(--text-primary);
        }

        /* Header Styles */
        .main-header {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 0 20px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 70px;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
            height: 70px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--primary-color);
        }

        .logo-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary-color);
            background: rgba(0, 188, 212, 0.1);
        }

        /* Profile Dropdown - FIXED with high z-index */
        .profile-dropdown {
            position: relative;
            z-index: 9999 !important;
        }

        .profile-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .profile-btn:hover {
            background: rgba(0, 188, 212, 0.15);
            border-color: var(--primary-color);
        }

        .profile-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
        }

        .profile-name {
            color: white;
            font-weight: 500;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .profile-dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            min-width: 220px;
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }

        .profile-dropdown.active .profile-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .dropdown-item:hover {
            background: rgba(0, 188, 212, 0.1);
            color: var(--primary-color);
        }

        .dropdown-item.danger:hover {
            background: rgba(244, 67, 54, 0.1);
            color: var(--danger-color);
        }

        .dropdown-divider {
            height: 1px;
            background: var(--border-color);
            margin: 8px 0;
        }

        /* Main Content */
        .main-content {
            margin-top: 70px;
            padding: 30px;
            min-height: calc(100vh - 70px);
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 70px;
            left: 0;
            width: 260px;
            height: calc(100vh - 70px);
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border-color);
            padding: 20px;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu-item {
            margin-bottom: 5px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .sidebar-link:hover, .sidebar-link.active {
            background: linear-gradient(135deg, rgba(0, 188, 212, 0.2), rgba(76, 175, 80, 0.1));
            color: var(--primary-color);
            border-left: 3px solid var(--primary-color);
        }

        .sidebar-link i {
            font-size: 20px;
            width: 24px;
        }

        /* Content Wrapper */
        .content-wrapper {
            margin-left: 260px;
            padding: 30px;
        }

        /* Cards */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            backdrop-filter: blur(10px);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: white;
            margin: 0;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-family: 'Hind Siliguri', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #00acc1);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 188, 212, 0.3);
        }

        .btn-secondary {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary-color);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #d32f2f);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--secondary-color), #43a047);
            color: white;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: white;
            font-family: 'Hind Siliguri', sans-serif;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1);
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 40px;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background: rgba(0, 188, 212, 0.1);
            color: var(--primary-color);
            font-weight: 600;
            white-space: nowrap;
        }

        .data-table tbody tr {
            transition: all 0.3s;
        }

        .data-table tbody tr:hover {
            background: rgba(0, 188, 212, 0.05);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }

        .stat-icon.primary {
            background: linear-gradient(135deg, var(--primary-color), #00acc1);
        }

        .stat-icon.success {
            background: linear-gradient(135deg, var(--secondary-color), #43a047);
        }

        .stat-icon.warning {
            background: linear-gradient(135deg, var(--accent-color), #f57c00);
        }

        .stat-icon.danger {
            background: linear-gradient(135deg, var(--danger-color), #d32f2f);
        }

        .stat-content h3 {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin: 0 0 5px 0;
        }

        .stat-content p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 14px;
        }

        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.15);
            border: 1px solid var(--secondary-color);
            color: var(--secondary-color);
        }

        .alert-danger {
            background: rgba(244, 67, 54, 0.15);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
        }

        .alert-warning {
            background: rgba(255, 152, 0, 0.15);
            border: 1px solid var(--accent-color);
            color: var(--accent-color);
        }

        .alert-info {
            background: rgba(0, 188, 212, 0.15);
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(76, 175, 80, 0.2);
            color: var(--secondary-color);
        }

        .badge-warning {
            background: rgba(255, 152, 0, 0.2);
            color: var(--accent-color);
        }

        .badge-danger {
            background: rgba(244, 67, 54, 0.2);
            color: var(--danger-color);
        }

        .badge-primary {
            background: rgba(0, 188, 212, 0.2);
            color: var(--primary-color);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #1e293b;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: white;
            margin: 0;
        }

        .modal-close {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.3s;
        }

        .modal-close:hover {
            background: var(--danger-color);
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding: 20px 24px;
            border-top: 1px solid var(--border-color);
        }

        /* Pagination */
        .pagination {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 30px;
        }

        .pagination-item {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .pagination-item:hover,
        .pagination-item.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* Loading Spinner */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .content-wrapper {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                padding: 0 15px;
            }

            .nav-menu {
                display: none;
            }

            .main-content {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <?php if ($isLoggedIn): ?>
    <!-- Logged In Header -->
    <header class="main-header">
        <div class="header-content">
            <a href="<?php echo SITE_URL; ?>/pages/dashboard/<?php echo $currentUser['role']; ?>/index.php" class="logo">
                <div class="logo-icon">
                    <i class="bi bi-water"></i>
                </div>
                <span class="logo-text"><?php echo SITE_NAME_BN; ?></span>
            </a>

            <nav class="nav-menu">
                <a href="<?php echo SITE_URL; ?>/pages/dashboard/<?php echo $currentUser['role']; ?>/index.php" class="nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                    <i class="bi bi-house"></i> ড্যাশবোর্ড
                </a>

                <!-- Profile Dropdown - FIXED z-index issue -->
                <div class="profile-dropdown" id="profileDropdown">
                    <button class="profile-btn" onclick="toggleProfileDropdown()">
                        <div class="profile-avatar">
                            <?php echo isset($currentUser['name_bn']) ? mb_substr($currentUser['name_bn'], 0, 1) : 'U'; ?>
                        </div>
                        <span class="profile-name"><?php echo isset($currentUser['name_bn']) ? $currentUser['name_bn'] : 'User'; ?></span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <div class="profile-dropdown-menu">
                        <a href="<?php echo SITE_URL; ?>/pages/dashboard/<?php echo $currentUser['role']; ?>/profile.php" class="dropdown-item">
                            <i class="bi bi-person"></i>
                            আমার প্রোফাইল
                        </a>
                        <a href="<?php echo SITE_URL; ?>/pages/dashboard/<?php echo $currentUser['role']; ?>/settings.php" class="dropdown-item">
                            <i class="bi bi-gear"></i>
                            সেটিংস
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo SITE_URL; ?>/pages/auth/logout.php" class="dropdown-item danger">
                            <i class="bi bi-box-arrow-right"></i>
                            লগআউট
                        </a>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <script>
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>
    <?php endif; ?>
