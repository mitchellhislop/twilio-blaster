<?php
if ((isset($_POST['name'])) && (strlen(trim($_POST['name'])) > 0)) {
	$name = stripslashes(strip_tags($_POST['name']));
}
if ((isset($_POST['email'])) && (strlen(trim($_POST['email'])) > 0)) {
	$email = stripslashes(strip_tags($_POST['email']));
}
if ((isset($_POST['phone'])) && (strlen(trim($_POST['phone'])) > 0)) {
	$phone = stripslashes(strip_tags($_POST['phone']));
} 
ob_start();

$body = <<<BODYSTR
Name: $name
Email: $email
Phone: $phone
BODYSTR;

$to = 'someone@example.com';
$email = 'email@example.com';
$fromaddress = "you@example.com";
$fromname = "Online Contact";

require("phpmailer.php");

$mail = new PHPMailer();

$mail->From     = "mhislop@smcpros.com";
$mail->FromName = "Contact Form";
$mail->AddAddress("tolson+encyc@smcpros.com");


$mail->WordWrap = 50;
$mail->IsHTML(true);

$mail->Subject  =  "Encyclopedia Downloaded";
$mail->Body     =  $body;
$mail->AltBody  =  $body;

$mail->Send();





?>
