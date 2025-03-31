<?php
require 'config/db.php';
include 'header.php';

// Vérifier si l'utilisateur est connecté et est un vacancier
if (!isset($_SESSION['user']) || $_SESSION['user']['TYPEPROFIL'] !== 'va') {
    // Rediriger vers la page de connexion si non connecté ou non vacancier
    header('Location: login.php');
    exit;
}

// Récupérer l'identifiant de l'utilisateur connecté
$user = $_SESSION['user']['USER'];

// Traitement de la désinscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desinscription'])) {
    $noInscrip = $_POST['no_inscription'];
    $codeAnim = $_POST['code_anim'];
    
    // Commencer une transaction pour garantir l'intégrité des données
    $pdo->beginTransaction();
    
    try {
        // 1. Mettre à jour la date d'annulation dans la table INSCRIPTION
        $dateAnnulation = date('Y-m-d');
        
        $stmt = $pdo->prepare("UPDATE INSCRIPTION SET DATEANNULE = :dateAnnulation 
                              WHERE NOINSCRIP = :noInscrip AND USER = :user");
        
        $stmt->execute([
            'dateAnnulation' => $dateAnnulation,
            'noInscrip' => $noInscrip,
            'user' => $user
        ]);
        
        // 2. Récupérer puis incrémenter le nombre de places disponibles dans ANIMATION
        $stmt = $pdo->prepare("UPDATE ANIMATION 
                              SET NBREPLACEANIM = NBREPLACEANIM + 1 
                              WHERE CODEANIM = :codeAnim");
        
        $stmt->execute([
            'codeAnim' => $codeAnim
        ]);
        
        // Si tout s'est bien passé, valider la transaction
        $pdo->commit();
        
        $message = "Vous avez été désinscrit de cette activité avec succès. La place a été libérée.";
        $messageClass = "success";
    } 
    catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
        $pdo->rollBack();
        
        $message = "Erreur lors de la désinscription : " . $e->getMessage();
        $messageClass = "error";
    }
}

