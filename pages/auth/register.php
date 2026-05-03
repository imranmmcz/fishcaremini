<?php
/**
 * Fish Care System - Registration Page
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

$error = '';
$success = '';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    $user = getCurrentUser();
    redirect(getDashboardUrl($user['role']));
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'নিরাপত্তা ত্রুটি। অনুগ্রহ করে পুনরায় চেষ্টা করুন।';
    } else {
        $data = [
            'username' => sanitize($_POST['username']),
            'email' => sanitize($_POST['email']),
            'password' => $_POST['password'],
            'confirm_password' => $_POST['confirm_password'],
            'name_bn' => sanitize($_POST['full_name_bn']),
            'name_en' => sanitize($_POST['full_name_en']),
            'phone' => sanitize($_POST['phone']),
            'role' => sanitize($_POST['role']),
            'division_id' => !empty($_POST['division_id']) ? intval($_POST['division_id']) : null,
            'district_id' => !empty($_POST['district_id']) ? intval($_POST['district_id']) : null,
            'upazila_id' => !empty($_POST['upazila_id']) ? intval($_POST['upazila_id']) : null,
            'address' => sanitize($_POST['address'])
        ];

        // Validation
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            $error = 'অনুগ্রহ করে সকল প্রয়োজনীয় তথ্য দিন।';
        } elseif ($data['password'] !== $data['confirm_password']) {
            $error = 'পাসওয়ার্ড এবং কনফার্ম পাসওয়ার্ড মিলে না।';
        } elseif (strlen($data['password']) < 6) {
            $error = 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।';
        } else {
            $result = registerUser($data);
            if ($result['success']) {
                $success = $result['message'] . ' <a href="login.php">এখানে লগইন করুন</a>';
            } else {
                $error = $result['message'];
            }
        }
    }
}

$csrfToken = generateCSRFToken();

// Get divisions for dropdown
$divisions = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM divisions WHERE is_active = 1 ORDER BY name_bn");
    $divisions = $stmt->fetchAll();
} catch (Exception $e) {
    // Ignore error
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>রেজিস্টার - <?php echo SITE_NAME_BN; ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Hind Siliguri', 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            color: var(--text-primary);
            padding: 30px 20px;
        }

        .register-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .register-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        .register-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .register-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
        }

        .register-title {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
        }

        .register-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .alert-danger {
            background: rgba(244, 67, 54, 0.15);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.15);
            border: 1px solid var(--secondary-color);
            color: var(--secondary-color);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 576px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 14px;
        }

        .form-label .required {
            color: var(--danger-color);
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: white;
            font-family: 'Hind Siliguri', sans-serif;
            font-size: 15px;
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

        .btn-register {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary-color), #00acc1);
            border: none;
            border-radius: 12px;
            color: white;
            font-family: 'Hind Siliguri', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 188, 212, 0.4);
        }

        .register-footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
        }

        .register-footer p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .register-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .register-footer a:hover {
            text-decoration: underline;
        }

        .location-fields {
            display: none;
        }

        .location-fields.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="register-logo">
                    <i class="bi bi-water"></i>
                </div>
                <h1 class="register-title"><?php echo SITE_NAME_BN; ?></h1>
                <p class="register-subtitle">নতুন অ্যাকাউন্ট তৈরি করুন</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">ইউজারনাম <span class="required">*</span></label>
                        <input type="text" name="username" class="form-control" placeholder="ইউজারনাম দিন" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">ভূমিকা নির্বাচন করুন <span class="required">*</span></label>
                        <select name="role" class="form-control" required>
                            <option value="">নির্বাচন করুন</option>
                            <option value="farmer">চাষী</option>
                            <option value="seller">বিক্রেতা</option>
                            <option value="customer">গ্রাহক</option>
                            <option value="wholesaler">হোলসেলার</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">পূর্ণ নাম (বাংলা) <span class="required">*</span></label>
                        <input type="text" name="full_name_bn" class="form-control" placeholder="আপনার নাম বাংলায় লিখুন" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">পূর্ণ নাম (English)</label>
                        <input type="text" name="full_name_en" class="form-control" placeholder="Your Name in English">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">ইমেইল <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">ফোন নম্বর</label>
                        <input type="tel" name="phone" class="form-control" placeholder="01XXXXXXXXX">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">পাসওয়ার্ড <span class="required">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="কমপক্ষে ৬ অক্ষর" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label class="form-label">কনফার্ম পাসওয়ার্ড <span class="required">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="পাসওয়ার্ড আবার দিন" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">বিভাগ</label>
                    <select name="division_id" id="division_id" class="form-control" onchange="loadDistricts()">
                        <option value="">বিভাগ নির্বাচন করুন</option>
                        <?php foreach ($divisions as $div): ?>
                            <option value="<?php echo $div['id']; ?>"><?php echo $div['name_bn']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group location-fields" id="districtField">
                    <label class="form-label">জেলা</label>
                    <select name="district_id" id="district_id" class="form-control" onchange="loadUpazilas()">
                        <option value="">জেলা নির্বাচন করুন</option>
                    </select>
                </div>

                <div class="form-group location-fields" id="upazilaField">
                    <label class="form-label">উপজেলা</label>
                    <select name="upazila_id" id="upazila_id" class="form-control">
                        <option value="">উপজেলা নির্বাচন করুন</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">ঠিকানা</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="আপনার ঠিকানা লিখুন"></textarea>
                </div>

                <button type="submit" class="btn-register">
                    <i class="bi bi-person-plus"></i> রেজিস্টার করুন
                </button>
            </form>

            <div class="register-footer">
                <p>অ্যাকাউন্ট আছে? <a href="login.php">এখানে লগইন করুন</a></p>
            </div>
        </div>
    </div>

    <script>
        function loadDistricts() {
            const divisionId = document.getElementById('division_id').value;
            const districtField = document.getElementById('districtField');
            const upazilaField = document.getElementById('upazilaField');
            const districtSelect = document.getElementById('district_id');
            const upazilaSelect = document.getElementById('upazila_id');

            if (divisionId) {
                districtField.classList.add('active');
                upazilaField.classList.remove('active');
                upazilaSelect.innerHTML = '<option value="">উপজেলা নির্বাচন করুন</option>';

                // Load districts via AJAX
                fetch('<?php echo SITE_URL; ?>/api/locations.php?type=districts&division_id=' + divisionId)
                    .then(response => response.json())
                    .then(data => {
                        districtSelect.innerHTML = '<option value="">জেলা নির্বাচন করুন</option>';
                        data.forEach(district => {
                            districtSelect.innerHTML += '<option value="' + district.id + '">' + district.name_bn + '</option>';
                        });
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                districtField.classList.remove('active');
                upazilaField.classList.remove('active');
            }
        }

        function loadUpazilas() {
            const districtId = document.getElementById('district_id').value;
            const upazilaField = document.getElementById('upazilaField');
            const upazilaSelect = document.getElementById('upazila_id');

            if (districtId) {
                upazilaField.classList.add('active');

                // Load upazilas via AJAX
                fetch('<?php echo SITE_URL; ?>/api/locations.php?type=upazilas&district_id=' + districtId)
                    .then(response => response.json())
                    .then(data => {
                        upazilaSelect.innerHTML = '<option value="">উপজেলা নির্বাচন করুন</option>';
                        data.forEach(upazila => {
                            upazilaSelect.innerHTML += '<option value="' + upazila.id + '">' + upazila.name_bn + '</option>';
                        });
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                upazilaField.classList.remove('active');
            }
        }
    </script>
</body>
</html>
