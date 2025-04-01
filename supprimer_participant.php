<?php
require 'config/db.php';
include 'header.php';

// Vérifier si les données du formulaire sont présentes
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['noinscrip'])) {
    $noinscrip = $_POST['noinscrip'];
    $user = $_POST['user'];
    $codeanim = $_POST['codeanim'];
    $dateact = $_POST['dateact'];
    
    // Modifier la requête pour mettre à jour la date d'annulation au lieu de supprimer
    $today = date('Y-m-d'); // Date du jour
    
    $stmt = $pdo->prepare("UPDATE INSCRIPTION SET DATEANNULE = ? WHERE NOINSCRIP = ?");
    $result = $stmt->execute([$today, $noinscrip]);
    
    if ($result) {
        // Redirection avec message de succès
        header("Location: afficher_participant.php?success=1");
        exit;
    } else {
        // Redirection avec message d'erreur
        header("Location: afficher_participant.php?error=1");
        exit;
    }
} else {
    // Redirection si les données ne sont pas valides
    header("Location: afficher_participant.php");
    exit;
}
?>
