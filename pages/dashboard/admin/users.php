<?php
/**
 * Fish Care System - Admin User Management
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'admin') {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'ব্যবহারকারী ব্যবস্থাপনা';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $pdo = getDBConnection();

            if ($_POST['action'] === 'add_user') {
                $username = sanitize($_POST['username']);
                $email = sanitize($_POST['email']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $role = sanitize($_POST['role']);
                $full_name_bn = sanitize($_POST['full_name_bn']);
                $phone = sanitize($_POST['phone']);
                $status = sanitize($_POST['status']);

                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, full_name_bn, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $password, $role, $full_name_bn, $phone, $status]);
                $message = 'ব্যবহারকারী সফলভাবে যোগ করা হয়েছে';
                $messageType = 'success';
                logActivity('user_add', 'নতুন ব্যবহারকারী যোগ করেছেন: ' . $username);
            }

            if ($_POST['action'] === 'edit_user') {
                $id = intval($_POST['id']);
                $full_name_bn = sanitize($_POST['full_name_bn']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $role = sanitize($_POST['role']);
                $status = sanitize($_POST['status']);

                $stmt = $pdo->prepare("UPDATE users SET full_name_bn = ?, email = ?, phone = ?, role = ?, status = ? WHERE id = ?");
                $stmt->execute([$full_name_bn, $email, $phone, $role, $status, $id]);
                $message = 'ব্যবহারকারী সফলভাবে আপডেট করা হয়েছে';
                $messageType = 'success';
                logActivity('user_edit', 'ব্যবহারকারী সম্পাদনা করেছেন: ' . $id);
            }

            if ($_POST['action'] === 'delete_user') {
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'ব্যবহারকারী সফলভাবে মুছে ফেলা হয়েছে';
                $messageType = 'success';
                logActivity('user_delete', 'ব্যবহারকারী মুছে ফেলেছেন: ' . $id);
            }

            if ($_POST['action'] === 'reset_password') {
                $id = intval($_POST['id']);
                $newPassword = password_hash('123456', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$newPassword, $id]);
                $message = 'পাসওয়ার্ড রিসেট করা হয়েছে (নতুন পাসওয়ার্ড: 123456)';
                $messageType = 'success';
            }
        } catch (Exception $e) {
            $message = 'ত্রুটি: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get all users
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
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
            <a href="users.php" class="sidebar-link active">
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
            <a href="locations.php" class="sidebar-link">
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
            <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">ব্যবহারকারী তালিকা</h3>
            <button class="btn btn-primary" onclick="openModal('addUserModal')">
                <i class="bi bi-plus-lg"></i> নতুন ব্যবহারকারী
            </button>
        </div>

        <div class="table-container">
            <table class="data-table" id="usersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ব্যবহারকারী</th>
                        <th>ইমেইল</th>
                        <th>ফোন</th>
                        <th>ভূমিকা</th>
                        <th>স্ট্যাটাস</th>
                        <th>তারিখ</th>
                        <th>কার্যক্রম</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $u): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                    <?php echo substr($u['full_name_bn'], 0, 1); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 500;"><?php echo $u['full_name_bn']; ?></div>
                                    <div style="font-size: 12px; color: var(--text-secondary);">@<?php echo $u['username']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $u['email']; ?></td>
                        <td><?php echo $u['phone'] ?? '-'; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $u['role'] == 'admin' ? 'primary' : ($u['role'] == 'seller' ? 'warning' : 'success'); ?>">
                                <?php echo getRoleName($u['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $u['status'] == 'active' ? 'success' : 'danger'; ?>">
                                <?php echo $u['status']; ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($u['created_at']); ?></td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn btn-sm btn-secondary" onclick="editUser(<?php echo htmlspecialchars(json_encode($u)); ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('নিশ্চিত করুন?');">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-warning" title="পাসওয়ার্ড রিসেট">
                                        <i class="bi bi-key"></i>
                                    </button>
                                </form>
                                <?php if ($u['id'] != $user['id']): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('মুছে ফেলতে চান?');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal" id="addUserModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">নতুন ব্যবহারকারী যোগ করুন</h3>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_user">

                <div class="form-group">
                    <label class="form-label">ইউজারনাম <span class="required">*</span></label>
                    <input type="text" name="username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">পূর্ণ নাম <span class="required">*</span></label>
                    <input type="text" name="full_name_bn" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">ইমেইল <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">ফোন</label>
                    <input type="tel" name="phone" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">পাসওয়ার্ড <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">ভূমিকা <span class="required">*</span></label>
                    <select name="role" class="form-control" required>
                        <option value="">নির্বাচন করুন</option>
                        <option value="admin">অ্যাডমিন</option>
                        <option value="farmer">চাষী</option>
                        <option value="seller">বিক্রেতা</option>
                        <option value="wholesaler">হোলসেলার</option>
                        <option value="customer">গ্রাহক</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">স্ট্যাটাস</label>
                    <select name="status" class="form-control">
                        <option value="active">সক্রিয়</option>
                        <option value="inactive">নিষ্ক্রিয়</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">বাতিল</button>
                <button type="submit" class="btn btn-primary">সংরক্ষণ</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal" id="editUserModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">ব্যবহারকারী সম্পাদনা</h3>
            <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label class="form-label">পূর্ণ নাম</label>
                    <input type="text" name="full_name_bn" id="edit_full_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">ইমেইল</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">ফোন</label>
                    <input type="tel" name="phone" id="edit_phone" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">ভূমিকা</label>
                    <select name="role" id="edit_role" class="form-control" required>
                        <option value="admin">অ্যাডমিন</option>
                        <option value="farmer">চাষী</option>
                        <option value="seller">বিক্রেতা</option>
                        <option value="wholesaler">হোলসেলার</option>
                        <option value="customer">গ্রাহক</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">স্ট্যাটাস</label>
                    <select name="status" id="edit_status" class="form-control">
                        <option value="active">সক্রিয়</option>
                        <option value="inactive">নিষ্ক্রিয়</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">বাতিল</button>
                <button type="submit" class="btn btn-primary">আপডেট</button>
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

function editUser(userData) {
    document.getElementById('edit_id').value = userData.id;
    document.getElementById('edit_full_name').value = userData.full_name_bn;
    document.getElementById('edit_email').value = userData.email;
    document.getElementById('edit_phone').value = userData.phone || '';
    document.getElementById('edit_role').value = userData.role;
    document.getElementById('edit_status').value = userData.status;
    openModal('editUserModal');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
