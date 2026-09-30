<?php
/**
 * table.php
 * 
 * Creates all necessary tables for the ezAccount system.
 */

include('connection.php');

// Array of SQL statements
$tables = [

    // business_owners
    "CREATE TABLE IF NOT EXISTS business_owners (
        owner_id INT(11) NOT NULL AUTO_INCREMENT,
        owner_name VARCHAR(255) NOT NULL,
        company_name VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(100) NOT NULL,
        phone_number VARCHAR(12) NOT NULL,
        PRIMARY KEY(owner_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // outlets
    "CREATE TABLE IF NOT EXISTS outlets (
        outlet_id INT(11) NOT NULL AUTO_INCREMENT,
        owner_id INT(11) NOT NULL,
        outlet_name VARCHAR(255) NOT NULL,
        outlet_address VARCHAR(255) NOT NULL,
        PRIMARY KEY(outlet_id),
        FOREIGN KEY(owner_id) REFERENCES business_owners(owner_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // staff
    "CREATE TABLE IF NOT EXISTS staff (
        staff_id INT(11) NOT NULL AUTO_INCREMENT,
        outlet_id INT(11) NOT NULL,
        staff_name VARCHAR(255) NOT NULL,
        ic_number VARCHAR(14) NOT NULL UNIQUE,
        age INT(3) NOT NULL,
        phone_number VARCHAR(12) NOT NULL,
        address VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(100) NOT NULL,
        PRIMARY KEY(staff_id),
        FOREIGN KEY(outlet_id) REFERENCES outlets(outlet_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // stock_category
    "CREATE TABLE IF NOT EXISTS stock_category (
        category_id INT(11) NOT NULL AUTO_INCREMENT,
        category_name VARCHAR(255) NOT NULL,
        PRIMARY KEY(category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // stock_items
    "CREATE TABLE IF NOT EXISTS stock_items (
        item_id INT(11) NOT NULL AUTO_INCREMENT,
        category_id INT(11) NOT NULL,
        outlet_id INT(11) NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        base_price DECIMAL(10,2) NOT NULL,
        remaining_quantity INT(11) DEFAULT NULL,
        image VARCHAR(255),
        PRIMARY KEY(item_id),
        FOREIGN KEY(category_id) REFERENCES stock_category(category_id) ON DELETE CASCADE,
        FOREIGN KEY(outlet_id) REFERENCES outlets(outlet_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // stock_movements
    "CREATE TABLE IF NOT EXISTS stock_movements (
        movement_id INT(11) NOT NULL AUTO_INCREMENT,
        item_id INT(11) NOT NULL,
        staff_id INT(11) DEFAULT NULL,
        movement_date DATE NOT NULL,
        movement_type ENUM('IN','OUT') NOT NULL,
        movement_quantity INT(11) NOT NULL,
        total_stock_value DECIMAL(10,2) NOT NULL,
        note TEXT DEFAULT NULL,
        PRIMARY KEY(movement_id),
        FOREIGN KEY(item_id) REFERENCES stock_items(item_id) ON DELETE CASCADE,
        FOREIGN KEY(staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // closing_reports
    "CREATE TABLE IF NOT EXISTS closing_reports (
        report_id INT(11) NOT NULL AUTO_INCREMENT,
        outlet_id INT(11) NOT NULL,
        staff_id INT(11) DEFAULT NULL,
        report_date DATE NOT NULL,
        shift ENUM('Morning','Evening') NOT NULL,
        cash_float DECIMAL(10,2) NOT NULL,
        cash_sale DECIMAL(10,2) NOT NULL,
        qr_sale DECIMAL(10,2) NOT NULL,
        total_cash DECIMAL(10,2) NOT NULL,
        total_payment DECIMAL(10,2) NOT NULL,
        expected_cash_in_hand DECIMAL(10,2) NOT NULL,
        actual_cash_in_hand DECIMAL(10,2) NOT NULL,
        difference DECIMAL(10,2) NOT NULL,
        note TEXT DEFAULT NULL,
        PRIMARY KEY(report_id),
        FOREIGN KEY(outlet_id) REFERENCES outlets(outlet_id) ON DELETE CASCADE,
        FOREIGN KEY(staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // closing_report_cash_breakdown
    "CREATE TABLE IF NOT EXISTS closing_report_cash_breakdown (
        breakdown_id INT(11) NOT NULL AUTO_INCREMENT,
        report_id INT(11) NOT NULL,
        denomination DECIMAL(10,2) NOT NULL,
        quantity INT(11) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        PRIMARY KEY(breakdown_id),
        FOREIGN KEY(report_id) REFERENCES closing_reports(report_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // closing_report_payments
    "CREATE TABLE IF NOT EXISTS closing_report_payments (
        payment_id INT(11) NOT NULL AUTO_INCREMENT,
        report_id INT(11) NOT NULL,
        payment_type VARCHAR(100) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        PRIMARY KEY(payment_id),
        FOREIGN KEY(report_id) REFERENCES closing_reports(report_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

// Execute each table creation
foreach ($tables as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "✅ Table created successfully.<br>";
    } else {
        echo "❌ Error creating table: " . $conn->error . "<br>";
    }
}

$conn->close();
?>
