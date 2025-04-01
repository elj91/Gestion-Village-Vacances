<?php
session_start();
require 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    // Vérification des identifiants
    $stmt = $pdo->prepare("SELECT * FROM COMPTE WHERE USER = ? AND MDP = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user'] = $user;
        header("Location: index.php");
        exit();
    } else {
        $error = "Identifiants invalides";
    }
}

// Supprimez toutes les balises HTML, DOCTYPE, etc.
// NE PAS inclure header.php ici

// Définissez un titre de page qui sera utilisé par votre header
$pageTitle = "Connexion - VVA";
?>

<!-- Insérez votre CSS dans le head via une variable -->
<link rel="stylesheet" href="css/login.css">

<div class="auth-container">
    <form method="post" class="login">
        <h2>Connexion</h2>
        <input type="text" name="username" placeholder="Nom d'utilisateur" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit">Se connecter</button>
        <?php if (isset($error)): ?>
            <p><?= $error ?></p>
        <?php endif; ?>
        
    </form>
</div>

<?php include 'footer.php'; ?>
