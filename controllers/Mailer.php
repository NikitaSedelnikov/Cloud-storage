<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as Exception;

//Использование PHP Mailer для отправки сообщения
class Mailer
{
    public function resetPassword (string $email, string $name, string $password): string
    {
        $changeKeyUrl = $this->getResetUrl($password);
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->CharSet = "utf-8";
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'ssl';

            $mail->Host = 'smtp.gmail.com';
            $mail->Username = 'skillbox.task@gmail.com';
            $mail->Password = 'qcpzshuydojdgoce';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->setFrom('skillbox.task@gmail.com', 'Reset Password');

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'Восстановление пароля';
            $mail->Body = 'Ссылка на восстановление пароля: '. $changeKeyUrl;
            $mail->send();

            return json_encode('отправлено');
        }
        catch (Exception $e) {
            return json_encode('Ошибка');
        }
    }
//Подготовка уникальной ссылки для отправки
    public function getResetUrl ($passwordHash)
    {
        return 'http://127.0.0.1/users/reset_password?key='.$passwordHash;
    }
}