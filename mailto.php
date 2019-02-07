<?php

require('./includes/phpmailer/class.phpmailer.php');
require('./includes/phpmailer/class.smtp.php');

function Mailto($email, $url) {
    $mail = new PHPMailer();
    $subject = ("Подтверждение регистрации на сайте 'Dereban.ua'");
    $content = "<div style='text-align: center; font-size: 18px;'>"
            . "<b>"
            . "Для подтверждения регистрации на сайте 'Dereban.ua' перейдите по ссылке: "
            . "</b>"
            . "<br>"
            . "<a href=".$url." target='_blank' style='color: #3f51b5;'>Подтвердить регистрацию</a>"
            . "</div>";
    $mail->IsSMTP();
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = TRUE;
    $mail->SMTPSecure = "tls";
    $mail->Port = 587;
    $mail->Username = "dereban.info@gmail.com";
    $mail->Password = "!oKzpcGa2%FV";
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

    $mail->Send();
}

?>