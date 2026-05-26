<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'libs/PHPMailer/PHPMailer.php';
require_once 'libs/PHPMailer/SMTP.php';
require_once 'libs/PHPMailer/Exception.php';

function sendOtpEmail(string $toEmail, string $otp): void
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'royalinfinitygroup8@gmail.com'; // ← email Gmail kamu
    $mail->Password   = '123456';    // ← App Password (tanpa spasi)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('emailkamu@gmail.com', 'Piawai App');
    $mail->addAddress($toEmail);

    $mail->isHTML(true);
    $mail->Subject = 'Kode OTP Reset Password Piawai';
    $mail->Body    = "
        <div style='font-family: Arial, sans-serif; max-width: 480px; margin: auto;'>
            <h2 style='color: #04A5BA;'>Reset Password Piawai</h2>
            <p>Gunakan kode OTP berikut untuk reset password kamu:</p>
            <div style='font-size: 36px; font-weight: bold; letter-spacing: 8px;
                        color: #04A5BA; text-align: center; padding: 20px;
                        background: #f5f7fa; border-radius: 8px;'>
                {$otp}
            </div>
            <p style='color: #888; font-size: 13px; margin-top: 16px;'>
                Kode ini berlaku selama <strong>5 menit</strong>.<br>
                Abaikan email ini jika kamu tidak melakukan permintaan reset password.
            </p>
        </div>
    ";

    $mail->send();
}
