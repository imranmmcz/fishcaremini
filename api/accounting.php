<?php
/**
 * Fish Care System - Accounting API
 * Handles income and expense transactions
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'অনুগ্রহ করে লগইন করুন']);
        exit;
    }

    $user = getCurrentUser();
    $pdo = getDBConnection();

    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Add Income
    if ($action === 'add_income') {
        $pondId = !empty($_POST['pond_id']) ? intval($_POST['pond_id']) : null;
        $category = sanitize($_POST['category']);
        $amount = floatval($_POST['amount']);
        $description = sanitize($_POST['description']);

        $stmt = $pdo->prepare("
            INSERT INTO incomes (user_id, pond_id, category, amount, description, income_date)
            VALUES (?, ?, ?, ?, ?, date('now'))
        ");
        $stmt->execute([$user['id'], $pondId, $category, $amount, $description]);

        $response['success'] = true;
        $response['message'] = 'আয় সফলভাবে যোগ করা হয়েছে';
        logActivity('add_income', 'আয় যোগ করেছেন: ' . formatCurrency($amount));
    }

    // Add Expense
    if ($action === 'add_expense') {
        $pondId = !empty($_POST['pond_id']) ? intval($_POST['pond_id']) : null;
        $category = sanitize($_POST['category']);
        $amount = floatval($_POST['amount']);
        $description = sanitize($_POST['description']);

        $stmt = $pdo->prepare("
            INSERT INTO expenses (user_id, pond_id, category, amount, description, expense_date)
            VALUES (?, ?, ?, ?, ?, date('now'))
        ");
        $stmt->execute([$user['id'], $pondId, $category, $amount, $description]);

        $response['success'] = true;
        $response['message'] = 'ব্যয় সফলভাবে যোগ করা হয়েছে';
        logActivity('add_expense', 'ব্যয় যোগ করেছেন: ' . formatCurrency($amount));
    }

    // Get accounting summary
    if ($action === 'get_summary') {
        $period = isset($_POST['period']) ? $_POST['period'] : 'month';

        $dateCondition = '';
        if ($period == 'day') {
            $dateCondition = "AND income_date = date('now')";
        } elseif ($period == 'week') {
            $dateCondition = "AND income_date >= date('now', '-7 days')";
        } elseif ($period == 'month') {
            $dateCondition = "AND strftime('%m', income_date) = strftime('%m', 'now') AND strftime('%Y', income_date) = strftime('%Y', 'now')";
        } elseif ($period == 'year') {
            $dateCondition = "AND strftime('%Y', income_date) = strftime('%Y', 'now')";
        }

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM incomes WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $totalIncome = $stmt->fetchColumn();

        $expenseDateCondition = str_replace('income_date', 'expense_date', $dateCondition);
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $totalExpense = $stmt->fetchColumn();

        $response['success'] = true;
        $response['data'] = [
            'income' => $totalIncome,
            'expense' => $totalExpense,
            'profit' => $totalIncome - $totalExpense
        ];
    }

} catch (Exception $e) {
    $response['message'] = 'ত্রুটি: ' . $e->getMessage();
}

echo json_encode($response);
