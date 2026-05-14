<?php
require_once 'includes/db.php';

echo "Veritabanı bağlantısı başarılı!<br>";

$result = $mysqli->query("SELECT COUNT(*) as count FROM users");
$row = $result->fetch_assoc();
echo "Kullanıcı sayısı: " . $row['count'] . "<br>";

$result = $mysqli->query("SELECT COUNT(*) as count FROM projects");
$row = $result->fetch_assoc();
echo "Proje sayısı: " . $row['count'] . "<br>";

echo "Test tamamlandı.";
?>