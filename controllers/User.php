<?php
class User
{
    private
        $host = "localhost",
        $db_name = "cloud_storage",
        $charset = 'utf8',
        $username = "root",
        $password = "";

    public $connection;

    public function __construct ()
    {
        header('Content-Type:application/json');
        $this->connection = null;

        try {
            $this->connection = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ';charset=' . $this->charset, $this->username, $this->password);
        }
        catch (PDOException $exception) {
            die (json_encode("Connection to DataBase falied"));
        }
    }

    public function list(): string
    {
        if (!isset($_COOKIE['sessID'])) //Проверка на наличие токена в куках
        {
            die(json_encode('Not autorised'));
        }

        try {
        $statement = $this->connection->query("SELECT `name`, `email`, `age`, `gender` FROM `user`");
        $statement->execute();
        $data=$statement->fetchAll();
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode('failed');
        }

        http_response_code(200);
        return json_encode($data);
    }

    public function get(int $id): string
    {
        if (!isset($_COOKIE['sessID']))
        {
            die(json_encode('Not autorised'));
        }

        try {
        $statement = $this->connection->prepare("SELECT * FROM `user` WHERE id=:id");
        $statement->bindValue('id', $id);
        $statement->execute();
        $data=$statement->fetchAll();
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode('failed');
        }

        http_response_code(200);
        return json_encode($data[0]['email']. ', ' . $data[0]['name'] . ', ' . $data[0]['age'] . ', ' . $data[0]['gender']);
    }

    public function update(): string
    {
        if ($_SESSION['user'])
        {
            //Присвоение каждого параметра - обязательно. Во фронтенд части в инпуты должны передаваться данные из сессии, которые можно оставить или изменить или оставить как есть
            parse_str(file_get_contents('php://input'), $PUT);
            $id = $_SESSION['user']['id'];
            if (filter_var($PUT['email'], FILTER_VALIDATE_EMAIL))
            {
                $email = $PUT['email'];
            } else {
                http_response_code(203);
                return json_encode('bad email');
            }
            $password = password_hash($PUT['password'], PASSWORD_BCRYPT);
            $name = $PUT['name'];
            $age = strval($PUT['age']);
            $gender = $PUT['gender'];
            $isAdmin = $PUT['isAdmin']; //В рамках учебного проекта, для проверки возможностей админ-панели, права администратора можэно изменить через данный метод

            try {
            $statement = $this->connection->prepare("UPDATE `user` set `name` = :name, `email` = :email, `password` = :password, `age` = :age, `gender` = :gender, `is_admin` = :isAdmin where `user`.`id` = :id");
            $statement->bindValue('id', $id);
            $statement->bindValue('name', $name);
            $statement->bindValue('email', $email);
            $statement->bindValue('password', $password);
            $statement->bindValue('age', $age);
            $statement->bindValue('gender', $gender);
            $statement->bindValue('isAdmin', $isAdmin);
            $statement->execute();
            } catch (PDOException $e) {
                http_response_code(500);
                return json_encode('failed');
            }

            http_response_code(202);
            return json_encode('success');
        } else {
            http_response_code(401);
            return json_encode('not autorised');
        }
    }

    public function add(): string
    {
        //Регистрация нового пользователя. Если с данным E-mail уже регистрировались - произойдет ошибка
        $name = $_POST['name'];
        $email = strval($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $age = strval($_POST['age']);
        $gender = $_POST['gender'];
        $isAdmin = 0;

        try {
        $statement = $this->connection->prepare("SELECT * FROM `user` WHERE email=:email");
        $statement->bindValue('email', $email);
        $statement->execute();
        $data=$statement->fetchAll();
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode('failed');
        }

        if (isset($data[0]))
        {
            return json_encode('email already used');
        } else {
            try {
        $statement = $this->connection->prepare("INSERT INTO `user` (`id`, `name`, `email`, `password`, `age`, `gender`, `is_admin`) VALUES (NULL, :name, :email, :password, :age, :gender, :isAdmin)");
        $statement->bindValue('name', $name);
        $statement->bindValue('email', $email);
        $statement->bindValue('password', $password);
        $statement->bindValue('age', $age);
        $statement->bindValue('gender', $gender);
        $statement->bindValue('isAdmin', $isAdmin);
        $statement->execute();
            } catch (PDOException $e) {
                http_response_code(500);
                return json_encode('failed');
            }

        http_response_code(201);
        return json_encode('success');
        }
    }

    public function login(): string
    {
        //Вход пользователя в аккаунт. При входе создается куки с токеном сессии. Время жизни куки - сутки
        $email = $_POST['email'];
        $password = $_POST['password'];

        try {
        $statement = $this->connection->prepare("SELECT * FROM `user` WHERE email=:email");
        $statement->bindValue('email', $email);
        $statement->execute();
        $data=$statement->fetchAll();
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode('failed');
        }

        if (isset($data[0]))
        {
            if (password_verify($password, $data[0]['password']))
            {
                $_SESSION['user'] = $data[0];
                setcookie("sessID", session_id(), time()+86400, '/');

                http_response_code(200);
                return json_encode('success');
            } else {
                http_response_code(401);
                return json_encode('not validate');
            }
        } else {
            http_response_code(400);
            return json_encode('bad email');
        }

    }

    public function logout(): string
    {
        //При выходе, сессия + куки уничтожаются
        if (!isset($_COOKIE['sessID']))
        {
            die(json_encode('Not autorised'));
        }
        session_destroy();
        setcookie("sessID", '', time()-3600, '/');
        http_response_code(200);
        return json_encode('logout');
    }

    public function reset_password(): mixed
    {
        //Получение е-mail, исходя из ТЗ, происходит через GET параметр, который передается в класс Mailer для отправки ссылки на восстановление
        if (isset($_GET['email']))
        {
            $email = $_GET['email'];

            try {
            $statement = $this->connection->prepare("SELECT * FROM `user` WHERE `email`=:email");
            $statement->bindValue('email', $email);
            $statement->execute();
            $data=$statement->fetchAll();
            } catch (PDOException $e) {
                http_response_code(500);
                return json_encode('failed');
            }

            if (isset($data[0]))
            {
                if (filter_var($email, FILTER_VALIDATE_EMAIL))
                {
                    $mail = new Mailer();
                    $mail->resetPassword($email, $data[0]['name'], $data[0]['password']);

                    http_response_code(200);
                    return json_encode('Url are send for this email');
                }
            } else {
                http_response_code(400);
                return json_encode('bad email');
            }

        }
        //Как только ссылка будет получена - появится возможность ввести новый пароль, который сохраняется в БД
        elseif (isset($_GET['key']))
        {
            try {
            $statement = $this->connection->prepare("SELECT * FROM `user` WHERE `password`=:password");
            $statement->bindValue('password', $_GET['key']);
            $statement->execute();
            $data=$statement->fetchAll();
            } catch (PDOException $e) {
                http_response_code(500);
                return json_encode('failed');
            }

            if (isset($data[0]))
            {
                $hash = password_hash($_GET['password'], PASSWORD_BCRYPT);
                try {
                $statement = $this->connection->prepare("Update `user` set `password` = :newPassword where `user`.`password`=:password");
                $statement->bindValue('password', $_GET['key']);
                $statement->bindValue('newPassword', $hash);
                $statement->execute();
                } catch (PDOException $e) {
                    http_response_code(500);
                    return json_encode('failed');
                }

                http_response_code(200);
                return json_encode('Password updated');
            } else {
                http_response_code(400);
                return json_encode('Url not validate');
            }
        }
    }
}