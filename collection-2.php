<?php 
    session_start();
    require_once './php_back/config.php'; // ajout connexion bdd 

    // si la session existe pas soit si l'on est pas connecté on redirige
    if(!isset($_SESSION['user'])){
        header('Location: login.php');
        die();
    }

    // On récupere les données de l'utilisateur
    $req = $bdd->prepare('SELECT * FROM users WHERE token = ?');
    $req->execute(array($_SESSION['user']));
    $data = $req->fetch();

    $id = $data['id'];

    // Handle POST request and store collection ID in session
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['collection_id'])) {
        $_SESSION['collection_id'] = htmlspecialchars($_POST['collection_id']);
        header("Location: collection.php"); // Redirect without the collection ID in the URL
        exit();
    }

    // Retrieve collection ID from session
    if (isset($_SESSION['collection_id'])) {
        $collectionId = $_SESSION['collection_id'];
    } else {
        header('Location: ./home.php');
        exit();
    }

    $checkCollection = $bdd->prepare('SELECT * FROM collections WHERE collection_id = ? AND user_id = ?');
    $checkCollection->execute(array($collectionId, $id));
    if (!$checkCollection->fetch()) {
        header('Location: ./home.php');
        exit();
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
        <form enctype="multipart/form-data" method="POST" action="./php_back/new_file.php?=<?php echo $collectionId; ?>">
    
        <input class="input-file" id="my-file" name="file[]" type="file" accept="image/png, image/jpeg" style="width: 182px;" multiple required="required"/>

        <label for="my-file" class="input-file-trigger" tabindex="0">
            <i class="fa-solid fa-upload"></i> Selectionner un fichier
        </label>
        <button type="submit">Upload</button>
    </form>
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
            $photos[] = $photo;
        }

        // Retrieve screen width from localStorage via JavaScript and pass it to PHP using a cookie
        if (isset($_COOKIE['screen_width'])) {
            $screenWidth = (int)$_COOKIE['screen_width'];
            if ($screenWidth <= 768) {
                $numColumns = 2;
            } elseif ($screenWidth <= 1024) {
                $numColumns = 3;
            } else {
                $numColumns = 4;
            }
        } else {
            $numColumns = 4; // Default to 4 columns if no screen width is available
        }

        $columns = array_fill(0, $numColumns, []); // Create empty arrays for each column
        foreach ($photos as $index => $photo) {
            $columns[$index % $numColumns][] = $photo; // Distribute photos across columns
        }

        foreach ($columns as $column) {
            echo '<div class="grid-column">';
            foreach ($column as $photo) {
                $backgroundImage = './assets/' . htmlspecialchars($id) . '/' . htmlspecialchars($collectionId) . '/thumbnails/' . htmlspecialchars($photo['directory_name_file']);
        ?>
                <div class="grid-item">
                    <img src="<?php echo $backgroundImage; ?>" alt="Image">
                    <form method="POST" action="./php_back/delete_file.php" class="delete">
                        <input type="hidden" name="file_id" value="<?php echo htmlspecialchars($photo['file_id']); ?>">
                        <button type="submit" class="delete-button">Supprimer</button>
                    </form>
                </div>
        <?php
            }
            echo '</div>';
        }
        ?>
    </div>

    <script>
        // Store screen width in a cookie and reload the page if it changes
        function updateScreenWidth() {
            const screenWidth = window.innerWidth;
            document.cookie = `screen_width=${screenWidth}; path=/`;
            location.reload();
        }

        // Check if screen width is stored in a cookie
        if (!document.cookie.includes('screen_width')) {
            updateScreenWidth();
        }

        // Add an event listener to detect screen size changes
        window.addEventListener('resize', () => {
            clearTimeout(window.resizeTimeout);
            window.resizeTimeout = setTimeout(updateScreenWidth, 500); // Debounce to avoid excessive reloads
        });
    </script>

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

        .grid-container {
            display: grid;
            gap: 20px; /* Increase the gap between items */
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            justify-content: center; /* Center the grid items */
        }

        @media (max-width: 768px) {
            .grid-container {
                grid-template-columns: repeat(2, minmax(0px, 1fr)) !important; /* 2 columns for small screens */
            }
        }

        @media (max-width: 1024px) {
            .grid-container {
                grid-template-columns: repeat(3, minmax(0px, 1fr)); /* 3 columns for medium screens */
            }
        }

        @media (min-width: 1025px) {
            .grid-container {
                grid-template-columns: repeat(4, minmax(0px, 1fr)); /* 4 columns for large screens */
            }
        }


        .grid-item {
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .grid-item img {
            display: block;
            width: 100%;
            height: auto;
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Add smooth hover effect */
        }

        .grid-item:hover img {
            transform: scale(1.05); /* Zoom effect on hover */
        }

        .delete-button {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: rgba(209, 44, 44, 0.8);
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
            z-index: 10; /* Ensure button is above the image */
            opacity: 0; /* Hidden by default */
            transition: opacity 0.3s ease;
        }

        .grid-item:hover .delete-button {
            opacity: 1; /* Show on hover */
        }

        .delete-button:hover {
            background-color: rgba(161, 14, 14, 0.9);
        }
    </style>
</body>
</html>