<?php
//Whois Domain Expiration-Checker by Doorman
//
require "config.php";
require 'PHPMailerAutoload.php';

set_time_limit (0);

// PHP Mailer function call
function SendPHPMail($toName, $toAddress, $subject, $message, $attachment)
{
    global $mailer_mailhost, $mailer_userid, $mailer_password, $mailer_secure, $mailer_port, $mailer_from_name, $mailer_from;
    $mail = new PHPMailer;

    //$mail->SMTPDebug = 3;                         // Enable verbose debug output

    $mail->isSMTP();                                // Set mailer to use SMTP
    $mail->Host = $mailer_mailhost;                 // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                         // Enable SMTP authentication
    $mail->Username = $mailer_userid;               // SMTP username
    $mail->Password = $mailer_password;             // SMTP password
    $mail->SMTPSecure = $mailer_secure;             // Enable TLS encryption, `ssl` also accepted
    $mail->Port = $mailer_port;                     // TCP port to connect to

    $mail->setFrom($mailer_from, $mailer_from_name);
    $mail->addAddress($toAddress, $toName);         // Add a recipient

    $mail->addAttachment($attachment);              // Optional name
    $mail->isHTML(true);                  // Set email format to HTML

    $mail->Subject = $subject;
    $mail->Body    = $message;
    $mail->AltBody = strip_tags($message);

    ///DEBUG::  echo "Host:".$mailer_mailhost."<br>User:".$mailer_userid."<br>Password:".$mailer_password."<br>Secure:".$mailer_secure."<br>";
    if(!$mail->send()) {
        //echo 'Message could not be sent.';
        //echo 'Mailer Error: ' . $mail->ErrorInfo;
        return false;
    } else {
        //echo 'Message has been sent';
        return true;
    }    
}

readConfigFile();

$attachmentFile = "email/".date("Y-m-d H_i_s").".txt";
$handle = fopen($attachmentFile, "w") or die("Can't access the file to write."); 

$res =  queryMysql("UPDATE domains SET domain_status='Expired' WHERE domain_status='Unknown' AND (expiry_date < NOW())");
$res =  queryMysql("UPDATE domains SET domain_status='Expiring' WHERE domain_status='Unknown' AND (expiry_date < NOW() + INTERVAL $domain_expiring_days DAY)");
$res =  queryMysql("UPDATE domains SET domain_status='Running' WHERE domain_status='Unknown'");

$res =  queryMysql("SELECT domain_name, expiry_date from domains where (domain_status='Expired' OR domain_status='Deleted' OR domain_status='Expiring') AND (expiry_date >= NOW() - INTERVAL $email_days_before DAY) AND (expiry_date <= NOW() + INTERVAL $email_days_after DAY) ORDER BY expiry_date");
while($domRows = mysqli_fetch_row($res)) {
    // append to the attachment file
    fputs($handle, strtolower($domRows[0])." | Expiry : ".$domRows[1]."\n");
}

fclose($handle);

if(SendPHPMail($send_mail_to_name, $send_mail_to, $mail_subject, $mail_body, $attachmentFile)) {
    echo "<br>Report Emailed to ".$send_mail_to_name;
}
else {
    echo "<br>Email could not be sent.";
}

?>