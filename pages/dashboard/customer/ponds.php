<?php
/**
 * Fish Care System - Customer Ponds Management
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'customer') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'আমার পুকুর';
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'অবৈধ অনুরোধ। অনুগ্রহ করে পুনরায় চেষ্টা করুন।';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            try {
                $pdo = getDBConnection();

                $stmt = $pdo->prepare("
                    INSERT INTO ponds (user_id, name, size_decimal, depth_feet, water_type, status, address, division_id, district_id, upazila_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user['id'],
                    sanitize($_POST['name']),
                    floatval($_POST['size_decimal']),
                    floatval($_POST['depth_feet'] ?? 0),
                    sanitize($_POST['water_type'] ?? 'fresh'),
                    sanitize($_POST['status'] ?? 'active'),
                    sanitize($_POST['address'] ?? ''),
                    intval($_POST['division_id'] ?? 0),
                    intval($_POST['district_id'] ?? 0),
                    intval($_POST['upazila_id'] ?? 0)
                ]);

                $success = 'পুকুর সফলভাবে যোগ করা হয়েছে।';
                logActivity('pond_add', 'নতুন পুকুর যোগ করেছেন: ' . $_POST['name']);
            } catch (Exception $e) {
                $error = 'ত্রুটি: ' . $e->getMessage();
            }
        } elseif ($action === 'delete') {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("DELETE FROM ponds WHERE id = ? AND user_id = ?");
                $stmt->execute([intval($_POST['id']), $user['id']]);
                $success = 'পুকুর সফলভাবে মুছে ফেলা হয়েছে।';
            } catch (Exception $e) {
                $error = 'ত্রুটি: ' . $e->getMessage();
            }
        }
    }
}

$csrfToken = generateCSRFToken();

// Get ponds
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM ponds WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $ponds = $stmt->fetchAll();

    // Get divisions
    $stmt = $pdo->query("SELECT * FROM divisions ORDER BY name_bn");
    $divisions = $stmt->fetchAll();
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
            <a href="ponds.php" class="sidebar-link active">
                <i class="bi bi-water"></i>
                আমার পুকুর
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="purchases.php" class="sidebar-link">
                <i class="bi bi-cart-check"></i>
                মাছ ক্রয়
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="payments.php" class="sidebar-link">
                <i class="bi bi-wallet2"></i>
                পেমেন্ট
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="profile.php" class="sidebar-link">
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
            <h3 class="card-title">পুকুর তালিকা</h3>
            <button type="button" class="btn btn-primary" onclick="openModal('addPondModal')">
                <i class="bi bi-plus-circle"></i> নতুন পুকুর
            </button>
        </div>

        <?php if (empty($ponds)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            আপনি এখনো কোনো পুকুর যোগ করেননি।
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>পুকুরের নাম</th>
                        <th>আকার (ডেসিমাল)</th>
                        <th>গভীরতা (ফুট)</th>
                        <th>পানির ধরন</th>
                        <th>ঠিকানা</th>
                        <th>স্ট্যাটাস</th>
                        <th>কার্যক্রম</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ponds as $pond): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pond['name']); ?></td>
                        <td><?php echo $pond['size_decimal']; ?></td>
                        <td><?php echo $pond['depth_feet']; ?></td>
                        <td><?php echo ucfirst($pond['water_type']); ?></td>
                        <td><?php echo htmlspecialchars($pond['address'] ?? '-'); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $pond['status'] == 'active' ? 'success' : 'warning'; ?>">
                                <?php echo $pond['status']; ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('এই পুকুর মুছে ফেলতে চান?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $pond['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Pond Modal -->
<div class="modal" id="addPondModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">নতুন পুকুর যোগ করুন</h3>
            <button type="button" class="modal-close" onclick="closeModal('addPondModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label class="form-label">পুকুরের নাম *</label>
                    <input type="text" name="name" class="form-control" required placeholder="যেমন: পূর্ব পুকুর">
                </div>

                <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">আকার (ডেসিমাল) *</label>
                        <input type="number" name="size_decimal" class="form-control" step="0.01" required placeholder="যেমন: 50">
                    </div>

                    <div class="form-group">
                        <label class="form-label">গভীরতা (ফুট)</label>
                        <input type="number" name="depth_feet" class="form-control" step="0.1" placeholder="যেমন: 8">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">পানির ধরন</label>
                    <select name="water_type" class="form-control">
                        <option value="fresh">মিঠা পানি</option>
                        <option value="brackish">অর্ধ-লবণ পানি</option>
                        <option value="salt">লবণ পানি</option>
                    </select>
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

                <div class="form-group">
                    <label class="form-label">জেলা</label>
                    <select name="district_id" id="district_id" class="form-control" onchange="loadUpazilas()">
                        <option value="">জেলা নির্বাচন করুন</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">উপজেলা</label>
                    <select name="upazila_id" id="upazila_id" class="form-control">
                        <option value="">উপজেলা নির্বাচন করুন</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">ঠিকানা</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="বিস্তারিত ঠিকানা"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">স্ট্যাটাস</label>
                    <select name="status" class="form-control">
                        <option value="active">সক্রিয়</option>
                        <option value="inactive">নিষ্ক্রিয়</option>
                        <option value="preparation">প্রস্তুতি</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addPondModal')">বাতিল</button>
                <button type="submit" class="btn btn-primary">সংরক্ষণ করুন</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

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
                data.data.forEach(district => {
                    districtSelect.innerHTML += `<option value="${district.id}">${district.name_bn}</option>`;
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
                data.data.forEach(upazila => {
                    upazilaSelect.innerHTML += `<option value="${upazila.id}">${upazila.name_bn}</option>`;
                });
            }
        });
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
