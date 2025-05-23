<?php
// send_mail.php: PHPMailer SMTP mail utility for kaiTOP
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function send_support_mail($to, $subject, $body, $replyTo = null) {
    $mail = new PHPMailer(true);
    try {
        // SMTP config (Gmail example)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_gmail@gmail.com'; // <-- CHANGE THIS
        $mail->Password = 'your_app_password';    // <-- CHANGE THIS (App Password, not real password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('your_gmail@gmail.com', 'kaiTOP Support');
        $mail->addAddress($to);
        if ($replyTo) $mail->addReplyTo($replyTo);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}
