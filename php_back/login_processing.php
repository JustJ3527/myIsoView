<?php 
    session_start(); // Démarrage de la session
    require_once 'config.php'; // On inclut la connexion à la base de données

    if(!empty($_POST['email']) && !empty($_POST['password'])) // Si il existe les champs email, password et qu'il sont pas vides
    {
        // Patch XSS
        $email = htmlspecialchars($_POST['email']); 
        $password = htmlspecialchars($_POST['password']);
        
        $email = strtolower($email); // email transformé en minuscule
        
        // On regarde si l'utilisateur est inscrit dans la table utilisateurs
        $check = $bdd->prepare('SELECT email, password, token FROM users WHERE email = ?');
        $check->execute(array($email));
        $data = $check->fetch();
        $row = $check->rowCount();
        
        

        // Si > à 0 alors l'utilisateur existe
        if(filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            // Si le mail est bon niveau format
            if($row > 0)
            {
                // Si le mot de passe est le bon
                if(password_verify($password, $data['password']))
                {
                    $last_ip = $_SERVER['REMOTE_ADDR']; 
                    $_SESSION['user'] = $data['token'];
                    $update = $bdd->prepare('UPDATE users SET last_ip = :last_ip WHERE token = :token');
                    $update->execute(array(
                        "last_ip" => $last_ip,
                        "token" => $_SESSION['user']
                    ));

                    // On créer la session et on redirige sur landing.php

                    header('Location: ../home.php');
                    die();
                }else{ header('Location: ../home.php?login_err=password'); die(); }
            }else{ header('Location: ../registration.php?reg_err=no_email'); die(); }
        }else{ header('Location: ../login.php?login_err=email'); die(); }
    }else{ header('Location: ../login.php'); die();} // si le formulaire est envoyé sans aucune données