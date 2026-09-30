<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// include PHPMailer manually
require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // generate random 6-digit code (with leading zeros if needed)
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $mail = new PHPMailer(true);

    try {
        // SMTP server settings
        $mail->isSMTP();
        $mail->Host       = 'sp162.mschosting.cloud';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admin@bijikupi.com';   // your email
        $mail->Password   = 'Bijikupi@2022';       // change to your real password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // sender & recipient
        $mail->setFrom('admin@bijikupi.com', 'Your Account Has Been Hacked');
        $mail->addAddress('soonlai1129@gmail.com');     // test recipient

        // email content
        $mail->isHTML(true);
        
        $mail->Body    = "<p>Hello!</p><p>Your Account Has Been Hacked !!!</p>";
        $mail->AltBody = "Your Account Has Been Hacked !!!";

        $mail->send();
        
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

<!-- Simple button -->
<form method="post">
  <button type="submit">Send Verification Code</button>
</form>
