<?php
session_start();
$userType = $_SESSION['user']['TYPEPROFIL'] ?? null; // Vérifie le type de profil
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Projet</title>
    <link rel="stylesheet" href="css/header.css"> <!-- Inclure ton CSS -->
</head>
<body>
    <header>
        <div class="header-container">
            <h1>VVA</h1>
            <nav>
                <a href="index.php" class="nav-button">Home</a>
                
                <?php if ($userType === 'va'): ?>
                    <!-- Bouton spécifique aux vacanciers -->
                    <a href="mes_reservations.php" class="vacancier-button">Mes Réservations</a>
                <?php endif; ?>
                
                <?php if ($userType === 'ad' || $userType === 'en'): ?>
                    <a href="suppression.php" class="nav-button">Gérer</a>
                    <a href="afficher_participant.php" class="nav-button">Liste Participant</a>
                <?php endif; ?>
                
                <?php if ($userType === 'ad'): ?>
                    <!-- Bouton spécifique aux administrateurs -->
                    <a href="identifiant.php" class="admin-button">Identifiant</a>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="logout.php" class="nav-button">Déconnexion</a>
                <?php else: ?>
                    <a href="login.php" class="nav-button">Se connecter</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
</body>
</html>