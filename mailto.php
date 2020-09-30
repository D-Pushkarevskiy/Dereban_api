<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './includes/phpmailer/Exception.php';
require './includes/phpmailer/PHPMailer.php';
require './includes/phpmailer/SMTP.php';

function Mailto($email, $subject, $content) {
    $mail = new PHPMailer();
    $subject = $subject;
    $content = $content;
    $mail->IsSMTP();
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = TRUE;
    $mail->SMTPSecure = "tls";
    $mail->Port = 587;
    $mail->Username = "dereban.info@gmail.com";
    $mail->Password = "!oKzpcGa2%FV111";
    $mail->Host = "smtp.gmail.com";
    $mail->Mailer = "smtp";
    $mail->Charset = "UTF-8";
    $mail->SetFrom("dereban.info@gmail.com", "Dereban.info");
    $mail->AddReplyTo("dereban.info@gmail.com", "Dereban.info");
    $mail->AddAddress($email);
    $mail->Subject = $subject;
    $mail->WordWrap = 80;
    $mail->MsgHTML($content);
    $mail->IsHTML(true);

    if($mail->Send()){
        return true;
    } else {
        echo $mail->ErrorInfo;
        return false;
    }
}
