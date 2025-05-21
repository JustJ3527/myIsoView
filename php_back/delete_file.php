<?php
    // Démarrage de la session 
    session_start();
    // Include de la base de données
    require_once './config.php';

    if(!isset($_SESSION['user']))
    {
        header('Location: ../login.php');
        die();
    }

    // On récupere les données de l'utilisateur
    $req = $bdd->prepare('SELECT * FROM users WHERE token = ?');
    $req->execute(array($_SESSION['user']));
    $data = $req->fetch();

    if(!$data) {
        // Redirection vers la page de connexion si les données de l'utilisateur ne sont pas disponibles
        header('Location: ../login.php');
        exit(); // Terminer le script
    }
    // Si la session n'existe pas 

    $id = $data['id'];
    $recupUser = $bdd->query('SELECT * FROM photos WHERE user_id = '.$id.'');
    if($user = $recupUser->fetch()){
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'){ 
            $url = "https"; 
        }else{
            $url = "http";
        } 
        $url .= $_SERVER['REQUEST_URI']; 
            
        // Afficher l'URL
        $domaine = strstr($url, '=',);
        $id_file = substr($domaine, 1);

        $id_user = $data['id'];

        $folder = "assets/$id_user";
        $name_img = $user['name_file'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_id'])) {
            $id_file = htmlspecialchars($_POST['file_id']);

            $recupFile = $bdd->prepare('SELECT * FROM photos WHERE user_id = ? AND file_id = ?');
            $recupFile->execute([$id, $id_file]);
            if ($file = $recupFile->fetch()) {
                $name_file = $file['directory_name_file'];
                $collectionId = $file['collection_id'];

                // Delete the file from the server
                $place = "../assets/$id/$collectionId/$name_file";
                if (file_exists($place)) {
                    unlink($place);
                }

                // Delete the thumbnail from the server
                $thumbnailPlace = "../assets/$id/$collectionId/thumbnails/$name_file";
                if (file_exists($thumbnailPlace)) {
                    unlink($thumbnailPlace);
                }

                // Delete the record from the database
                $suppr = $bdd->prepare('DELETE FROM photos WHERE file_id = ?');
                $suppr->execute([$id_file]);

                // Update the collection's photo count
                $update = $bdd->prepare('UPDATE collections SET photos_number = photos_number - 1 WHERE collection_id = ?');
                $update->execute([$collectionId]);

                // Redirect to the previous page or collection.php if no referer is available
                $previousPage = $_SERVER['HTTP_REFERER'] ?? '../collection.php';
                header("Location: $previousPage");
                exit();
            }
        }
    }
?>