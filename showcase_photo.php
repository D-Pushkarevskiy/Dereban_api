<?php

$destination = ADS_IMAGES_PATH;
$date = gmmktime();
$file_path = SqlGetUserId() . '/';
$user_photo_path = 'date' . $date . '-user_id-' . SqlGetUserId() . '.jpg';

$photo = $_FILES['photo']['tmp_name'];

if (is_uploaded_file($photo)) {
    if (!file_exists($destination)) {
        mkdir($destination);
        $destination .= $file_path;
        if (!file_exists($destination)) {
            mkdir($destination);
        }
    }

    // Переміщаємо файл з тимчасової папки в вказану.
    if (move_uploaded_file($photo, $destination . $user_photo_path)) {
        result_text(0, $file_path . $user_photo_path);
    } else {
        result_text(1, 'NO_HAVE_PERMISSION_DIRECTORY = ' . $destination);
    }
} else {
    result_text(1, 'FAILED_TO_UPLOAD_FILE');
}
?>
