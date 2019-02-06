<?php

require_once './config.php';
require_once './connect_db.php';
include_once './includes/helpers/data-process.php';
include_once './mailto.php';
include_once './jwt/JWT.php';

header('Access-Control-Allow-Origin: ' . ALLOW_FRONT_URL);
header("Access-Control-Allow-Headers: content-type");

//Ключ шифрования токенов
define('secret_key', '@p5U-xMtZbt=\vf6]xJy$q/(vJX4\y');
//Пути сохранения фотографии пользователя
define('photo_url', $_SERVER['DOCUMENT_ROOT'] . 'dereban/src/assets/users_images/');
define('photo_url_ang', '../assets/users_images/');

//Пути сохранения фотографий товара пользователя
define('ads_images_url', $_SERVER['DOCUMENT_ROOT'] . 'dereban/src/assets/users_images/showcase_photos');
define('ads_images_url_ang', '../assets/users_images/showcase_photos/');

use \Firebase\JWT\JWT;

//Роутинг по функциям
if (isset($_GET['func'])) {
    switch ($_GET['func']) {
        case 'auth':
            Auth();
            break;
        case 'conf_register':
            Conf_register();
            break;
        case 'mailto':
            Mailto($to_who);
            break;
        case 'get_user_name':
            GetUserName();
            break;
        case 'get_title_for_user_ads_component':
            GetTitleForUserAdsComp();
            break;
        case 'get_user_id':
            GetUserId();
            break;
        case 'get_user_rating':
            GetUserRating();
            break;
        case 'add_user_info':
            AddUserInfo();
            break;
        case 'get_user_info':
            GetUserInfo();
            break;
        case 'get_user_photo':
            GetUserPhoto();
            break;
        case 'set_user_photo':
            SetUserPhoto();
            break;
        case 'remove_auth_token':
            RemoveAuthToken();
            break;
        case 'save_showcase_photo':
            SaveShowcasePhoto();
            break;
        case 'save_showcase':
            SaveShowcase();
            break;
        case 'get_show_cases':
            GetShowCases();
            break;
        case 'showcase_change_rating':
            ShowCaseChangeRating();
            break;
        case 'showcase_get_rating':
            ShowCaseGetRating();
            break;
        case 'showcase_toggle_favorite':
            ShowCaseToggleFavorite();
            break;
        case 'get_active_favorite':
            GetActiveFavorite();
            break;
    }
}

//Ответ фронтенду
function result_text($code, $text, $isAuth = NULL) {
    echo json_encode([
        'code' => $code,
        'text' => $text,
        'isAuth' => $isAuth
    ]);
    return false;
}

function result_user_info($name, $surname, $phone, $phone2, $area, $telegram, $vk, $facebook, $instagram) {
    echo json_encode([
        'name' => $name,
        'surname' => $surname,
        'phone' => $phone,
        'phone2' => $phone2,
        'area' => $area,
        'telegram' => $telegram,
        'vk' => $vk,
        'facebook' => $facebook,
        'instagram' => $instagram
    ]);
    return false;
}

