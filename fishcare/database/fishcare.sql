-- Fish Care System Database Schema
-- Auto-generated for Fish Care System v2.0

-- Drop existing database if exists
DROP DATABASE IF EXISTS fishcare;
CREATE DATABASE fishcare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fishcare;

-- ========================================
-- USER & AUTHENTICATION TABLES
-- ========================================

-- Users table with roles
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'farmer', 'wholesaler', 'seller', 'customer') DEFAULT 'customer',
    full_name_bn VARCHAR(100) NOT NULL,
    full_name_en VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    division_id INT DEFAULT NULL,
    district_id INT DEFAULT NULL,
    upazila_id INT DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Sessions table
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- ========================================
-- GEOGRAPHICAL LOCATION TABLES
-- ========================================

-- Divisions (বিভাগ)
CREATE TABLE divisions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_bn VARCHAR(50) NOT NULL,
    name_en VARCHAR(50) NOT NULL,
    code VARCHAR(10),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name_bn (name_bn),
    INDEX idx_name_en (name_en)
) ENGINE=InnoDB;

-- Districts (জেলা)
CREATE TABLE districts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    division_id INT NOT NULL,
    name_bn VARCHAR(50) NOT NULL,
    name_en VARCHAR(50) NOT NULL,
    code VARCHAR(10),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE CASCADE,
    INDEX idx_division (division_id),
    INDEX idx_name_bn (name_bn)
) ENGINE=InnoDB;

-- Upazilas (উপজেলা)
CREATE TABLE upazilas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    district_id INT NOT NULL,
    name_bn VARCHAR(50) NOT NULL,
    name_en VARCHAR(50) NOT NULL,
    code VARCHAR(10),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
    INDEX idx_district (district_id),
    INDEX idx_name_bn (name_bn)
) ENGINE=InnoDB;

-- ========================================
-- FISH FARMING TABLES
-- ========================================

-- Fish Species (মাছের প্রজাতি)
CREATE TABLE fish_species (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_bn VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    scientific_name VARCHAR(100),
    category VARCHAR(50), -- freshwater, saltwater, brackish
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Fish Diseases (মাছের রোগ)
CREATE TABLE fish_diseases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_bn VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    symptoms_bn TEXT,
    symptoms_en TEXT,
    treatment_bn TEXT,
    treatment_en TEXT,
    prevention_bn TEXT,
    prevention_en TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Ponds (পুকুর)
CREATE TABLE ponds (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    size_decimal DECIMAL(10,2), -- in decimals
    size_bigha DECIMAL(10,2), -- in bigha
    water_type ENUM('freshwater', 'brackish', 'saltwater') DEFAULT 'freshwater',
    depth_feet DECIMAL(5,2),
    division_id INT,
    district_id INT,
    upazila_id INT,
    address TEXT,
    description TEXT,
    status ENUM('active', 'fallow', 'preparing') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE SET NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL,
    FOREIGN KEY (upazila_id) REFERENCES upazilas(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Fish Stocking (মাছ ছাড়া)
CREATE TABLE fish_stockings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pond_id INT NOT NULL,
    species_id INT NOT NULL,
    quantity_kg DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL,
    supplier_name VARCHAR(100),
    batch_number VARCHAR(50),
    stocking_date DATE NOT NULL,
    expected_harvest_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE,
    FOREIGN KEY (species_id) REFERENCES fish_species(id) ON DELETE CASCADE,
    INDEX idx_pond (pond_id),
    INDEX idx_date (stocking_date)
) ENGINE=InnoDB;

-- Sample Testing (নমুনা পরীক্ষা)
CREATE TABLE sample_tests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pond_id INT NOT NULL,
    test_date DATE NOT NULL,
    sample_weight_kg DECIMAL(8,2),
    sample_length_cm DECIMAL(8,2),
    ph_level DECIMAL(4,2),
    dissolved_oxygen DECIMAL(5,2),
    temperature DECIMAL(5,2),
    ammonia DECIMAL(5,2),
    nitrate DECIMAL(5,2),
    observations TEXT,
    recommendations TEXT,
    tested_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE CASCADE,
    INDEX idx_pond (pond_id),
    INDEX idx_date (test_date)
) ENGINE=InnoDB;

-- ========================================
-- ACCOUNTING & TRANSACTIONS
-- ========================================

-- Income Categories (আয়ের ধরন)
CREATE TABLE income_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_bn VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    type ENUM('fish_sale', 'other') DEFAULT 'other',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Expense Categories (ব্যয়ের ধরন)
CREATE TABLE expense_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_bn VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    type ENUM('feed', 'medicine', 'labor', 'electricity', 'fuel', 'equipment', 'maintenance', 'other') DEFAULT 'other',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Income Records (আয়)
CREATE TABLE incomes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    pond_id INT,
    category_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description TEXT,
    payment_method ENUM('cash', 'bank', 'mobile') DEFAULT 'cash',
    transaction_date DATE NOT NULL,
    reference_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES income_categories(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_date (transaction_date),
    INDEX idx_category (category_id)
) ENGINE=InnoDB;

-- Expense Records (ব্যয়)
CREATE TABLE expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    pond_id INT,
    category_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description TEXT,
    payment_method ENUM('cash', 'bank', 'mobile') DEFAULT 'cash',
    vendor_name VARCHAR(100),
    transaction_date DATE NOT NULL,
    reference_number VARCHAR(50),
    receipt_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pond_id) REFERENCES ponds(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_date (transaction_date),
    INDEX idx_category (category_id)
) ENGINE=InnoDB;

