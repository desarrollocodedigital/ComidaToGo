<?php
$host = 'localhost';
$db_name = 'comidatogo_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('CUSTOMER', 'OWNER', 'ADMIN') DEFAULT 'CUSTOMER',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $conn->exec($sql);
    echo "Tabla users creada.\n";

    // Columnas extra en companies
    // Revisar si existen antes de agregar para no fallar
    $check = $conn->query("SHOW COLUMNS FROM companies LIKE 'owner_id'");
    if ($check->rowCount() == 0) {
        $conn->exec("ALTER TABLE companies ADD COLUMN owner_id INT AFTER id");
        echo "Columna owner_id agregada.\n";
    }

} catch(PDOException $e) {
    echo "Error DB: " . $e->getMessage();
}
