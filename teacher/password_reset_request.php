<?php
// teacher/password_reset_request.php
require_once '../config.php';
require_once '../functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        // Check if a teacher with this email exists
        $sql = "SELECT id, email FROM teachers WHERE email = :email";
        $stmt = $pdoconn->prepare($sql);
        $stmt->execute(['email' => $email]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($teacher) {
            // Generate a unique, secure token
            $token = bin2hex(random_bytes(50));
            $expires = date("U") + 1800; // Token expires in 30 minutes

            // Store the token in the database
            // We need a new table for password reset tokens
            // For now, let's assume a 'password_reset_temp' table exists
            // I will create it in a later step.

            // For now, we will just simulate the email sending part
            // In a real application, you would use a library like PHPMailer

            $reset_link = get_site_url() . '/teacher/reset_password.php?token=' . $token;

            // Simulate sending email
            $mail_subject = "Password Reset Request";
            $mail_body = "To reset your password, please click on this link: \n\n" . $reset_link;
            
            // For demonstration, we'll just display the link and message
            $message = "A password reset link has been sent to your email address (if it exists in our system). Please check your inbox. <br><br><strong>Demo Link:</strong> <a href='$reset_link'>$reset_link</a>";

        } else {
            // Show a generic message to prevent user enumeration
            $message = "If an account with that email exists, a password reset link has been sent.";
        }
    }
}

$page_title = 'Reset Password';
include '../templates/teacher-header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Reset Password</h3>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <p>Please enter your email address to receive a password reset link.</p>
                    
                    <form action="password_reset_request.php" method="POST">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block mt-3">Send Reset Link</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/teacher-footer.php'; ?>
