<?php
/**
 * Fish Care System - Profile Page
 * Edit Profile with Dynamic Location Dropdowns
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

$pageTitle = 'আমার প্রোফাইল';
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $data = [
            'full_name_bn' => sanitize($_POST['full_name_bn']),
            'full_name_en' => sanitize($_POST['full_name_en']),
            'phone' => sanitize($_POST['phone']),
            'address' => sanitize($_POST['address']),
            'division_id' => !empty($_POST['division_id']) ? intval($_POST['division_id']) : null,
            'district_id' => !empty($_POST['district_id']) ? intval($_POST['district_id']) : null,
            'upazila_id' => !empty($_POST['upazila_id']) ? intval($_POST['upazila_id']) : null
        ];

        $result = updateProfile($user['id'], $data);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';

        if ($result['success']) {
            $user = getCurrentUser(); // Refresh user data
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $oldPassword = $_POST['old_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if ($newPassword !== $confirmPassword) {
            $message = 'নতুন পাসওয়ার্ড এবং কনফার্ম পাসওয়ার্ড মিলে না';
            $messageType = 'danger';
        } elseif (strlen($newPassword) < 6) {
            $message = 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে';
            $messageType = 'danger';
        } else {
            $result = changePassword($user['id'], $oldPassword, $newPassword);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
        }
    }
}

// Get location data
try {
    $pdo = getDBConnection();

    // Get all divisions
    $stmt = $pdo->query("SELECT * FROM divisions WHERE is_active = 1 ORDER BY name_bn");
    $divisions = $stmt->fetchAll();

    // Get user's current division
    if ($user['division_id']) {
        $stmt = $pdo->prepare("SELECT * FROM districts WHERE division_id = ? AND is_active = 1 ORDER BY name_bn");
        $stmt->execute([$user['division_id']]);
        $districts = $stmt->fetchAll();
    }

    // Get user's current district
    if ($user['district_id']) {
        $stmt = $pdo->prepare("SELECT * FROM upazilas WHERE district_id = ? AND is_active = 1 ORDER BY name_bn");
        $stmt->execute([$user['district_id']]);
        $upazilas = $stmt->fetchAll();
    }

} catch (Exception $e) {
    $message = 'ত্রুটি: ' . $e->getMessage();
    $messageType = 'danger';
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<style>
.profile-container {
    max-width: 800px;
    margin: 0 auto;
}

.profile-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    margin-bottom: 30px;
}

.profile-avatar-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 48px;
    font-weight: 700;
    color: var(--primary-color);
    border: 4px solid white;
}

.profile-name {
    font-size: 28px;
    font-weight: 700;
    color: white;
    margin-bottom: 5px;
}

.profile-role {
    color: rgba(255, 255, 255, 0.9);
    font-size: 16px;
}
</style>

<div class="content-wrapper">
    <div class="profile-container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar-large">
                <?php echo substr($user['full_name_bn'], 0, 1); ?>
            </div>
            <h1 class="profile-name"><?php echo $user['full_name_bn']; ?></h1>
            <p class="profile-role"><?php echo getRoleName($user['role']); ?></p>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-person"></i> প্রোফাইল তথ্য</h3>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">পূর্ণ নাম (বাংলা)</label>
                        <input type="text" name="full_name_bn" class="form-control" value="<?php echo $user['full_name_bn']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">পূর্ণ নাম (English)</label>
                        <input type="text" name="full_name_en" class="form-control" value="<?php echo $user['full_name_en']; ?>">
                    </div>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">ইমেইল</label>
                        <input type="email" class="form-control" value="<?php echo $user['email']; ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label class="form-label">ফোন নম্বর</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo $user['phone']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">ঠিকানা</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo $user['address']; ?></textarea>
                </div>

                <!-- Dynamic Location Dropdowns -->
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">বিভাগ</label>
                        <select name="division_id" id="division_id" class="form-control" onchange="loadDistricts()">
                            <option value="">বিভাগ নির্বাচন করুন</option>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?php echo $div['id']; ?>" <?php echo $user['division_id'] == $div['id'] ? 'selected' : ''; ?>>
                                    <?php echo $div['name_bn']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">জেলা</label>
                        <select name="district_id" id="district_id" class="form-control" onchange="loadUpazilas()">
                            <option value="">জেলা নির্বাচন করুন</option>
                            <?php if (!empty($districts)): ?>
                                <?php foreach ($districts as $dist): ?>
                                    <option value="<?php echo $dist['id']; ?>" <?php echo $user['district_id'] == $dist['id'] ? 'selected' : ''; ?>>
                                        <?php echo $dist['name_bn']; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">উপজেলা</label>
                        <select name="upazila_id" id="upazila_id" class="form-control">
                            <option value="">উপজেলা নির্বাচন করুন</option>
                            <?php if (!empty($upazilas)): ?>
                                <?php foreach ($upazilas as $upa): ?>
                                    <option value="<?php echo $upa['id']; ?>" <?php echo $user['upazila_id'] == $upa['id'] ? 'selected' : ''; ?>>
                                        <?php echo $upa['name_bn']; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> প্রোফাইল আপডেট করুন
                </button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-key"></i> পাসওয়ার্ড পরিবর্তন</h3>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label class="form-label">বর্তমান পাসওয়ার্ড</label>
                    <input type="password" name="old_password" class="form-control" required>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">নতুন পাসওয়ার্ড</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label class="form-label">কনফার্ম পাসওয়ার্ড</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-lock"></i> পাসওয়ার্ড পরিবর্তন করুন
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function loadDistricts() {
    const divisionId = document.getElementById('division_id').value;
    const districtSelect = document.getElementById('district_id');
    const upazilaSelect = document.getElementById('upazila_id');

    // Clear dependent fields
    districtSelect.innerHTML = '<option value="">জেলা নির্বাচন করুন</option>';
    upazilaSelect.innerHTML = '<option value="">উপজেলা নির্বাচন করুন</option>';

    if (divisionId) {
        fetch('<?php echo SITE_URL; ?>/api/locations.php?type=districts&division_id=' + divisionId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.data.forEach(district => {
                        districtSelect.innerHTML += '<option value="' + district.id + '">' + district.name_bn + '</option>';
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    }
}

function loadUpazilas() {
    const districtId = document.getElementById('district_id').value;
    const upazilaSelect = document.getElementById('upazila_id');

    upazilaSelect.innerHTML = '<option value="">উপজেলা নির্বাচন করুন</option>';

    if (districtId) {
        fetch('<?php echo SITE_URL; ?>/api/locations.php?type=upazilas&district_id=' + districtId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.data.forEach(upazila => {
                        upazilaSelect.innerHTML += '<option value="' + upazila.id + '">' + upazila.name_bn + '</option>';
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
