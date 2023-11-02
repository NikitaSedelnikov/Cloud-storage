<?php

function addRoute (array $route)
{
    foreach ($route as $method => $controller)
    {
        foreach ($controller as $class => $action)
        {
            foreach ($action as $function => $args)
            {
                if(file_exists( $_SERVER['DOCUMENT_ROOT'] . '/controllers/' . $class . '.php') && $_SERVER['REQUEST_METHOD'] === $method)
                {
                    $obj = new $class();
                    return call_user_func_array([$obj, $function], $args);
                } else {
                    http_response_code(404);
                }
            }
        }
    }
}
