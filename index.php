<?php
require 'config/db.php';
include 'header.php';

// Vérifier le type d'utilisateur
$isStaff = isset($_SESSION['user']) && ($_SESSION['user']['TYPEPROFIL'] === 'en' || $_SESSION['user']['TYPEPROFIL'] === 'ad');

$animations = $pdo->query("SELECT * FROM ANIMATION")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Animations</title>
    <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>">

</head>
<body>
    <h2>Liste Des Animations Du Moment</h2>
    
    <?php if ($isStaff): ?>
    <div class="staff-notice">
        <i class="fas fa-info-circle"></i>
        Vous êtes connecté en tant que membre du staff. Vous pouvez consulter les activités mais ne pouvez pas effectuer de réservation.
    </div>
    <?php endif; ?>
    
    <div class="animations-container">
        <?php foreach ($animations as $anim): ?>
            <div class="animation-box <?php echo $isStaff ? 'staff-mode' : ''; ?>" onclick="window.location.href='activities.php?animation=<?php echo $anim['CODEANIM']; ?>'">
                <?php if ($isStaff): ?>
                <div class="staff-overlay">
                    <div class="staff-text">Mode Staff</div>
                </div>
                <?php endif; ?>
                
                <div class="animation-content">
                    <h3><?php echo htmlspecialchars($anim['NOMANIM']); ?></h3>
                    <p><?php echo htmlspecialchars($anim['DESCRIPTANIM']); ?></p>
                    <p><?php echo htmlspecialchars($anim['NBREPLACEANIM']); ?> place(s) disponible(s)</p>
                    <button><?php echo $isStaff ? 'Consulter les activités' : 'Voir les activités'; ?></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <script>
        // Ajout de la classe FontAwesome si elle n'est pas déjà incluse
        if (!document.querySelector('link[href*="font-awesome"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
            document.head.appendChild(link);
        }
    </script>
</body>
</html>

<?php
// Inclusion du footer
include 'footer.php';
?>