<?php
declare(strict_types=1);

require_once $_SERVER['ROOT_PATH'] . '/class/user.class.php';
?>
<?php switch ($_SERVER['REQUEST_METHOD']): ?>
<?php case 'GET': ?>
<?php $error = $_GET['error']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Seemus | Login</title>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/metadata.template.php'; ?>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/bootstrap.template.php'; ?>
</head>
<body>
    <?php if ($error): ?>
    <div id="errorModal" class="modal fade" tabindex="-1" aria-labelledby="#errorModalTitle" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="errorModalTitle" class="modal-title h5">
                    <?php if ($error === 'credentials'): ?>
                        Incorrect Login Credentials...
                    <?php elseif ($error === 'archived'): ?>
                        Your Account Has Been Archived...
                    <?php else: ?>
                        Something Went Wrong...
                    <?php endif ?>
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>
                    <?php if ($error === 'archived'): ?>
                        Please contact an administator.
                    <?php else: ?>
                        Please try again.
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
    <script defer>
    const errorModal = new bootstrap.Modal("#errorModal");
    errorModal.show();
    </script>
    <?php endif ?>

    <?php include_once $_SERVER['ROOT_PATH'] . '/template/header.template.php'; ?>
    
    <main class="container">
        <h2 class="my-3">Log in</h2>
        <p class="lead my-3">to continue to Seemus.</p>
        
        <form id="loginForm" class="needs-validation" action="/user/login.php" method="POST" novalidate>
            <div class="form-floating my-3">
                <input id="emailInput" class="form-control" type="email" name="email" placeholder required aria-describedby="#emailFeedback" />
                <label class="form-label" for="emailInput">Email</label>
                <div id="emailFeedback"></div>
            </div>
            <div class="form-floating my-3">
                <input id="passwordInput" class="form-control" type="password" name="password" placeholder required aria-describedby="#passwordFeedback" />
                <label class="form-label" for="passwordInput">Password</label>
                <div id="passwordFeedback"></div>
            </div>
            <button class="btn btn-success" type="submit">Login</button>
        </form>
    </main>

    <!-- TODO: Implement client-side validation -->
</body>
</html>
<?php break ?>
<?php case 'POST': ?>
<?php
try {
    User::login($_POST['email'], $_POST['password']);
} catch (UserLoggedInException $exception) {
    header('Location: /');
    exit();
} catch (InvalidUserCredentialsException $exception) {
    header('Location: /user/login.php?error=credentials');
    exit();
} catch (UserArchivedException $exception) {
    header('Location: /user/login.php?error=archived');
    exit();
}
header('Location: /');
?>
<?php break; ?>
<?php default: ?>
<?php header('Location: /redirect.php') ?>
<?php break; ?>
<?php endswitch ?>