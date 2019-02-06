<?php

$destination = photo_url;

$photo = $_FILES['photo']['tmp_name'];

if (is_uploaded_file($photo)) {

    $destination .= 'user_id-' . $sql_get_user_id->Fields('user_id') . '.jpg';

    //Перемещаем файл из временной папки в указанную
    if (move_uploaded_file($photo, $destination)) {
        result_text(0, 'Фотография сохранена успешно');
    } else {
        result_text(1, 'Ошибка сервера');
    }
} else {
    result_text(1, 'Ошибка сервера');
}
?>