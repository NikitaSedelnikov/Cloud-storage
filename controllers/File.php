<?php

class File
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
        if (!isset($_COOKIE['sessID']))
        {
            die(json_encode('Not autorised')); //Проверка на вход пользователя
        }
        $this->connection = null;

        try {
            $this->connection = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ';charset=' . $this->charset, $this->username, $this->password);
        } catch (PDOException $exception) {
            die (json_encode("Connection to DataBase falied"));
        }

    }

    public function list (): mixed
    {
        if ($_SESSION['user']) {
            $id = $_SESSION['user']['id'];

            try {
            $statement = $this->connection->prepare("SELECT * FROM `files` WHERE `id_user` = :id");
            $statement->bindValue('id', $id);
            $statement->execute();
            $data = $statement->fetchAll();
            } catch (PDOException $e) {
                http_response_code(500);
                return json_encode('failed');
            }

            http_response_code(200);
            return json_encode($data);
        }
        else {
            http_response_code(401);
            return json_encode('Not authorised');
        }
    }

    public function get (int $id): mixed
    {
        //Осуществляется сбор информации по файлу из основной БД + у каких пользователей есть доступ к этому файлу
        if ($_SESSION['user']) {
            $userId = $_SESSION['user']['id'];
            $userEmail = $_SESSION['user']['email'];

            try {
            $statement = $this->connection->prepare("SELECT * FROM `files` WHERE `id_user` = :id AND id_file = :fileId");
            $statement->bindValue('id', $userId);
            $statement->bindValue('fileId', $id);
            $statement->execute();
            $authorData = $statement->fetchAll();

            $statement = $this->connection->prepare("SELECT `emails` FROM `shares` WHERE id_file = :fileId");
            $statement->bindValue('fileId', $id);
            $statement->execute();
            $usersData = $statement->fetchAll();
            } catch (PDOException $e) {
                http_response_code(500);
                return json_encode('failed');
            }

            if (!isset($authorData[0]))
            {
                return json_encode('File access is locked');
            } else {
                http_response_code(200);
                return json_encode([$authorData, 'Emails' => $usersData]);
            }

        } else {
            http_response_code(401);
            return json_encode('Not authorised');
        }
    }

    public function add (): string
    {
        //Происходит проверка файла на вес ниже 2 ГБ. Далее осуществялется проверка на сохранение файла в установленную для этого папку.
        //Имя хэшируется, а оригинальное имя + хэш и другие данные идут в БД.
        if (isset($_FILES))
        {
            foreach ($_FILES as $keyFile => $data)
            {
                if ($data['size'] < 2147483648) {
                    $idUser = $_SESSION['user']['id'];
                    $name = $data['name'];
                    $size = $data['size'];
                    $slug = substr(strrchr($data['full_path'], '.'), 1);
                    $hash = hash('sha512', $data['name']);
                    try {
                    $statement = $this->connection->prepare("INSERT INTO `files` (`id_file`, `id_dir`, `id_user`, `name`, `size`, `slug`, `hash`) VALUES (NULL, NULL, :id, :name, :size, :slug, :hash)");
                    $statement->bindValue('id', $idUser);
                    $statement->bindValue('name', $name);
                    $statement->bindValue('size', $size);
                    $statement->bindValue('slug', $slug);
                    $statement->bindValue('hash', $hash);
                    $statement->execute();
                    } catch (PDOException $e) {
                        http_response_code(500);
                        return json_encode('failed');
                    }

                    if (move_uploaded_file($_FILES[$keyFile]['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . '/files/'.$hash.'.'.$slug))
                    {
                        http_response_code(201);
                        return json_encode('success');
                    } else {
                        http_response_code(400);
                        return json_encode('File is not upload');
                    }

                } else {
                    http_response_code(400);
                    return json_encode('The file is too big');
                }
            }
        } else {
            return json_encode('File is not upload');
        }
    }

    public function rename (int $id): string
    {
        //Новое имя файла так же хэшируется и переименовывается на сервере
        parse_str(file_get_contents('php://input'), $PUT);
        if ($PUT['name'])
        {
            $userId = $_SESSION['user']['id'];
            $newHash = hash('sha512', $PUT['name']);
            try {
            $statement = $this->connection->prepare("SELECT * FROM `files` WHERE id_file = :id AND id_user = :userId");
            $statement->bindValue('id', $id);
            $statement->bindValue('userId', $userId);
            $statement->execute();
            $fileData = $statement->fetch();
            } catch (PDOException $e) {
                http_response_code(500);
                return json_encode('failed');
            }

            if (empty($fileData))
            {
                http_response_code(200);
                return json_encode('File access is locked');
            } else {
                rename($_SERVER['DOCUMENT_ROOT'] . '/files/' . $fileData['hash'] . '.' . $fileData['slug'], $_SERVER['DOCUMENT_ROOT'] . '/files/' . $newHash . '.' . $fileData['slug']);

                try {
                $statement = $this->connection->prepare("UPDATE `files` set `name` = :name, `hash` = :hash where `files`.`id_file` = :idFile");
                $statement->bindValue('idFile', $id);
                $statement->bindValue('name', $PUT['name']);
                $statement->bindValue('hash', $newHash);
                $statement->execute();
                } catch (PDOException $e) {
                    http_response_code(500);
                    return json_encode('failed');
                }

                http_response_code(202);
                return json_encode('success');
            }
        } else {
            http_response_code(400);
            return json_encode('The name is empty');
        }
    }

    public function remove (int $id): string
    {
        $userId = $_SESSION['user']['id'];
        try {
        $statement = $this->connection->prepare("SELECT * FROM `files` WHERE id_file = :id AND id_user = :userId");
        $statement->bindValue('id', $id);
        $statement->bindValue('userId', $userId);
        $statement->execute();
        $fileData = $statement->fetch();

        $statement = $this->connection->prepare("DELETE FROM `files` WHERE id_file = :id AND id_user = :userId");
        $statement->bindValue('id', $id);
        $statement->bindValue('userId', $userId);
        $statement->execute();
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode('failed');
        }

        unlink($_SERVER['DOCUMENT_ROOT'] . '/files/' . $fileData['hash'] . '.' . $fileData['slug']);

        http_response_code(200);
        return json_encode('success');
    }

    public function dirAdd (): mixed
    {
        $name = $_POST['name'];
        $idUser = $_SESSION['user']['id'];

        try {
        $statement = $this->connection->prepare("INSERT INTO `directories` (`id_dir`, `id_user`, `name`) VALUES (NULL, :id, :name)");
        $statement->bindValue('id', $idUser);
        $statement->bindValue('name', $name);
        $statement->execute();
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode('failed');
        }

        http_response_code(201);
        return json_encode('create dir: '.$name);
    }

    public function dirRename (int $id): string
    {
        parse_str(file_get_contents('php://input'), $PUT);

        if ($PUT['name'])
        {
            try {
            $statement = $this->connection->prepare("UPDATE `directories` set `name` = :name where `directories`.`id_dir` = :id");
            $statement->bindValue('id', $id);
            $statement->bindValue('name', $PUT['name']);
            $statement->execute();
            } catch (PDOException $e) {
                http_response_code(500);
                return json_encode('failed');
            }

            return json_encode('success');
        }
    }

    public function dirGet (int $id): mixed
    {
        //Получение папки + всех файлов, что в этой папке находятся
        if ($_SESSION['user']) {
            $userId = $_SESSION['user']['id'];
            try {
            $statement = $this->connection->prepare("SELECT * FROM `directories` WHERE `id_dir` = :id AND id_user = :userId");
            $statement->bindValue('id', $id);
            $statement->bindValue('userId', $userId);
            $statement->execute();
            $authorData = $statement->fetchAll();

            $statement = $this->connection->prepare("SELECT `name` FROM `files` WHERE `id_dir` = :id AND id_user = :userId");
            $statement->bindValue('id', $id);
            $statement->bindValue('userId', $userId);
            $statement->execute();
            $filesData = $statement->fetchAll();
            } catch (PDOException $e) {
                http_response_code(500);
                return json_encode('failed');
            }

            if (isset($authorData))
            {
                return json_encode(['dirs' => $authorData, 'files' => $filesData]);
            } else {
                return json_encode('failed');
            }
        }
    }

    public function dirSet(int $id, int $fileId): string
    {
        //Нужному файлу просто присваивается необходимая папка по id
        $userID = $_SESSION['user']['id'];
        try {
        $statement = $this->connection->prepare("SELECT * FROM `directories` WHERE `id_dir` = :id and `id_user` = :userID");
        $statement->bindValue('id', $id);
        $statement->bindValue('userID', $userID);
        $statement->execute();
        $authorData = $statement->fetch();
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode('failed');
        }
        if (isset($authorData["id_dir"]))
        {
            try {
            $statement = $this->connection->prepare("UPDATE `files` set `id_dir` = :id where `id_file` = :fileId and `id_user` = :userID");
            $statement->bindValue('fileId', $fileId);
            $statement->bindValue('userID', $userID);
            $statement->bindValue('id', $id);
            $statement->execute();
            } catch (PDOException $e) {
                http_response_code(500);
                return json_encode('failed');
            }

            return json_encode('success');
        } else {
            return json_encode('failed');
        }

    }

    public function dirDelete (int $id): string
    {
        $userId = $_SESSION['user']['id'];
        try {
        $statement = $this->connection->prepare("DELETE FROM `directories` WHERE id_dir = :id AND id_user = :userId");
        $statement->bindValue('id', $id);
        $statement->bindValue('userId', $userId);
        $statement->execute();
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode('failed');
        }

        http_response_code(200);
        return json_encode('success');
    }

    public function fileShare (int $id): mixed
    {
        if ($_SESSION['user']) {
            $userId = $_SESSION['user']['id'];
            try {
            $statement = $this->connection->prepare("SELECT `emails` FROM `shares` WHERE `id_file` = :id AND id_user_admin = :adminId");
            $statement->bindValue('id', $id);
            $statement->bindValue('adminId', $userId);
            $statement->execute();
            $authorData = $statement->fetchAll();
            } catch (PDOException $e) {
                http_response_code(500);
                return json_encode('failed');
            }
            http_response_code(200);
            return json_encode($authorData);
        } else {
            http_response_code(401);
            return json_encode('not autorised');
        }
    }

    public function fileShareUser (int $id, int $userId): string
    {
        //Для присвоения доступа к файлу, производится поиск пользователя по id, откуда достается email
        //далее, производится поиск файла и создается запись в БД о присвоении файла для конкретного пользователя
        if ($_SESSION['user']) {
            $idUser = $_SESSION['user']['id'];
            try {
            $statement = $this->connection->prepare("SELECT `email` FROM `user` WHERE id=:id");
            $statement->bindValue('id', $userId);
            $statement->execute();
            $userEmail=$statement->fetch();

            $statement = $this->connection->prepare("SELECT `name` FROM `files` WHERE `id_file` = :fileId");
            $statement->bindValue('fileId', $id);
            $statement->execute();
            $data=$statement->fetch();

            $statement = $this->connection->prepare("INSERT INTO `shares` (`id_file`, `id_user_admin`, `emails`, `filename`) VALUES (:idFile, :idAdmin, :email, :name)");
            $statement->bindValue('idFile', $id);
            $statement->bindValue('idAdmin', $idUser);
            $statement->bindValue('email', $userEmail[0]);
            $statement->bindValue('name', $data[0]);
            $statement->execute();
            } catch (PDOException $e) {
                http_response_code(500);
                return json_encode('failed');
            }
            http_response_code(200);
            return json_encode('file is share');
        } else {
            http_response_code(401);
            return json_encode('not authorise');
        }

    }

    public function fileShareDelete (int $id, int $userId): string
    {
        try {
            $statement = $this->connection->prepare("SELECT `email` FROM `user` WHERE id=:id");
            $statement->bindValue('id', $userId);
            $statement->execute();
            $userEmail=$statement->fetch();

            $statement = $this->connection->prepare("delete from `shares` where `id_file`=:id and `emails` = :email");
            $statement->bindValue('id', $id);
            $statement->bindValue('email', $userEmail['email']);
            $statement->execute();
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode('failed');
        }

        http_response_code(200);
        return json_encode('success');
    }
}