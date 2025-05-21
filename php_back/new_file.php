<?php 
session_start();

// Debug: Display uploaded files
echo var_dump($_FILES), "<br>";

// Check if the total file size exceeds the limit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_FILES)) {
    die("Error: Total file size exceeds the allowed limit.");
}

$previousPage = $_SERVER['HTTP_REFERER'] ?? '../collection.php';

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

    // Create user folder if it doesn't exist
    $folder = "../assets/$id_user";
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }

    // Create collection folder if it doesn't exist
    $collectionFolder = "$folder/$collectionId";
    if (!file_exists($collectionFolder)) {
        mkdir($collectionFolder, 0777, true);
    }

    // Check if the collection folder is writable
    if (!is_writable($collectionFolder)) {
        die("Error: Destination folder is not writable.");
    }

    // Process uploaded files
    if (!empty($_FILES['file']['name'])) {
        foreach ($_FILES['file']['name'] as $key => $filename) {
            // Skip files with upload errors
            if ($_FILES['file']['error'][$key] !== UPLOAD_ERR_OK) {
                echo "Error uploading file: $filename (Error code: " . $_FILES['file']['error'][$key] . ")<br>";
                continue;
            }

            $extensionUpload = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $og_name = $_FILES['file']['name'][$key];
            $directory_name = md5(uniqid(rand(), true)) . "." . $extensionUpload;

            // Ensure unique file name
            $check = $bdd->prepare('SELECT directory_name_file FROM photos WHERE directory_name_file = ?');
            $check->execute([$directory_name]);
            $attempts = 0;
            while ($check->rowCount() > 0) {
                $directory_name = md5(uniqid(rand(), true)) . "." . $extensionUpload;
                $check->execute([$directory_name]);
                $attempts++;
                if ($attempts > 10) {
                    die("Error: Unable to generate a unique file name.");
                }
            }

            $tmp_name = $_FILES["file"]["tmp_name"][$key];
            $destination = "$collectionFolder/" . $directory_name;

            // Compress the image before moving it
            $compressionQuality = 100; // Compression quality (0-100, 100 = best quality)
            if (compressImage($tmp_name, $destination, $compressionQuality)) {
                $file_size2 = filesize($destination);

                // Create thumbnails folder if it doesn't exist
                $thumbnailFolder = "$collectionFolder/thumbnails";
                if (!file_exists($thumbnailFolder)) {
                    mkdir($thumbnailFolder, 0777, true);
                }

                // Generate thumbnail
                $thumbnailPath = "$thumbnailFolder/" . $directory_name;
                if (!createThumbnail($destination, $thumbnailPath, 720)) { // Max width/height: 720px
                    echo "Error: Unable to generate thumbnail for image $filename<br>";
                }

                // Extract EXIF metadata from the image
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
                ]) : null;

                // Insert file data into the database
                $insert = $bdd->prepare('INSERT INTO photos (user_id, name_file, size, file_extension, directory_name_file, collection_id, metadata) VALUES(:user_id, :name_file, :size, :file_extension, :directory_name_file, :collection_id, :metadata)');
                $insert->execute([
                    'user_id' => $id_user,
                    'name_file' => $og_name,
                    'size' => $file_size2,
                    'file_extension' => $extensionUpload,
                    'directory_name_file' => $directory_name,
                    'collection_id' => $collectionId,
                    'metadata' => $metadata
                ]);

                // Update photo count in the collection
                $update = "UPDATE collections SET photos_number = photos_number + 1 WHERE collection_id = $collectionId";
                $bdd->exec($update);
            } else {
                echo "Error: Unable to compress image $filename<br>";
            }
        }
        // Ensure redirection after processing all files
        if (!headers_sent()) {
            header("Location: $previousPage");
            exit();
        } else {
            echo "<script>window.location.href='../collection.php?collection_id=$collectionId';</script>";
            exit();
        }
    } else {
        // Redirect if no files were uploaded
        header('Location: ../collection.php?collection_id=' . $collectionId);
    }
} else {
    // Redirect to login page if user is not authenticated
    header('Location: ../login.php');
}

/**
 * Compress an image and save it to a specified destination.
 *
 * @param string $source Path to the source file.
 * @param string $destination Path to the destination file.
 * @param int $quality Compression quality (0-100).
 * @return bool Returns true if compression succeeds, otherwise false.
 */
function compressImage($source, $destination, $quality) {
    $info = getimagesize($source);

    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } else {
        return false; // Unsupported format
    }

    // Save the compressed image
    $result = imagejpeg($image, $destination, $quality);
    imagedestroy($image);

    return $result;
}

/**
 * Generate a thumbnail for a given image with a maximum resolution of 1080p.
 *
 * @param string $source Path to the source file.
 * @param string $destination Path to the destination file.
 * @param int $maxDimension Maximum dimension (width or height).
 * @return bool Returns true if thumbnail generation succeeds, otherwise false.
 */
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
            return false; // Unsupported format
    }

    $width = imagesx($image);
    $height = imagesy($image);

    // Calculate new dimensions while maintaining aspect ratio
    if ($width > $height) {
        $thumbWidth = $maxDimension;
        $thumbHeight = floor($height * ($maxDimension / $width));
    } else {
        $thumbHeight = $maxDimension;
        $thumbWidth = floor($width * ($maxDimension / $height));
    }

    $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);

    // Resize the image
    imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

    // Save the thumbnail
    $result = imagejpeg($thumbnail, $destination, 80); // Quality 80
    imagedestroy($image);
    imagedestroy($thumbnail);

    return $result;
}
?>
