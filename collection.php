<?php 
    // Start the session
    session_start();
    require_once './php_back/config.php'; // ajout connexion bdd 

    // Check if the user is logged in
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        die();
    }

    // Retrieve user data from the database
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

    // Check if the collection belongs to the user
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
        $totalSizeInMB = $collection['total_size'] / (1024 * 1024); // Convertir en Mo
    ?>
        <h1><?php echo htmlspecialchars($collection['name']); ?></h1>
        <h4><?php echo htmlspecialchars($collection['collection_id']); ?></h4>

        <h2><?php echo htmlspecialchars($collection['status']); ?></h2>
        <h3><?php echo htmlspecialchars($collection['photos_number']); ?> photos</h3>
        <h3>Taille totale : <?php echo number_format($totalSizeInMB, 2); ?> Mo</h3>
        <form enctype="multipart/form-data" method="POST" action="./php_back/new_file.php?=<?php echo $collectionId; ?>">
            <label>
                <input type="checkbox" name="hd_option" value="1"> Activer l'option HD
            </label>
            <input class="input-file" id="my-file" name="file[]" type="file" accept="image/png, image/jpeg" style="width: 182px;" multiple required="required"/>

            <label for="my-file" class="input-file-trigger" tabindex="0">
                <i class="fa-solid fa-upload"></i> Sélectionner un fichier
            </label>
            <button type="submit">Upload</button>
        </form>

    <form method="POST" action="./php_back/delete_collection.php" class="delete-collection-form">
        <input type="hidden" name="collection_id" value="<?php echo htmlspecialchars($collectionId); ?>">
        <button type="submit" class="delete-collection-button">Supprimer la collection</button>
    </form>

    <form method="POST" action="./php_back/delete_all_photos.php" class="delete-all-photos-form">
        <input type="hidden" name="collection_id" value="<?php echo htmlspecialchars($collectionId); ?>">
        <button type="submit" class="delete-all-photos-button">Supprimer toutes les photos</button>
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

        // Trier les photos par date de capture (plus récentes en haut)
        usort($photos, function($a, $b) use ($id, $collectionId) {
            $metadataA = json_decode($a['metadata'], true);
            $metadataB = json_decode($b['metadata'], true);

            $dateA = $metadataA['DateTimeOriginal'] ?? '1970:01:01 00:00:00';
            $dateB = $metadataB['DateTimeOriginal'] ?? '1970:01:01 00:00:00';

            return strtotime($dateB) - strtotime($dateA); // Plus récentes en haut
        });

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
                    <img src="<?php echo $backgroundImage; ?>" alt="Image" onclick="openPopup('./assets/<?php echo htmlspecialchars($id); ?>/<?php echo htmlspecialchars($collectionId); ?>/HD/<?php echo htmlspecialchars($photo['directory_name_file']); ?>')">
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

    <!-- Popup for full image view -->
    <div id="imagePopup" class="popup">
        <span class="popup-close" onclick="closePopup()">&times;</span>
        <img id="popupImage" src="" alt="Full Image">
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


        //------------------------------------//


        // Function to open the popup with the full image
        function openPopup(imageSrc) {
            const popup = document.getElementById('imagePopup');
            const popupImage = document.getElementById('popupImage');
            popupImage.src = imageSrc; // Update path to include HD
            popup.style.display = 'flex';
        }

        // Function to close the popup
        function closePopup() {
            const popup = document.getElementById('imagePopup');
            popup.classList.add('closing'); // Add closing class to trigger animation
            setTimeout(() => {
                popup.style.display = 'none';
                popup.classList.remove('closing'); // Remove closing class after animation
            }, 500); // Match this duration with the CSS animation duration
        }

        // Close the popup when clicking outside the image
        document.getElementById('imagePopup').addEventListener('click', function(event) {
            if (event.target === this) {
                closePopup();
            }
        });
    </script>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    <script type="text/javascript" src="./js/script.js"></script>
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
            display: flex;
            gap: 20px; /* Increase the gap between columns */
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .grid-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px; /* Increase the gap between rows */
        }

        .grid-item {
            position: relative;
            overflow: hidden;
        }

        .grid-item img {
            display: block;
            width: 100%;
            height: auto;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .grid-item img:active {
            transform: scale(1.5);
            z-index: 1000;
            position: relative;
        }

        .grid-item:hover img {
            transform: scale(1.05); /* Zoom effect on hover */
        }

        @media (max-width: 1024px) {
            .grid-container {
                /*flex-wrap: wrap;*/
            }
            .grid-column {
                flex: 0 0 calc(33.33% - 10px); /* 3 columns for medium screens */
            }
        }

        @media (max-width: 768px) {
            .grid-column {
                flex: 0 0 calc(50% - 10px); /* 2 columns for small screens */
            }
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

        /* Styles for the popup */
        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeInBackground 0.5s ease-in-out forwards;
            cursor: pointer; /* Add pointer cursor when outside the image */
        }

        .popup.closing {
            animation: fadeOutBackground 0.5s ease-in-out forwards;
        }

        @keyframes fadeInBackground {
            from {
                background-color: rgba(0, 0, 0, 0);
            }
            to {
                background-color: rgba(0, 0, 0, 0.8);
            }
        }

        @keyframes fadeOutBackground {
            from {
                background-color: rgba(0, 0, 0, 0.8);
            }
            to {
                background-color: rgba(0, 0, 0, 0);
            }
        }

        .popup img {
            max-width: 90%;
            max-height: 90%;
            border: 5px solid white;
            border-radius: 10px;
            transform-origin: var(--click-x) var(--click-y);
            animation: zoomIn 0.5s ease-in-out forwards;
            cursor: default; /* Default cursor for the image */
        }

        .popup.closing img {
            animation: zoomOut 0.5s ease-in-out forwards;
        }

        @keyframes zoomIn {
            from {
                transform: scale(0.5);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes zoomOut {
            from {
                transform: scale(1);
                opacity: 1;
            }
            to {
                transform: scale(0.5);
                opacity: 0;
            }
        }

        .popup-close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 30px;
            color: white;
            cursor: pointer;
        }

        .delete-collection-button {
            background-color: #D12C2C;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .delete-collection-button:hover {
            background-color: #A10E0E;
        }

        .delete-all-photos-button {
            background-color: #D12C2C;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .delete-all-photos-button:hover {
            background-color: #A10E0E;
        }
    </style>
</body>
</html>