// Récupérer les inscriptions de l'utilisateur qui ne sont pas annulées
$stmt = $pdo->prepare("
    SELECT 
        i.NOINSCRIP,
        i.DATEINSCRIP,
        a.CODEANIM,
        a.DATEACT,
        anim.NOMANIM,
        a.HRRDVACT,
        a.HRDEBUTACT,
        a.HRFINACT,
        a.NOMRESP,
        a.PRENOMRESP,
        a.PRIXACT,
        et.NOMETATACT
    FROM 
        INSCRIPTION i
    JOIN 
        ACTIVITE a ON i.CODEANIM = a.CODEANIM AND i.DATEACT = a.DATEACT
    JOIN 
        ANIMATION anim ON a.CODEANIM = anim.CODEANIM
    JOIN 
        ETAT_ACT et ON a.CODEETATACT = et.CODEETATACT
    WHERE 
        i.USER = :user
        AND i.DATEANNULE IS NULL
        AND a.DATEANNULEACT IS NULL
    ORDER BY 
        a.DATEACT DESC
");

$stmt->execute(['user' => $user]);
$inscriptions = $stmt->fetchAll();

// Récupérer les inscriptions annulées par l'utilisateur
$stmt = $pdo->prepare("
    SELECT 
        i.NOINSCRIP,
        i.DATEINSCRIP,
        i.DATEANNULE,
        a.CODEANIM,
        a.DATEACT,
        anim.NOMANIM,
        a.HRRDVACT,
        a.HRDEBUTACT,
        a.HRFINACT,
        a.NOMRESP,
        a.PRENOMRESP,
        a.PRIXACT,
        et.NOMETATACT
    FROM 
        INSCRIPTION i
    JOIN 
        ACTIVITE a ON i.CODEANIM = a.CODEANIM AND i.DATEACT = a.DATEACT
    JOIN 
        ANIMATION anim ON a.CODEANIM = anim.CODEANIM
    JOIN 
        ETAT_ACT et ON a.CODEETATACT = et.CODEETATACT
    WHERE 
        i.USER = :user
        AND i.DATEANNULE IS NOT NULL
    ORDER BY 
        i.DATEANNULE DESC
");

$stmt->execute(['user' => $user]);
$inscriptionsAnnulees = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations</title>
    <link rel="stylesheet" href="css/resa.css">
</head>
<body>
    <div class="container">
        <h1>Mes Réservations</h1>
        
        <?php if (isset($message)): ?>
            <div class="message <?= $messageClass ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <h2>Mes Activités Réservées</h2>
        
        <?php if (count($inscriptions) > 0): ?>
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>Activité</th>
                        <th>Date</th>
                        <th>Horaires</th>
                        <th>Responsable</th>
                        <th>Prix</th>
                        <th>Catégorie</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscriptions as $inscription): ?>
                        <tr>
                            <td><?= htmlspecialchars($inscription['NOMANIM']) ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($inscription['DATEACT'])) ?>
                                <div class="date-badge">
                                    Inscrit le <?= date('d/m/Y', strtotime($inscription['DATEINSCRIP'])) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($inscription['HRRDVACT']): ?>
                                    <strong>RDV:</strong> <?= date('H:i', strtotime($inscription['HRRDVACT'])) ?><br>
                                <?php endif; ?>
                                
                                <?php if ($inscription['HRDEBUTACT'] && $inscription['HRFINACT']): ?>
                                    <strong>Horaires:</strong> <?= date('H:i', strtotime($inscription['HRDEBUTACT'])) ?> - <?= date('H:i', strtotime($inscription['HRFINACT'])) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($inscription['NOMRESP'] && $inscription['PRENOMRESP']): ?>
                                    <?= htmlspecialchars($inscription['PRENOMRESP']) ?> <?= htmlspecialchars($inscription['NOMRESP']) ?>
                                <?php else: ?>
                                    Non assigné
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($inscription['PRIXACT'], 2) ?> €</td>
                            <td><?= htmlspecialchars($inscription['NOMETATACT']) ?></td>
                            <td>
                                <form action="mes_reservations.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir vous désinscrire de cette activité ?');">
                                    <input type="hidden" name="no_inscription" value="<?= $inscription['NOINSCRIP'] ?>">
                                    <input type="hidden" name="code_anim" value="<?= $inscription['CODEANIM'] ?>">
                                    <button type="submit" name="desinscription" class="cancel-button">Se désinscrire</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-activities">
                <p>Vous n'avez aucune réservation active pour le moment.</p>
                <p>Consultez notre liste d'activités pour vous inscrire à de nouvelles aventures !</p>
            </div>
        <?php endif; ?>
        
        <!-- Historique des inscriptions annulées -->
        <?php if (count($inscriptionsAnnulees) > 0): ?>
            <h2>Historique des Annulations</h2>
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>Activité</th>
                        <th>Date de l'activité</th>
                        <th>Dates d'inscription/annulation</th>
                        <th>Responsable</th>
                        <th>Prix</th>
                        <th>Catégorie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscriptionsAnnulees as $inscription): ?>
                        <tr class="canceled-row">
                            <td><?= htmlspecialchars($inscription['NOMANIM']) ?></td>
                            <td><?= date('d/m/Y', strtotime($inscription['DATEACT'])) ?></td>
                            <td>
                                <div class="date-badge">
                                    Inscrit le <?= date('d/m/Y', strtotime($inscription['DATEINSCRIP'])) ?>
                                </div>
                                <div class="date-badge" style="background-color: #f8d7da; color: #721c24;">
                                    Annulé le <?= date('d/m/Y', strtotime($inscription['DATEANNULE'])) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($inscription['NOMRESP'] && $inscription['PRENOMRESP']): ?>
                                    <?= htmlspecialchars($inscription['PRENOMRESP']) ?> <?= htmlspecialchars($inscription['NOMRESP']) ?>
                                <?php else: ?>
                                    Non assigné
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($inscription['PRIXACT'], 2) ?> €</td>
                            <td><?= htmlspecialchars($inscription['NOMETATACT']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>

<?php include 'footer.php'; ?>