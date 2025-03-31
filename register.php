<?php
require 'config/db.php';
session_start();

// Variable pour stocker les messages
$messageType = '';
$messageTitle = '';
$messageContent = '';

// Traitement du formulaire de confirmation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_registration'])) {
    // Vérifie si l'utilisateur est connecté
    if (!isset($_SESSION['user'])) {
        $messageType = 'error';
        $messageTitle = 'Erreur d\'authentification';
        $messageContent = 'Vous devez être connecté pour vous inscrire.';
    } else {
        // Récupère les informations
        $activityCode = $_POST['activity_code'];
        $activityDate = $_POST['activity_date'];
        $user = $_SESSION['user']['USER']; // Récupère l'identifiant de l'utilisateur

        // Récupérer les dates de séjour de l'utilisateur
        $stmt = $pdo->prepare("SELECT DATEDEBSEJOUR, DATEFINSEJOUR, DATENAISCOMPTE FROM COMPTE WHERE USER = ?");
        $stmt->execute([$user]);
        $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userDetails) {
            $messageType = 'error';
            $messageTitle = 'Utilisateur introuvable';
            $messageContent = 'Impossible de trouver vos informations utilisateur.';
        } else {
            $startDate = $userDetails['DATEDEBSEJOUR'];
            $endDate = $userDetails['DATEFINSEJOUR'];
            $birthDate = $userDetails['DATENAISCOMPTE'];

            // Vérification si la date de l'activité est comprise dans les dates de séjour
            if ($startDate && $endDate) {
                if ($activityDate < $startDate || $activityDate > $endDate) {
                    $messageType = 'error';
                    $messageTitle = 'Dates incompatibles';
                    $messageContent = 'La date de l\'activité ne correspond pas à vos dates de séjour. Veuillez vérifier et réessayer.';
                } else {
                    // Vérification de l'âge minimum pour participer à l'animation
                    $stmt = $pdo->prepare("SELECT a.LIMITEAGE, a.NBREPLACEANIM, a.NOMANIM FROM ANIMATION a WHERE a.CODEANIM = ?");
                    $stmt->execute([$activityCode]);
                    $activityDetails = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$activityDetails) {
                        $messageType = 'error';
                        $messageTitle = 'Activité introuvable';
                        $messageContent = 'Impossible de trouver les détails de l\'activité.';
                    } else {
                        $ageLimit = $activityDetails['LIMITEAGE'];
                        $remainingPlaces = $activityDetails['NBREPLACEANIM'];
                        $activityName = $activityDetails['NOMANIM'];

                        $currentDate = new DateTime();
                        $birthDate = new DateTime($birthDate);
                        $age = $currentDate->diff($birthDate)->y;

                        if ($age < $ageLimit) {
                            $messageType = 'error';
                            $messageTitle = 'Âge insuffisant';
                            $messageContent = "Vous n'avez pas l'âge requis pour participer à cette activité. Age minimum requis : {$ageLimit} ans.";
                        } elseif ($remainingPlaces <= 0) {
                            $messageType = 'error';
                            $messageTitle = 'Plus de places disponibles';
                            $messageContent = "Il n'y a plus de places disponibles pour cette activité.";
                        } else {
                            // Vérifier si l'utilisateur est déjà inscrit
                            $stmt = $pdo->prepare("SELECT * FROM INSCRIPTION WHERE USER = ? AND CODEANIM = ? AND DATEACT = ?");
                            $stmt->execute([$user, $activityCode, $activityDate]);
                            
                            if ($stmt->rowCount() > 0) {
                                $messageType = 'error';
                                $messageTitle = 'Déjà inscrit';
                                $messageContent = "Vous êtes déjà inscrit à cette activité.";
                            } else {
                                // Tout est bon, on peut inscrire l'utilisateur
                                $stmt = $pdo->prepare("INSERT INTO INSCRIPTION (USER, CODEANIM, DATEACT, DATEINSCRIP) VALUES (?, ?, ?, NOW())");
                                
                                if ($stmt->execute([$user, $activityCode, $activityDate])) {
                                    // Mise à jour du nombre de places disponibles
                                    $stmt = $pdo->prepare("UPDATE ANIMATION SET NBREPLACEANIM = NBREPLACEANIM - 1 WHERE CODEANIM = ?");
                                    $stmt->execute([$activityCode]);
                                    
                                    $messageType = 'success';
                                    $messageTitle = 'Inscription réussie';
                                    $messageContent = "Votre inscription à l'activité $activityName a été enregistrée avec succès!";
                                } else {
                                    $messageType = 'error';
                                    $messageTitle = 'Erreur d\'inscription';
                                    $messageContent = "Une erreur s'est produite lors de l'inscription. Veuillez réessayer.";
                                }
                            }
                        }
                    }
                }
            } else {
                $messageType = 'error';
                $messageTitle = 'Dates non définies';
                $messageContent = "Vos dates de séjour ne sont pas définies. Veuillez vérifier votre compte.";
            }
        }
    }
}

