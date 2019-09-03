<?php

require_once './config.php';
require_once './connect_db.php';
include_once './includes/helpers/data-process.php';
include_once './mailto.php';
include_once './jwt/JWT.php';

header('Access-Control-Allow-Origin: ' . ALLOW_FRONT_URL);
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

//Ключ шифрования токенов
define('SECRET_KEY', '@p5U-xMtZbt=\vf6]xJy$q/(vJX4\y');
//Пути сохранения фотографии пользователя
define('PHOTO_PATH', 'C:/OSpanel/OSPanel/domains/dereban/src/assets/users_images/');
define('PHOTO_PATH_ANG', '../assets/users_images/');

//Пути сохранения фотографий товара пользователя
define('ADS_IMAGES_PATH', 'C:/OSpanel/OSPanel/domains/dereban/src/assets/users_images/showcase_photos/');
define('ADS_IMAGES_PATH_ANG', '../assets/users_images/showcase_photos/');

use \Firebase\JWT\JWT;

/** 
 * Get header Authorization
 * */
function getAuthorizationHeader()
{
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}
/**
 * get access token from header
 * */
function getBearerToken()
{
    $headers = getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

//Роутинг по функциям
if (isset($_GET['func'])) {
    switch ($_GET['func']) {
        case 'auth':
            Auth();
            break;
        case 'refresh_password_request':
            RefreshPasswordRequest();
            break;
        case 'refresh_password':
            RefreshPassword();
            break;
        case 'conf_register':
            Conf_register();
            break;
        case 'mailto':
            Mailto($param1, $param2, $param3);
            break;
        case 'get_user_name':
            GetUserName();
            break;
        case 'get_title_for_user_ads_component':
            GetTitleForUserAdsComp();
            break;
        case 'get_user_data':
            GetUserData();
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
        case 'get_active_rating':
            GetActiveRating();
            break;
        case 'showcase_toggle_favorite':
            ShowCaseToggleFavorite();
            break;
        case 'get_active_favorite':
            GetActiveFavorite();
            break;
        case 'is_owner':
            IsOwner();
            break;
        case 'get_case':
            GetCase();
            break;
        case 'showcase_toggle_active':
            ShowCaseToggleActive();
            break;
        case 'delete_showcase':
            DeleteShowCase();
            break;
    }
}

//Ответ фронтенду
function result_text($code, $text, $isAuth = NULL)
{
    echo json_encode([
        'code' => $code,
        'text' => $text,
        'isAuth' => $isAuth
    ]);
    return false;
}

function result_user_info($photo, $name, $surname, $phone, $phone2, $area, $telegram, $vk, $facebook, $instagram)
{
    echo json_encode([
        'photo' => $photo,
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

function Auth()
{

    global $db;

    //Проверка на установление переменной значением и на ее пустоту (login, password)
    if (isset($_GET['login']) && $_GET['login'] != '' && isset($_GET['password']) && $_GET['password'] != '') {

        //Превращаем объект в строку ... (не очень)
        $user_login = $_GET['login'];
        $user_password = $_GET['password'];

        // Запрос на поле пароля имейл которого совпадает с введенным пользователем
        $sql_check_data = 'select password from `user` where email=' . QPrepStr($user_login);
        $query = $db->Execute($sql_check_data);

        // Авторизация, иначе регистрация
        if ($query && ($query->RecordCount() > 0)) {

            $sql_check_token = 'select '
                . 'ut.regToken '
                . 'from `user_tokens` ut '
                . 'inner join `user` u on ut.user_id=u.id '
                . 'where u.email=' . QPrepStr($user_login);
            $query_check_token = $db->Execute($sql_check_token);
            // Вход в существующий аккаунт
            if ($user_password != $query->Fields('password')) {
                // Ошибка ввода пароля
                // Проверка кол-ва неправильных вводов пароля
                $sql_get_login_attempt = $db->Execute("select login_attempts from `user` where email=" . QPrepStr($user_login));
                if ($sql_get_login_attempt && $sql_get_login_attempt->Fields('login_attempts') >= 5) {
                    result_text(4, 'Пароль не верный. Вы можете воспользоваться функцией "забыли пароль?"');
                } else {
                    $sql_add_login_attempt = $db->Execute('update `user` set login_attempts=login_attempts + 1 where email=' . QPrepStr($user_login));
                    result_text(3, 'Проверьте правильность написания e-mail-а и пароля');
                }
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

                        $jwt = JWT::encode($authToken, SECRET_KEY);

                        //Выбираем id пользователя из таблицы user
                        $sql_get_user_id = $db->Execute("select id from `user` where email=" . QPrepStr($user_login));

                        if ($sql_get_user_id) {
                            // Проверка на существование authToken-а
                            $sql_auth_token = $db->Execute("select authToken from `user_tokens` where id=" . $sql_get_user_id->Fields('id'));
                            if ($sql_auth_token && ($sql_auth_token->Fields('authToken') != NULL)) {
                                //Успешная авторизация
                                result_text(0, $sql_auth_token->Fields('authToken'));
                                $sql_add_login_attempt = $db->Execute('update `user` set login_attempts=0 where email=' . QPrepStr($user_login));
                            } else {
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
                                    $sql_add_login_attempt = $db->Execute('update `user` set login_attempts=0 where email=' . QPrepStr($user_login));
                                } else {
                                    result_text(1, 'Ошибка сервера');
                                }
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
            $jwt = JWT::encode($regToken, SECRET_KEY);

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
                    if ($sql_set_user_id && $sql_add_user_contacts_data) {
                        //Отправляем e-mail
                        $subject = "Подтверждение регистрации на сайте 'Dereban.ua'";
                        $content = "<div style='text-align: center; font-size: 18px;'>"
                            . "<b>"
                            . "Для подтверждения регистрации на сайте 'Dereban.ua' перейдите по ссылке: "
                            . "</b>"
                            . "<br>"
                            . "<a href=" . $url . " target='_blank' style='color: #3f51b5;'>Подтвердить регистрацию</a>"
                            . "</div>";
                        if (Mailto(PrepStr($user_login), $subject, $content)) {
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
            } else {
                result_text(1, 'Ошибка сервера');
            }
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function RefreshPasswordRequest()
{
    global $db;

    if (isset($_GET['email']) && $_GET['email'] != '') {
        $user_email = $_GET['email'];

        $sql_check_user_email = $db->Execute("select email from `user` where email=" . QPrepStr($user_email));

        if ($sql_check_user_email && ($sql_check_user_email->RecordCount() > 0)) {
            $url = 'http://localhost:4200/refresh-password/' . gmmktime();
            $sql_get_user_id = $db->Execute('select id from `user` where email=' . QPrepStr($user_email));
            if ($sql_get_user_id && ($sql_get_user_id->RecordCount() > 0)) {
                $sql_add_passToken = $db->Execute('update `user_tokens` set passToken=' . intval(gmmktime()) . ' where user_id=' . $sql_get_user_id->Fields('id'));
                if ($sql_add_passToken) {
                    $subject = "Сброс пароля на сайте 'Dereban.ua'";
                    $content = "Для сброса пароля, передите по ссылке: " . $url;
                    if (Mailto($user_email, $subject, $content)) {
                        result_text(2, 'На ваш e-mail (' . $user_email . ') отправлено письмо с информацией о сбросе пароля');
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
            result_text(1, 'Введенный e-mail не зарегистрирован на сайте');
        }
    }
}

function RefreshPassword()
{
    global $db;

    if (isset($_GET['current']) && $_GET['current'] != '' && isset($_GET['new']) && $_GET['new'] != '' && $_GET['token'] != '') {

        $current_password = $_GET['current'];
        $new_password = $_GET['new'];
        $token = $_GET['token'];

        $sql_check_pass = 'select '
            . 'u.password '
            . 'from `user` u '
            . 'inner join `user_tokens` ut on u.id=ut.user_id '
            . 'where ut.passToken=' . QPrepStr($token);
        $query = $db->Execute($sql_check_pass);

        $sql_get_user_id = $db->Execute('select user_id from `user_tokens` where passToken=' . QPrepStr($token));

        if ($query && ($query->RecordCount() > 0)) {
            if ($current_password != $query->Fields('password')) {
                //Ошибка ввода пароля
                result_text(3, 'Проверьте правильность написания пароля');
            } else {
                //Успешная смена пароля
                $sql_delete_passToken = 'update `user_tokens` set passToken=null where passToken=' . QPrepStr($token);
                $query = $db->Execute($sql_delete_passToken);
                if ($query) {
                    $sql_update_password = 'update `user` set password=' . QPrepStr($new_password) . ' where id=' . intval($sql_get_user_id->Fields('user_id'));
                    $query = $db->Execute($sql_update_password);
                    if ($query) {
                        result_text(0, "Сброс пароля прошел успешно!"
                            . " "
                            . "Для продолжения пожалуйста авторизуйтесь с новым паролем");
                    } else {
                        result_text(1, 'Ошибка сервера');
                    }
                } else {
                    result_text(1, 'Ошибка сервера');
                }
            }
        } else {
            result_text(2, 'Смена пароля была совершена ранее');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function Conf_register()
{

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

function SqlGetUserId()
{
    global $db;
    $authToken = getBearerToken();

    if ($authToken) {
        $sql_get_user_id = $db->Execute('select user_id from `user_tokens` where authToken=' . QPrepStr($authToken));
        if ($sql_get_user_id && ($sql_get_user_id->Fields('user_id') != '')) {
            return $sql_get_user_id->Fields('user_id');
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function GetUserData()
{
    global $db;
    $authToken = getBearerToken();

    if ($authToken) {
        $sql_get_user_data = $db->Execute('select '
            . 'uc.user_id, '
            . 'uc.name, '
            . 'uc.photo '
            . 'from `user_contacts` uc '
            . 'inner join `user_tokens` ut on uc.user_id=ut.user_id '
            . 'where ut.authToken=' . QPrepStr($authToken));

        if ($sql_get_user_data && ($sql_get_user_data->RecordCount() > 0)) {
            result_text(0, [
                'id' => $sql_get_user_data->Fields('user_id'),
                'name' => $sql_get_user_data->Fields('name'),
                'photo' => $sql_get_user_data->Fields('photo'),
                'rating' => $sql_get_user_data->Fields('rating')
            ]);
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function GetTitleForUserAdsComp()
{
    global $db;

    if (isset($_GET['id']) && $_GET['id'] != '') {

        $id = $_GET['id'];

        if (SqlGetUserId() === $id) {
            result_text(0, 'Мои объявления');
        } else {
            $sql_get_user_name_surname = $db->Execute('select name, surname from `user_contacts` where user_id=' . $id);

            if ($sql_get_user_name_surname && ($sql_get_user_name_surname->RecordCount() > 0)) {
                result_text(0, 'Объявления пользователя - ' . $sql_get_user_name_surname->Fields('name') . ' ' . $sql_get_user_name_surname->Fields('surname'));
            } else {
                result_text(1, 'Ошибка сервера');
            }
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function GetUserRating()
{
    global $db;
    $authToken = getBearerToken();

    if ((isset($_GET['case_id']) && $_GET['case_id'] != '') || $authToken) {
        if (isset($_GET['case_id']) && $_GET['case_id'] != '') {
            $case_id = $_GET['case_id'];
            $sql_get_user_id = $db->Execute('select user_id from `user_showcase` where id=' . intval($case_id));
        } else if ($authToken) {
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

function AddUserInfo()
{
    global $db;

    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);

    if (SqlGetUserId()) {
        if (isset($data['user']) && ($user = $data['user']) && isset($data['contacts']) && ($contacts = $data['contacts']) && isset($data['social']) && ($social = $data['social'])) {

            if (empty($contacts['phone2']) || isset($contacts['phone2']) || $contacts['phone2'] === '' || $contacts['phone2'] === null) {
                $phone2 = 'null';
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
                . 'where user_id=' . SqlGetUserId());

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

function GetUserInfo()
{
    global $db;

    if (SqlGetUserId()) {
        $sql_select_user_info = $db->Execute('select 
        photo,
        name, 
        surname, 
        phone, 
        phone2, 
        area, 
        telegram, 
        vk, 
        facebook, 
        instagram 
        from `user_contacts` where user_id=' . SqlGetUserId());

        if ($sql_select_user_info && ($sql_select_user_info->RecordCount() > 0)) {
            result_user_info(
                $sql_select_user_info->Fields('photo'),
                $sql_select_user_info->Fields('name'),
                $sql_select_user_info->Fields('surname'),
                $sql_select_user_info->Fields('phone'),
                $sql_select_user_info->Fields('phone2'),
                $sql_select_user_info->Fields('area'),
                $sql_select_user_info->Fields('telegram'),
                $sql_select_user_info->Fields('vk'),
                $sql_select_user_info->Fields('facebook'),
                $sql_select_user_info->Fields('instagram')
            );
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function SetUserPhoto()
{
    global $db;

    if (isset($_FILES['photo']) && $_FILES['photo'] != '') {

        if (SqlGetUserId()) {
            include_once './user_photo.php';
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function RemoveAuthToken()
{
    global $db;
    $authToken = getBearerToken();

    if ($authToken) {
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

function SaveShowcasePhoto()
{
    global $db;

    if (isset($_FILES['photo']) && $_FILES['photo'] != '') {
        if (SqlGetUserId()) {
            include_once './showcase_photo.php';
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function SaveShowcase()
{
    global $db;

    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);

    if (SqlGetUserId()) {
        if (isset($data['main']) && ($main = $data['main']) && isset($data['options']) && ($options = $data['options']) && isset($data['description']) && ($description = $data['description']) && isset($data['additionalPhotos']) && ($additionalPhotos = $data['additionalPhotos'])) {

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

            if (SqlGetUserId()) {
                if ($_GET['edit'] && $_GET['edit'] == true) {
                    $sql_update_showcase = $db->Execute('update `user_showcase` set '
                        . 'update_time=' . gmmktime() . ','
                        . 'photo_url=' . QPrepStr($filePath) . ','
                        . 'case_name=' . QPrepStr($main['name']) . ','
                        . 'price=' . intval($main['price']) . ','
                        . 'type=' . intval($options['type']) . ','
                        . 'full_type=' . intval($options['fullType']) . ','
                        . 'detail_type=' . intval($options['detailType']) . ','
                        . 'state=' . intval($options['state']) . ','
                        . 'wheel_size=' . intval($options['wheelSize']) . ','
                        . 'velo_type=' . intval($options['veloType']) . ','
                        . 'direction=' . intval($options['direction']) . ','
                        . 'description=' . QPrepStr($description['description']) . ','
                        . 'additionalPhotos=' . QPrepStr($additionalPhotos['addPhotosLink'])
                        . ' where id=' . $_GET['id']);
                    if ($sql_update_showcase) {
                        result_text(0, 'Изменения сохранены');
                    } else {
                        result_text(1, 'Ошибка сервера');
                    }
                } else {
                    $sql_add_showcase = $db->Execute('insert into `user_showcase` (user_id, adding_time, photo_url, case_name, price, type, full_type, detail_type, state, wheel_size, velo_type, direction, description, additionalPhotos) values ('
                        . SqlGetUserId() . ','
                        . gmmktime() . ','
                        . QPrepStr($filePath) . ','
                        . implode(',', $upd)
                        . ')');
                    if ($sql_add_showcase) {
                        result_text(0, 'Объявление добавлено');
                    } else {
                        result_text(1, 'Ошибка сервера');
                    }
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

function GetShowCases()
{
    global $db;

    $case_id = '';
    $condition = '';

    if (isset($_GET['id']) && $_GET['id'] != '') {
        $condition = 'where us.id=' . intval($_GET['id']);
    } else if (isset($_GET['user_id']) && $_GET['user_id'] != '') {
        $condition = 'where us.user_id=' . intval($_GET['user_id']) . ' order by adding_time desc';
    } else if (isset($_GET['authToken']) && $_GET['authToken'] != '') {
        $sql_get_favorite_case_id = $db->Execute('select case_id from `case_favorite` where user_id=' . SqlGetUserId());
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
        . 'us.active, '
        . 'uc.name, '
        . 'uc.photo, '
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
                    'photo_url' => ADS_IMAGES_PATH_ANG . $sql_get_show_cases->Fields('photo_url'),
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
                    'active' => $sql_get_show_cases->Fields('active'),
                    'case_rating' => $sql_get_show_case_rating->Fields('sum'),
                    'user_rating' => $user_rating,
                    'user_photo' => $sql_get_show_cases->Fields('photo'),
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

function FormatDate($date)
{
    if ($date >= strtotime("today")) {
        return "Сегодня в " . strftime("%H:%M", $date);
    } else if ($date >= strtotime("yesterday")) {
        return "Вчера в " . strftime("%H:%M", $date);
    } else {
        return strftime("%d.%m", $date);
    }
}

function ShowCaseChangeRating()
{
    global $db;

    if (SqlGetUserId()) {

        $case_id = intval($_GET['case_id']);
        $type = intval($_GET['type']);

        // id пользователя изменившего рейтинг
        $cur_user_id = SqlGetUserId();
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

function ShowCaseGetRating()
{
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

function GetActiveRating()
{
    global $db;

    if (SqlGetUserId()) {
        $sql_get_case_rating_status = $db->Execute('select case_id, rating_value from `case_rating` where user_id=' . SqlGetUserId());

        if ($sql_get_case_rating_status && $sql_get_case_rating_status->Fields('case_id') != null) {
            $case_id_arr = [];
            while (!$sql_get_case_rating_status->EOF) {
                $case_id_arr[] = [
                    'id' => $sql_get_case_rating_status->Fields('case_id'),
                    'value' => $sql_get_case_rating_status->Fields('rating_value')
                ];
                $sql_get_case_rating_status->MoveNext();
            }
            result_text(0, $case_id_arr);
        } else {
            result_text(1, 'Не выбранно ни одного активного объявления');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}

function ShowCaseToggleFavorite()
{
    global $db;

    if (isset($_GET['case_id']) && $_GET['case_id'] != '' && SqlGetUserId()) {
        $case_id = intval($_GET['case_id']);

        $sql_get_case_favorite_status = $db->Execute('select case_id from `case_favorite` where case_id=' . $case_id . ' and user_id=' . SqlGetUserId());

        if ($sql_get_case_favorite_status->Fields('case_id') != null) {
            $sql_delete_favorite_from_case = $db->Execute('delete from `case_favorite` where case_id=' . $case_id . ' and user_id=' . SqlGetUserId());
            if ($sql_delete_favorite_from_case) {
                result_text(0, 'Объявление удалено из избранных');
            } else {
                result_text(1, 'Ошибка сервера');
            }
        } else {
            $sql_set_favorite_to_case = $db->Execute('insert into `case_favorite` (case_id, user_id) values (' . $case_id . ',' . SqlGetUserId() . ')');
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

function GetActiveFavorite()
{
    global $db;

    if (SqlGetUserId()) {
        $sql_select_favorite_case_id = $db->Execute('select case_id from `case_favorite` where user_id=' . SqlGetUserId());
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

function IsOwner()
{
    global $db;
    if (isset($_GET['id']) && $_GET['id'] != '') {
        $case_id = $_GET['id'];
        $sql_is_owner = $db->Execute('select case_name from `user_showcase` where user_id=' . SqlGetUserId() . ' and id=' . intval($case_id));
        if ($sql_is_owner && $sql_is_owner->Fields('case_name') != '') {
            echo json_encode(true);
            return true;
        } else {
            echo json_encode(false);
            return false;
        }
    } else {
        echo json_encode(false);
        return false;
    }
}

function GetCase()
{
    global $db;

    if (isset($_GET['id']) && $_GET['id'] != '') {
        $sql_get_show_case = $db->Execute('select `id`, `user_id`, `adding_time`, `case_name`, `photo_url`, `price`, `type`, `full_type`, `detail_type`, `state`, `wheel_size`, `velo_type`, `direction`, `description`, `additionalPhotos` from `user_showcase` where id=' . intval($_GET['id']));

        if ($sql_get_show_case && ($sql_get_show_case->RecordCount() > 0)) {
            if ($sql_get_show_case->Fields('case_name') != null) {
                $show_case_result = [
                    'id' => $sql_get_show_case->Fields('id'),
                    'user_id' => $sql_get_show_case->Fields('user_id'),
                    'adding_time' => FormatDate($sql_get_show_case->Fields('adding_time')),
                    'case_name' => $sql_get_show_case->Fields('case_name'),
                    'photo_url' => ADS_IMAGES_PATH_ANG . $sql_get_show_case->Fields('photo_url'),
                    'price' => $sql_get_show_case->Fields('price'),
                    'type' => $sql_get_show_case->Fields('type'),
                    'full_type' => $sql_get_show_case->Fields('full_type'),
                    'detail_type' => $sql_get_show_case->Fields('detail_type'),
                    'state' => $sql_get_show_case->Fields('state'),
                    'wheel_size' => $sql_get_show_case->Fields('wheel_size'),
                    'velo_type' => $sql_get_show_case->Fields('velo_type'),
                    'direction' => $sql_get_show_case->Fields('direction'),
                    'description' => $sql_get_show_case->Fields('description'),
                    'additionalPhotos' => $sql_get_show_case->Fields('additionalPhotos'),
                ];
                result_text(0, $show_case_result);
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

function ShowCaseToggleActive()
{
    global $db;

    if (isset($_GET['case_id']) && $_GET['case_id'] != '' && SqlGetUserId()) {
        $case_id = intval($_GET['case_id']);

        $sql_get_case_active_status = $db->Execute('select active from `user_showcase` where id=' . $case_id . ' and user_id=' . SqlGetUserId());

        if ($sql_get_case_active_status->Fields('active') != null) {
            if ($sql_get_case_active_status->Fields('active') == 1) {
                $sql_toggle_case_active_status = $db->Execute('update `user_showcase` set active=0 where id=' . $case_id . ' and user_id=' . SqlGetUserId());
                if ($sql_toggle_case_active_status) {
                    result_text(0, 'Объявление деактивировано');
                } else {
                    result_text(2, 'Ошибка сервера');
                }
            } else if ($sql_get_case_active_status->Fields('active') == 0) {
                $sql_toggle_case_active_status = $db->Execute('update `user_showcase` set active=1 where id=' . $case_id . ' and user_id=' . SqlGetUserId());
                if ($sql_toggle_case_active_status) {
                    result_text(1, 'Объявление активировано');
                } else {
                    result_text(2, 'Ошибка сервера');
                }
            } else {
                result_text(2, 'Ошибка сервера');
            }
        } else {
            result_text(2, 'Ошибка сервера');
        }
    } else {
        result_text(2, 'Ошибка сервера');
    }
}

function DeleteShowCase()
{
    global $db;

    if (isset($_GET['case_id']) && $_GET['case_id'] != '' && SqlGetUserId()) {
        $case_id = $_GET['case_id'];
        $sql_delete_show_case = $db->Execute('delete from `user_showcase` where id=' . $case_id . ' and user_id=' . SqlGetUserId());
        if ($sql_delete_show_case) {
            result_text(0, 'Объявление удалено успешно');
        } else {
            result_text(1, 'Ошибка сервера');
        }
    } else {
        result_text(1, 'Ошибка сервера');
    }
}
