<?php 
        /*
           Attention ! le host => l'adresse de la base de données et non du site !!
        
           Pour ceux qui doivent spécifier le port ex : 
           $bdd = new PDO("mysql:host=CHANGER_HOST_ICI;dbname=CHANGER_DB_NAME;charset=utf8;port=3306", "CHANGER_LOGIN", "CHANGER_PASS");
           
         */
    $hostname = "localhost";
    $dbname = "myISOView_db";
    try 
    {
        $bdd = new PDO("mysql:host=$hostname; dbname=$dbname; charset=utf8","root", "");
    }
    catch(PDOException $e)
    {
        die('Erreur : '.$e->getMessage());
    }
?>
<?php


  // $conn = mysqli_connect($hostname, $username, $password, $dbname);
  // if(!$conn){
  //   echo "Database connection error".mysqli_connect_error();
  // }
?>