// Si c'est une demande initiale (première étape pour afficher la confirmation)
$activityInfo = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['confirm_registration'])) {
    // Vérifie si l'utilisateur est connecté
    if (!isset($_SESSION['user'])) {
        $messageType = 'error';
        $messageTitle = 'Erreur d\'authentification';
        $messageContent = 'Vous devez être connecté pour vous inscrire.';
    } else {
        $activityCode = $_POST['activity_code'];
        $activityDate = $_POST['activity_date'];
        
        // Récupérer les informations de l'activité pour affichage
        $stmt = $pdo->prepare("SELECT a.NOMANIM, ac.HRDEBUTACT, ac.HRFINACT, ac.PRIXACT, ac.DATEACT, a.LIMITEAGE
                            FROM ANIMATION a 
                            JOIN ACTIVITE ac ON a.CODEANIM = ac.CODEANIM 
                            WHERE a.CODEANIM = ? AND ac.DATEACT = ?");
        $stmt->execute([$activityCode, $activityDate]);
        $activityInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$activityInfo) {
            $messageType = 'error';
            $messageTitle = 'Activité introuvable';
            $messageContent = 'Impossible de trouver l\'activité demandée.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription à l'activité</title>
    <link rel="stylesheet" href="css/styles1.css?v=<?php echo time(); ?>">
    <style>
        /* CSS intégré pour les animations et modales */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes zoomIn {
            from { 
                opacity: 0; 
                transform: scale(0.5); 
            }
            to { 
                opacity: 1; 
                transform: scale(1); 
            }
        }
        
        @keyframes bounceIn {
            0%, 20%, 40%, 60%, 80%, to {
                animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
            }
            0% {
                opacity: 0;
                transform: scale3d(0.3, 0.3, 0.3);
            }
            20% {
                transform: scale3d(1.1, 1.1, 1.1);
            }
            40% {
                transform: scale3d(0.9, 0.9, 0.9);
            }
            60% {
                opacity: 1;
                transform: scale3d(1.03, 1.03, 1.03);
            }
            80% {
                transform: scale3d(0.97, 0.97, 0.97);
            }
            to {
                opacity: 1;
                transform: scale3d(1, 1, 1);
            }
        }
        
        @keyframes pulse {
            from { transform: scale3d(1, 1, 1); }
            50% { transform: scale3d(1.05, 1.05, 1.05); }
            to { transform: scale3d(1, 1, 1); }
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            animation: zoomIn 0.3s ease;
        }
        
        .modal-header {
            background: var(--primary-color);
            color: white;
            padding: 1.2rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .activity-details {
            background: rgba(74, 111, 255, 0.05);
            border-radius: var(--border-radius);
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .activity-info {
            margin-bottom: 0.8rem;
            display: flex;
            align-items: flex-start;
        }
        
        .activity-info:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--text-dark);
            width: 100px;
            flex-shrink: 0;
        }
        
        .info-value {
            color: var(--text-gray);
        }
        
        .confirmation-text {
            text-align: center;
            font-size: 1.1rem;
            margin: 1.5rem 0;
            color: var(--text-dark);
            animation: pulse 1.5s infinite;
        }
        
        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .confirm-btn, .cancel-btn, .action-btn {
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .confirm-btn {
            background: var(--primary-color);
            color: white;
            border: none;
        }
        
        .confirm-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 111, 255, 0.3);
        }
        
        .cancel-btn {
            background: transparent;
            color: var(--text-gray);
            border: 1px solid var(--text-gray);
        }
        
        .cancel-btn:hover {
            background: rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }
        
        /* Styles pour les résultats */
        .result-icon {
            font-size: 4rem;
            margin: 2rem 0 1rem;
            text-align: center;
            animation: bounceIn 0.75s;
        }
        
        .success-icon {
            color: #4CAF50;
        }
        
        .error-icon {
            color: #FF6B6B;
        }
        
        .result-title {
            margin: 0 0 1rem;
            color: var(--text-dark);
            font-size: 1.5rem;
            text-align: center;
        }
        
        .result-message {
            margin: 0 1.5rem 2rem;
            color: var(--text-gray);
            font-size: 1.1rem;
            padding: 0 1rem;
            line-height: 1.6;
            text-align: center;
        }
        
        .result-actions {
            background: rgba(0, 0, 0, 0.02);
            padding: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: center;
        }
        
        .action-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            margin: 0 auto;
            justify-content: center;
        }
        
        .action-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 111, 255, 0.3);
        }
        
        /* Conteneur principal */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem 3rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .modal-actions {
                flex-direction: column;
                gap: 0.8rem;
            }
            
            .confirm-btn, .cancel-btn, .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h2>Inscription à l'activité</h2>
        
        <?php if ($activityInfo && !$messageType): ?>
            <!-- Popup de confirmation -->
            <div class="modal-overlay">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Confirmer votre inscription</h3>
                        <button type="button" class="close-button" onclick="window.history.back()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="activity-details">
                            <div class="activity-info">
                                <span class="info-label">Activité:</span>
                                <span class="info-value"><?= htmlspecialchars($activityInfo['NOMANIM']) ?></span>
                            </div>
                            <div class="activity-info">
                                <span class="info-label">Date:</span>
                                <span class="info-value"><?= date('d/m/Y', strtotime($activityInfo['DATEACT'])) ?></span>
                            </div>
                            <div class="activity-info">
                                <span class="info-label">Horaires:</span>
                                <span class="info-value">
                                    <?= substr($activityInfo['HRDEBUTACT'], 0, 5) ?> - <?= substr($activityInfo['HRFINACT'], 0, 5) ?>
                                </span>
                            </div>
                            <div class="activity-info">
                                <span class="info-label">Prix:</span>
                                <span class="info-value"><?= number_format($activityInfo['PRIXACT'], 2, ',', ' ') ?> €</span>
                            </div>
                            <?php if ($activityInfo['LIMITEAGE'] > 0): ?>
                            <div class="activity-info">
                                <span class="info-label">Âge minimum:</span>
                                <span class="info-value"><?= $activityInfo['LIMITEAGE'] ?> ans</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <p class="confirmation-text">Êtes-vous sûr de vouloir vous inscrire à cette activité?</p>
                        
                        <div class="modal-actions">
                            <form method="post" action="register_activity.php">
                                <input type="hidden" name="activity_code" value="<?= htmlspecialchars($_POST['activity_code']) ?>">
                                <input type="hidden" name="activity_date" value="<?= htmlspecialchars($_POST['activity_date']) ?>">
                                <input type="hidden" name="confirm_registration" value="1">
                                <button type="submit" class="confirm-btn">
                                    <i class="fas fa-check"></i> Confirmer l'inscription
                                </button>
                            </form>
                            <button type="button" class="cancel-btn" onclick="window.history.back()">
                                <i class="fas fa-times"></i> Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($messageType): ?>
            <!-- Modal de résultat -->
            <div class="modal-overlay">
                <div class="modal-content">
                    <div class="result-icon <?= $messageType ?>-icon">
                        <?php if ($messageType == 'success'): ?>
                            <i class="fas fa-check-circle"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle"></i>
                        <?php endif; ?>
                    </div>
                    <h3 class="result-title"><?= $messageTitle ?></h3>
                    <p class="result-message"><?= $messageContent ?></p>
                    <div class="result-actions">
                        <?php if ($messageType == 'success'): ?>
                            <a href="index.php" class="action-btn">
                                <i class="fas fa-home"></i> Retour à l'accueil
                            </a>
                        <?php else: ?>
                            <button type="button" class="action-btn" onclick="window.history.back()">
                                <i class="fas fa-arrow-left"></i> Retour
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <!-- Script pour les animations -->
    <script>
        // Si on a un résultat, empêcher le retour arrière normal
        <?php if ($messageType): ?>
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function () {
            history.pushState(null, null, document.URL);
        });
        <?php endif; ?>
    </script>
</body>
</html>