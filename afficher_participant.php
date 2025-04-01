<?php
require 'config/db.php';
include 'header.php';

// Récupérer les animations pour le filtre
$stmtAnimations = $pdo->query("SELECT DISTINCT a.CODEANIM, an.NOMANIM 
                              FROM ACTIVITE a 
                              JOIN ANIMATION an ON a.CODEANIM = an.CODEANIM 
                              ORDER BY an.NOMANIM");
$animations = $stmtAnimations->fetchAll(PDO::FETCH_ASSOC);

// Filtres
$selectedAnimation = isset($_GET['animation']) ? $_GET['animation'] : '';
$dateDebut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$dateFin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';

// Construction de la requête avec filtres
$sql = "
    SELECT 
        a.CODEANIM,
        a.DATEACT,
        a.PRIXACT,
        a.NOMRESP,
        a.PRENOMRESP,
        a.DATEANNULEACT,
        an.NOMANIM,
        i.USER,
        i.DATEINSCRIP,
        i.NOINSCRIP,
        i.DATEANNULE as DATEANNULEINSCRIPTION,
        c.NOMCOMPTE,
        c.PRENOMCOMPTE
    FROM ACTIVITE a
    JOIN ANIMATION an ON a.CODEANIM = an.CODEANIM
    LEFT JOIN INSCRIPTION i ON a.CODEANIM = i.CODEANIM AND a.DATEACT = i.DATEACT
    LEFT JOIN COMPTE c ON i.USER = c.USER
    WHERE 1=1
";

$params = [];

if (!empty($selectedAnimation)) {
    $sql .= " AND a.CODEANIM = ?";
    $params[] = $selectedAnimation;
}

if (!empty($dateDebut)) {
    $sql .= " AND a.DATEACT >= ?";
    $params[] = $dateDebut;
}

if (!empty($dateFin)) {
    $sql .= " AND a.DATEACT <= ?";
    $params[] = $dateFin;
}

$sql .= " ORDER BY a.DATEACT DESC, an.NOMANIM";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les données par activité
$activites = [];
foreach ($results as $row) {
    $key = $row['CODEANIM'] . '|' . $row['DATEACT'];

    if (!isset($activites[$key])) {
        $activites[$key] = [
            'CODEANIM' => $row['CODEANIM'],
            'NOMANIM' => $row['NOMANIM'],
            'DATEACT' => $row['DATEACT'],
            'PRIXACT' => $row['PRIXACT'],
            'NOMRESP' => $row['NOMRESP'],
            'PRENOMRESP' => $row['PRENOMRESP'],
            'DATEANNULEACT' => $row['DATEANNULEACT'],
            'PARTICIPANTS' => [],
            'PARTICIPANTS_COUNT' => 0
        ];
    }

    if (!empty($row['USER'])) {
        $activites[$key]['PARTICIPANTS'][] = [
            'USER' => $row['USER'],
            'NOMCOMPTE' => $row['NOMCOMPTE'],
            'PRENOMCOMPTE' => $row['PRENOMCOMPTE'],
            'DATEINSCRIP' => $row['DATEINSCRIP'],
            'NOINSCRIP' => $row['NOINSCRIP'],
            'DATEANNULEINSCRIPTION' => $row['DATEANNULEINSCRIPTION']
        ];
        
        // Incrémenter le compteur uniquement pour les participants non annulés
        if (empty($row['DATEANNULEINSCRIPTION'])) {
            $activites[$key]['PARTICIPANTS_COUNT']++;
        }
    }
}

// Tri des activités : d'abord celles avec des participants (par nombre décroissant), puis les autres par date
uasort($activites, function($a, $b) {
    // Si une activité a des participants et l'autre non, celle avec des participants vient en premier
    if ($a['PARTICIPANTS_COUNT'] > 0 && $b['PARTICIPANTS_COUNT'] == 0) {
        return -1;
    }
    if ($a['PARTICIPANTS_COUNT'] == 0 && $b['PARTICIPANTS_COUNT'] > 0) {
        return 1;
    }
    
    // Si les deux ont des participants, triez par nombre de participants (décroissant)
    if ($a['PARTICIPANTS_COUNT'] > 0 && $b['PARTICIPANTS_COUNT'] > 0) {
        if ($a['PARTICIPANTS_COUNT'] != $b['PARTICIPANTS_COUNT']) {
            return $b['PARTICIPANTS_COUNT'] - $a['PARTICIPANTS_COUNT'];
        }
    }
    
    // Si les deux n'ont pas de participants ou ont le même nombre, triez par date (décroissant)
    return strtotime($b['DATEACT']) - strtotime($a['DATEACT']);
});
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Participants</title>
    <link rel="stylesheet" href="css/affiche.css">
    <style>
        .cancelled-inscription {
            display: block;
            margin-top: 5px;
            font-size: 0.85em;
            color: #dc3545;
            font-style: italic;
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 10px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        tr.cancelled-row {
            background-color: rgba(220, 53, 69, 0.1);
        }

        tr.cancelled-row td {
            text-decoration: line-through;
            color: #6c757d;
        }
        
        .action-disabled {
            color: #6c757d;
            font-style: italic;
            font-size: 0.9em;
        }
        
        /* Style pour mettre en évidence les activités avec participants */
        .activity-card.has-participants {
            border-left: 4px solid #28a745;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        .participant-count {
            padding: 5px 10px;
            background-color: #f8f9fa;
            border-radius: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .participant-count.active {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestion des Participants</h1>
        
        <!-- Messages d'alerte -->
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> L'inscription du participant a été annulée avec succès.
            </div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] == 1): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> Une erreur est survenue lors de l'annulation de l'inscription.
            </div>
        <?php endif; ?>
        
        <!-- Filtres -->
        <div class="filters">
            <h2>Filtrer les activités</h2>
            <form action="" method="GET" class="filter-form">
                <div class="form-group">
                    <label for="animation">Animation</label>
                    <select name="animation" id="animation" class="form-control">
                        <option value="">Toutes les animations</option>
                        <?php foreach ($animations as $animation): ?>
                            <option value="<?= htmlspecialchars($animation['CODEANIM']) ?>" <?= $selectedAnimation == $animation['CODEANIM'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($animation['NOMANIM']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_debut">Date de début</label>
                    <input type="date" id="date_debut" name="date_debut" class="form-control" value="<?= htmlspecialchars($dateDebut) ?>">
                </div>
                <div class="form-group">
                    <label for="date_fin">Date de fin</label>
                    <input type="date" id="date_fin" name="date_fin" class="form-control" value="<?= htmlspecialchars($dateFin) ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <a href="afficher_participant.php" class="btn btn-reset">
                        <i class="fas fa-undo"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Liste des activités -->
        <div class="activities-grid">
            <?php if (count($activites) > 0): ?>
                <?php foreach ($activites as $key => $activite): 
                    $isAnnulee = !empty($activite['DATEANNULEACT']);
                    $hasParticipants = $activite['PARTICIPANTS_COUNT'] > 0;
                ?>
                    <div class="activity-card <?= $isAnnulee ? 'cancelled' : '' ?> <?= $hasParticipants ? 'has-participants' : '' ?>">
                        <?php if ($isAnnulee): ?>
                            <div class="cancelled-badge">
                                <i class="fas fa-ban"></i> Annulée
                            </div>
                        <?php endif; ?>
                        
                        <div class="activity-header">
                            <h3 class="activity-title"><?= htmlspecialchars($activite['NOMANIM']) ?></h3>
                            <div class="participant-count <?= $hasParticipants ? 'active' : '' ?>">
                                <i class="fas fa-users"></i> <?= $activite['PARTICIPANTS_COUNT'] ?>
                            </div>
                        </div>
                        
                        <div class="activity-content">
                            <div class="activity-details">
                                <div class="detail-item">
                                    <span class="detail-label">Date</span>
                                    <span class="detail-value"><?= date('d/m/Y', strtotime($activite['DATEACT'])) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Prix</span>
                                    <span class="detail-value"><?= number_format($activite['PRIXACT'], 2) ?> €</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Responsable</span>
                                    <span class="detail-value">
                                        <?= htmlspecialchars($activite['PRENOMRESP'] . ' ' . $activite['NOMRESP']) ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Code</span>
                                    <span class="detail-value"><?= htmlspecialchars($activite['CODEANIM']) ?></span>
                                </div>
                            </div>
                            
                            <div class="activity-actions">
                                <button class="btn-participants" onclick="toggleParticipants('<?= $key ?>')">
                                    <i class="fas fa-user-friends"></i> 
                                    <?= count($activite['PARTICIPANTS']) > 0 ? 'Voir les participants' : 'Aucun participant' ?>
                                </button>
                            </div>
                            
                            <div id="participants-<?= $key ?>" class="participant-list <?= $hasParticipants ? 'auto-expand' : '' ?>">
                                <?php if (count($activite['PARTICIPANTS']) > 0): ?>
                                    <table class="participant-table">
                                        <thead>
                                            <tr>
                                                <th>Utilisateur</th>
                                                <th>Inscription</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($activite['PARTICIPANTS'] as $participant): 
                                                $isAnnuleeInscription = !empty($participant['DATEANNULEINSCRIPTION']);
                                            ?>
                                                <tr class="<?= $isAnnuleeInscription ? 'cancelled-row' : '' ?>">
                                                    <td>
                                                        <?php if (!empty($participant['NOMCOMPTE']) && !empty($participant['PRENOMCOMPTE'])): ?>
                                                            <?= htmlspecialchars($participant['PRENOMCOMPTE'] . ' ' . $participant['NOMCOMPTE']) ?>
                                                            <small>(<?= htmlspecialchars($participant['USER']) ?>)</small>
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($participant['USER']) ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= date('d/m/Y', strtotime($participant['DATEINSCRIP'])) ?>
                                                        <?php if ($isAnnuleeInscription): ?>
                                                            <span class="cancelled-inscription">
                                                                <i class="fas fa-ban"></i> Annulée le <?= date('d/m/Y', strtotime($participant['DATEANNULEINSCRIPTION'])) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!$isAnnuleeInscription): ?>
                                                            <form method="POST" action="supprimer_participant.php" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette inscription ?');">
                                                                <input type="hidden" name="noinscrip" value="<?= htmlspecialchars($participant['NOINSCRIP']) ?>">
                                                                <input type="hidden" name="user" value="<?= htmlspecialchars($participant['USER']) ?>">
                                                                <input type="hidden" name="codeanim" value="<?= htmlspecialchars($activite['CODEANIM']) ?>">
                                                                <input type="hidden" name="dateact" value="<?= htmlspecialchars($activite['DATEACT']) ?>">
                                                                <button type="submit" class="delete-btn">
                                                                    <i class="fas fa-ban"></i> Annuler
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="action-disabled">Déjà annulée</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="no-participants">
                                        <i class="fas fa-info-circle"></i> Aucun participant inscrit pour cette activité.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>Aucune activité trouvée</h3>
                    <p>Ajustez vos critères de recherche ou essayez sans filtre.</p>
                    <a href="afficher_participant.php" class="btn btn-primary">
                        <i class="fas fa-undo"></i> Voir toutes les activités
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleParticipants(key) {
            const participantList = document.getElementById('participants-' + key);
            participantList.classList.toggle('visible');
        }
        
        // Auto-expand la liste des participants pour les activités qui en ont
        document.addEventListener('DOMContentLoaded', function() {
            const autoExpandLists = document.querySelectorAll('.participant-list.auto-expand');
            autoExpandLists.forEach(function(list) {
                // Si l'activité a des participants, on peut automatiquement afficher la liste
                // Décommentez la ligne suivante pour activer cette fonctionnalité
                // list.classList.add('visible');
            });
        });
    </script>
    
    <?php include 'footer.php'; ?>
</body>
</html>
