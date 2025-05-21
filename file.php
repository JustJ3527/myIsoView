<?php 
    session_start();
    require_once './php_back/config.php'; // ajout connexion bdd 
   // si la session existe pas soit si l'on est pas connecté on redirige
    if(!isset($_SESSION['user'])){
        header('Location:login.php');
        die();
    }

    // On récupere les données de l'utilisateur
    $req = $bdd->prepare('SELECT * FROM users WHERE token = ?');
    $req->execute(array($_SESSION['user']));
    $data = $req->fetch();

    if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
    {
        $url = "https"; 
    }else{
        $url = "http"; 
    }


    
  // Ajouter l'emplacement de la ressource demandée à l'URL
  $url .= $_SERVER['REQUEST_URI']; 
      
  // Afficher l'URL
  $domaine = strstr($url, '=');
  $directory_name_file = substr($domaine, 1);
   
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
    <link rel="stylesheet" type="text/css" href="./css/style.css">

    <script src="https://kit.fontawesome.com/2dd49550a9.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php
        $id = $data['id'];
        $recupPhoto = $bdd->query('SELECT * FROM photos WHERE user_id = '.$id.' AND directory_name_file = "'.$directory_name_file.'"');
        if($photo = $recupPhoto->fetch()){
    ?>
    <h1><?php echo $photo['name_file']; ?></h1>

    <img src="./assets/<?php echo $id; ?>/<?php echo $photo['directory_name_file']; ?>" width="200px">
    <br>
    <a href="./php_back/delete_file.php?=<?php echo $user['file_id']; ?>" class="delete">Delete</a><br>

    <?php 
        }
    ?>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    <script src="js/custom.js"></script>

    <style>
        body {
            padding: 20px;
        }
        h1::first-letter {
            text-transform: capitalize;
        }
        h1 {
            background-color: #598fb3;
        }
        .delete {
            color: #D12C2C;
        }
    </style>
</body>
</html>