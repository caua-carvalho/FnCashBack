<?php

include_once APP_ROOT . '/Models/UserModel.php';

class UserService
{
    function login($email, $password)
    {
        $userModel = new UserModel();
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        return $user;
    }
}