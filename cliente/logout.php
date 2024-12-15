<?php
session_start();
// Destruir todas as sessões
session_destroy();
// Redirecionar para a página de login ou home
header("Location: /agendamento-php/index.php"); // Redirecting to the correct index.php path
exit();
?>
