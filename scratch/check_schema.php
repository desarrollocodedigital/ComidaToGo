<?php
require 'src/bootstrap.php';
$db = App\Config\Database::getInstance()->getConnection();
$res = $db->query('DESCRIBE orders');
while($row = $res->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}
