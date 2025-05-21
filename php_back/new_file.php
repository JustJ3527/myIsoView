<?php 

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Check if the request method is POST and if files are uploaded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_FILES)) {
    die("Error: Total file size exceeds the allowed limit.");
}

// Get the previous page for redirection
$previousPage = $_SERVER['HTTP_REFERER'] ?? '../collection.php';

// Check if the user is logged in
if (isset($_SESSION['user'])) {
    include_once "./config.php";

    // Retrieve user data from the database
    $req = $bdd->prepare('SELECT * FROM users WHERE token = ?');
    $req->execute([$_SESSION['user']]);
    $data = $req->fetch();

    $id_user = $data['id'];

    // Extract collection ID from the URL
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $url .= $_SERVER['REQUEST_URI']; 
    $collectionId = substr(strstr($url, '='), 1);

    // Create user and collection folders if they don't exist
    $folder = "../assets/$id_user";
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }

    $collectionFolder = "$folder/$collectionId";
    if (!file_exists($collectionFolder)) {
        mkdir($collectionFolder, 0777, true);
    }

    // Check if the collection folder is writable
    if (!is_writable($collectionFolder)) {
        die("Error: Destination folder is not writable.");
    }

    $totalSize = 0; // Initialize total size variable

    // Process uploaded files
    if (!empty($_FILES['file']['name'])) {
        foreach ($_FILES['file']['name'] as $key => $filename) {
            // Skip files with upload errors
            if ($_FILES['file']['error'][$key] !== UPLOAD_ERR_OK) {
                continue;
            }

            // Generate a unique name for the file
            $extensionUpload = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $og_name = $_FILES['file']['name'][$key];
            $directory_name = md5(uniqid(rand(), true)) . "." . $extensionUpload;

            // Ensure the file name is unique in the database
            $check = $bdd->prepare('SELECT directory_name_file FROM photos WHERE directory_name_file = ?');
            $check->execute([$directory_name]);
            while ($check->rowCount() > 0) {
                $directory_name = md5(uniqid(rand(), true)) . "." . $extensionUpload;
                $check->execute([$directory_name]);
            }

            // Create HD folder if it doesn't exist
            $hdFolder = "$collectionFolder/HD";
            if (!file_exists($hdFolder)) {
                mkdir($hdFolder, 0777, true);
            }

            $tmp_name = $_FILES["file"]["tmp_name"][$key];
            $destination = "$hdFolder/$directory_name";

            // Handle compression or direct copy based on HD option
            $compressionQuality = isset($_POST['hd_option']) ? null : 60;

            if (isset($_POST['hd_option'])) {
                if (!move_uploaded_file($tmp_name, $destination)) {
                    continue;
                }
                $compressionQuality = 100;
            } else {
                if (!compressImage($tmp_name, $destination, $compressionQuality)) {
                    continue;
                }
            }

            // Update total size
            $file_size2 = filesize($destination);
            $totalSize += $file_size2;

            // Create thumbnail folder if it doesn't exist
            $thumbnailFolder = "$collectionFolder/thumbnails";
            if (!file_exists($thumbnailFolder)) {
                mkdir($thumbnailFolder, 0777, true);
            }

            // Generate thumbnail
            $thumbnailPath = "$thumbnailFolder/$directory_name";
            createThumbnail($destination, $thumbnailPath, 720);

            // Extract metadata from the image
            $exifData = @exif_read_data($tmp_name);
            $metadata = $exifData ? json_encode([
                'DateTimeOriginal' => $exifData['DateTimeOriginal'] ?? null,
                'Make' => $exifData['Make'] ?? null,
                'Model' => $exifData['Model'] ?? null,
                'ExposureTime' => $exifData['ExposureTime'] ?? null,
                'FNumber' => $exifData['FNumber'] ?? null,
                'ISOSpeedRatings' => $exifData['ISOSpeedRatings'] ?? null,
                'FocalLength' => $exifData['FocalLength'] ?? null,
                'GPSLatitude' => $exifData['GPSLatitude'] ?? null,
                'GPSLongitude' => $exifData['GPSLongitude'] ?? null
            ]) : json_encode([]);

            // Insert photo details into the database
            $insert = $bdd->prepare('INSERT INTO photos (user_id, name_file, size, file_extension, directory_name_file, collection_id, metadata, quality) VALUES(:user_id, :name_file, :size, :file_extension, :directory_name_file, :collection_id, :metadata, :quality)');
            $insert->execute([
                'user_id' => $id_user,
                'name_file' => $og_name,
                'size' => $file_size2,
                'file_extension' => $extensionUpload,
                'directory_name_file' => $directory_name,
                'collection_id' => $collectionId,
                'metadata' => $metadata,
                'quality' => $compressionQuality ?? 60
            ]);

            // Update photo count in the collection
            $update = "UPDATE collections SET photos_number = photos_number + 1 WHERE collection_id = $collectionId";
            $bdd->exec($update);
        }

        // Update total size in the collection
        $updateSize = $bdd->prepare("UPDATE collections SET total_size = total_size + :total_size WHERE collection_id = :collection_id");
        $updateSize->execute([
            'total_size' => $totalSize,
            'collection_id' => $collectionId
        ]);

        // Redirect to the previous page
        if (!headers_sent()) {
            header("Location: $previousPage");
            exit();
        } else {
            echo "<script>window.location.href='../collection.php?collection_id=$collectionId';</script>";
            exit();
        }
    } else {
        header('Location: ../collection.php?collection_id=' . $collectionId);
    }
} else {
    header('Location: ../login.php');
}

// Function to compress an image
function compressImage($source, $destination, $quality) {
    $info = getimagesize($source);

    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } else {
        return false;
    }

    $result = imagejpeg($image, $destination, $quality);
    imagedestroy($image);

    return $result;
}

// Function to create a thumbnail
function createThumbnail($source, $destination, $maxDimension = 1080) {
    $info = getimagesize($source);
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }

    $width = imagesx($image);
    $height = imagesy($image);

    if ($width > $height) {
        $thumbWidth = $maxDimension;
        $thumbHeight = floor($height * ($maxDimension / $width));
    } else {
        $thumbHeight = $maxDimension;
        $thumbWidth = floor($width * ($maxDimension / $height));
    }

    $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
    imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

    $result = imagejpeg($thumbnail, $destination, 80);
    imagedestroy($image);
    imagedestroy($thumbnail);

    return $result;
}
?>
