<?php
/**
 * Fish Care System - Seller Profile Page
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'seller') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'আমার প্রোফাইল';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'অবৈধ অনুরোধ।';
    } else {
        $result = updateProfile($user['id'], $_POST);
        if ($result['success']) {
            $success = $result['message'];
            $user = getCurrentUser();
        } else {
            $error = $result['message'];
        }
    }
}

$csrfToken = generateCSRFToken();

try {
    $pdo = getDBConnection();

    $stmt = $pdo->query("SELECT * FROM divisions ORDER BY name_bn");
    $divisions = $stmt->fetchAll();

    $districts = [];
    $upazilas = [];

    if ($user['division_id']) {
        $stmt = $pdo->prepare("SELECT * FROM districts WHERE division_id = ? ORDER BY name_bn");
        $stmt->execute([$user['division_id']]);
        $districts = $stmt->fetchAll();
    }

    if ($user['district_id']) {
        $stmt = $pdo->prepare("SELECT * FROM upazilas WHERE district_id = ? ORDER BY name_bn");
        $stmt->execute([$user['district_id']]);
        $upazilas = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

include __DIR__ . '/../../../includes/header.php';
?>

<aside class="sidebar">
    <ul class="sidebar-menu">
        <li class="sidebar-menu-item">
            <a href="index.php" class="sidebar-link">
                <i class="bi bi-speedometer2"></i>
                ড্যাশবোর্ড
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="products.php" class="sidebar-link">
                <i class="bi bi-box-seam"></i>
                পণ্য ম্যানেজমেন্ট
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="invoices.php" class="sidebar-link">
                <i class="bi bi-receipt"></i>
                চালান
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="fish-sales.php" class="sidebar-link">
                <i class="bi bi-water"></i>
                মাছ বিক্রয়
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="customers.php" class="sidebar-link">
                <i class="bi bi-people"></i>
                গ্রাহক
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="suppliers.php" class="sidebar-link">
                <i class="bi bi-truck"></i>
                সরবরাহকারী
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="accounting.php" class="sidebar-link">
                <i class="bi bi-calculator"></i>
                হিসাব-নিকাশ
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="profile.php" class="sidebar-link active">
                <i class="bi bi-person"></i>
                প্রোফাইল
            </a>
        </li>
    </ul>
</aside>

<div class="content-wrapper">
    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i>
        <?php echo $success; ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">প্রোফাইল তথ্য</h3>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">বাংলা নাম *</label>
                    <input type="text" name="name_bn" class="form-control" required value="<?php echo htmlspecialchars($user['name_bn'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">English Name</label>
                    <input type="text" name="name_en" class="form-control" value="<?php echo htmlspecialchars($user['name_en'] ?? ''); ?>">
                </div>
            </div>

            <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">ইমেইল</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>

                <div class="form-group">
                    <label class="form-label">ফোন</label>
                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">বিভাগ</label>
                <select name="division_id" id="division_id" class="form-control" onchange="loadDistricts()">
                    <option value="">বিভাগ নির্বাচন করুন</option>
                    <?php foreach ($divisions as $div): ?>
                    <option value="<?php echo $div['id']; ?>" <?php echo ($user['division_id'] == $div['id']) ? 'selected' : ''; ?>>
                        <?php echo $div['name_bn']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">জেলা</label>
                    <select name="district_id" id="district_id" class="form-control" onchange="loadUpazilas()">
                        <option value="">জেলা নির্বাচন করুন</option>
                        <?php foreach ($districts as $dist): ?>
                        <option value="<?php echo $dist['id']; ?>" <?php echo ($user['district_id'] == $dist['id']) ? 'selected' : ''; ?>>
                            <?php echo $dist['name_bn']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">উপজেলা</label>
                    <select name="upazila_id" id="upazila_id" class="form-control">
                        <option value="">উপজেলা নির্বাচন করুন</option>
                        <?php foreach ($upazilas as $upa): ?>
                        <option value="<?php echo $upa['id']; ?>" <?php echo ($user['upazila_id'] == $upa['id']) ? 'selected' : ''; ?>>
                            <?php echo $upa['name_bn']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">ঠিকানা</label>
                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> সংরক্ষণ করুন
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function loadDistricts() {
    const divisionId = document.getElementById('division_id').value;
    const districtSelect = document.getElementById('district_id');
    const upazilaSelect = document.getElementById('upazila_id');

    districtSelect.innerHTML = '<option value="">লোড হচ্ছে...</option>';
    upazilaSelect.innerHTML = '<option value="">উপজেলা নির্বাচন করুন</option>';

    if (!divisionId) {
        districtSelect.innerHTML = '<option value="">জেলা নির্বাচন করুন</option>';
        return;
    }

    fetch('<?php echo SITE_URL; ?>/api/locations.php?type=districts&division_id=' + divisionId)
        .then(res => res.json())
        .then(data => {
            districtSelect.innerHTML = '<option value="">জেলা নির্বাচন করুন</option>';
            if (data.success) {
                data.data.forEach(d => {
                    districtSelect.innerHTML += `<option value="${d.id}">${d.name_bn}</option>`;
                });
            }
        });
}

function loadUpazilas() {
    const districtId = document.getElementById('district_id').value;
    const upazilaSelect = document.getElementById('upazila_id');

    upazilaSelect.innerHTML = '<option value="">লোড হচ্ছে...</option>';

    if (!districtId) {
        upazilaSelect.innerHTML = '<option value="">উপজেলা নির্বাচন করুন</option>';
        return;
    }

    fetch('<?php echo SITE_URL; ?>/api/locations.php?type=upazilas&district_id=' + districtId)
        .then(res => res.json())
        .then(data => {
            upazilaSelect.innerHTML = '<option value="">উপজেলা নির্বাচন করুন</option>';
            if (data.success) {
                data.data.forEach(u => {
                    upazilaSelect.innerHTML += `<option value="${u.id}">${u.name_bn}</option>`;
                });
            }
        });
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