-- ========================================
-- SELLER & INVENTORY TABLES
-- ========================================

-- Product Categories (পণ্যের ধরন)
CREATE TABLE product_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_bn VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    type ENUM('medicine', 'feed', 'equipment', 'other') DEFAULT 'other',
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Products (পণ্য)
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    category_id INT NOT NULL,
    name_bn VARCHAR(150) NOT NULL,
    name_en VARCHAR(150),
    brand VARCHAR(100),
    unit VARCHAR(20), -- kg, piece, liter, etc.
    buy_price DECIMAL(10,2) NOT NULL,
    sell_price DECIMAL(10,2) NOT NULL,
    stock_quantity DECIMAL(10,2) DEFAULT 0,
    min_stock_alert DECIMAL(10,2) DEFAULT 10,
    expiry_date DATE,
    description TEXT,
    image VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE CASCADE,
    INDEX idx_seller (seller_id),
    INDEX idx_category (category_id),
    INDEX idx_name (name_bn)
) ENGINE=InnoDB;

-- Customers (গ্রাহক)
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    division_id INT,
    district_id INT,
    upazila_id INT,
    balance DECIMAL(12,2) DEFAULT 0, -- due amount
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE SET NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL,
    FOREIGN KEY (upazila_id) REFERENCES upazilas(id) ON DELETE SET NULL,
    INDEX idx_seller (seller_id)
) ENGINE=InnoDB;

-- Suppliers (সরবরাহকারী)
CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    company_name VARCHAR(150),
    address TEXT,
    balance DECIMAL(12,2) DEFAULT 0, -- payable amount
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_seller (seller_id)
) ENGINE=InnoDB;

-- Invoices/Chalan (চালান)
CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    customer_id INT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    invoice_date DATE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    paid_amount DECIMAL(12,2) DEFAULT 0,
    due_amount DECIMAL(12,2) DEFAULT 0,
    payment_status ENUM('paid', 'partial', 'due') DEFAULT 'due',
    payment_method ENUM('cash', 'bank', 'mobile') DEFAULT 'cash',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_seller (seller_id),
    INDEX idx_customer (customer_id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_date (invoice_date)
) ENGINE=InnoDB;

-- Invoice Items (চালান আইটেম)
CREATE TABLE invoice_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_invoice (invoice_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

-- Purchases (ক্রয়)
CREATE TABLE purchases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    supplier_id INT,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    paid_amount DECIMAL(12,2) DEFAULT 0,
    due_amount DECIMAL(12,2) DEFAULT 0,
    purchase_date DATE NOT NULL,
    invoice_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_seller (seller_id),
    INDEX idx_supplier (supplier_id),
    INDEX idx_date (purchase_date)
) ENGINE=InnoDB;

-- ========================================
-- FISH SALES (For Seller - মাছ বিক্রয়)
-- ========================================

