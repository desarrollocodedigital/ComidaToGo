<?php
$host = 'localhost';
$db_name = 'comidatogo_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $email = 'admin@tacosjuan.com';
    $password = 'password';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Check
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $conn->prepare("UPDATE users SET password = :pass WHERE email = :email");
        $stmt->execute([':pass' => $hash, ':email' => $email]);
        echo "Password actualizado.\n";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES ('Juan Admin', :email, :pass, 'OWNER')");
        $stmt->execute([':email' => $email, ':pass' => $hash]);
        $uid = $conn->lastInsertId();
        echo "Usuario creado ID $uid.\n";
        
        $stmt = $conn->prepare("UPDATE companies SET owner_id = :uid WHERE id = 1");
        $stmt->execute([':uid' => $uid]);
        echo "Asignado a empresa 1.\n";
    }

} catch(PDOException $e) {
    echo "Error DB: " . $e->getMessage();
}
