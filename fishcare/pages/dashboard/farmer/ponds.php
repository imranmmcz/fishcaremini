<?php
/**
 * Fish Care System - Farmer Ponds Management
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if (!in_array($user['role'], ['farmer', 'customer'])) {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'পুকুর ব্যবস্থাপনা';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        $userId = $user['id'];

        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add_pond') {
                $name = sanitize($_POST['name']);
                $sizeDecimal = floatval($_POST['size_decimal']);
                $sizeBigha = floatval($_POST['size_bigha']);
                $waterType = sanitize($_POST['water_type']);
                $depthFeet = floatval($_POST['depth_feet']);
                $divisionId = !empty($_POST['division_id']) ? intval($_POST['division_id']) : null;
                $districtId = !empty($_POST['district_id']) ? intval($_POST['district_id']) : null;
                $upazilaId = !empty($_POST['upazila_id']) ? intval($_POST['upazila_id']) : null;
                $address = sanitize($_POST['address']);
                $description = sanitize($_POST['description']);

                $stmt = $pdo->prepare("
                    INSERT INTO ponds (user_id, name, size_decimal, size_bigha, water_type, depth_feet, division_id, district_id, upazila_id, address, description)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $name, $sizeDecimal, $sizeBigha, $waterType, $depthFeet, $divisionId, $districtId, $upazilaId, $address, $description]);
                $message = 'পুকুর সফলভাবে যোগ করা হয়েছে';
                $messageType = 'success';
                logActivity('add_pond', 'নতুন পুকুর যোগ করেছেন: ' . $name);
            }

            if ($_POST['action'] === 'delete_pond') {
                $pondId = intval($_POST['pond_id']);
                $stmt = $pdo->prepare("DELETE FROM ponds WHERE id = ? AND user_id = ?");
                $stmt->execute([$pondId, $userId]);
                $message = 'পুকুর মুছে ফেলা হয়েছে';
                $messageType = 'success';
            }
        }
    } catch (Exception $e) {
        $message = 'ত্রুটি: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get ponds
try {
    $pdo = getDBConnection();
    $userId = $user['id'];

    $stmt = $pdo->prepare("
        SELECT p.*,
            (SELECT COUNT(*) FROM fish_stockings WHERE pond_id = p.id) as total_stockings,
            (SELECT SUM(amount) FROM incomes WHERE pond_id = p.id) as total_income,
            (SELECT SUM(amount) FROM expenses WHERE pond_id = p.id) as total_expense
        FROM ponds p
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$userId]);
    $ponds = $stmt->fetchAll();

    // Get divisions
    $stmt = $pdo->query("SELECT * FROM divisions WHERE is_active = 1 ORDER BY name_bn");
    $divisions = $stmt->fetchAll();

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<aside class="sidebar">
    <ul class="sidebar-menu">
        <li class="sidebar-menu-item">
            <a href="index.php" class="sidebar-link">
                <i class="bi bi-speedometer2"></i> ড্যাশবোর্ড
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="ponds.php" class="sidebar-link active">
                <i class="bi bi-water"></i> আমার পুকুর
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="fish-stock.php" class="sidebar-link">
                <i class="bi bi-layer-group"></i> মাছ স্টকিং
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="income.php" class="sidebar-link">
                <i class="bi bi-arrow-down-circle"></i> আয়
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="expense.php" class="sidebar-link">
                <i class="bi bi-arrow-up-circle"></i> ব্যয়
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="accounting.php" class="sidebar-link">
                <i class="bi bi-calculator"></i> হিসাব-নিকাশ
            </a>
        </li>
    </ul>
</aside>

<div class="content-wrapper">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Ponds Grid -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="bi bi-water"></i> আমার পুকুর</h3>
            <button class="btn btn-primary" onclick="openModal('addPondModal')">
                <i class="bi bi-plus-lg"></i> নতুন পুকুর
            </button>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($ponds as $pond): ?>
            <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px;">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                    <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-water" style="font-size: 24px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0; font-size: 18px; color: white;"><?php echo $pond['name']; ?></h4>
                        <span class="badge badge-<?php echo $pond['status'] == 'active' ? 'success' : 'warning'; ?>">
                            <?php echo $pond['status'] == 'active' ? 'সক্রিয়' : 'অপেক্ষায়'; ?>
                        </span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px; color: var(--text-secondary); margin-bottom: 15px;">
                    <div><i class="bi bi-rulers"></i> <?php echo $pond['size_decimal']; ?> ডেসিমাল</div>
                    <div><i class="bi bi-droplet"></i> <?php echo ucfirst($pond['water_type']); ?></div>
                    <div><i class="bi bi-layers"></i> <?php echo $pond['total_stockings']; ?> স্টকিং</div>
                    <div><i class="bi bi-ruler-vertical"></i> <?php echo $pond['depth_feet']; ?> ফুট</div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                    <div>
                        <div style="color: var(--secondary-color); font-weight: 600;"><?php echo formatCurrency($pond['total_income'] ?? 0); ?></div>
                        <div style="color: var(--text-secondary);">মোট আয়</div>
                    </div>
                    <div>
                        <div style="color: var(--danger-color); font-weight: 600;"><?php echo formatCurrency($pond['total_expense'] ?? 0); ?></div>
                        <div style="color: var(--text-secondary);">মোট ব্যয়</div>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <a href="fish-stock.php?pond=<?php echo $pond['id']; ?>" class="btn btn-secondary btn-sm" style="flex: 1;">
                        <i class="bi bi-layer-group"></i> স্টকিং
                    </a>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('মুছে ফেলতে চান?');">
                        <input type="hidden" name="action" value="delete_pond">
                        <input type="hidden" name="pond_id" value="<?php echo $pond['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($ponds)): ?>
            <div style="text-align: center; padding: 60px; color: var(--text-secondary); grid-column: 1 / -1;">
                <i class="bi bi-water" style="font-size: 64px; margin-bottom: 20px; opacity: 0.5;"></i>
                <p style="font-size: 18px;">আপনার কোনো পুকুর নেই</p>
                <p>নতুন পুকুর যোগ করে শুরু করুন</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Pond Modal -->
<div class="modal" id="addPondModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">নতুন পুকুর যোগ করুন</h3>
            <button class="modal-close" onclick="closeModal('addPondModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_pond">

                <div class="form-group">
                    <label class="form-label">পুকুরের নাম <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="যেমন: পূর্ব পুকুর" required>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">আকার (ডেসিমাল)</label>
                        <input type="number" step="0.01" name="size_decimal" class="form-control" placeholder="যেমন: 10">
                    </div>

                    <div class="form-group">
                        <label class="form-label">আকার (বিঘা)</label>
                        <input type="number" step="0.01" name="size_bigha" class="form-control" placeholder="যেমন: 3">
                    </div>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">পানির ধরন</label>
                        <select name="water_type" class="form-control">
                            <option value="freshwater">মিঠা পানি</option>
                            <option value="brackish">অল্প লবণাক্ত</option>
                            <option value="saltwater">লবণাক্ত</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">গভীরতা (ফুট)</label>
                        <input type="number" step="0.01" name="depth_feet" class="form-control" placeholder="যেমন: 8">
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

                <div class="form-group" id="districtField" style="display: none;">
                    <label class="form-label">জেলা</label>
                    <select name="district_id" id="district_id" class="form-control" onchange="loadUpazilas()">
                        <option value="">জেলা নির্বাচন করুন</option>
                    </select>
                </div>

                <div class="form-group" id="upazilaField" style="display: none;">
                    <label class="form-label">উপজেলা</label>
                    <select name="upazila_id" id="upazila_id" class="form-control">
                        <option value="">উপজেলা নির্বাচন করুন</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">ঠিকানা</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="পুকুরের সম্পূর্ণ ঠিকানা"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">বিবরণ</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="পুকুর সম্পর্কে বিবরণ"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addPondModal')">বাতিল</button>
                <button type="submit" class="btn btn-primary">সংরক্ষণ</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function loadDistricts() {
    const divisionId = document.getElementById('division_id').value;
    const districtField = document.getElementById('districtField');
    const upazilaField = document.getElementById('upazilaField');
    const districtSelect = document.getElementById('district_id');

    if (divisionId) {
        districtField.style.display = 'block';
        upazilaField.style.display = 'none';

        fetch('<?php echo SITE_URL; ?>/api/locations.php?type=districts&division_id=' + divisionId)
            .then(response => response.json())
            .then(data => {
                districtSelect.innerHTML = '<option value="">জেলা নির্বাচন করুন</option>';
                data.data.forEach(district => {
                    districtSelect.innerHTML += '<option value="' + district.id + '">' + district.name_bn + '</option>';
                });
            });
    } else {
        districtField.style.display = 'none';
        upazilaField.style.display = 'none';
    }
}

function loadUpazilas() {
    const districtId = document.getElementById('district_id').value;
    const upazilaField = document.getElementById('upazilaField');
    const upazilaSelect = document.getElementById('upazila_id');

    if (districtId) {
        upazilaField.style.display = 'block';

        fetch('<?php echo SITE_URL; ?>/api/locations.php?type=upazilas&district_id=' + districtId)
            .then(response => response.json())
            .then(data => {
                upazilaSelect.innerHTML = '<option value="">উপজেলা নির্বাচন করুন</option>';
                data.data.forEach(upazila => {
                    upazilaSelect.innerHTML += '<option value="' + upazila.id + '">' + upazila.name_bn + '</option>';
                });
            });
    } else {
        upazilaField.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
