<?php
function sendEmail($to, $subject, $message) {
    // Basic headers for HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: LaParfume <no-reply@laparfume.atwebpages.com>" . "\r\n";

    // Try to send using the server's default mailer
    return mail($to, $subject, $message, $headers);
}
?>