function Auth() {

    global $db;

    //Проверка на установление переменной значением и на ее пустоту (login, password)
    if (isset($_GET['login']) && $_GET['login'] != '' && isset($_GET['password']) && $_GET['password'] != '') {

        //Превращаем объект в строку ... (не очень)
        $user_login = $_GET['login'];
        $user_password = $_GET['password'];

        //Запрос на поле пароля имейл которого совпадает с введенным пользователем
        $sql_check_data = 'select password from `user` where email=' . QPrepStr($user_login);
        $query = $db->Execute($sql_check_data);

        //Проверка на существование запроса и на не нулевое значение количества записей
        if ($query && ($query->RecordCount() > 0)) {

            $sql_check_token = 'select '
                    . 'ut.regToken '
                    . 'from `user_tokens` ut '
                    . 'inner join `user` u on ut.user_id=u.id '
                    . 'where u.email=' . QPrepStr($user_login);
            $query_check_token = $db->Execute($sql_check_token);
            //вход в существующий аккаунт
            if ($user_password != $query->Fields('password')) {
                //Ошибка ввода пароля
                result_text(3, 'Проверьте правильность написания e-mail-а и пароля');
            } else {
                if ($query_check_token && ($query_check_token->RecordCount() > 0)) {
                    if ($query_check_token->Fields('regToken') != null) {
                        result_text(1, 'Аккаунт не подтвержден при регистрации');
                    } else {
                        //Создание токена авторизированного пользователя
                        $authToken = array(
                            'user' => $user_login,
                            'date' => gmmktime()
                        );

                        $jwt = JWT::encode($authToken, secret_key);

                        //Выбираем id пользователя из таблицы user
                        $sql_get_user_id = $db->Execute("select id from `user` where email=" . QPrepStr($user_login));

                        if ($sql_get_user_id) {
                            //Занесение токена в базу данных
                            $sql_add_auth_token = 'update `user_tokens` set authToken=' . QPrepStr($jwt) . ' where user_id=' . $sql_get_user_id->Fields('id');
                            $query_add_auth_token = $db->Execute($sql_add_auth_token);

                            if ($query_add_auth_token) {

                                $sql_check_date_login = $db->Execute('select last_login_date from `user` where email=' . QPrepStr($user_login));

                                if ($sql_check_date_login && ($sql_check_date_login->Fields('last_login_date') != NULL)) {
                                    //Успешная авторизация
                                    result_text(0, $jwt);
                                } else {
                                    //Успешная авторизация в первый раз
                                    result_text(0, $jwt, false);
                                }

                                $sql_add_date_login = $db->Execute('update `user` set last_login_date=' . intval(gmmktime()) . ' where email=' . QPrepStr($user_login));
                            } else {
                                result_text(1, 'Ошибка сервера');
                            }
                        } else {
                            result_text(1, 'Ошибка сервера');
                        }
                    }
                } else {
                    result_text(1, 'Ошибка сервера');
                }
            }
        } else {

            $regToken = array(
                'user' => $user_login,
                'date' => gmmktime()
            );
            $jwt = JWT::encode($regToken, secret_key);

            $url = 'http://localhost:4200/confirm-registration/' . $jwt;

            //Заносим данные в базу
            $sql_add_user_data = $db->Execute("insert into `user`(email, password) values (" . QPrepStr($user_login) . "," . QPrepStr($user_password) . ")");

            if ($sql_add_user_data) {
                //Выбираем id пользователя из таблицы user
                $sql_get_user_id = $db->Execute("select id from `user` where email=" . QPrepStr($user_login));

                if ($sql_get_user_id) {
                    //Добавляем user_id из таблицы user поля id
                    $sql_set_user_id = $db->Execute("insert into `user_tokens` (user_id, regToken) values(" . $sql_get_user_id->Fields('id') . ", " . QPrepStr($jwt) . ")");
                    //Добавляем информацию о пользователе в таблицу user_contacts
                    $login = explode('@', $user_login)[0];
                    $sql_add_user_contacts_data = $db->Execute("insert into `user_contacts`(user_id,name) values (" . $sql_get_user_id->Fields('id') . "," . QPrepStr($login) . ")");
                    if ($sql_set_user_id) {
                        //Отправляем e-mail
                        Mailto(PrepStr($user_login), $url);
                        //Отправляем результат на фронтенд
                        result_text(2, 'На ваш e-mail (' . $user_login . ') отправлено письмо с подтверждением регистрации');
                    } else {
                        result_text(1, 'Ошибка сервера');
                    }
                } else {
                    result_text(1, 'Ошибка сервера');
                }
            } else {
                result_text(1, 'Ошибка сервера');
            }
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function Conf_register() {

    global $db;

    //Проверка на установление переменной значением и на ее пустоту (password)

    if (isset($_GET['password']) && $_GET['password'] != '' && $_GET['regToken'] != '') {

        $user_password = $_GET['password'];
        $regToken = $_GET['regToken'];

        $sql_check_pass = 'select '
                . 'u.password '
                . 'from `user` u '
                . 'inner join `user_tokens` ut on u.id=ut.user_id '
                . 'where ut.regToken=' . QPrepStr($regToken);
        $query = $db->Execute($sql_check_pass);

        if ($query && ($query->RecordCount() > 0)) {
            if ($user_password != $query->Fields('password')) {
                //Ошибка ввода пароля
                result_text(3, 'Проверьте правильность написания пароля');
            } else {
                //Успешная регистрация
                $sql_delete_regToken = 'update `user_tokens` set regToken=null where regToken=' . QPrepStr($regToken);
                $query = $db->Execute($sql_delete_regToken);
                result_text(0, "Добро пожаловать на сайт 'Dereban.ua', подтверждение регистрации прошло успешно!"
                        . " "
                        . "Для продолжения пожалуйста авторизуйтесь");
            }
        } else {
            result_text(1, 'Аккаунт уже был ранее подтвержден');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function SqlGetUserId($authToken) {
    global $db;

    $sql_get_user_id = $db->Execute('select user_id from `user_tokens` where authToken=' . QPrepStr($authToken));
    if ($sql_get_user_id && ($sql_get_user_id->Fields('user_id') != '')) {
        return $sql_get_user_id->Fields('user_id');
    } else {
        return false;
    }
}

function GetUserId() {
    if (isset($_GET['authToken']) && $_GET['authToken'] != '') {

        $authToken = $_GET['authToken'];

        if (SqlGetUserId($authToken)) {
            result_text(0, SqlGetUserId($authToken));
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function GetUserName() {
    global $db;

    if (isset($_GET['authToken']) && $_GET['authToken'] != '') {

        $authToken = $_GET['authToken'];

        $sql_get_user_name = $db->Execute('select '
                . 'uc.name '
                . 'from `user_contacts` uc '
                . 'inner join `user_tokens` ut on uc.user_id=ut.user_id '
                . 'where ut.authToken=' . QPrepStr($authToken));

        if ($sql_get_user_name && ($sql_get_user_name->RecordCount() > 0)) {
            result_text(0, $sql_get_user_name->Fields('name'));
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function GetTitleForUserAdsComp() {
    global $db;

    if (isset($_GET['id']) && $_GET['id'] != '') {

        $id = $_GET['id'];

        if (isset($_GET['authToken']) && $_GET['authToken'] != '') {
            $authToken = $_GET['authToken'];

            if (SqlGetUserId($authToken) == $id) {
                result_text(0, 'Мои объявления');
            } else {
                $sql_get_user_name_surname = $db->Execute('select name, surname from `user_contacts` where user_id=' . $id);

                if ($sql_get_user_name_surname && ($sql_get_user_name_surname->RecordCount() > 0)) {
                    result_text(0, 'Объявления пользователя - ' . $sql_get_user_name_surname->Fields('name') . ' ' . $sql_get_user_name_surname->Fields('surname'));
                } else {
                    result_text(1, 'Ошибка сервера');
                }
            }
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function GetUserRating() {
    global $db;

    if ((isset($_GET['case_id']) && $_GET['case_id'] != '') || (isset($_GET['authToken']) && $_GET['authToken'] != '')) {
        if (isset($_GET['case_id']) && $_GET['case_id'] != '') {
            $case_id = $_GET['case_id'];
            $sql_get_user_id = $db->Execute('select user_id from `user_showcase` where id=' . intval($case_id));
        }
        if (isset($_GET['authToken']) && $_GET['authToken'] != '') {
            $authToken = $_GET['authToken'];
            $sql_get_user_id = $db->Execute('select user_id from `user_tokens` where authToken=' . QPrepStr($authToken));
        }
        if ($sql_get_user_id) {

            $sql_get_user_ads_id = $db->Execute('select id from `user_showcase` where user_id=' . $sql_get_user_id->Fields('user_id'));
            if ($sql_get_user_ads_id) {
                $rating = [];

                while (!$sql_get_user_ads_id->EOF) {
                    $rating[] = $sql_get_user_ads_id->Fields('id');
                    $sql_get_user_ads_id->MoveNext();
                }
                if ($sql_get_user_ads_id && $rating) {
                    $sql_get_users_rating = $db->Execute('select sum(rating_value) as `sum` from `case_rating` where case_id in (' . implode(',', $rating) . ')');
                    if ($sql_get_users_rating) {
                        result_text(0, $sql_get_users_rating->Fields('sum'));
                    } else {
                        result_text(1, 'Ошибка сервера');
                    }
                } else {
                    result_text(0, 0);
                }
            } else {
                result_text(1, 'Ошибка сервера');
            }
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function AddUserInfo() {
    global $db;

    if (isset($_GET['authToken']) && $_GET['authToken'] != '') {
        if (isset($_GET['user']) && ($user = json_decode($_GET['user'], true)) && isset($_GET['contacts']) && ($contacts = json_decode($_GET['contacts'], true)) && isset($_GET['social']) && ($social = json_decode($_GET['social'], true))) {

            $authToken = $_GET['authToken'];

            if ($contacts['phone2'] === '') {
                $phone2 = 'NULL';
            } else {
                $phone2 = intval($contacts['phone2']);
            }

            $upd = [];
            $upd[] = ' name=' . QPrepStr($user['name']);
            $upd[] = ' surname=' . QPrepStr($user['surname']);
            $upd[] = ' phone=' . intval($contacts['phone']);
            $upd[] = ' phone2=' . $phone2;
            $upd[] = ' area=' . QPrepStr($contacts['area']);
            $upd[] = ' telegram=' . QPrepStr($social['telegram']);
            $upd[] = ' vk=' . QPrepStr($social['vk']);
            $upd[] = ' facebook=' . QPrepStr($social['facebook']);
            $upd[] = ' instagram=' . QPrepStr($social['instagram']);

            $sql_add_user_info = $db->Execute('update `user_contacts` set '
                    . implode(',', $upd)
                    . 'where user_id=' . SqlGetUserId($authToken));

            if ($sql_add_user_info) {
                result_text(0, 'Изменения сохранены');
            } else {
                result_text(1, 'Ошибка сервера');
            }
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function GetUserInfo() {
    global $db;

    if (isset($_GET['authToken']) && $_GET['authToken'] != '') {

        $authToken = $_GET['authToken'];
        $sql_select_user_info = $db->Execute('select name, surname, phone, phone2, area, telegram, vk, facebook, instagram from `user_contacts` where user_id=' . SqlGetUserId($authToken));

        if ($sql_select_user_info && ($sql_select_user_info->RecordCount() > 0)) {
            result_user_info($sql_select_user_info->Fields('name'), $sql_select_user_info->Fields('surname'), $sql_select_user_info->Fields('phone'), $sql_select_user_info->Fields('phone2'), $sql_select_user_info->Fields('area'), $sql_select_user_info->Fields('telegram'), $sql_select_user_info->Fields('vk'), $sql_select_user_info->Fields('facebook'), $sql_select_user_info->Fields('instagram'));
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function GetUserPhoto() {
    global $db;

    if (isset($_GET['authToken']) && $_GET['authToken'] != '') {

        $authToken = $_GET['authToken'];

        if (SqlGetUserId($authToken)) {
            $photo_url = photo_url . 'user_id-' . SqlGetUserId($authToken) . '.jpg';
            $default_photo = '../assets/users_images/user_profile_image_default.jpg';
            if (file_exists($photo_url)) {
                $photo_url = photo_url_ang . 'user_id-' . SqlGetUserId($authToken) . '.jpg';
                result_text(0, $photo_url);
            } else {
                result_text(0, $default_photo);
            }
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function SetUserPhoto() {
    global $db;

    if (isset($_FILES['photo']) && $_FILES['photo'] != '') {

        if (isset($_GET['authToken'])) {
            $authToken = $_GET['authToken'];

            if ($SqlGetUserId($authToken)) {
                include_once './user_photo.php';
            } else {
                result_text(1, 'Ошибка сервера');
            }
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function RemoveAuthToken() {
    global $db;

    if (isset($_GET['authToken']) && $_GET['authToken'] != '') {
        $authToken = $_GET['authToken'];

        $sql_remove_token = $db->Execute('update `user_tokens` set authToken="" where authToken=' . QPrepStr($authToken));

        if ($sql_remove_token) {
            result_text(0, 'Токен удален успешно');
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function SaveShowcasePhoto() {
    global $db;

    if (isset($_FILES['photo']) && $_FILES['photo'] != '') {
        if (isset($_GET['authToken'])) {
            $authToken = $_GET['authToken'];

            if (SqlGetUserId($authToken)) {
                include_once './showcase_photo.php';
            } else {
                result_text(1, 'Ошибка сервера');
            }
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function SaveShowcase() {
    global $db;

    if (isset($_GET['authToken']) && $_GET['authToken'] != '') {
        if (isset($_GET['main']) && ($main = json_decode($_GET['main'], true)) && isset($_GET['options']) && ($options = json_decode($_GET['options'], true)) && isset($_GET['description']) && ($description = json_decode($_GET['description'], true)) && isset($_GET['additionalPhotos']) && ($additionalPhotos = json_decode($_GET['additionalPhotos'], true))) {

            $authToken = $_GET['authToken'];
            $filePath = $_GET['file_path'];

            $upd = [];
            $upd[] = ' ' . QPrepStr($main['name']);
            $upd[] = ' ' . intval($main['price']);
            $upd[] = ' ' . intval($options['type']);
            $upd[] = ' ' . intval($options['fullType']);
            $upd[] = ' ' . intval($options['detailType']);
            $upd[] = ' ' . intval($options['state']);
            $upd[] = ' ' . intval($options['wheelSize']);
            $upd[] = ' ' . intval($options['veloType']);
            $upd[] = ' ' . intval($options['direction']);
            $upd[] = ' ' . QPrepStr($description['description']);
            $upd[] = ' ' . QPrepStr($additionalPhotos['addPhotosLink']);

            if (SqlGetUserId($authToken)) {
                $sql_add_showcase = $db->Execute('insert into `user_showcase` (user_id, adding_time, photo_url, case_name, price, type, full_type, detail_type, state, wheel_size, velo_type, direction, description, additionalPhotos) values ('
                        . SqlGetUserId($authToken) . ','
                        . gmmktime() . ','
                        . QPrepStr($filePath) . ','
                        . implode(',', $upd)
                        . ')');

                $sql_select_showcase_id = $db->Execute('select id from `user_showcase` where adding_time=' . gmmktime());

                if ($sql_add_showcase) {
                    result_text(0, 'Объявление добавлено');
                } else {
                    result_text(1, 'Ошибка сервера');
                }
            } else {
                result_text(1, 'Ошибка сервера');
            }
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function GetShowCases() {
    global $db;

    $case_id;
    $condition;

    if (isset($_GET['id']) && $_GET['id'] != '') {
        $condition = 'where us.id=' . intval($_GET['id']);
    } else if (isset($_GET['user_id']) && $_GET['user_id'] != '') {
        $condition = 'where us.user_id=' . intval($_GET['user_id']) . ' order by adding_time desc';
    } else if (isset($_GET['authToken']) && $_GET['authToken'] != '') {
        $authToken = $_GET['authToken'];
        $sql_get_favorite_case_id = $db->Execute('select case_id from `case_favorite` where user_id=' . SqlGetUserId($authToken));
        $case_ids = [];
        if ($sql_get_favorite_case_id->Fields('case_id')) {
            while (!$sql_get_favorite_case_id->EOF) {
                $case_ids[] = $sql_get_favorite_case_id->Fields('case_id');
                $sql_get_favorite_case_id->MoveNext();
            }
            $condition = 'where us.id in (' . implode(',', $case_ids) . ') order by adding_time desc';
        } else {
            result_text(1, 'Не найдено избранных объявлений');
            return;
        }
    } else {
        $condition = 'order by adding_time desc';
    }

    $sql_get_show_cases = $db->Execute('select '
            . 'us.id, '
            . 'us.user_id, '
            . 'us.adding_time, '
            . 'us.case_name, '
            . 'us.photo_url, '
            . 'us.price, '
            . 'us.type, '
            . 'us.full_type, '
            . 'us.detail_type, '
            . 'us.state, '
            . 'us.wheel_size, '
            . 'us.velo_type, '
            . 'us.direction, '
            . 'us.description, '
            . 'us.additionalPhotos, '
            . 'uc.name, '
            . 'uc.surname, '
            . 'uc.area, '
            . 'uc.telegram, '
            . 'uc.phone, '
            . 'uc.phone2, '
            . 'uc.vk, '
            . 'uc.facebook, '
            . 'uc.instagram '
            . 'from `user_showcase` us '
            . 'inner join `user_contacts` uc '
            . 'on us.user_id=uc.user_id '
            . $condition);

    if ($sql_get_show_cases && ($sql_get_show_cases->RecordCount() > 0)) {
        $show_case_result = [];
        if ($sql_get_show_cases->Fields('case_name') != null) {
            while (!$sql_get_show_cases->EOF) {

                $sql_get_user_showcase_id = $db->Execute('select id from `user_showcase` where user_id=' . $sql_get_show_cases->Fields('user_id'));
                $showcase_ids = [];
                while (!$sql_get_user_showcase_id->EOF) {
                    $showcase_ids[] = $sql_get_user_showcase_id->Fields('id');
                    $sql_get_user_showcase_id->MoveNext();
                }
                $sql_get_user_rating = $db->Execute('select sum(rating_value) as `sum` from `case_rating` where case_id in (' . implode(',', $showcase_ids) . ')');
                $user_rating = $sql_get_user_rating->Fields('sum');

                if (isset($_GET['id']) && $_GET['id'] != '') {
                    $case_id = intval($_GET['id']);
                } else {
                    $case_id = $sql_get_show_cases->Fields('id');
                }

                $sql_get_show_case_rating = $db->Execute('select sum(rating_value) as `sum` from `case_rating` where case_id=' . $case_id);

                $show_case_result[] = [
                    'id' => $sql_get_show_cases->Fields('id'),
                    'user_id' => $sql_get_show_cases->Fields('user_id'),
                    'adding_time' => FormatDate($sql_get_show_cases->Fields('adding_time')),
                    'case_name' => $sql_get_show_cases->Fields('case_name'),
                    'photo_url' => ads_images_url_ang . $sql_get_show_cases->Fields('photo_url'),
                    'price' => $sql_get_show_cases->Fields('price'),
                    'type' => $sql_get_show_cases->Fields('type'),
                    'full_type' => $sql_get_show_cases->Fields('full_type'),
                    'detail_type' => $sql_get_show_cases->Fields('detail_type'),
                    'state' => $sql_get_show_cases->Fields('state'),
                    'wheel_size' => $sql_get_show_cases->Fields('wheel_size'),
                    'velo_type' => $sql_get_show_cases->Fields('velo_type'),
                    'direction' => $sql_get_show_cases->Fields('direction'),
                    'description' => $sql_get_show_cases->Fields('description'),
                    'additionalPhotos' => $sql_get_show_cases->Fields('additionalPhotos'),
                    'case_rating' => $sql_get_show_case_rating->Fields('sum'),
                    'user_rating' => $user_rating,
                    'user_name' => $sql_get_show_cases->Fields('name'),
                    'user_surname' => $sql_get_show_cases->Fields('surname'),
                    'user_area' => $sql_get_show_cases->Fields('area'),
                    'user_telegram' => $sql_get_show_cases->Fields('telegram'),
                    'user_phone' => $sql_get_show_cases->Fields('phone'),
                    'user_phone2' => $sql_get_show_cases->Fields('phone2'),
                    'user_vk' => $sql_get_show_cases->Fields('vk'),
                    'user_facebook' => $sql_get_show_cases->Fields('facebook'),
                    'user_instagram' => $sql_get_show_cases->Fields('instagram')
                ];
                $sql_get_show_cases->MoveNext();
            }
        }

        result_text(0, $show_case_result);
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function FormatDate($date) {
    if ($date >= strtotime("today"))
        return "Сегодня в " . strftime("%R", $date + 7200);
    else if ($date >= strtotime("yesterday"))
        return "Вчера в " . strftime("%R", $date + 7200);
    else 
        return strftime("%d.%m", $date);
}

function ShowCaseChangeRating() {
    global $db;

    if (isset($_GET['authToken']) && $_GET['authToken'] != '') {

        $case_id = intval($_GET['case_id']);
        $type = intval($_GET['type']);
        $authToken = QPrepStr($_GET['authToken']);

        // id пользователя изменившего рейтинг
        $cur_user_id = SqlGetUserId($authToken);
        // id пользователя этого объявления
        $sql_get_user_id_case = $db->Execute('select user_id from `user_showcase` where id=' . $case_id);
        // id пользователей изменявших когда-либо рейтинг этого объявления
        $sql_get_user_state = $db->Execute('select user_id from `case_rating` where case_id=' . $case_id);

        if ($cur_user_id != $sql_get_user_id_case->Fields('user_id')) {
            if (!$sql_get_user_state->Fields('user_id') || $sql_get_user_state->Fields('user_id') != $cur_user_id) {
                $sql_add_new_rating = $db->Execute('insert into `case_rating` '
                        . '(case_id, user_id, rating_value)'
                        . ' values '
                        . '(' . $case_id . ', ' . $cur_user_id . ', ' . $type . ')');
                if ($sql_add_new_rating) {
                    result_text(2, $type);
                } else {
                    result_text(1, 'Ошибка сервера');
                }
            } else {
                $sql_get_user_type = $db->Execute('select rating_value from `case_rating` where case_id=' . $case_id . ' and user_id=' . $cur_user_id);

                if ($sql_get_user_type->Fields('rating_value') != $type) {
                    $sql_set_user_type = $db->Execute('delete from `case_rating` where case_id=' . $case_id . ' and user_id=' . $cur_user_id . ' and rating_value!=' . $type);
                    if ($sql_set_user_type) {
                        result_text(2, $type);
                    } else {
                        result_text(1, 'Ошибка сервера');
                    }
                } else {
                    result_text(1, 'Повторное голосование');
                }
            }
        } else {
            result_text(0, 'Нельзя голосовать за свои объявления');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function ShowCaseGetRating() {
    global $db;

    if (isset($_GET['case_id']) && $_GET['case_id'] != '') {
        $case_id = intval($_GET['case_id']);
        $sql_get_case_rating = $db->Execute('select sum(rating_value) as `sum` from `case_rating` where case_id=' . $case_id);
        if ($sql_get_case_rating) {
            result_text(0, $sql_get_case_rating->Fields('sum'));
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function ShowCaseToggleFavorite() {
    global $db;
    if (isset($_GET['case_id']) && $_GET['case_id'] != '' && isset($_GET['authToken']) && $_GET['authToken'] != '') {
        $case_id = intval($_GET['case_id']);
        $authToken = QPrepStr($_GET['authToken']);

        $sql_get_case_favorite_status = $db->Execute('select case_id from `case_favorite` where case_id=' . $case_id . ' and user_id=' . SqlGetUserId($authToken));

        if ($sql_get_case_favorite_status->Fields('case_id') != null) {
            $sql_delete_favorite_from_case = $db->Execute('delete from `case_favorite` where case_id=' . $case_id . ' and user_id=' . SqlGetUserId($authToken));
            if ($sql_delete_favorite_from_case) {
                result_text(0, 'Объявление удалено из избранных');
            } else {
                result_text(1, 'Ошибка сервера');
            }
        } else {
            $sql_set_favorite_to_case = $db->Execute('insert into `case_favorite` (case_id, user_id) values (' . $case_id . ',' . SqlGetUserId($authToken) . ')');
            if ($sql_set_favorite_to_case) {
                result_text(0, 'Объявление добавлено в избранные');
            } else {
                result_text(1, 'Ошибка сервера');
            }
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function GetActiveFavorite() {
    global $db;
    if (isset($_GET['authToken']) && $_GET['authToken'] != '') {
        $authToken = QPrepStr($_GET['authToken']);

        $sql_select_favorite_case_id = $db->Execute('select case_id from `case_favorite` where user_id=' . SqlGetUserId($authToken));
        if ($sql_select_favorite_case_id && $sql_select_favorite_case_id->Fields('case_id') != null) {
            $case_id_arr = [];
            while (!$sql_select_favorite_case_id->EOF) {
                $case_id_arr[] = $sql_select_favorite_case_id->Fields('case_id');
                $sql_select_favorite_case_id->MoveNext();
            }
            result_text(0, $case_id_arr);
        } else {
            result_text(1, 'Не выбранно ни одного избранного объявления');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

?>