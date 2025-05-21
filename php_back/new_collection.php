<?php
    session_start();

    // Vérifie si l'utilisateur est connecté
    if(isset($_SESSION['user'])){
        include_once "config.php"; // Inclusion du fichier de configuration pour la base de données

        // Récupération des données de l'utilisateur connecté
        $req = $bdd->prepare('SELECT * FROM users WHERE token = ?');
        $req->execute(array($_SESSION['user']));
        $data = $req->fetch();

        // Vérifie si les champs requis pour créer une collection sont remplis
        if(!empty($_POST['collectionName']) && !empty($_POST['status'])){
            $userId = $data['id'];

            // Vérifie si l'utilisateur existe dans la base de données
            $recupUser = $bdd->prepare('SELECT * FROM users WHERE id = ?');
            $recupUser->execute(array($userId));

            // Si le formulaire de création de collection est soumis
            if(isset($_POST['newCollection'])){
                // Récupération et sécurisation des données du formulaire
                $collectionName = htmlspecialchars(($_POST['collectionName']));
                $status = $_POST['status'];

                // Insertion de la nouvelle collection dans la base de données
                $newCollection = $bdd->prepare('INSERT INTO collections(name, user_id, status, photos_number) VALUES(?, ?, ?, ?)');
                $newCollection->execute(params: array($collectionName, $userId, $status, 0));

                // Récupération de l'ID de la dernière collection insérée pour cet utilisateur
                $recupId = $bdd->query('SELECT MAX(collection_id) as max_id FROM collections where user_id = '.$userId.'');
                if($id = $recupId->fetch()){
                    if(isset($id['max_id'])) {
                        $lastId = $id['max_id']; // ID de la dernière collection
                        echo "Last ID: ".$lastId;

                        // Création du dossier pour l'utilisateur s'il n'existe pas
                        $folder = "../assets/$userId";
                        if (!file_exists($folder)) {
                            mkdir($folder, 0777, true);
                        }

                        // Création d'un sous-dossier pour la collection avec l'ID de la collection
                        $folderCollection = "../assets/$userId/$lastId/";
                        mkdir($folderCollection, 0777, true);
                    }
                }

                // Redirection vers la page de la collection avec l'ID de la collection
                header('Location: ../collection.php?collection_id='.$lastId.'');
                die();
            }
        } else {
            // Redirection vers la page d'accueil avec un message d'erreur si les champs sont incomplets
            header('Location: ../home.php?collection_err=incomplete');
            die();
        }
    } else {
        // Redirection vers la page de connexion si l'utilisateur n'est pas connecté
        header('Location: ../login.php');
        die();
    }
?>