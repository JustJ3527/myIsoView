<?php 
session_start();
require_once './php_back/config.php'; // ajout connexion bdd 

// Si la session n'existe pas, redirige vers la page de connexion
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    die();
}

// On récupère les données de l'utilisateur
$req = $bdd->prepare('SELECT * FROM users WHERE token = ?');
$req->execute(array($_SESSION['user']));
$data = $req->fetch();

$id = $data['id'];

// Récupération de l'ID de la collection
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $url = "https"; 
} else {
    $url = "http"; 
}
$url .= $_SERVER['REQUEST_URI']; 
$domaine = strstr($url, '=');
$collectionId = substr($domaine, 1);

if (empty($collectionId)) {
    header('Location: ./home.php');
    die();
}

// Vérifie si la collection appartient à l'utilisateur
$checkCollection = $bdd->query('SELECT * FROM collections WHERE collection_id = ' . $collectionId);
if ($collectionCheck = $checkCollection->fetch()) {
    if ($collectionCheck['user_id'] != $id) {
        header('Location: ./home.php');
        die();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
    <link rel="stylesheet" type="text/css" href="css/style.css">

    <script src="https://kit.fontawesome.com/2dd49550a9.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php
    $recupCollection = $bdd->query('SELECT * FROM collections WHERE collection_id = ' . $collectionId . ' AND user_id = ' . $id);
    if ($collection = $recupCollection->fetch()) {
    ?>
        <h1><?php echo htmlspecialchars($collection['name']); ?></h1>
        <h3><?php echo htmlspecialchars($collection['photos_number']); ?> photos</h3>
    <?php
    }
    ?>
    <br>

    <div class="grid-container">
        <?php 
        $photos = [];
        $recupPhotos = $bdd->prepare('SELECT * FROM photos WHERE collection_id = ? AND user_id = ?');
        $recupPhotos->execute(array($collectionId, $id));
        while ($photo = $recupPhotos->fetch()) {
            $photos[] = $photo; // Ajouter toutes les colonnes de la table photos
        }

        $i = 0; // Compteur pour alterner les grandes images
        foreach ($photos as $user) {
            $class = ($i % 5 === 0) ? 'large' : ''; // Une grande div tous les 5 éléments
            $backgroundImage = './assets/' . htmlspecialchars($id) . '/' . htmlspecialchars($collectionId) . '/thumbnails/' . htmlspecialchars($user['directory_name_file']);
            $originalImage = './assets/' . htmlspecialchars($id) . '/' . htmlspecialchars($collectionId) . '/' . htmlspecialchars($user['directory_name_file']);
            $deleteLink = './php_back/delete_file.php?file_id=' . urlencode($user['file_id']);
        ?>
            <div class="grid-item <?php echo $class; ?>" style="background-image: url('<?php echo $backgroundImage; ?>');">
                <!-- Lien pour ouvrir l'image originale -->
                <a href="<?php echo $originalImage; ?>" target="_blank" class="view-link"></a>

                <!-- Bouton "Supprimer" -->
                <a href="<?php echo $deleteLink; ?>" class="delete-link">Supprimer</a>
            </div>
        <?php
            $i++;
        }
        ?>
    </div>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Colonnes flexibles */
            gap: 10px;
            width: 100%; /* Utilise toute la largeur */
            max-width: 1200px;
            grid-auto-flow: dense; /* Remplit les espaces vides */
        }

        .grid-item {
            position: relative;
            overflow: hidden;
            background-size: cover; /* Pour remplir la boîte */
            background-position: center; /* Centrer l'image */
            width: 100%;
            aspect-ratio: 1; /* Conserve un ratio carré par défaut */
        }

        /* Lien invisible pour ouvrir l'image originale */
        .grid-item .view-link {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            text-decoration: none;
            z-index: 1; /* Au-dessus du fond */
        }

        /* Bouton "Supprimer" */
        .delete-link {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: rgba(255, 0, 0, 0.8); /* Rouge semi-transparent */
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            font-size: 14px;
            border-radius: 4px;
            z-index: 2; /* Au-dessus du lien invisible */
            display: none; /* Caché par défaut */
        }

        /* Afficher le bouton "Supprimer" au survol */
        .grid-item:hover .delete-link {
            display: block;
        }

        .grid-item.large {
            grid-column: span 2; /* S'étend sur 2 colonnes */
            grid-row: span 2;    /* S'étend sur 2 lignes */
        }

        @media (max-width: 768px) {
            .grid-container {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); /* Colonnes plus petites sur mobile */
            }
            .grid-item.large {
                grid-column: span 2; /* Conserve la taille 2x2 */
                grid-row: span 2;
            }
        }

        @media (max-width: 480px) {
            .grid-container {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); /* Colonnes encore plus petites */
            }
        }
    </style>
</body>
</html>