-- Fish Sales (মাছ বিক্রয়)
CREATE TABLE fish_sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    customer_id INT,
    species_id INT NOT NULL,
    quantity_kg DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    paid_amount DECIMAL(12,2) DEFAULT 0,
    due_amount DECIMAL(12,2) DEFAULT 0,
    payment_status ENUM('paid', 'partial', 'due') DEFAULT 'paid',
    sale_date DATE NOT NULL,
    pond_source VARCHAR(100), -- উৎস পুকুর
    vehicle_number VARCHAR(30),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (species_id) REFERENCES fish_species(id) ON DELETE CASCADE,
    INDEX idx_seller (seller_id),
    INDEX idx_species (species_id),
    INDEX idx_date (sale_date)
) ENGINE=InnoDB;

-- ========================================
-- SYSTEM TABLES
-- ========================================

-- Profile Update Logs
CREATE TABLE profile_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    field_name VARCHAR(50) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- System Logs
CREATE TABLE system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Market Prices (বাজার মূল্য)
CREATE TABLE market_prices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    species_id INT NOT NULL,
    min_price DECIMAL(10,2),
    max_price DECIMAL(10,2),
    avg_price DECIMAL(10,2),
    market_name VARCHAR(100),
    record_date DATE NOT NULL,
    source VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (species_id) REFERENCES fish_species(id) ON DELETE CASCADE,
    INDEX idx_species (species_id),
    INDEX idx_date (record_date)
) ENGINE=InnoDB;

-- ========================================
-- INSERT INITIAL DATA
-- ========================================

-- Insert Divisions (বিভাগ)
INSERT INTO divisions (name_bn, name_en, code) VALUES
('ঢাকা', 'Dhaka', 'DHK'),
('চট্টগ্রাম', 'Chittagong','CTG'),
('খুলনা', 'Khulna', 'KHL'),
('রাজশাহী', 'Rajshahi', 'RAJ'),
('বরিশাল', 'Barisal', 'BAR'),
('সিলেট', 'Sylhet', 'SYL'),
('রংপুর', 'Rangpur', 'RNG'),
('ময়মনসিংহ', 'Mymensingh', 'MYM');

-- Insert Districts for Dhaka Division
INSERT INTO districts (division_id, name_bn, name_en, code) VALUES
(1, 'ঢাকা', 'Dhaka', 'DHA'),
(1, 'গাজীপুর', 'Gazipur', 'GAZ'),
(1, 'মানিকগঞ্জ', 'Manikganj', 'MAN'),
(1, 'নারায়ণগঞ্জ', 'Narayanganj', 'NAR'),
(1, 'মুন্সীগঞ্জ', 'Munshiganj', 'MUN'),
(1, 'কিশোরগঞ্জ', 'Kishoreganj', 'KIS'),
(1, 'টাঙ্গাইল', 'Tangail', 'TAN'),
(1, 'ফরিদপুর', 'Faridpur', 'FAR');

-- Insert Districts for Chittagong Division
INSERT INTO districts (division_id, name_bn, name_en, code) VALUES
(2, 'চট্টগ্রাম', 'Chittagong', 'CTG'),
(2, 'কক্সবাজার', 'Cox\'s Bazar', 'COX'),
(2, 'ফেনী', 'Feni', 'FEN'),
(2, 'নোয়াখালী', 'Noakhali', 'NOA'),
(2, 'লক্ষ্মীপুর', 'Lakshmipur', 'LAK'),
(2, 'চাঁদপুর', 'Chandpur', 'CHA'),
(2, 'ব্রাহ্মণবাড়িয়া', 'Brahmanbaria', 'BRA'),
(2, 'কুমিল্লা', 'Cumilla', 'CUM');

-- Insert Upazilas for Dhaka District
INSERT INTO upazilas (district_id, name_bn, name_en, code) VALUES
(1, 'ঢাকা সদর', 'Dhaka Sadar', 'DHA-1'),
(1, 'সূত্রাপুর', 'Savar', 'DHA-2'),
(1, 'আশুলিয়া', 'Ashulia', 'DHA-3'),
(1, 'কেরাণীটেক', 'Keraniganj', 'DHA-4'),
(1, 'দোহার', 'Dohar', 'DHA-5');

-- Insert Upazilas for Gazipur District
INSERT INTO upazilas (district_id, name_bn, name_en, code) VALUES
(2, 'গাজীপুর সদর', 'Gazipur Sadar', 'GAZ-1'),
(2, 'কালীগঞ্জ', 'Kaliganj', 'GAZ-2'),
(2, 'শ্রীপুর', 'Sirajganj', 'GAZ-3'),
(2, 'গড়িলা', 'Ghorashal', 'GAZ-4');

