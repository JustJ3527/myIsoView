<?php
    require_once 'config.php';
    if(!empty($_POST['fname']) && !empty($_POST['lname']) && !empty($_POST['email']) && !empty($_POST['password']) && !empty($_POST['password_retype']) )
    {
        // Patch XSS
        $fname = htmlspecialchars($_POST['fname']);
        $lname = htmlspecialchars($_POST['lname']);
        $email = htmlspecialchars($_POST['email']);
        $password = htmlspecialchars($_POST['password']);
        $password_retype = htmlspecialchars($_POST['password_retype']);

        // On vérifie si l'utilisateur existe
        $check = $bdd->prepare('SELECT email, password FROM users WHERE email = ?');
        $check->execute(array($email));
        $data = $check->fetch();
        $row = $check->rowCount();

        $email = strtolower($email); // on transforme toute les lettres majuscule en minuscule pour éviter que Foo@gmail.com et foo@gmail.com soient deux compte différents ..
        
        // Si la requete renvoie un 0 alors l'utilisateur n'existe pas 
        if($row == 0){
            if(strlen($email) <= 100){ // On verifie que la longueur du mail <= 100
                if(filter_var($email, FILTER_VALIDATE_EMAIL)){ // Si l'email est de la bonne forme
                    if($password === $password_retype){ // si les deux mdp saisis sont bon

                        // On hash le mot de passe avec Bcrypt, via un coût de 12
                        $cost = ['cost' => 12];
                        $password = password_hash($password, PASSWORD_BCRYPT, $cost);
                        
                        // On stock l'adresse IP
                        $ip = $_SERVER['REMOTE_ADDR']; 

                        // On insère dans la base de données
                        $insert = $bdd->prepare('INSERT INTO users(fname, lname, email, password, ip, type, token, last_ip) VALUES(:fname, :lname, :email, :password, :ip, :type, :token, :last_ip)');
                        $insert->execute(array(
                            'fname' => $fname,
                            'lname' => $lname,
                            'email' => $email,
                            'password' => $password,
                            'ip' => $ip,
                            'type' => "user",
                            'token' => bin2hex(openssl_random_pseudo_bytes(64)), 
                            'last_ip' => $ip
                        ));
                        // On redirige avec le message de succès
                        header('Location:../login.php?reg_err=success');
                        die();
                    }else{ header('Location: ../registration.php?reg_err=password'); die();}
                }else{ header('Location: ../registration.php?reg_err=email'); die();}
            }else{ header('Location: ../registration.php?reg_err=email_length'); die();}
        }else{ header('Location: ../login.php?login_err=email_already_used'); die();}
    }
?>