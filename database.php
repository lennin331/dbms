<?php
class Database {
    private $db;
    
    public function __construct() {
        $this->db = new SQLite3('pharmacy.db');
        $this->createTables();
    }
    
    private function createTables() {
        // Users table
        $this->db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            role TEXT DEFAULT 'user',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Categories table
        $this->db->exec("CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Products table
        $this->db->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            price REAL NOT NULL,
            category_id INTEGER,
            stock INTEGER DEFAULT 0,
            manufacturer TEXT,
            prescription_required INTEGER DEFAULT 0,
            expiry_date DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories (id)
        )");
        
        // Orders table
        $this->db->exec("CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            total_amount REAL,
            status TEXT DEFAULT 'pending',
            shipping_address TEXT,
            payment_method TEXT,
            FOREIGN KEY (user_id) REFERENCES users (id)
        )");
        
        // Order items table
        $this->db->exec("CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER,
            product_id INTEGER,
            quantity INTEGER,
            price REAL,
            FOREIGN KEY (order_id) REFERENCES orders (id),
            FOREIGN KEY (product_id) REFERENCES products (id)
        )");
        
        // Prescriptions table
        $this->db->exec("CREATE TABLE IF NOT EXISTS prescriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            product_id INTEGER,
            prescription_file TEXT,
            doctor_name TEXT,
            status TEXT DEFAULT 'pending',
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users (id),
            FOREIGN KEY (product_id) REFERENCES products (id)
        )");
        
        // Insert sample data if tables are empty
        $this->insertSampleData();
    }
    
    private function insertSampleData() {
        // Check if categories exist
        $result = $this->db->query("SELECT COUNT(*) as count FROM categories");
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($row['count'] == 0) {
            // Insert categories
            $categories = [
                ['Pain Relief', 'Medications for pain management'],
                ['Antibiotics', 'Antibacterial medications'],
                ['Vitamins & Supplements', 'Dietary supplements and vitamins'],
                ['Cold & Flu', 'Medications for cold and flu symptoms'],
                ['First Aid', 'First aid supplies and equipment'],
                ['Diabetes Care', 'Products for diabetes management']
            ];
            
            $stmt = $this->db->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
            foreach ($categories as $cat) {
                $stmt->bindValue(':name', $cat[0], SQLITE3_TEXT);
                $stmt->bindValue(':description', $cat[1], SQLITE3_TEXT);
                $stmt->execute();
            }
            
            // Insert sample products
            $products = [
                ['Paracetamol 500mg', 'Pain reliever and fever reducer', 5.99, 1, 100, 'PharmaCo', 0, '2025-12-31'],
                ['Amoxicillin 250mg', 'Antibiotic for bacterial infections', 12.50, 2, 50, 'MediLab', 1, '2025-06-30'],
                ['Vitamin C 1000mg', 'Immune system support', 8.99, 3, 200, 'NutriHealth', 0, '2026-01-31'],
                ['Cold Relief Syrup', 'Relief from cold symptoms', 7.50, 4, 75, 'PharmaCare', 0, '2024-11-30'],
                ['Bandage Kit', 'Complete first aid bandage set', 15.99, 5, 150, 'MediSafe', 0, '2027-12-31'],
                ['Glucose Monitor', 'Blood glucose monitoring system', 45.99, 6, 30, 'DiabetiCare', 0, '2025-03-31']
            ];
            
            $stmt = $this->db->prepare("INSERT INTO products (name, description, price, category_id, stock, manufacturer, prescription_required, expiry_date) VALUES (:name, :description, :price, :category_id, :stock, :manufacturer, :prescription_required, :expiry_date)");
            
            foreach ($products as $prod) {
                $stmt->bindValue(':name', $prod[0], SQLITE3_TEXT);
                $stmt->bindValue(':description', $prod[1], SQLITE3_TEXT);
                $stmt->bindValue(':price', $prod[2], SQLITE3_FLOAT);
                $stmt->bindValue(':category_id', $prod[3], SQLITE3_INTEGER);
                $stmt->bindValue(':stock', $prod[4], SQLITE3_INTEGER);
                $stmt->bindValue(':manufacturer', $prod[5], SQLITE3_TEXT);
                $stmt->bindValue(':prescription_required', $prod[6], SQLITE3_INTEGER);
                $stmt->bindValue(':expiry_date', $prod[7], SQLITE3_TEXT);
                $stmt->execute();
            }
        }
    }
    
    public function getConnection() {
        return $this->db;
    }
    
    public function prepare($query) {
        return $this->db->prepare($query);
    }
    
    public function query($query) {
        return $this->db->query($query);
    }
    
    public function lastInsertRowID() {
        return $this->db->lastInsertRowID();
    }
}
?>