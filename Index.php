<?php

if (isset($_COOKIE['sessID'])) //Осуществляется проверка наличия куков с токеном сессии, если есть, сессии присвавается токен при логине
{
    session_id($_COOKIE['sessID']);
    session_start();
} else {
    session_start();
}

//Подгрузка класса-посредника, который обрабатывает URI запрос, вытягивая параметры методом Explode
function loadClass($class_name) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/controllers/' . $class_name . '.php'))
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/controllers/' . $class_name . '.php');
    }
}
include_once $_SERVER['DOCUMENT_ROOT'].'/middleware/'.'ParamURL.php';
include_once __DIR__.'/vendor/'.'autoload.php';
spl_autoload_register('loadClass');

$getParam = getArgs(); //Вызов класса-посредника
$id = $getParam[1];
$user_id = $getParam[2];
$resetPass[1] = $getParam[0];

/*
 * В массив URI добавлены несколько дополнительно:
 * /users/add - Регистрация аккаунта
 * /directories/set-file - переместить файл в папку
 */
$urlList = [
    "/users/list" => ['GET' => ['User'=>['list' => [] ]]],
    "/users/get/{$id}" => ['GET' => ['User'=>['get' => [$id] ]]],
    "/users/update" => ['PUT' => ['User'=>['update' => [] ]]],
    "/users/add" => ['POST' => ['User'=>['add' => [] ]]],
    "/users/login" => ['POST' => ['User'=>['login' => [] ]]],
    "/users/logout" => ['GET' => ['User'=>['logout' => [] ]]],
    "/users/reset_password?{$resetPass[1]}" => ['GET' => ['User'=>['reset_password' => [] ]]],
    "/admin/users/list" => ['GET' => ['Admin'=>['list' => [] ]]],
    "/admin/users/get/{$id}" => ['GET' => ['Admin'=>['get' => [$id] ]]],
    "/admin/users/delete/{$id}" => ['DELETE' => ['Admin'=>['delete' => [$id] ]]],
    "/admin/users/update/{$id}" => ['PUT' => ['Admin'=>['update' => [$id] ]]],
    "/files/list" => ['GET' => ['File'=>['list' => [] ]]],
    "/files/get/{$id}" => ['GET' => ['File'=>['get' => [$id] ]]],
    "/files/add" => ['POST' => ['File'=>['add' => [] ]]],
    "/files/rename/{$id}" => ['PUT' => ['File'=>['rename' => [$id] ]]],
    "/files/remove/{$id}" => ['DELETE' => ['File'=>['remove' => [$id] ]]],
    "/directories/add" => ['POST' => ['File'=>['dirAdd' => [] ]]],
    "/directories/rename/{$id}" => ['PUT' => ['File'=>['dirRename' => [$id] ]]],
    "/directories/get/{$id}" => ['GET' => ['File'=>['dirGet' => [$id] ]]],
    "/directories/set-file/{$id}/{$user_id}" => ['POST' => ['File'=>['dirSet' => [$id, $user_id] ]]],
    "/directories/delete/{$id}" => ['DELETE' => ['File'=>['dirDelete' => [$id] ]]],
    "/files/share/{$id}" => ['GET' => ['File'=>['fileShare' => [$id] ]]],
    "/share/set/{$id}/{$user_id}" => ['PUT' => ['File'=>['fileShareUser' => [$id, $user_id] ]]],
    "/files/share/{$id}/{$user_id}" => ['DELETE' => ['File'=>['fileShareDelete' => [$id, $user_id] ]]],
    ];

//Вызов роутера, в качестве аргументов поступает массив с методом, классов, функцией и массивом параметров
foreach ($urlList as $uri => $route)
{
    foreach ($route as $method => $controller)
    {
        if($_SERVER['REQUEST_URI'] === $uri && $_SERVER['REQUEST_METHOD'] === $method)
        {
            require_once($_SERVER['DOCUMENT_ROOT'] . '/routes/Router.php');
            print_r(addRoute($route));
        }
    }
}





