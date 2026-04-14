<?php
$host = 'localhost';
$db_name = 'comidatogo_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Revisar si existe
    $check = $conn->query("SHOW COLUMNS FROM orders LIKE 'estimated_completion_time'");
    if ($check->rowCount() == 0) {
        $conn->exec("ALTER TABLE orders ADD COLUMN estimated_completion_time DATETIME NULL AFTER scheduled_at");
        echo "Columna estimated_completion_time agregada.\n";
    } else {
        echo "Columna ya existe.\n";
    }

} catch(PDOException $e) {
    echo "Error DB: " . $e->getMessage();
}
