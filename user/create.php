<?php
declare(strict_types=1);

require_once $_SERVER['ROOT_PATH'] . '/class/user.class.php';

$currentUser = User::current();
if (!$currentUser) {
    header('Location: /marcus/seemus/redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
    exit();
} else if (!$currentUser->isAdministrator()) {
    header('Location: /marcus/seemus/redirect.php?message=' . urlencode('You\'re Not Permitted...'));
    exit();
}
?>
<?php switch ($_SERVER['REQUEST_METHOD']): ?>
<?php case 'GET': ?>
<?php $errors = $_GET['errors'] ?? []; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Seemus | Create User</title>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/metadata.template.php'; ?>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/bootstrap.template.php'; ?>
</head>
<body>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/header.template.php'; ?>

    <main class="container">
        <h2 class="my-3">Create New User</h2>
        
        <form id="createUserForm" class="needs-validation" action="/marcus/seemus/user/create.php" method="POST" novalidate>
            <div class="form-floating my-3">
                <?php if (isset($errors['name-missing'])): ?>
                <input id="nameInput" class="form-control is-invalid" type="text" name="name" placeholder required aria-describedby="#nameFeedback" />
                <label class="form-label" for="nameInput">Name</label>
                <div id="nameFeedback" class="invalid-feedback">Please enter their name.</div>
                <?php elseif (isset($errors['name-invalid'])): ?>
                <input id="nameInput" class="form-control is-invalid" type="text" name="name" value="<?= htmlspecialchars($errors['name-invalid']) ?>" placeholder required aria-describedby="#nameFeedback" />
                <label class="form-label" for="nameInput">Name</label>
                <div id="nameFeedback" class="invalid-feedback">This name is invalid.</div>
                <?php else: ?>
                <input id="nameInput" class="form-control" type="text" name="name" placeholder required aria-describedby="#nameFeedback" />
                <label class="form-label" for="nameInput">Name</label>
                <div id="nameFeedback"></div>
                <?php endif ?>
            </div>
            <div class="form-floating my-3">
                <?php if (isset($errors['email-missing'])): ?>
                <input id="emailInput" class="form-control is-invalid" type="email" name="email" placeholder required aria-describedby="#emailFeedback" />
                <label class="form-label" for="emailInput">Email</label>
                <div id="emailFeedback" class="invalid-feedback">Please enter their email.</div>
                <?php elseif (isset($errors['email-invalid'])): ?>
                <input id="emailInput" class="form-control is-invalid" type="email" name="email" value="<?= htmlspecialchars($errors['email-invalid']) ?>" placeholder required aria-describedby="#emailFeedback" />
                <label class="form-label" for="emailInput">Email</label>
                <div id="emailFeedback" class="invalid-feedback">This email is invalid.</div>
                <?php else: ?>
                <input id="emailInput" class="form-control" type="email" name="email" placeholder required aria-describedby="#emailFeedback" />
                <label class="form-label" for="emailInput">Email</label>
                <div id="emailFeedback"></div>
                <?php endif ?>
            </div>
            <div class="form-floating my-3">
                <select id="roleSelect" class="form-select" name="role" required aria-label="Select user role" aria-describedby="#roleFeedback">
                    <?php foreach (User::ROLES as $roleValue => $roleName): ?>
                    <option value="<?= htmlspecialchars($roleValue) ?>"><?= htmlspecialchars($roleName) ?></option>
                    <?php endforeach ?>
                </select>
                <label class="form-label" for="roleSelect">Role</label>
                <div id="roleFeedback"></div>
            </div>
            <div class="form-floating my-3">
                <?php if (isset($errors['password-missing'])): ?>
                <input id="passwordInput" class="form-control is-invalid" type="password" name="password" placeholder aria-describedby="#passwordFeedback" />
                <label class="form-label" for="passwordInput">Password</label>
                <div id="passwordFeedback" class="invalid-feedback">Please enter their new password.</div>
                <?php else: ?>
                <input id="passwordInput" class="form-control" type="password" name="password" placeholder aria-describedby="#passwordFeedback" />
                <label class="form-label" for="passwordInput">Password</label>
                <div id="passwordFeedback"></div>
                <?php endif ?>
            </div>
            <div class="form-floating my-3">
                <?php if (isset($errors['confirm-password-missing'])): ?>
                <input id="confirmPasswordInput" class="form-control is-invalid" type="password" name="confirmPassword" placeholder aria-describedby="#confirmPasswordFeedback" />
                <label class="form-label" for="confirmPasswordInput">Confirm Password</label>
                <div id="confirmPasswordFeedback" class="invalid-feedback">Please confirm their new password.</div>
                <?php elseif (isset($errors['confirm-password-invalid'])): ?>
                <input id="confirmPasswordInput" class="form-control is-invalid" type="password" name="confirmPassword" placeholder aria-describedby="#confirmPasswordFeedback" />
                <label class="form-label" for="confirmPasswordInput">Confirm Password</label>
                <div id="confirmPasswordFeedback" class="invalid-feedback">Passwords do not match.</div>
                <?php else: ?>
                <input id="confirmPasswordInput" class="form-control" type="password" name="confirmPassword" placeholder aria-describedby="#confirmPasswordFeedback" />
                <label class="form-label" for="confirmNewPasswordInput">Confirm Password</label>
                <div id="confirmPasswordFeedback"></div>
                <?php endif ?>
            </div>
            <button class="btn btn-success" type="submit">Create</button>
            <a class="btn btn-danger" href="/marcus/seemus/user/view.php">Cancel</a>
        </form>

        <!-- Implement client-side validation -->
    </main>
</body>
</html>
<?php break ?>
<?php case 'POST': ?>
<?php
$errors = [];

if (empty($_POST['name'])) {
    $errors []= 'errors[name-missing]=true';
}

if (empty($_POST['email'])) {
    $errors []= 'errors[email-missing]=true';
}

if (empty($_POST['role'])) {
    $errors []= 'errors[role-missing]=true';
}

if (empty($_POST['password'])) {
    $errors []= 'errors[password-missing]=true';
}

if (empty($_POST['confirmPassword'])) {
    $errors []= 'errors[confirm-password-missing]=true';
} else if ($_POST['password'] !== $_POST['confirmPassword']) {
    $errors []= 'errors[confirm-password-invalid]=true';
}

if (empty($errors)) {
    $creatingUser = NULL;
    try {
        $creatingUser = User::create(
            $_POST['name'],
            $_POST['email'],
            $_POST['password'],
            $_POST['role']
        );
    } catch (UserNotLoggedInException $exception) {
        header('Location: /marcus/seemus/redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
        exit();
    } catch (UserNotPermittedException $exception) {
        header('Location: /marcus/seemus/redirect.php?message=' . urlencode('You\'re Not Permitted...'));
        exit();
    } catch (InvalidUserDataException $exception) {
        $invalidFields = $exception->getInvalidFields();
        if (in_array('name', $invalidFields)) {
            $errors []= 'errors[name-invalid]=' . urlencode($_POST['name']);
        }
        if (in_array('email', $invalidFields)) {
            $errors []= 'errors[email-invalid]=' . urlencode($_POST['email']);
        }
        if (in_array('role', $invalidFields)) {
            $errors []= 'errors[role-invalid]=' . urlencode($_POST['role']);
        }
    }
    header('Location: /marcus/seemus/user/view.php?uuid=' . urlencode($creatingUser->getUUID()));
} else {
    header('Location: /marcus/seemus/user/create.php?' . implode('&', $errors));
}
?>
<?php break ?>
<?php default: ?>
<?php header('Location: /marcus/seemus/redirect.php'); ?>
<?php endswitch ?>