-- Insert Fish Species
INSERT INTO fish_species (name_bn, name_en, scientific_name, category) VALUES
('রুই', 'Rohu', 'Labeo rohita', 'freshwater'),
('কাতল', 'Catla', 'Catla catla', 'freshwater'),
('মৃগেল', 'Mrigal', 'Cirrhinus mrigala', 'freshwater'),
('তেলাপিয়া', 'Tilapia', 'Oreochromis niloticus', 'freshwater'),
('পাঙাস', 'Pangas', 'Pangasianodon hypophthalmus', 'freshwater'),
('সিলভার কার্প', 'Silver Carp', 'Hypophthalmichthys molitrix', 'freshwater'),
('গ্রাস কার্প', 'Grass Carp', 'Ctenopharyngodon idella', 'freshwater'),
('কার্প', 'Common Carp', 'Cyprinus carpio', 'freshwater');

-- Insert Fish Diseases
INSERT INTO fish_diseases (name_bn, name_en, symptoms_bn, symptoms_en, treatment_bn, treatment_en, prevention_bn, prevention_en) VALUES
('আইচ', 'Ich (White Spot)', 'শরীরে সাদা দাগ, মাছ ঘঁষায়', 'White spots on body, fish rubbing against surfaces', 'ফরমালিন বা লবণ দ্রবণ', 'Formalin or salt solution', 'পানির গুণমান বজায় রাখা', 'Maintain water quality'),
('ফিন রট', 'Fin Rot', 'পাখনা ক্ষয়, লালচে দাগ', 'Fins eroding, reddish spots', 'অ্যান্টিবায়োটিক স্নান', 'Antibiotic bath', 'পরিষ্কার পানি রাখা', 'Keep water clean'),
('দাঁড়িপাল্লা', 'Dropsy', 'শরীর ফুলে যাওয়া, আঁশ উঠে যাওয়া', 'Body swelling, scales raised', 'অ্যান্টিবায়োটিক চিকিৎসা', 'Antibiotic treatment', 'স্বাস্থ্যকর পরিবেশ', 'Healthy environment'),
('এঁচো', 'Epizootic Ulcerative Syndrome', 'শরীরে ক্ষত, আলসার', 'Body wounds, ulcers', 'ফুরাডিন বা পটাশিয়াম পারম্যাঙ্গানেট', 'Furadan or Potassium Permanganate', 'রোগ প্রতিরোধী মাছ ব্যবহার', 'Use disease-resistant fish');

-- Insert Income Categories
INSERT INTO income_categories (name_bn, name_en, type) VALUES
('মাছ বিক্রয়', 'Fish Sale', 'fish_sale'),
('পোনা বিক্রয়', 'Fry Sale', 'fish_sale'),
('অন্যান্য আয়', 'Other Income', 'other');

-- Insert Expense Categories
INSERT INTO expense_categories (name_bn, name_en, type) VALUES
('খাবার', 'Feed', 'feed'),
('ঔষধ', 'Medicine', 'medicine'),
('শ্রমিক খরচ', 'Labor', 'labor'),
('বিদ্যুৎ', 'Electricity', 'electricity'),
('জ্বালানি', 'Fuel', 'fuel'),
('সরঞ্জাম', 'Equipment', 'equipment'),
('মেরামত', 'Maintenance', 'maintenance'),
('অন্যান্য', 'Other', 'other');

-- Insert Product Categories
INSERT INTO product_categories (name_bn, name_en, type) VALUES
('ঔষধ', 'Medicine', 'medicine'),
('মাছের খাবার', 'Fish Feed', 'feed'),
('সরঞ্জাম', 'Equipment', 'equipment'),
('অন্যান্য', 'Other', 'other');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, role, full_name_bn, full_name_en, phone, status) VALUES
('admin', 'admin@fishcare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'অ্যাডমিন', 'Administrator', '01700000000', 'active');

-- Insert market prices sample
INSERT INTO market_prices (species_id, min_price, max_price, avg_price, market_name, record_date) VALUES
(1, 180, 220, 200, 'ঢাকা মাছ বাজার', CURDATE()),
(2, 200, 250, 225, 'ঢাকা মাছ বাজার', CURDATE()),
(4, 120, 160, 140, 'ঢাকা মাছ বাজার', CURDATE()),
(5, 100, 140, 120, 'ঢাকা মাছ বাজার', CURDATE());
