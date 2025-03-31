<?php
require 'config/db.php';
include 'header.php';

// Récupérer la liste des animations et activités
$animations = $pdo->query("SELECT CODEANIM, NOMANIM FROM ANIMATION")->fetchAll();
// Récupérer seulement les activités non annulées
$activites = $pdo->query("SELECT CODEANIM, DATEACT, NOMRESP, PRENOMRESP FROM ACTIVITE WHERE DATEANNULEACT IS NULL")->fetchAll();
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
        $activites = $pdo->query("SELECT CODEANIM, DATEACT, NOMRESP, PRENOMRESP FROM ACTIVITE WHERE DATEANNULEACT IS NULL")->fetchAll();
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
    <link rel="stylesheet" href="css/gestion.css">
    <style>
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        select, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: #0056b3;
        }
        h2 {
            text-align: center;
            margin-top: 30px;
        }
        .no-activities {
            text-align: center;
            color: #721c24;
            padding: 15px;
            background-color: #f8d7da;
            border-radius: 4px;
            margin: 20px auto;
            max-width: 600px;
        }
    </style>
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
                        <?= htmlspecialchars($activite['NOMRESP']) . ' ' . htmlspecialchars($activite['PRENOMRESP']) . ' - ' . htmlspecialchars($activite['DATEACT']) ?>
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