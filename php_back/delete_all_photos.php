<?php
// Start the session
session_start();
require_once './config.php';

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit();
}

// Retrieve user data from the database
$req = $bdd->prepare('SELECT * FROM users WHERE token = ?');
$req->execute([$_SESSION['user']]);
$data = $req->fetch();

$id = $data['id'];

// Handle POST request to delete all photos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['collection_id'])) {
    $collectionId = htmlspecialchars($_POST['collection_id']);

    // Check if the collection belongs to the user
    $checkCollection = $bdd->prepare('SELECT * FROM collections WHERE collection_id = ? AND user_id = ?');
    $checkCollection->execute([$collectionId, $id]);

    if ($checkCollection->rowCount() > 0) {
        // Delete associated photos from the database
        $deletePhotos = $bdd->prepare('DELETE FROM photos WHERE collection_id = ?');
        $deletePhotos->execute([$collectionId]);

        // Delete photo files
        $folderPath = "../assets/$id/$collectionId/HD";
        if (is_dir($folderPath)) {
            $files = array_diff(scandir($folderPath), ['.', '..']);
            foreach ($files as $file) {
                unlink("$folderPath/$file");
            }
        }

        // Delete thumbnails
        $thumbnailPath = "../assets/$id/$collectionId/thumbnails";
        if (is_dir($thumbnailPath)) {
            $files = array_diff(scandir($thumbnailPath), ['.', '..']);
            foreach ($files as $file) {
                unlink("$thumbnailPath/$file");
            }
        }

        // Update the collection's photo count and total size
        $updateCollection = $bdd->prepare('UPDATE collections SET photos_number = 0, total_size = 0 WHERE collection_id = ?');
        $updateCollection->execute([$collectionId]);

        header('Location: ../collection.php?collection_id=' . $collectionId . '&message=all_photos_deleted');
        exit();
    } else {
        header('Location: ../home.php?error=unauthorized');
        exit();
    }
} else {
    header('Location: ../home.php?error=invalid_request');
    exit();
}
?>