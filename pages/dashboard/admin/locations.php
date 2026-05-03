<?php
/**
 * Fish Care System - Admin Location Management
 * Manage Divisions, Districts, Upazilas
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'admin') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'অবস্থান ব্যবস্থাপনা';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'division';

$message = '';
$messageType = '';

// Handle form submissions via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    try {
        $pdo = getDBConnection();
        $action = $_POST['action'];

        if ($action === 'add_division') {
            $name_bn = sanitize($_POST['name_bn']);
            $name_en = sanitize($_POST['name_en']);
            $code = sanitize($_POST['code']);

            $stmt = $pdo->prepare("INSERT INTO divisions (name_bn, name_en, code) VALUES (?, ?, ?)");
            $stmt->execute([$name_bn, $name_en, $code]);
            echo json_encode(['success' => true, 'message' => 'বিভাগ সফলভাবে যোগ করা হয়েছে']);
            exit;
        }

        if ($action === 'add_district') {
            $division_id = intval($_POST['division_id']);
            $name_bn = sanitize($_POST['name_bn']);
            $name_en = sanitize($_POST['name_en']);
            $code = sanitize($_POST['code']);

            $stmt = $pdo->prepare("INSERT INTO districts (division_id, name_bn, name_en, code) VALUES (?, ?, ?, ?)");
            $stmt->execute([$division_id, $name_bn, $name_en, $code]);
            echo json_encode(['success' => true, 'message' => 'জেলা সফলভাবে যোগ করা হয়েছে']);
            exit;
        }

        if ($action === 'add_upazila') {
            $district_id = intval($_POST['district_id']);
            $name_bn = sanitize($_POST['name_bn']);
            $name_en = sanitize($_POST['name_en']);
            $code = sanitize($_POST['code']);

            $stmt = $pdo->prepare("INSERT INTO upazilas (district_id, name_bn, name_en, code) VALUES (?, ?, ?, ?)");
            $stmt->execute([$district_id, $name_bn, $name_en, $code]);
            echo json_encode(['success' => true, 'message' => 'উপজেলা সফলভাবে যোগ করা হয়েছে']);
            exit;
        }

        if ($action === 'delete_division') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM divisions WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'বিভাগ মুছে ফেলা হয়েছে']);
            exit;
        }

        if ($action === 'delete_district') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM districts WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'জেলা মুছে ফেলা হয়েছে']);
            exit;
        }

        if ($action === 'delete_upazila') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM upazilas WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'উপজেলা মুছে ফেলা হয়েছে']);
            exit;
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Get data for display
try {
    $pdo = getDBConnection();

    $stmt = $pdo->query("SELECT * FROM divisions ORDER BY name_bn");
    $divisions = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT d.*, div.name_bn as division_name FROM districts d LEFT JOIN divisions div ON d.division_id = div.id ORDER BY d.name_bn");
    $districts = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT u.*, d.name_bn as district_name, div.name_bn as division_name FROM upazilas u LEFT JOIN districts d ON u.district_id = d.id LEFT JOIN divisions div ON d.division_id = div.id ORDER BY u.name_bn");
    $upazilas = $stmt->fetchAll();

} catch (Exception $e) {
    $message = 'ত্রুটি: ' . $e->getMessage();
    $messageType = 'danger';
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<aside class="sidebar">
    <ul class="sidebar-menu">
        <li class="sidebar-menu-item">
            <a href="index.php" class="sidebar-link">
                <i class="bi bi-speedometer2"></i>
                ড্যাশবোর্ড
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="users.php" class="sidebar-link">
                <i class="bi bi-people"></i>
                ব্যবহারকারী
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="ponds.php" class="sidebar-link">
                <i class="bi bi-water"></i>
                পুকুর
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="locations.php" class="sidebar-link active">
                <i class="bi bi-geo-alt"></i>
                অবস্থান ব্যবস্থাপনা
            </a>
        </li>
        <li class="sidebar-menu-item">
            <a href="reports.php" class="sidebar-link">
                <i class="bi bi-file-earmark-bar-graph"></i>
                রিপোর্ট
            </a>
        </li>
    </ul>
</aside>

<div class="content-wrapper">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; gap: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
            <a href="?tab=division" class="btn <?php echo $activeTab == 'division' ? 'btn-primary' : 'btn-secondary'; ?>">
                <i class="bi bi-map"></i> বিভাগ
            </a>
            <a href="?tab=district" class="btn <?php echo $activeTab == 'district' ? 'btn-primary' : 'btn-secondary'; ?>">
                <i class="bi bi-map-fill"></i> জেলা
            </a>
            <a href="?tab=upazila" class="btn <?php echo $activeTab == 'upazila' ? 'btn-primary' : 'btn-secondary'; ?>">
                <i class="bi bi-geo"></i> উপজেলা
            </a>
        </div>
    </div>

    <!-- Divisions Tab -->
    <?php if ($activeTab == 'division'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">বিভাগ তালিকা</h3>
            <button class="btn btn-primary" onclick="openModal('addDivisionModal')">
                <i class="bi bi-plus-lg"></i> নতুন বিভাগ
            </button>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>বিভাগের নাম (বাংলা)</th>
                        <th>বিভাগের নাম (English)</th>
                        <th>কোড</th>
                        <th>জেলা সংখ্যা</th>
                        <th>কার্যক্রম</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT d.*, (SELECT COUNT(*) FROM districts WHERE division_id = d.id) as district_count FROM divisions d ORDER BY d.name_bn");
                    $divisionsWithCount = $stmt->fetchAll();
                    foreach ($divisionsWithCount as $index => $div):
                    ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td style="font-weight: 600;"><?php echo $div['name_bn']; ?></td>
                        <td><?php echo $div['name_en']; ?></td>
                        <td><?php echo $div['code']; ?></td>
                        <td>
                            <span class="badge badge-primary"><?php echo $div['district_count']; ?>টি জেলা</span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="deleteItem('division', <?php echo $div['id']; ?>, '<?php echo $div['name_bn']; ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Districts Tab -->
    <?php if ($activeTab == 'district'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">জেলা তালিকা</h3>
            <button class="btn btn-primary" onclick="openModal('addDistrictModal')">
                <i class="bi bi-plus-lg"></i> নতুন জেলা
            </button>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>জেলার নাম (বাংলা)</th>
                        <th>জেলার নাম (English)</th>
                        <th>বিভাগ</th>
                        <th>কোড</th>
                        <th>উপজেলা সংখ্যা</th>
                        <th>কার্যক্রম</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT d.*, div.name_bn as division_name, (SELECT COUNT(*) FROM upazilas WHERE district_id = d.id) as upazila_count FROM districts d LEFT JOIN divisions div ON d.division_id = div.id ORDER BY d.name_bn");
                    $districtsWithCount = $stmt->fetchAll();
                    foreach ($districtsWithCount as $index => $dist):
                    ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td style="font-weight: 600;"><?php echo $dist['name_bn']; ?></td>
                        <td><?php echo $dist['name_en']; ?></td>
                        <td><?php echo $dist['division_name']; ?></td>
                        <td><?php echo $dist['code']; ?></td>
                        <td>
                            <span class="badge badge-primary"><?php echo $dist['upazila_count']; ?>টি উপজেলা</span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="deleteItem('district', <?php echo $dist['id']; ?>, '<?php echo $dist['name_bn']; ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Upazilas Tab -->
    <?php if ($activeTab == 'upazila'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">উপজেলা তালিকা</h3>
            <button class="btn btn-primary" onclick="openModal('addUpazilaModal')">
                <i class="bi bi-plus-lg"></i> নতুন উপজেলা
            </button>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>উপজেলার নাম (বাংলা)</th>
                        <th>উপজেলার নাম (English)</th>
                        <th>জেলা</th>
                        <th>বিভাগ</th>
                        <th>কোড</th>
                        <th>কার্যক্রম</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upazilas as $index => $upa): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td style="font-weight: 600;"><?php echo $upa['name_bn']; ?></td>
                        <td><?php echo $upa['name_en']; ?></td>
                        <td><?php echo $upa['district_name']; ?></td>
                        <td><?php echo $upa['division_name']; ?></td>
                        <td><?php echo $upa['code']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="deleteItem('upazila', <?php echo $upa['id']; ?>, '<?php echo $upa['name_bn']; ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add Division Modal -->
<div class="modal" id="addDivisionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">নতুন বিভাগ যোগ করুন</h3>
            <button class="modal-close" onclick="closeModal('addDivisionModal')">&times;</button>
        </div>
        <form onsubmit="submitForm(event, 'add_division', 'addDivisionModal')">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">বিভাগের নাম (বাংলা) <span class="required">*</span></label>
                    <input type="text" name="name_bn" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">বিভাগের নাম (English)</label>
                    <input type="text" name="name_en" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">কোড</label>
                    <input type="text" name="code" class="form-control" placeholder="যেমন: DHK">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addDivisionModal')">বাতিল</button>
                <button type="submit" class="btn btn-primary">সংরক্ষণ</button>
            </div>
        </form>
    </div>
</div>

<!-- Add District Modal -->
<div class="modal" id="addDistrictModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">নতুন জেলা যোগ করুন</h3>
            <button class="modal-close" onclick="closeModal('addDistrictModal')">&times;</button>
        </div>
        <form onsubmit="submitForm(event, 'add_district', 'addDistrictModal')">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">বিভাগ <span class="required">*</span></label>
                    <select name="division_id" class="form-control" required>
                        <option value="">বিভাগ নির্বাচন করুন</option>
                        <?php foreach ($divisions as $div): ?>
                            <option value="<?php echo $div['id']; ?>"><?php echo $div['name_bn']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">জেলার নাম (বাংলা) <span class="required">*</span></label>
                    <input type="text" name="name_bn" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">জেলার নাম (English)</label>
                    <input type="text" name="name_en" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">কোড</label>
                    <input type="text" name="code" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addDistrictModal')">বাতিল</button>
                <button type="submit" class="btn btn-primary">সংরক্ষণ</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Upazila Modal -->
<div class="modal" id="addUpazilaModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">নতুন উপজেলা যোগ করুন</h3>
            <button class="modal-close" onclick="closeModal('addUpazilaModal')">&times;</button>
        </div>
        <form onsubmit="submitForm(event, 'add_upazila', 'addUpazilaModal')">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">জেলা <span class="required">*</span></label>
                    <select name="district_id" class="form-control" required>
                        <option value="">জেলা নির্বাচন করুন</option>
                        <?php foreach ($districts as $dist): ?>
                            <option value="<?php echo $dist['id']; ?>"><?php echo $dist['name_bn'] . ' (' . $dist['division_name'] . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">উপজেলার নাম (বাংলা) <span class="required">*</span></label>
                    <input type="text" name="name_bn" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">উপজেলার নাম (English)</label>
                    <input type="text" name="name_en" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">কোড</label>
                    <input type="text" name="code" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addUpazilaModal')">বাতিল</button>
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

function submitForm(event, action, modalId) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', action);
    formData.append('ajax', '1');

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal(modalId);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('ত্রুটি হয়েছে');
    });
}

function deleteItem(type, id, name) {
    if (confirm(name + ' মুছে ফেলতে চান?')) {
        const formData = new FormData();
        formData.append('action', 'delete_' + type);
        formData.append('id', id);
        formData.append('ajax', '1');

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            alert('ত্রুটি হয়েছে');
        });
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
