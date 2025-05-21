<?php
session_start();

// Définir des constantes pour les chemins de fichiers
define('CONFIG_PATH', './php_back/config.php');
define('LOGIN_PAGE', 'login.php');

// Inclure le fichier de configuration pour la connexion à la BDD
require_once CONFIG_PATH;

// Vérifier si l'utilisateur est connecté, sinon rediriger vers la page de login
if (!isset($_SESSION['user'])) {
    header('Location: ' . LOGIN_PAGE);
    exit();
}

// Préparer et exécuter la requête pour récupérer les données de l'utilisateur
$req = $bdd->prepare('SELECT * FROM users WHERE token = ?');
$req->execute([$_SESSION['user']]);
$data = $req->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
    <link rel="stylesheet" type="text/css" href="./CSS/style.css">
    <link rel="stylesheet" type="text/css" href="./CSS/navbar-style.css">

    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="./navbar-style.css">

    <script src="https://kit.fontawesome.com/2dd49550a9.js" crossorigin="anonymous"></script>
</head>
<body>
<header>
    <nav class="nav-container-desktop">
        <div class="nav-logo">
            <img class="logo" src="./assets/logo.png" alt="logo" id="logo">
        </div>
        <input label="burger" class="checkbox" type="checkbox" name="" id="checkbox"/>
        <div class="hamburger-lines">
            <span class="line line1"></span>
            <span class="line line2"></span>
            <span class="line line3"></span>
        </div>
        <div class="menu">
            <ul>
                <li><span class="material-symbols-outlined">Element-1</span></li>
                <li><span class="material-symbols-outlined">Element-2</span></li>
                <li><span class="material-symbols-outlined">Element-3</span></li>
            </ul>
        </div> 
        <ul class="nav-center">
            <li class="l-nav"><span class="material-symbols-outlined">Element-1</span></li>
            <li class="l-nav"><span class="material-symbols-outlined">Element-2</span></li>
            <li class="l-nav"><span class="material-symbols-outlined">Element-3</span></li>
        </ul> 
        <ul class="nav-right">
            <a href="./my-account.php" class="navbar_item l-nav"><i class="fa-regular fa-user"></i></a>
        </ul>
    </nav>
</header>
    <main>
        <a href="./php_back/logout.php">Log out</a>
        <br><br><br>

        <form enctype="multipart/form-data" method="POST" action="./php_back/new_collection.php">
            <input id="collectionName" name="collectionName" type="text" placeholder="Collection's name" required="required"/>

            <legend>Status</legend>

            <div>
                <input type="radio" id="public" name="status" value="public" checked />
                <label for="public">Public</label>
            </div>

            <div>
                <input type="radio" id="private" name="status" value="private" />
                <label for="private">Private</label>
            </div>
            <button type="submit" name="newCollection">Upload</button>
        </form>
        <br>
        <h2>All my collections</h2>
        <ul>
            <?php
                $id = $data['id'];
                $recupUser = $bdd->query('SELECT * FROM collections where user_id = '.$id.'');
                while($user = $recupUser->fetch()){
            ?>
            <li>
                <form method="POST" action="./collection.php" style="display: inline;">
                    <input type="hidden" name="collection_id" value="<?php echo $user['collection_id']; ?>">
                    <button type="submit" style="background: none; border: none; color: blue; text-decoration: underline; cursor: pointer;">
                        <?php echo htmlspecialchars($user['name']); ?>
                    </button>
                </form>
            </li>
            <?php
                }
            ?>
        </ul>
</body>
</html>