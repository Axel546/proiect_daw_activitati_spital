<?php
require_once __DIR__ . '/../src/auth.php';

$pageTitle = 'Acces Neautorizat';
ob_start();
?>
<h1>Acces Refuzat</h1>
<p class="subtitle">Nu ai permisiunea de a accesa această resursă.</p>

<div class="info">
    <p>Nu ai permisiunile necesare pentru a efectua această acțiune.</p>
    <p>Dacă crezi că aceasta este o eroare, te rugăm să contactezi administratorul sistemului.</p>
</div>

<div style="margin-top: 20px;">
    <a href="index.php" class="btn">Mergi la Dashboard</a>
    <a href="activities/index.php" class="btn btn-secondary" style="margin-left: 10px;">Vezi Activități</a>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
