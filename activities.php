<?php
require 'config/db.php';
include 'header.php';

// Vérifier le type d'utilisateur
$isStaff = isset($_SESSION['user']) && ($_SESSION['user']['TYPEPROFIL'] === 'en' || $_SESSION['user']['TYPEPROFIL'] === 'ad');

if (isset($_GET['animation'])) {
    $animationCode = $_GET['animation'];
    $activites = $pdo->prepare("SELECT a.*, 
                                      an.NOMANIM 
                               FROM ACTIVITE a 
                               JOIN ANIMATION an ON a.CODEANIM = an.CODEANIM 
                               WHERE a.CODEANIM = ?
                               ORDER BY a.DATEACT ASC");
    $activites->execute([$animationCode]);
    $activites = $activites->fetchAll();
    
    // Récupérer le nom de l'animation
    $animationName = "";
    if (count($activites) > 0) {
        $animationName = $activites[0]['NOMANIM'];
    } else {
        $animStmt = $pdo->prepare("SELECT NOMANIM FROM ANIMATION WHERE CODEANIM = ?");
        $animStmt->execute([$animationCode]);
        $anim = $animStmt->fetch();
        if ($anim) {
            $animationName = $anim['NOMANIM'];
        }
    }
} else {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activités - <?php echo htmlspecialchars($animationName); ?></title>
    <link rel="stylesheet" href="css/styles1.css?v=<?php echo time(); ?>">
    <style>
        /* Style pour le filigrane du staff */
        .activity-box.staff-mode {
            position: relative;
            overflow: hidden;
        }
        
        .staff-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1;
            pointer-events: none;
        }
        
        .staff-text {
            font-size: 1.8rem;
            font-weight: bold;
            color: rgba(52, 152, 219, 0.3); /* Bleu avec transparence */
            transform: rotate(-30deg);
            text-transform: uppercase;
            letter-spacing: 2px;
            border: 3px solid rgba(52, 152, 219, 0.2);
            padding: 5px 15px;
            white-space: nowrap;
            user-select: none;
        }
        
        .activity-box.staff-mode .activity-content {
            position: relative;
            z-index: 2;
        }
        
        .staff-notice {
            background-color: rgba(52, 152, 219, 0.1);
            border-left: 4px solid #3498db;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 500;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .staff-notice i {
            color: #3498db;
            font-size: 1.4rem;
        }
        
        .staff-button {
            background-color: #3498db !important;
            cursor: pointer !important;
        }
        
        .staff-message {
            color: #3498db;
            font-weight: 500;
            margin-top: 5px;
            font-size: 0.9rem;
            padding: 5px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h2><?php echo htmlspecialchars($animationName); ?> - Activités</h2>
    
    <?php if ($isStaff): ?>
    <div class="staff-notice">
        <i class="fas fa-info-circle"></i>
        Vous êtes connecté en tant que membre du staff. Vous pouvez consulter les activités mais ne pouvez pas effectuer de réservation.
    </div>
    <?php endif; ?>
    
    <div class="activities-container">
        <?php if (count($activites) > 0): ?>
            <?php foreach ($activites as $activite): 
                $isAnnulee = !empty($activite['DATEANNULEACT']);
            ?>
                <div class="activity-box <?php echo $isAnnulee ? 'cancelled' : ($isStaff ? 'staff-mode' : ''); ?>">
                    <?php if ($isAnnulee): ?>
                    <div class="cancelled-overlay">
                        <div class="cancelled-text">ANNULÉ</div>
                    </div>
                    <?php elseif ($isStaff): ?>
                    <div class="staff-overlay">
                        <div class="staff-text">Mode Staff</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="activity-content">
                        <h3><?php echo htmlspecialchars($activite['NOMRESP'] . ' ' . $activite['PRENOMRESP']); ?></h3>
                        <p class="activity-date">
                            <strong>Date :</strong> <?php echo date('d/m/Y', strtotime($activite['DATEACT'])); ?>
                        </p>
                        <p><strong>Heure de début :</strong> <?php echo date('H:i', strtotime($activite['HRDEBUTACT'])); ?></p>
                        <p><strong>Heure de fin :</strong> <?php echo date('H:i', strtotime($activite['HRFINACT'])); ?></p>
                        <p><strong>Prix :</strong> <?php echo htmlspecialchars($activite['PRIXACT']); ?>€</p>
                        
                        <?php if ($isAnnulee): ?>
                            <p class="activity-cancelled-notice">
                                Cette activité a été annulée le <?php echo date('d/m/Y', strtotime($activite['DATEANNULEACT'])); ?>
                            </p>
                            <button type="button" disabled>Inscription impossible</button>
                        <?php elseif ($isStaff): ?>
                            <p class="staff-message">
                                En tant que membre du staff, vous ne pouvez pas vous inscrire aux activités.
                            </p>
                            <button type="button" class="staff-button">Consultation uniquement</button>
                        <?php else: ?>
                            <form method="post" action="register_activity.php">
                                <input type="hidden" name="activity_code" value="<?php echo htmlspecialchars($activite['CODEANIM']); ?>">
                                <input type="hidden" name="activity_date" value="<?php echo htmlspecialchars($activite['DATEACT']); ?>">
                                <button type="submit">S'inscrire</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-activities">Aucune activité disponible pour cette animation.</div>
        <?php endif; ?>
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
<?php include 'footer.php'; ?>