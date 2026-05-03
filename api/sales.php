<?php
/**
 * Fish Care System - Sales API
 * Handles invoice and fish sales
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

    // Add Invoice (Product Sale)
    if ($action === 'add_invoice') {
        $customerId = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
        $productId = intval($_POST['product_id']);
        $quantity = floatval($_POST['quantity']);
        $totalAmount = floatval($_POST['total_amount']);
        $paidAmount = floatval($_POST['paid_amount']);
        $paymentStatus = sanitize($_POST['payment_status']);

        // Generate invoice number
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Insert invoice
        $stmt = $pdo->prepare("
            INSERT INTO invoices (seller_id, customer_id, invoice_number, invoice_date, total_amount, paid_amount, due_amount, status)
            VALUES (?, ?, ?, date('now'), ?, ?, ?, ?)
        ");
        $dueAmount = $totalAmount - $paidAmount;
        $stmt->execute([$user['id'], $customerId, $invoiceNumber, $totalAmount, $paidAmount, $dueAmount, $paymentStatus]);
        $invoiceId = $pdo->lastInsertId();

        // Insert invoice item
        $stmt = $pdo->prepare("SELECT sell_price FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        $unitPrice = $product['sell_price'];
        $totalPrice = $quantity * $unitPrice;

        $stmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$invoiceId, $productId, $quantity, $unitPrice, $totalPrice]);

        // Update product stock
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $stmt->execute([$quantity, $productId]);

        // Update customer balance if due
        if ($customerId && $dueAmount > 0) {
            $stmt = $pdo->prepare("UPDATE customers SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$dueAmount, $customerId]);
        }

        $response['success'] = true;
        $response['message'] = 'বিক্রয় সফল! চালান নম্বর: ' . $invoiceNumber;
        logActivity('add_invoice', 'নতুন চালান তৈরি: ' . $invoiceNumber);
    }

    // Add Fish Sale
    if ($action === 'add_fish_sale') {
        $customerId = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
        $fishType = sanitize($_POST['fish_type']);
        $quantityKg = floatval($_POST['quantity_kg']);
        $unitPrice = floatval($_POST['unit_price']);
        $totalAmount = floatval($_POST['total_amount']);
        $paidAmount = floatval($_POST['paid_amount']);
        $paymentStatus = sanitize($_POST['payment_status']);

        // Generate sale number
        $saleNumber = 'FISH-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Insert fish sale
        $stmt = $pdo->prepare("
            INSERT INTO fish_sales (seller_id, customer_id, sale_number, fish_type, quantity_kg, unit_price, total_amount, paid_amount, due_amount, status, sale_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, date('now'))
        ");
        $dueAmount = $totalAmount - $paidAmount;
        $stmt->execute([$user['id'], $customerId, $saleNumber, $fishType, $quantityKg, $unitPrice, $totalAmount, $paidAmount, $dueAmount, $paymentStatus]);

        // Update customer balance if due
        if ($customerId && $dueAmount > 0) {
            $stmt = $pdo->prepare("UPDATE customers SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$dueAmount, $customerId]);
        }

        $response['success'] = true;
        $response['message'] = 'মাছ বিক্রয় সফল!';
        logActivity('add_fish_sale', 'মাছ বিক্রয় করেছেন');
    }

    // Get products for seller
    if ($action === 'get_products') {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? AND is_active = 1 ORDER BY name_bn");
        $stmt->execute([$user['id']]);
        $response['success'] = true;
        $response['data'] = $stmt->fetchAll();
    }

    // Get customers for seller
    if ($action === 'get_customers') {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE seller_id = ? ORDER BY name");
        $stmt->execute([$user['id']]);
        $response['success'] = true;
        $response['data'] = $stmt->fetchAll();
    }

} catch (Exception $e) {
    $response['message'] = 'ত্রুটি: ' . $e->getMessage();
}

echo json_encode($response);
