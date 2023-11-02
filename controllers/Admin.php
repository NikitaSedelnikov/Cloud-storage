<?php

class Admin
{
    private
        $host = "localhost",
        $db_name = "cloud_storage",
        $charset = 'utf8',
        $username = "root",
        $password = "";

    public $connection;
    /*
     * Доступ к панели для администраторов осуществляется через проверку на регистрацию путем проверки наличия токена в куках,
     * далее - наличие прав администратора на аккаунте
     */
    public function __construct ()
    {
        header('Content-Type:application/json');
        if (!isset($_COOKIE['sessID']))
        {
            die(json_encode('Not autorised'));
        }

        $this->connection = null;
        try {
            $this->connection = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ';charset=' . $this->charset, $this->username, $this->password);
        }
        catch (PDOException $exception) {
            die (json_encode("Connection to DataBase falied"));
        }

        $adminID = $_SESSION['user']['id'];
        try {
        $statement = $this->connection->prepare("SELECT * FROM `user` WHERE id=:id");
        $statement->bindValue('id', $adminID);
        $statement->execute();
        $data=$statement->fetchAll();
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode('failed');
        }

        if ($data[0]['is_admin'] === 0)
        {
            die(json_encode('Administration access is locked'));
        }
    }

    public function list(): string
    {
        try {
        $statement = $this->connection->query("SELECT * FROM `user`");
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
        return json_encode($data[0]);
    }

    public function update(int $id): string
    {
        parse_str(file_get_contents('php://input'), $PUT);

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
        $isAdmin = $PUT['isAdmin'];

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

        http_response_code(200);
        return json_encode('success');
    }

    public function delete(int $id): string
    {
        try {
        $statement = $this->connection->prepare("delete from `user` where `id`=:id");
        $statement->bindValue('id', $id);
        $statement->execute();
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode('failed');
        }

        http_response_code(200);
        return json_encode('success');
    }


}