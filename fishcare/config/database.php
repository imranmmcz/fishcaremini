<?php
/**
 * Fish Care System - SQLite Database Configuration
 * Auto-installs database on first run
 */

define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/../data/fishcare.db');
define('DATA_DIR', __DIR__ . '/../data');

// Create data directory if not exists
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}

$pdo = null;

function getDBConnection() {
    global $pdo;

    if ($pdo === null) {
        try {
            $dsn = 'sqlite:' . DB_PATH;
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            // Enable foreign keys
            $pdo->exec('PRAGMA foreign_keys = ON');

        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    return $pdo;
}

function checkDatabaseInstallation() {
    return file_exists(DB_PATH) && filesize(DB_PATH) > 0;
}

function autoInstallDatabase() {
    $pdo = getDBConnection();

    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE,
            password VARCHAR(255) NOT NULL,
            name_bn VARCHAR(100),
            name_en VARCHAR(100),
            phone VARCHAR(20),
            role VARCHAR(20) DEFAULT 'customer',
            division_id INTEGER,
            district_id INTEGER,
            upazila_id INTEGER,
            address TEXT,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS divisions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name_bn VARCHAR(100) NOT NULL,
            name_en VARCHAR(100),
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS districts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            division_id INTEGER NOT NULL,
            name_bn VARCHAR(100) NOT NULL,
            name_en VARCHAR(100),
            is_active INTEGER DEFAULT 1,
            FOREIGN KEY (division_id) REFERENCES divisions(id),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS upazilas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            district_id INTEGER NOT NULL,
            name_bn VARCHAR(100) NOT NULL,
            name_en VARCHAR(100),
            is_active INTEGER DEFAULT 1,
            FOREIGN KEY (district_id) REFERENCES districts(id),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS product_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name_bn VARCHAR(100) NOT NULL,
            name_en VARCHAR(100),
            description TEXT,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            seller_id INTEGER NOT NULL,
            category_id INTEGER,
            name_bn VARCHAR(200) NOT NULL,
            name_en VARCHAR(200),
            brand VARCHAR(100),
            unit VARCHAR(20) DEFAULT 'pcs',
            buy_price REAL DEFAULT 0,
            sell_price REAL DEFAULT 0,
            stock_quantity REAL DEFAULT 0,
            min_stock_alert INTEGER DEFAULT 10,
            description TEXT,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (seller_id) REFERENCES users(id),
            FOREIGN KEY (category_id) REFERENCES product_categories(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            seller_id INTEGER NOT NULL,
            name VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            email VARCHAR(100),
            address TEXT,
            balance REAL DEFAULT 0,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (seller_id) REFERENCES users(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS suppliers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            seller_id INTEGER NOT NULL,
            name VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            email VARCHAR(100),
            company_name VARCHAR(200),
            address TEXT,
            balance REAL DEFAULT 0,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (seller_id) REFERENCES users(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS invoices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_number VARCHAR(50) UNIQUE NOT NULL,
            seller_id INTEGER NOT NULL,
            customer_id INTEGER,
            invoice_date DATE DEFAULT CURRENT_DATE,
            total_amount REAL DEFAULT 0,
            discount REAL DEFAULT 0,
            paid_amount REAL DEFAULT 0,
            due_amount REAL DEFAULT 0,
            status VARCHAR(20) DEFAULT 'pending',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (seller_id) REFERENCES users(id),
            FOREIGN KEY (customer_id) REFERENCES customers(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS invoice_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity REAL NOT NULL,
            unit_price REAL NOT NULL,
            total_price REAL NOT NULL,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS purchases (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            purchase_number VARCHAR(50) UNIQUE NOT NULL,
            seller_id INTEGER NOT NULL,
            supplier_id INTEGER,
            purchase_date DATE DEFAULT CURRENT_DATE,
            total_amount REAL DEFAULT 0,
            paid_amount REAL DEFAULT 0,
            due_amount REAL DEFAULT 0,
            status VARCHAR(20) DEFAULT 'pending',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (seller_id) REFERENCES users(id),
            FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS purchase_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            purchase_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity REAL NOT NULL,
            unit_price REAL NOT NULL,
            total_price REAL NOT NULL,
            FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS fish_sales (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sale_number VARCHAR(50) UNIQUE NOT NULL,
            seller_id INTEGER NOT NULL,
            customer_id INTEGER,
            fish_type VARCHAR(100) NOT NULL,
            quantity_kg REAL NOT NULL,
            unit_price REAL NOT NULL,
            total_amount REAL NOT NULL,
            sale_date DATE DEFAULT CURRENT_DATE,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (seller_id) REFERENCES users(id),
            FOREIGN KEY (customer_id) REFERENCES customers(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ponds (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name VARCHAR(100) NOT NULL,
            size_decimal REAL DEFAULT 0,
            size_bigha REAL DEFAULT 0,
            water_type VARCHAR(20) DEFAULT 'freshwater',
            depth_feet REAL DEFAULT 0,
            division_id INTEGER,
            district_id INTEGER,
            upazila_id INTEGER,
            address TEXT,
            description TEXT,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (division_id) REFERENCES divisions(id),
            FOREIGN KEY (district_id) REFERENCES districts(id),
            FOREIGN KEY (upazila_id) REFERENCES upazilas(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS fish_stockings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pond_id INTEGER NOT NULL,
            fish_type VARCHAR(100) NOT NULL,
            quantity INTEGER DEFAULT 0,
            avg_weight_kg REAL DEFAULT 0,
            price_per_kg REAL DEFAULT 0,
            stocking_date DATE DEFAULT CURRENT_DATE,
            supplier VARCHAR(200),
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS fish_deaths (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            stocking_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL,
            reason TEXT,
            death_date DATE DEFAULT CURRENT_DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (stocking_id) REFERENCES fish_stockings(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS incomes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pond_id INTEGER,
            user_id INTEGER NOT NULL,
            category VARCHAR(100) NOT NULL,
            amount REAL NOT NULL,
            description TEXT,
            income_date DATE DEFAULT CURRENT_DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pond_id) REFERENCES ponds(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS expenses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pond_id INTEGER,
            user_id INTEGER NOT NULL,
            category VARCHAR(100) NOT NULL,
            amount REAL NOT NULL,
            description TEXT,
            expense_date DATE DEFAULT CURRENT_DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pond_id) REFERENCES ponds(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            ip_address VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Insert default divisions (Bangladesh)
    $divisions = [
        ['ঢাকা', 'Dhaka'],
        ['চট্টগ্রাম', 'Chittagong'],
        ['খুলনা', 'Khulna'],
        ['রাজশাহী', 'Rajshahi'],
        ['সিলেট', 'Sylhet'],
        ['বরিশাল', 'Barisal'],
        ['রংপুর', 'Rangpur'],
        ['ময়মনসিংহ', 'Mymensingh']
    ];

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO divisions (name_bn, name_en) VALUES (?, ?)");
    foreach ($divisions as $div) {
        $stmt->execute($div);
    }

    // Insert sample districts for Dhaka
    $districts = [
        [1, 'ঢাকা', 'Dhaka'],
        [1, 'গাজীপুর', 'Gazipur'],
        [1, 'নারায়ণগঞ্জ', 'Narayanganj'],
        [1, 'মানিকগঞ্ঘ', 'Manikganj'],
        [1, 'টাঙ্গাইল', 'Tangail']
    ];

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO districts (division_id, name_bn, name_en) VALUES (?, ?, ?)");
    foreach ($districts as $dist) {
        $stmt->execute($dist);
    }

    // Insert product categories
    $categories = [
        ['ঔষধ', 'Medicine'],
        ['খাদ্য', 'Feed'],
        ['সরঞ্জাম', 'Equipment'],
        ['অন্যান্য', 'Others']
    ];

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO product_categories (name_bn, name_en) VALUES (?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }

    // Insert default admin user (username: admin, password: admin123)
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, password, name_bn, name_en, email, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['admin', $password, 'অ্যাডমিন', 'Admin', 'admin@fishcare.com', 'admin']);

    // Insert sample seller (username: seller, password: seller123)
    $password2 = password_hash('seller123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, password, name_bn, name_en, email, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['seller', $password2, 'বিক্রেতা', 'Seller', 'seller@fishcare.com', 'seller']);

    // Insert sample farmer (username: farmer, password: farmer123)
    $password3 = password_hash('farmer123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, password, name_bn, name_en, email, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['farmer', $password3, 'কৃষক', 'Farmer', 'farmer@fishcare.com', 'farmer']);

    return true;
}

// Auto-install on first access
if (!checkDatabaseInstallation()) {
    autoInstallDatabase();
}
