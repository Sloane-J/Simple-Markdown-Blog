<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function resizeImage($sourcePath, $targetPath, $maxWidth, $maxHeight) {
    list($width, $height, $type) = getimagesize($sourcePath);
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $new_width = $width * $ratio;
    $new_height = $height * $ratio;
    
    // Create new image
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Handle transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Load source image
    switch ($type) {
        case IMAGETYPE_JPEG: $source = imagecreatefromjpeg($sourcePath); break;
        case IMAGETYPE_PNG: $source = imagecreatefrompng($sourcePath); break;
        case IMAGETYPE_GIF: $source = imagecreatefromgif($sourcePath); break;
        default: return false;
    }
    
    // Resize
    imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save resized image
    switch ($type) {
        case IMAGETYPE_JPEG: imagejpeg($new_image, $targetPath, 90); break;
        case IMAGETYPE_PNG: imagepng($new_image, $targetPath, 9); break;
        case IMAGETYPE_GIF: imagegif($new_image, $targetPath); break;
    }
    
    imagedestroy($new_image);
    imagedestroy($source);
    
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_dir = __DIR__ . "/images/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if($check !== false) {
            echo "File is an image - " . $check["mime"] . ".<br>";
            $uploadOk = 1;
        } else {
            echo "File is not an image.<br>";
            $uploadOk = 0;
        }
    }

    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 5000000) {
        echo "Sorry, your file is too large.<br>";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" ) {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.<br>";
        $uploadOk = 0;
    }

    // If everything is ok, try to upload file
    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.<br>";
            
            // Resize the image
            $resized_file = $target_dir . "resized_" . basename($_FILES["fileToUpload"]["name"]);
            if (resizeImage($target_file, $resized_file, 800, 600)) {
                echo "Image has been resized.<br>";
                // Optionally, you can delete the original file
                // unlink($target_file);
            } else {
                echo "Failed to resize image.<br>";
            }
        } else {
            echo "Sorry, there was an error uploading your file. Error: " . error_get_last()['message'] . "<br>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<body>
<form action="upload_image.php" method="post" enctype="multipart/form-data">
    Select image to upload:
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="Upload Image" name="submit">
</form>
</body>
</html>
