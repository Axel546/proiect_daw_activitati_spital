<?php
require_once __DIR__ . '/../app/src/db.php';
require_once __DIR__ . '/../app/src/helpers.php';
require_once __DIR__ . '/../app/src/auth.php';
require_once __DIR__ . '/../app/src/reports_helper.php';

requireLogin();

// Verifica daca FPDF disponibil
$fpdfPath = __DIR__ . '/../app/libs/fpdf.php';
$fontPath = __DIR__ . '/../app/libs/font/';
if (!file_exists($fpdfPath)) {
    http_response_code(500);
    die('
        <html>
        <head><title>Biblioteca FPDF Nu a Fost Gasita</title></head>
        <body style="font-family: Arial, sans-serif; padding: 40px; max-width: 600px; margin: 0 auto;">
            <h1>Biblioteca FPDF Nu a Fost Gasita</h1>
            <p><strong>Eroare:</strong> Biblioteca FPDF este necesara pentru a genera rapoarte PDF.</p>
            <p><strong>Instructiuni de Configurare:</strong></p>
            <ol>
                <li>Descarca FPDF de la: <a href="http://www.fpdf.org/" target="_blank">http://www.fpdf.org/</a></li>
                <li>Extrage fișierul ZIP</li>
                <li>Copiaza <code>fpdf.php</code> in <code>libs/fpdf.php</code> in proiectul tau</li>
                <li><strong>IMPORTANT:</strong> Copiaza intregul director <code>font/</code> din pachetul FPDF in <code>libs/font/</code></li>
                <li>Reîmprospătează această pagină</li>
            </ol>
            <p style="margin-top: 20px;">
                <a href="activities.php" style="color: #1976d2;">← Înapoi la Rapoarte</a>
            </p>
        </body>
        </html>
    ');
}

// Verifica daca font există
if (!is_dir($fontPath)) {
    http_response_code(500);
    die('
        <html>
        <head><title>Directorul Font FPDF Nu a Fost Gasit</title></head>
        <body style="font-family: Arial, sans-serif; padding: 40px; max-width: 600px; margin: 0 auto;">
            <h1>Directorul Font FPDF Nu a Fost Gasit</h1>
            <p><strong>Eroare:</strong> Directorul font FPDF este necesar. FPDF 1.86 necesita fisiere de definitie font chiar si pentru fonturile de baza.</p>
            <p><strong>Instructiuni de Configurare:</strong></p>
            <ol>
                <li>In pachetul FPDF pe care l-ai descarcat, localizeaza directorul <code>font/</code></li>
                <li>Copiaza intregul director <code>font/</code> in <code>libs/font/</code> in proiectul tau</li>
                <li>Directorul ar trebui sa contina fisiere precum: <code>helvetica.php</code>, <code>helveticab.php</code>, <code>times.php</code>, etc.</li>
                <li>Reimprospateaza această pagina</li>
            </ol>
            <p style="margin-top: 20px;">
                <a href="activities.php" style="color: #1976d2;">← Inapoi la Rapoarte</a>
            </p>
        </body>
        </html>
    ');
}

// Configureaza cale font FPDF înainte de a include
if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', __DIR__ . '/../app/libs/font/');
}

require_once $fpdfPath;

// Verifica daca clasa FPDF exista
if (!class_exists('FPDF')) {
    http_response_code(500);
    die('
        <html>
        <head><title>Clasa FPDF Nu a Fost Gasita</title></head>
        <body style="font-family: Arial, sans-serif; padding: 40px; max-width: 600px; margin: 0 auto;">
            <h1>Clasa FPDF Nu a Fost Gasita</h1>
            <p><strong>Eroare:</strong> Clasa FPDF nu este disponibila. Te rugam sa te asiguri ca fișierul bibliotecii FPDF este plasat corect in <code>libs/fpdf.php</code>.</p>
            <p style="margin-top: 20px;">
                <a href="activities.php" style="color: #1976d2;">← Inapoi la Rapoarte</a>
            </p>
        </body>
        </html>
    ');
}

$pdo = getDbConnection();

// Obtine parametrii de filtrare
$filters = [
    'department_id' => isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0,
    'status' => isset($_GET['status']) ? trim($_GET['status']) : '',
    'date_from' => isset($_GET['date_from']) ? trim($_GET['date_from']) : '',
    'date_to' => isset($_GET['date_to']) ? trim($_GET['date_to']) : '',
    'created_by' => isset($_GET['created_by']) ? (int)$_GET['created_by'] : 0
];

// Valideaza status dacă este furnizat
if ($filters['status'] && !in_array($filters['status'], ['planned', 'in_progress', 'completed', 'cancelled'])) {
    $filters['status'] = '';
}

// Preia activitati
$activities = getFilteredActivities($pdo, $filters);
$filterDescription = getFilterDescription($filters, $pdo);

// Creeaza PDF
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Helvetica', 'B', 16);
        $this->Cell(0, 10, 'Raport Activitati Spital', 0, 1, 'C');
        $this->SetFont('Helvetica', '', 10);
        $this->Cell(0, 5, 'Generat: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Helvetica', '', 10);

// Adauga info  filtre
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Filtre Aplicate:', 0, 1);
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(0, 6, $filterDescription, 0, 1);
$pdf->Cell(0, 6, 'Total Activitati: ' . count($activities), 0, 1);
$pdf->Ln(5);

// Antet tabel
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(15, 8, 'ID', 1, 0, 'C', true);
$pdf->Cell(60, 8, 'Titlu', 1, 0, 'L', true);
$pdf->Cell(35, 8, 'Departament', 1, 0, 'L', true);
$pdf->Cell(30, 8, 'Status', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Creată', 1, 0, 'C', true);
$pdf->Cell(20, 8, 'Creator', 1, 1, 'L', true);

// Date tabel
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetFillColor(255, 255, 255);
foreach ($activities as $activity) {
    $pdf->Cell(15, 6, $activity['id'], 1, 0, 'C');
    $pdf->Cell(60, 6, substr($activity['title'], 0, 35), 1, 0, 'L');
    $pdf->Cell(35, 6, substr($activity['department_name'] ?? 'N/A', 0, 20), 1, 0, 'L');
    $pdf->Cell(30, 6, translateStatus($activity['status']), 1, 0, 'C');
    $pdf->Cell(30, 6, date('Y-m-d', strtotime($activity['created_at'])), 1, 0, 'C');
    
    $creator = trim(($activity['first_name'] ?? '') . ' ' . ($activity['last_name'] ?? ''));
    $creatorName = $creator ?: $activity['created_by_username'] ?? 'Necunoscut';
    $pdf->Cell(20, 6, substr($creatorName, 0, 15), 1, 1, 'L');
    
    // Verifica daca avem nevoie de pagina noua
    if ($pdf->GetY() > 270) {
        $pdf->AddPage();
        // Repeta antetul
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(15, 8, 'ID', 1, 0, 'C', true);
        $pdf->Cell(60, 8, 'Titlu', 1, 0, 'L', true);
        $pdf->Cell(35, 8, 'Departament', 1, 0, 'L', true);
        $pdf->Cell(30, 8, 'Status', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Creată', 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'Creator', 1, 1, 'L', true);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetFillColor(255, 255, 255);
    }
}

// Genereaza PDF
$filename = 'raport_activitati_' . date('Y-m-d_His') . '.pdf';
$pdf->Output('D', $filename); // 'D' = descarcare, 'I' = inline
exit;
