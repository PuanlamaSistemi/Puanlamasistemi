<?php
// includes/db.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Veritabanı bağlantısı ayarları
$host = 'localhost';
$dbname = 'puanlama_db';
$username = 'puanlama_db';
$password = 'Samoli123.';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Hata modunu exception olarak ayarla
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Varsayılan fetch modunu associative array yap
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası (Vize sunumunda XAMPP/MySQL'in açık olduğundan emin olun): " . $e->getMessage());
}
?>
