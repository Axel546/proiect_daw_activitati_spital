<?php
require_once __DIR__ . '/../src/auth.php';

logoutUser();

// redirectioneaza la pagina principala
header('Location: index.php?logged_out=1');
exit;
