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
    exit(); // Utiliser exit() au lieu de die() pour une meilleure lisibilité
}

// Préparer et exécuter la requête pour récupérer les données de l'utilisateur
$req = $bdd->prepare('SELECT * FROM users WHERE token = ?');
$req->execute([$_SESSION['user']]);
$data = $req->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Home</title>


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
    <link rel="stylesheet" type="text/css" href="./CSS/style.css">
    <link rel="stylesheet" type="text/css" href="./CSS/style-navbar.css">

    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" type="text/css" href="./navbar-style.css">

    <script src="https://kit.fontawesome.com/2dd49550a9.js" crossorigin="anonymous"></script>
</head>
<body>
    <header class="header" id="header">
        <nav class="navbar container">
            <a href="#" class="brand">Brand</a>
            <div class="search">
                <form class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="Search for Destinations" autofocus>
                    <button type="submit" class="search-submit" disabled><i class="bx bx-search"></i></button>
                </form>
            </div>
            <div class="menu" id="menu">
                <ul class="menu-inner">
                    <li class="menu-item"><a href="#" class="menu-link">Explore</a></li>
                    <li class="menu-item"><a href="#" class="menu-link">My collections</a></li>
                    <!-- <li class="menu-item"><a href="#" class="menu-link">Popular</a></li> -->
                    <li class="menu-item"><a href="#" class="menu-link">My account</a></li>
                </ul>
            </div>
            <div class="burger" id="burger">
                <span class="burger-line"></span>
                <span class="burger-line"></span>
                <span class="burger-line"></span>
            </div>
        </nav>
    </header>

    <main>
        <div class="container" id="content">
        </div>
    </main>


    <script src="./js/script-navbar.js"></script>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    <style>
        body { 
            padding: 20px;
        }
    </style>
</body>
</html>