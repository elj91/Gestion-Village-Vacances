<?php
require 'config/db.php';
include 'header.php';

// Récupérer la liste des animations et activités
$animations = $pdo->query("SELECT CODEANIM, NOMANIM FROM ANIMATION")->fetchAll();
// Récupérer seulement les activités non annulées avec le nom de l'animation associée
$activites = $pdo->query("
    SELECT a.CODEANIM, a.DATEACT, a.NOMRESP, a.PRENOMRESP, an.NOMANIM 
    FROM ACTIVITE a
    JOIN ANIMATION an ON a.CODEANIM = an.CODEANIM
    WHERE a.DATEANNULEACT IS NULL
    ORDER BY an.NOMANIM, a.DATEACT
")->fetchAll();
$etatsActivite = $pdo->query("SELECT CODEETATACT, NOMETATACT FROM ETAT_ACT")->fetchAll();
$typeAnimations = $pdo->query("SELECT CODETYPEANIM, NOMTYPEANIM FROM TYPE_ANIM")->fetchAll();
$difficultes = $pdo->query("SELECT DISTINCT DIFFICULTEANIM FROM ANIMATION")->fetchAll();

// Traitement de l'annulation d'une activité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_activite'])) {
    list($codeAnim, $dateAct) = explode('|', $_POST['activite']);
    
    // Mettre à jour la date d'annulation au lieu de supprimer
    $dateAnnulation = date('Y-m-d');
    
    $stmt = $pdo->prepare("UPDATE ACTIVITE SET DATEANNULEACT = :dateAnnulation 
                          WHERE CODEANIM = :codeAnim AND DATEACT = :dateAct");
    
    $result = $stmt->execute([
        'dateAnnulation' => $dateAnnulation,
        'codeAnim' => $codeAnim, 
        'dateAct' => $dateAct
    ]);
    
    if ($result) {
        $message = "L'activité a été annulée avec succès.";
        $messageClass = "success";
        
        // Actualiser la liste des activités non annulées
        $activites = $pdo->query("
            SELECT a.CODEANIM, a.DATEACT, a.NOMRESP, a.PRENOMRESP, an.NOMANIM 
            FROM ACTIVITE a
            JOIN ANIMATION an ON a.CODEANIM = an.CODEANIM
            WHERE a.DATEANNULEACT IS NULL
            ORDER BY an.NOMANIM, a.DATEACT
        ")->fetchAll();
    } else {
        $message = "Erreur lors de l'annulation de l'activité.";
        $messageClass = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Activités</title>
    <link rel="stylesheet" href="css/gestion.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="navbar">
        <div class="navbar-container">
            <h1 class="navbar-title">Gestion</h1>
            <nav class="navbar-links">
                <?php if (isset($userType) && ($userType === 'ad' || $userType === 'en')): ?>
                    <a href="suppression.php" class="nav-button">Annuler Activité</a>
                    <a href="ajt_act.php" class="nav-button">Ajouter Activité</a>
                    <a href="ajt_anim.php" class="nav-button">Ajouter Animation</a>
                    <a href="modif_act.php" class="nav-button">Modifier Activité</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <?php if (isset($message)): ?>
        <div class="message <?= $messageClass ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <h2>Annuler une Activité</h2>
    <?php if (count($activites) > 0): ?>
        <form action="suppression.php" method="post">
            <label for="activite">Sélectionnez une activité à Annuler :</label>
            <select name="activite" id="activite" required>
                <?php foreach ($activites as $activite): ?>
                    <option value="<?= htmlspecialchars($activite['CODEANIM']) . '|' . htmlspecialchars($activite['DATEACT']) ?>">
                        <?= htmlspecialchars($activite['NOMANIM']) ?> - 
                        <?= date('d/m/Y', strtotime($activite['DATEACT'])) ?> - 
                        <?= htmlspecialchars($activite['NOMRESP']) . ' ' . htmlspecialchars($activite['PRENOMRESP']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="supprimer_activite">Annuler l'Activité</button>
        </form>
    <?php else: ?>
        <div class="no-activities">
            <p>Aucune activité disponible à Annuler.</p>
        </div>
    <?php endif; ?>

</body>
</html>
<?php include 'footer.php'; ?>
