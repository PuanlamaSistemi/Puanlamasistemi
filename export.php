<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'jury') {
    die("Erişim reddedildi.");
}

// Dosya adını ve türünü ayarla
$filename = "proje_oylama_sonuclari_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Çıktı tamponunu aç
$output = fopen('php://output', 'w');

// BOM (Byte Order Mark) ekle ki Excel Türkçe karakterleri düzgün okusun
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

// CSV başlıklarını yaz (Noktalı virgül ile)
fputcsv($output, array('Proje Kodu', 'Proje Adı', 'Toplam Oy', 'Tasarım Puanı', 'Teknik Puan', 'Sunum Puanı', 'İnovasyon Puanı', 'Genel Ortalama'), ';');

// Veritabanından projelerin ortalamalarını çek
$stmt = $pdo->query("
    SELECT 
        p.id, 
        p.name, 
        COUNT(v.id) as total_votes,
        AVG(v.design_score)/2 as avg_design,
        AVG(v.tech_score)/2 as avg_tech,
        AVG(v.presentation_score)/2 as avg_presentation,
        AVG(v.innovation_score)/2 as avg_innovation,
        AVG((v.design_score + v.tech_score + v.presentation_score + v.innovation_score)/4)/2 as general_avg
    FROM projects p
    LEFT JOIN votes v ON p.id = v.project_id
    GROUP BY p.id
    ORDER BY general_avg DESC
");

// Satırları CSV'ye yaz (Noktalı virgül ve virgüllü ondalık ile)
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, array(
        $row['id'],
        $row['name'],
        $row['total_votes'],
        $row['total_votes'] > 0 ? number_format($row['avg_design'], 2, ',', '') : '0,00',
        $row['total_votes'] > 0 ? number_format($row['avg_tech'], 2, ',', '') : '0,00',
        $row['total_votes'] > 0 ? number_format($row['avg_presentation'], 2, ',', '') : '0,00',
        $row['total_votes'] > 0 ? number_format($row['avg_innovation'], 2, ',', '') : '0,00',
        $row['total_votes'] > 0 ? number_format($row['general_avg'], 2, ',', '') : '0,00'
    ), ';');
}

fclose($output);
exit;
?>
