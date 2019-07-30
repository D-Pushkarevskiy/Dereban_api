<?php

$destination = ADS_IMAGES_PATH . '/';
$date = gmmktime();
$file_path = SqlGetUserId() . '/';
$user_photo_path = 'date' . $date . '-user_id-' . SqlGetUserId() . '.jpg';

$photo = $_FILES['photo']['tmp_name'];

if (is_uploaded_file($photo)) {
    if (!file_exists(ADS_IMAGES_PATH)) {
        mkdir(ADS_IMAGES_PATH);
    }
    if (!file_exists($destination)) {
        mkdir($destination);
    }
    $destination .= '/' . $file_path;
    if (!file_exists($destination)) {
        mkdir($destination);
    }

    //Перемещаем файл из временной папки в указанную
    if (move_uploaded_file($photo, $destination . $user_photo_path)) {
        result_text(0, $file_path . $user_photo_path);
    } else {
        result_text(1, 'Ошибка сервера');
    }
} else {
    result_text(1, 'Ошибка сервера');
}
?>