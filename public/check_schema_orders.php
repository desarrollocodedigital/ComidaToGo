<?php
$host = 'localhost';
$db_name = 'comidatogo_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->query("SHOW COLUMNS FROM orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Columnas en orders:\n";
    foreach ($columns as $col) {
        echo "- $col\n";
    }

} catch(PDOException $e) {
    echo "Error DB: " . $e->getMessage();
}
