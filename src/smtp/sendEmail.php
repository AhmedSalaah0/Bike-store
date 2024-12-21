<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
function sendVerificationEmail($email, $first_name, $last_name, $type, $verification_link) {
    require __DIR__ . '/../../vendor/autoload.php';

    $mail = new PHPMailer(true);

    $message = match ($type) {
        'OTP_mail' => 'This is your OTP: ',
        'Verification_mail' => 'Click The Link to Verify: ',
    };

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'Sender@domain.com'; // Sender email address
        $mail->Password = 'App Password'; // Email app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('Sender@domain.com', 'Bike Store');
        $mail->addAddress($email, $first_name . ' ' . $last_name);

        $mail->isHTML(true);
        $mail->Subject = 'Bike Store Verification';
        $mail->Body    = "$message $verification_link";
        $mail->AltBody = 'This is a verification email';

        $mail->send();
        echo json_encode(['SMTP' => 'Message has been sent']);
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
