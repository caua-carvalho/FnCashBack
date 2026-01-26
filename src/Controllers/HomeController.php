<?php

require_once APP_ROOT . "/../jwt_utils.php";

class HomeController
{
    public function index(): void
    {
        echo json_encode($GLOBALS['auth_user'] = null);
    }
}
