<?php
/**
 * Fish Care System - Location API
 * Handles dynamic loading of divisions, districts, upazilas
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $pdo = getDBConnection();
    $type = isset($_GET['type']) ? $_GET['type'] : '';

    switch ($type) {
        case 'divisions':
            $stmt = $pdo->query("SELECT * FROM divisions WHERE is_active = 1 ORDER BY name_bn");
            $response['data'] = $stmt->fetchAll();
            $response['success'] = true;
            break;

        case 'districts':
            $divisionId = isset($_GET['division_id']) ? intval($_GET['division_id']) : 0;
            if ($divisionId > 0) {
                $stmt = $pdo->prepare("SELECT * FROM districts WHERE division_id = ? AND is_active = 1 ORDER BY name_bn");
                $stmt->execute([$divisionId]);
                $response['data'] = $stmt->fetchAll();
                $response['success'] = true;
            }
            break;

        case 'upazilas':
            $districtId = isset($_GET['district_id']) ? intval($_GET['district_id']) : 0;
            if ($districtId > 0) {
                $stmt = $pdo->prepare("SELECT * FROM upazilas WHERE district_id = ? AND is_active = 1 ORDER BY name_bn");
                $stmt->execute([$districtId]);
                $response['data'] = $stmt->fetchAll();
                $response['success'] = true;
            }
            break;

        case 'add_division':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $name_bn = sanitize($_POST['name_bn']);
                $name_en = sanitize($_POST['name_en']);

                if (empty($name_bn)) {
                    $response['message'] = 'বিভাগের নাম প্রয়োজন';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO divisions (name_bn, name_en) VALUES (?, ?)");
                    $stmt->execute([$name_bn, $name_en]);
                    $response['success'] = true;
                    $response['message'] = 'বিভাগ সফলভাবে যোগ করা হয়েছে';
                }
            }
            break;

        case 'add_district':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $division_id = intval($_POST['division_id']);
                $name_bn = sanitize($_POST['name_bn']);
                $name_en = sanitize($_POST['name_en']);

                if (empty($division_id) || empty($name_bn)) {
                    $response['message'] = 'জেলার তথ্য প্রয়োজন';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO districts (division_id, name_bn, name_en) VALUES (?, ?, ?)");
                    $stmt->execute([$division_id, $name_bn, $name_en]);
                    $response['success'] = true;
                    $response['message'] = 'জেলা সফলভাবে যোগ করা হয়েছে';
                }
            }
            break;

        case 'add_upazila':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $district_id = intval($_POST['district_id']);
                $name_bn = sanitize($_POST['name_bn']);
                $name_en = sanitize($_POST['name_en']);

                if (empty($district_id) || empty($name_bn)) {
                    $response['message'] = 'উপজেলার তথ্য প্রয়োজন';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO upazilas (district_id, name_bn, name_en) VALUES (?, ?, ?)");
                    $stmt->execute([$district_id, $name_bn, $name_en]);
                    $response['success'] = true;
                    $response['message'] = 'উপজেলা সফলভাবে যোগ করা হয়েছে';
                }
            }
            break;

        case 'edit_division':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = intval($_POST['id']);
                $name_bn = sanitize($_POST['name_bn']);
                $name_en = sanitize($_POST['name_en']);

                $stmt = $pdo->prepare("UPDATE divisions SET name_bn = ?, name_en = ? WHERE id = ?");
                $stmt->execute([$name_bn, $name_en, $id]);
                $response['success'] = true;
                $response['message'] = 'বিভাগ সফলভাবে আপডেট করা হয়েছে';
            }
            break;

        case 'edit_district':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = intval($_POST['id']);
                $division_id = intval($_POST['division_id']);
                $name_bn = sanitize($_POST['name_bn']);
                $name_en = sanitize($_POST['name_en']);

                $stmt = $pdo->prepare("UPDATE districts SET division_id = ?, name_bn = ?, name_en = ? WHERE id = ?");
                $stmt->execute([$division_id, $name_bn, $name_en, $id]);
                $response['success'] = true;
                $response['message'] = 'জেলা সফলভাবে আপডেট করা হয়েছে';
            }
            break;

        case 'edit_upazila':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = intval($_POST['id']);
                $district_id = intval($_POST['district_id']);
                $name_bn = sanitize($_POST['name_bn']);
                $name_en = sanitize($_POST['name_en']);

                $stmt = $pdo->prepare("UPDATE upazilas SET district_id = ?, name_bn = ?, name_en = ? WHERE id = ?");
                $stmt->execute([$district_id, $name_bn, $name_en, $id]);
                $response['success'] = true;
                $response['message'] = 'উপজেলা সফলভাবে আপডেট করা হয়েছে';
            }
            break;

        case 'delete_division':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("DELETE FROM divisions WHERE id = ?");
                $stmt->execute([$id]);
                $response['success'] = true;
                $response['message'] = 'বিভাগ সফলভাবে মুছে ফেলা হয়েছে';
            }
            break;

        case 'delete_district':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("DELETE FROM districts WHERE id = ?");
                $stmt->execute([$id]);
                $response['success'] = true;
                $response['message'] = 'জেলা সফলভাবে মুছে ফেলা হয়েছে';
            }
            break;

        case 'delete_upazila':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("DELETE FROM upazilas WHERE id = ?");
                $stmt->execute([$id]);
                $response['success'] = true;
                $response['message'] = 'উপজেলা সফলভাবে মুছে ফেলা হয়েছে';
            }
            break;

        default:
            $response['message'] = 'অজানা অনুরোধ';
    }
} catch (Exception $e) {
    $response['message'] = 'ত্রুটি: ' . $e->getMessage();
}

echo json_encode($response);
