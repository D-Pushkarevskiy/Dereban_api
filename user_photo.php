<?php

$destination = PHOTO_PATH;
$photo_name = 'user_id-' . SqlGetUserId($authToken) . '.jpg';

$photo = $_FILES['photo']['tmp_name'];

if (is_uploaded_file($photo)) {

    $destination .= $photo_name;

    // Перемещаем файл из временной папки в указанную
    if (move_uploaded_file($photo, $destination)) {
        // Записываем имя фото в базу
        $sql_add_user_data = $db->Execute("update `user_contacts` set photo=" . QPrepStr($photo_name) . " where `user_id`=" . SqlGetUserId($authToken));
        if ($sql_add_user_data) {
            result_text(0, 'Фотография сохранена успешно');
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
} else {
    result_text(1, 'Ошибка сервера');
}
?>