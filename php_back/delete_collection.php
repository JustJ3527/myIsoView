<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    die();
}

include_once "config.php";

// On récupère les données de l'utilisateur
$req = $bdd->prepare('SELECT * FROM users WHERE token = ?');
$req->execute(array($_SESSION['user']));
$data = $req->fetch();

$id = $data['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['collection_id'])) {
    $collectionId = htmlspecialchars($_POST['collection_id']);

    // Vérifier si la collection appartient à l'utilisateur
    $checkCollection = $bdd->prepare('SELECT * FROM collections WHERE collection_id = ? AND user_id = ?');
    $checkCollection->execute([$collectionId, $id]);

    if ($checkCollection->rowCount() > 0) {
        // Supprimer les photos associées
        $deletePhotos = $bdd->prepare('DELETE FROM photos WHERE collection_id = ?');
        $deletePhotos->execute([$collectionId]);

        // Supprimer les fichiers et dossiers associés
        $folderPath = "../assets/$id/$collectionId";
        deleteFolder($folderPath);

        // Supprimer la collection
        $deleteCollection = $bdd->prepare('DELETE FROM collections WHERE collection_id = ?');
        $deleteCollection->execute([$collectionId]);

        header('Location: ../home.php?message=collection_deleted');
        exit();
    } else {
        header('Location: ../home.php?error=unauthorized');
        exit();
    }
} else {
    header('Location: ../home.php?error=invalid_request');
    exit();
}

// Fonction pour supprimer récursivement un dossier et son contenu
function deleteFolder($folderPath) {
    if (is_dir($folderPath)) {
        $files = array_diff(scandir($folderPath), ['.', '..']);
        foreach ($files as $file) {
            $filePath = "$folderPath/$file";
            if (is_dir($filePath)) {
                deleteFolder($filePath); // Appel récursif pour les sous-dossiers
            } else {
                unlink($filePath); // Supprimer le fichier
            }
        }
        rmdir($folderPath); // Supprimer le dossier une fois vide
    }
}
?>