<?php
require 'config/db.php';  // Inclus votre connexion PDO

// Gérer la suppression d'utilisateur si le formulaire est soumis
if (isset($_POST['delete_user'])) {
    $userToDelete = $_POST['user_id'];
    
    try {
        // Supprimer d'abord les inscriptions liées à cet utilisateur
        $deleteInscriptions = $pdo->prepare("DELETE FROM INSCRIPTION WHERE USER = ?");
        $deleteInscriptions->execute([$userToDelete]);
        
        // Puis supprimer le compte
        $deleteAccount = $pdo->prepare("DELETE FROM COMPTE WHERE USER = ?");
        $deleteAccount->execute([$userToDelete]);
        
        $successMessage = "L'utilisateur '$userToDelete' a été supprimé avec succès.";
    } catch (PDOException $e) {
        $errorMessage = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Requête SQL pour récupérer tous les utilisateurs avec toutes les informations utiles
$sql = "SELECT USER, MDP, NOMCOMPTE, PRENOMCOMPTE, TYPEPROFIL, DATEDEBSEJOUR, DATEFINSEJOUR FROM COMPTE";
// Préparation et exécution de la requête
$stmt = $pdo->query($sql);

// Définir le titre de la page
$pageTitle = "Gestion des Utilisateurs";

// Maintenant incluez le header
include 'header.php';
?>

<link rel="stylesheet" href="css/identifiants.css?v=<?php echo time(); ?>">

<h1>Gestion des Utilisateurs</h1>

<main>
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= $successMessage ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= $errorMessage ?>
        </div>
    <?php endif; ?>

    <?php
    // Vérification s'il y a des résultats
    if ($stmt->rowCount() > 0): ?>
        <div class="users-card">
            <div class="users-header">
                <span>Base de données utilisateurs</span>
                <span class="users-badge">
                    <i class="fas fa-users"></i> 
                    <?= $stmt->rowCount() ?> compte(s) enregistré(s)
                </span>
            </div>
            <div class="users-content">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Identifiants</th>
                            <th>Informations utilisateur</th>
                            <th>Type</th>
                            <th>Période de séjour</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $stmt->fetch()): 
                        // Déterminer le type de profil complet
                        $profileType = "";
                        $profileClass = "";
                        $profileIcon = "";
                        switch($row["TYPEPROFIL"]) {
                            case "ad":
                                $profileType = "Administrateur";
                                $profileClass = "profile-admin";
                                $profileIcon = "fas fa-user-shield";
                                break;
                            case "en":
                                $profileType = "Encadrant";
                                $profileClass = "profile-staff";
                                $profileIcon = "fas fa-user-tie";
                                break;
                            case "va":
                                $profileType = "Vacancier";
                                $profileClass = "profile-guest";
                                $profileIcon = "fas fa-user";
                                break;
                            default:
                                $profileType = "Non défini";
                                $profileClass = "profile-unknown";
                                $profileIcon = "fas fa-user-question";
                        }
                    ?>
                        <tr>
                            <td class="credentials-cell">
                                <div class="credential-item">
                                    <span class="credential-label"><i class="fas fa-user-circle"></i></span>
                                    <span class="credential-value"><?= htmlspecialchars($row["USER"]) ?></span>
                                </div>
                                <div class="credential-item">
                                    <span class="credential-label"><i class="fas fa-key"></i></span>
                                    <span class="credential-value password"><?= htmlspecialchars($row["MDP"]) ?></span>
                                </div>
                            </td>
                            <td class="user-info-cell">
                                <?php if (!empty($row["NOMCOMPTE"]) || !empty($row["PRENOMCOMPTE"])): ?>
                                    <div class="user-name">
                                        <?= !empty($row["PRENOMCOMPTE"]) ? htmlspecialchars($row["PRENOMCOMPTE"]) : "" ?> 
                                        <?= !empty($row["NOMCOMPTE"]) ? htmlspecialchars($row["NOMCOMPTE"]) : "" ?>
                                    </div>
                                <?php else: ?>
                                    <div class="user-name no-name">Non renseigné</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="profile-badge <?= $profileClass ?>">
                                    <i class="<?= $profileIcon ?>"></i>
                                    <span><?= $profileType ?></span>
                                </div>
                            </td>
                            <td class="dates-cell">
                                <?php if (!empty($row["DATEDEBSEJOUR"])): ?>
                                    <div class="date-range">
                                        <div class="date-start">
                                            <i class="far fa-calendar-plus"></i>
                                            <span><?= date('d/m/Y', strtotime($row["DATEDEBSEJOUR"])) ?></span>
                                        </div>
                                        
                                        <?php if (!empty($row["DATEFINSEJOUR"])): ?>
                                            <div class="date-separator">→</div>
                                            <div class="date-end">
                                                <i class="far fa-calendar-minus"></i>
                                                <span><?= date('d/m/Y', strtotime($row["DATEFINSEJOUR"])) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="no-dates">
                                        <i class="fas fa-infinity"></i> Permanent
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <p class="no-results">
            <i class="fas fa-info-circle"></i> Aucun utilisateur trouvé.
        </p>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>