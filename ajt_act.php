<?php
require 'config/db.php';
include 'header.php';

// Vérifier si l'utilisateur est connecté et a le droit d'ajouter une activité
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['TYPEPROFIL'], ['en', 'ad'])) {
    // Rediriger vers la page d'accueil si non connecté ou non autorisé
    header("Location: index.php");
    exit();
}

// Récupérer les informations de l'utilisateur connecté
$nomResp = $_SESSION['user']['NOMCOMPTE'] ?? 'Non défini';
$prenomResp = $_SESSION['user']['PRENOMCOMPTE'] ?? 'Non défini';

// Gérer l'ajout d'une activité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_activite'])) {
    $codeAnim = $_POST['codeanim'];
    $dateAct = $_POST['dateact'];
    $codeEtatAct = $_POST['codeetatact'];
    $hrrdvAct = $_POST['hrrdvact'];
    $prixAct = $_POST['prixact'];
    $hrDebutAct = $_POST['hrdebutact'];
    $hrFinAct = $_POST['hrfinact'];
    
    // Utiliser les valeurs de l'utilisateur connecté
    $stmt = $pdo->prepare("INSERT INTO ACTIVITE (CODEANIM, DATEACT, CODEETATACT, HRRDVACT, PRIXACT, HRDEBUTACT, HRFINACT, NOMRESP, PRENOMRESP) 
                           VALUES (:codeAnim, :dateAct, :codeEtatAct, :hrrdvAct, :prixAct, :hrDebutAct, :hrFinAct, :nomResp, :prenomResp)");
    $stmt->execute([
        'codeAnim' => $codeAnim,
        'dateAct' => $dateAct,
        'codeEtatAct' => $codeEtatAct,
        'hrrdvAct' => $hrrdvAct,
        'prixAct' => $prixAct,
        'hrDebutAct' => $hrDebutAct,
        'hrFinAct' => $hrFinAct,
        'nomResp' => $nomResp,
        'prenomResp' => $prenomResp
    ]);
    
    $message = "L'activité a été ajoutée avec succès.";
    $messageClass = "success";
}

// Récupérer les données pour les listes déroulantes
$animations = $pdo->query("SELECT CODEANIM, NOMANIM FROM ANIMATION")->fetchAll();
$activites = $pdo->query("SELECT CODEANIM, DATEACT, NOMRESP, PRENOMRESP FROM ACTIVITE")->fetchAll();
$etatsActivite = $pdo->query("SELECT CODEETATACT, NOMETATACT FROM ETAT_ACT")->fetchAll();
$typeAnimations = $pdo->query("SELECT CODETYPEANIM, NOMTYPEANIM FROM TYPE_ANIM")->fetchAll();
$difficultes = $pdo->query("SELECT DISTINCT DIFFICULTEANIM FROM ANIMATION")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Activité</title>
    <link rel="stylesheet" href="css/gestion.css">
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

    <?php if (isset($message)): ?>
        <div class="message <?= $messageClass ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <h2>Ajouter une Activité</h2>
    <form action="ajt_act.php" method="post">
        <label for="codeanim">Animation :</label>
        <select name="codeanim" id="codeanim" required>
            <?php foreach ($animations as $animation): ?>
                <option value="<?= htmlspecialchars($animation['CODEANIM']) ?>">
                    <?= htmlspecialchars($animation['NOMANIM']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label for="dateact">Date de l'Activité :</label>
        <input type="date" name="dateact" id="dateact" required><br>

        <label for="codeetatact">État de l'Activité :</label>
        <select name="codeetatact" id="codeetatact" required>
            <?php foreach ($etatsActivite as $etat): ?>
                <option value="<?= htmlspecialchars($etat['CODEETATACT']) ?>">
                    <?= htmlspecialchars($etat['NOMETATACT']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <label for="hrrdvact">Heure de Rendez-vous :</label>
        <input type="time" name="hrrdvact" id="hrrdvact"><br>

        <label for="prixact">Prix de l'Activité :</label>
        <input type="number" name="prixact" id="prixact" step="0.01"><br>

        <label for="hrdebutact">Heure de Début :</label>
        <input type="time" name="hrdebutact" id="hrdebutact"><br>

        <label for="hrfinact">Heure de Fin :</label>
        <input type="time" name="hrfinact" id="hrfinact"><br>

        <label for="nomresp">Nom du Responsable :</label>
        <input type="text" name="nomresp_display" id="nomresp" value="<?= htmlspecialchars($nomResp) ?>" readonly class="readonly-field">
        <!-- Champ caché pour envoyer la valeur -->
        <input type="hidden" name="nomresp" value="<?= htmlspecialchars($nomResp) ?>"><br>

        <label for="prenomresp">Prénom du Responsable :</label>
        <input type="text" name="prenomresp_display" id="prenomresp" value="<?= htmlspecialchars($prenomResp) ?>" readonly class="readonly-field">
        <!-- Champ caché pour envoyer la valeur -->
        <input type="hidden" name="prenomresp" value="<?= htmlspecialchars($prenomResp) ?>"><br>

        <button type="submit" name="ajouter_activite">Ajouter l'Activité</button>
    </form>
</body>
</html>

<?php
// Inclusion du footer
include 'footer.php';
?>