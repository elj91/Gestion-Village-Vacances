<?php
require 'config/db.php';
include 'header.php';

// Récupération des états d'activité
$etatsActivite = $pdo->query("SELECT CODEETATACT, NOMETATACT FROM ETAT_ACT")->fetchAll();

// Traitement du formulaire de modification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['modifier_activite'])) {
    $codeAnim = $_POST['codeanim'];
    $dateAct = $_POST['dateact'];
    $codeEtatAct = $_POST['codeetatact'];
    $hrrdvAct = $_POST['hrrdvact'];
    $prixAct = $_POST['prixact'];
    $hrDebutAct = $_POST['hrdebutact'];
    $hrFinAct = $_POST['hrfinact'];
    $nomResp = $_POST['nomresp'];
    $prenomResp = $_POST['prenomresp'];
    
    // Requête de mise à jour
    $stmt = $pdo->prepare("UPDATE ACTIVITE SET 
        CODEETATACT = :codeEtatAct,
        HRRDVACT = :hrrdvAct,
        PRIXACT = :prixAct,
        HRDEBUTACT = :hrDebutAct,
        HRFINACT = :hrFinAct,
        NOMRESP = :nomResp,
        PRENOMRESP = :prenomResp
        WHERE CODEANIM = :codeAnim AND DATEACT = :dateAct");
    
    $result = $stmt->execute([
        'codeEtatAct' => $codeEtatAct,
        'hrrdvAct' => $hrrdvAct,
        'prixAct' => $prixAct,
        'hrDebutAct' => $hrDebutAct,
        'hrFinAct' => $hrFinAct,
        'nomResp' => $nomResp,
        'prenomResp' => $prenomResp,
        'codeAnim' => $codeAnim,
        'dateAct' => $dateAct
    ]);
    
    if ($result) {
        $successMessage = "L'activité a été modifiée avec succès.";
    } else {
        $errorMessage = "Erreur lors de la modification de l'activité.";
    }
}

// Récupération de l'activité si un ID est fourni dans l'URL
$activity = null;
if (isset($_GET['code']) && isset($_GET['date'])) {
    $codeAnim = $_GET['code'];
    $dateAct = $_GET['date'];
    
    $stmt = $pdo->prepare("SELECT a.*, n.NOMANIM 
                          FROM ACTIVITE a 
                          JOIN ANIMATION n ON a.CODEANIM = n.CODEANIM 
                          WHERE a.CODEANIM = ? AND a.DATEACT = ?");
    $stmt->execute([$codeAnim, $dateAct]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Si l'activité n'existe pas et qu'aucun message de succès n'est affiché, rediriger vers la liste


// Récupérer la liste des activités pour le sélecteur
$activites = $pdo->query("SELECT a.CODEANIM, a.DATEACT, n.NOMANIM, a.NOMRESP, a.PRENOMRESP 
                        FROM ACTIVITE a 
                        JOIN ANIMATION n ON a.CODEANIM = n.CODEANIM 
                        ORDER BY a.DATEACT DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une Activité</title>
    <link rel="stylesheet" href="css/gestion.css?v=<?php echo time(); ?>">
</head>
<body>
<header class="navbar">
        <div class="navbar-container">
            <h1 class="navbar-title">Gestion</h1>
            <nav class="navbar-links">
                <a href="suppression.php" class="nav-button">Annuler Activité</a>
                <?php if ($userType === 'ad'|| $userType === 'en'): ?>
                    <a href="ajt_act.php" class="nav-button">Ajouter Activité</a>
                    <a href="ajt_anim.php" class="nav-button">Ajouter Animation</a>
                    <a href="modif_act.php" class="nav-button">Modifier Activité</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <h2>Modifier une Activité</h2>
    
    <main>
        <?php if (isset($successMessage)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?= $successMessage ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?= $errorMessage ?>
            </div>
        <?php endif; ?>
        
        <div class="activity-selector">
    <h3>Sélectionner une activité à modifier</h3>
    <form method="get" action="modif_act.php" class="selector-form">
        <select name="activity" id="activity-select">
            <option value="">Choisir une activité...</option>
            <?php foreach ($activites as $act): ?>
                <?php $selected = (isset($_GET['code']) && isset($_GET['date']) && $act['CODEANIM'] == $_GET['code'] && $act['DATEACT'] == $_GET['date']) ? 'selected' : ''; ?>
                <option value="<?= htmlspecialchars($act['CODEANIM']) . '|' . htmlspecialchars($act['DATEACT']) ?>" <?= $selected ?>>
                    <?= htmlspecialchars($act['NOMANIM']) ?> - 
                    <?= date('d/m/Y', strtotime($act['DATEACT'])) ?> 
                    (<?= htmlspecialchars($act['NOMRESP']) ?> <?= htmlspecialchars($act['PRENOMRESP']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        
    </form>
</div>

        <?php if ($activity): ?>
        <!-- Formulaire de modification -->
        <form method="post" class="edit-form">
            <div class="form-header">
                <h3>Modification de l'activité: <?= htmlspecialchars($activity['NOMANIM']) ?></h3>
                <div class="activity-date">Date: <?= htmlspecialchars($activity['DATEACT']) ?></div>
            </div>
            
            <input type="hidden" name="codeanim" value="<?= htmlspecialchars($activity['CODEANIM']) ?>">
            <input type="hidden" name="dateact" value="<?= htmlspecialchars($activity['DATEACT']) ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="codeetatact">État de l'activité:</label>
                    <select name="codeetatact" id="codeetatact" required>
                        <?php foreach ($etatsActivite as $etat): ?>
                            <option value="<?= htmlspecialchars($etat['CODEETATACT']) ?>" 
                                    <?= ($etat['CODEETATACT'] == $activity['CODEETATACT']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($etat['NOMETATACT']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="prixact">Prix:</label>
                    <input type="number" name="prixact" id="prixact" step="0.01" value="<?= htmlspecialchars($activity['PRIXACT']) ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="hrrdvact">Heure de rendez-vous:</label>
                    <input type="time" name="hrrdvact" id="hrrdvact" value="<?= htmlspecialchars($activity['HRRDVACT']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="hrdebutact">Heure de début:</label>
                    <input type="time" name="hrdebutact" id="hrdebutact" value="<?= htmlspecialchars($activity['HRDEBUTACT']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="hrfinact">Heure de fin:</label>
                    <input type="time" name="hrfinact" id="hrfinact" value="<?= htmlspecialchars($activity['HRFINACT']) ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nomresp">Nom du responsable:</label>
                    <input type="text" name="nomresp" id="nomresp" value="<?= htmlspecialchars($activity['NOMRESP']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="prenomresp">Prénom du responsable:</label>
                    <input type="text" name="prenomresp" id="prenomresp" value="<?= htmlspecialchars($activity['PRENOMRESP']) ?>" required>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="modifier_activite" class="submit-btn">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
                <a href="liste_activities.php" class="cancel-btn">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
        <?php endif; ?>
    </main>

<script>
// Script pour gérer le changement dans le sélecteur d'activité
document.getElementById('activity-select').addEventListener('change', function() {
    const selectedValue = this.value;
    if (selectedValue) {
        const [code, date] = selectedValue.split('|');
        window.location.href = `modif_act.php?code=${encodeURIComponent(code)}&date=${encodeURIComponent(date)}`;
    }
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>