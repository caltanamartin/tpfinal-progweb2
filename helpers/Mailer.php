<?php

require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{

    public static function send($to, $subject, $body)
    {
        $path = __DIR__ . '/../config/mail.ini';
        $config = parse_ini_file($path, true)['mail'];

        $mail = new PHPMailer(true);

        try {
            if (!empty($config['host'])) {
                $mail->isSMTP();
                $mail->Host = $config['host'];
                $mail->Port = (int)($config['port'] ?? 587);
                $mail->SMTPAuth = !empty($config['username']);
                if (!empty($config['username'])) {
                    $mail->Username = $config['username'];
                    $mail->Password = $config['password'];
                }
                if (!empty($config['encryption'])) {
                    $mail->SMTPSecure = $config['encryption'];
                }
            } else {
                $mail->isMail();
            }

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
