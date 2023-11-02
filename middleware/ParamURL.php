<?php

/*
 * С помощью функции explode, URI разделяется по спец. символу и производится простая проверка данных
 * исходя из того, что URI класса Admin длиннее, проверка его URI вынесена в отдельный кейс
 * после передаются параметры в виде массива, где id всегда 1, user_id - 2 и далее можно будет добавлять свои дополнительные параметры
 */
function getArgs()
{
    $uriId = explode('/', $_SERVER['REQUEST_URI']);
    if (mb_strpos($_SERVER['REQUEST_URI'], '?'))
    {
        $resetPass = explode('?', $_SERVER['REQUEST_URI']);
    } else {
        $resetPass[1] = null;
    }

    switch (count($uriId))
    {
        case 4:
            $id = intval($uriId[3]);
            $user_id = null;
            break;

        case 5:
            if ($uriId[1]==='admin')
            {
                $id = intval($uriId[4]);
                $user_id = null;
            } else {
                $id = intval($uriId[3]);
                $user_id = intval($uriId[4]);
            }
            break;

        default:
            $id = null;
            $user_id = null;
    }
    $args = [$resetPass[1], $id, $user_id];
    return $args;
}