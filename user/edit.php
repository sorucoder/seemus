<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/user.class.php';

$currentUser = User::current();
if (!$currentUser) {
    header('Location: /redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
    exit();
}
?>
<?php switch ($_SERVER['REQUEST_METHOD']): ?>
<?php case 'GET': ?>
<?php
$editingUserUUID = filter_var(
    $_GET['uuid'],
    FILTER_VALIDATE_REGEXP,
    [
        'options' => [
            'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        ],
        'flags' => FILTER_NULL_ON_FAILURE
    ]
);

$editingUser = $currentUser;
if ($editingUserUUID) {
    $editingUser = User::fromUUID($editingUserUUID);
}

if (!$currentUser->isAdministrator() && !$currentUser->is($editingUser)) {
    header('Location: /user/edit.php?uuid=' . urlencode($currentUser->getUUID()));
    exit();
} else if ($editingUser->isArchived()) {
    header('Location: /redirect.php?message=' . urlencode('This User is Archived...'));
    exit();
}

$errors = $_GET['errors'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Seemus | Edit User</title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/template/metadata.template.php'; ?>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/template/bootstrap.template.php'; ?>
</head>
<body>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/template/header.template.php'; ?>

    <main class="container">
        <h2 class="my-3">
            <?php if ($currentUser->is($editingUser)): ?>
            Your Settings
            <?php else: ?>
            <?= htmlspecialchars($editingUser->getName('first-possessive')) ?> Settings
            <?php endif ?>
        </h2>
        
        <form id="editUserForm" class="needs-validation" action="/user/edit.php" method="POST" novalidate>
            <input type="hidden" name="uuid" value="<?= htmlspecialchars($editingUser->getUUID()) ?>" />
            <div class="form-floating my-3">
                <?php if (isset($errors['name-missing'])): ?>
                <input id="nameInput" class="form-control is-invalid" type="text" name="name" placeholder required aria-describedby="#nameFeedback" />
                <label class="form-label" for="nameInput">Name</label>
                <div id="nameFeedback" class="invalid-feedback">
                    <?php if ($currentUser->is($editingUser)): ?>
                    Please enter your new name.
                    <?php else: ?>
                    Please enter their new name.
                    <?php endif ?>
                </div>
                <?php elseif (isset($errors['name-invalid'])): ?>
                <input id="nameInput" class="form-control is-invalid" type="text" name="name" value="<?= htmlspecialchars($errors['name-invalid']) ?>" placeholder required aria-describedby="#nameFeedback" />
                <label class="form-label" for="nameInput">Name</label>
                <div id="nameFeedback" class="invalid-feedback">This name is invalid.</div>
                <?php else: ?>
                <input id="nameInput" class="form-control" type="text" name="name" value="<?= htmlspecialchars($editingUser->getName()) ?>" placeholder required aria-describedby="#nameFeedback" />
                <label class="form-label" for="nameInput">Name</label>
                <div id="nameFeedback"></div>
                <?php endif ?>
            </div>
            <div class="form-floating my-3">
                <?php if (isset($errors['email-missing'])): ?>
                <input id="emailInput" class="form-control is-invalid" type="email" name="email" placeholder required aria-describedby="#emailFeedback" />
                <label class="form-label" for="emailInput">Email</label>
                <div id="emailFeedback" class="invalid-feedback">
                    <?php if ($currentUser->is($editingUser)): ?>
                    Please enter your new email.
                    <?php else: ?>
                    Please enter their new email.
                    <?php endif ?>
                </div>
                <?php elseif (isset($errors['email-invalid'])): ?>
                <input id="emailInput" class="form-control is-invalid" type="email" name="email" value="<?= htmlspecialchars($errors['email-invalid']) ?>" placeholder required aria-describedby="#emailFeedback" />
                <label class="form-label" for="emailInput">Email</label>
                <div id="emailFeedback" class="invalid-feedback">This email is invalid.</div>
                <?php else: ?>
                <input id="emailInput" class="form-control" type="email" name="email" value="<?= htmlspecialchars($editingUser->getEmail()) ?>" placeholder required aria-describedby="#emailFeedback" />
                <label class="form-label" for="emailInput">Email</label>
                <div id="emailFeedback"></div>
                <?php endif ?>
            </div>
            <?php if (!$currentUser->is($editingUser)): ?>
            <div class="form-floating my-3">
                <select id="roleSelect" class="form-select" name="role" required aria-label="Select user role" aria-describedby="#roleFeedback">
                    <?php foreach (User::ROLES as $roleValue => $roleName): ?>
                    <?php if ($editingUser->getRole() === $roleValue): ?>
                    <option selected value="<?= htmlspecialchars($roleValue) ?>">
                        <?= htmlspecialchars($roleName) ?>
                    </option>
                    <?php else: ?>
                    <option value="<?= htmlspecialchars($roleValue) ?>">
                        <?= htmlspecialchars($roleName) ?>
                    </option>
                    <?php endif ?>
                    <?php endforeach ?>
                </select>
                <label class="form-label" for="roleSelect">Role</label>
                <div id="roleFeedback"></div>
            </div>
            <?php else: ?>
            <div class="form-floating my-3">
                <?php if (isset($errors['old-password-missing'])): ?>
                <input id="oldPasswordInput" class="form-control is-invalid" type="password" name="oldPassword" placeholder aria-describedby="#oldPasswordFeedback" />
                <label class="form-label" for="oldPasswordInput">Old Password</label>
                <div id="oldPasswordFeedback" class="invalid-feedback">Please enter your old password.</div>
                <?php elseif (isset($errors['old-password-invalid'])): ?>
                <input id="oldPasswordInput" class="form-control is-invalid" type="password" name="oldPassword" placeholder aria-describedby="#oldPasswordFeedback" />
                <label class="form-label" for="oldPasswordInput">Old Password</label>
                <div id="oldPasswordFeedback" class="invalid-feedback">This password does not match your old password.</div>
                <?php else: ?>
                <input id="oldPasswordInput" class="form-control" type="password" name="oldPassword" placeholder aria-describedby="#oldPasswordFeedback" />
                <label class="form-label" for="oldPasswordInput">Old Password</label>
                <div id="oldPasswordFeedback"></div>
                <?php endif ?>
            </div>
            <div class="form-floating my-3">
                <?php if (isset($errors['new-password-missing'])): ?>
                <input id="newPasswordInput" class="form-control is-invalid" type="password" name="newPassword" placeholder aria-describedby="#newPasswordFeedback" />
                <label class="form-label" for="newPasswordInput">New Password</label>
                <div id="newPasswordFeedback" class="invalid-feedback">Please enter your new password.</div>
                <?php else: ?>
                <input id="newPasswordInput" class="form-control" type="password" name="newPassword" placeholder aria-describedby="#newPasswordFeedback" />
                <label class="form-label" for="newPasswordInput">New Password</label>
                <div id="newPasswordFeedback"></div>
                <?php endif ?>
            </div>
            <div class="form-floating my-3">
                <?php if (isset($errors['confirm-new-password-missing'])): ?>
                <input id="confirmNewPasswordInput" class="form-control is-invalid" type="password" name="confirmNewPassword" placeholder aria-describedby="#confirmNewPasswordFeedback" />
                <label class="form-label" for="confirmNewPasswordInput">Confirm New Password</label>
                <div id="confirmNewPasswordFeedback" class="invalid-feedback">Please confirm your new password.</div>
                <?php elseif (isset($errors['confirm-new-password-invalid'])): ?>
                <input id="confirmNewPasswordInput" class="form-control is-invalid" type="password" name="confirmNewPassword" placeholder aria-describedby="#confirmNewPasswordFeedback" />
                <label class="form-label" for="confirmNewPasswordInput">Confirm New Password</label>
                <div id="confirmNewPasswordFeedback" class="invalid-feedback">Passwords do not match.</div>
                <?php else: ?>
                <input id="confirmNewPasswordInput" class="form-control" type="password" name="confirmNewPassword" placeholder aria-describedby="#confirmNewPasswordFeedback" />
                <label class="form-label" for="confirmNewPasswordInput">Confirm New Password</label>
                <div id="confirmNewPasswordFeedback"></div>
                <?php endif ?>
            </div>
            <?php endif ?>
            <button class="btn btn-success" type="submit">Update</button>
            <?php if ($currentUser->is($editingUser)): ?>
            <a class="btn btn-danger" href="/">Cancel</a>
            <?php else: ?>
            <a class="btn btn-danger" href="/user/view.php?uuid=<?= urlencode($editingUser->getUUID()) ?>">Cancel</a>
            <?php endif ?>
        </form>

        <!-- Implement client-side validation -->
    </main>
</body>
</html>
<?php break ?>
<?php case 'POST': ?>
<?php
$errors = [];

$editingUserUUID = filter_var(
    $_POST['uuid'],
    FILTER_VALIDATE_REGEXP,
    [
        'options' => [
            'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        ],
        'flags' => FILTER_NULL_ON_FAILURE
    ]
);

$editingUser = $currentUser;
if ($editingUserUUID) {
    $editingUser = User::fromUUID($editingUserUUID);
    if (!$editingUser) {
        header('Location: /redirect.php');
        exit();
    }
}

if (!empty($_POST['name'])) {
    try {
        $editingUser->changeName($_POST['name']);
    } catch (UserNotLoggedInException $exception) {
        header('Location: /redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
        exit();
    } catch (UserNotPermittedException $exception) {
        header('Location: /redirect.php?message=' . urlencode('You\'re Not Permitted...'));
        exit();
    } catch (UserArchivedException $exception) {
        header('Location: /redirect.php?message=' . urlencode('This User is Archived...'));
        exit();
    } catch (InvalidUserDataException $exception) {
        $errors []= 'errors[name-invalid]=' . urlencode($_POST['name']);
    }
} else {
    $errors []= 'errors[missing-name]=true';
}

if (!empty($_POST['email'])) {
    try {
        $editingUser->changeEmail($_POST['email']);
    } catch (UserNotLoggedInException $exception) {
        header('Location: /redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
        exit();
    } catch (UserNotPermittedException $exception) {
        header('Location: /redirect.php?message=' . urlencode('You\'re Not Permitted...'));
        exit();
    } catch (UserArchivedException $exception) {
        header('Location: /redirect.php?message=' . urlencode('This User is Archived...'));
        exit();
    } catch (InvalidUserDataException $exception) {
        $errors []= 'errors[email-invalid]=' . urlencode($_POST['email']);
    }
} else {
    $errors []= 'errors[missing-email]=true';
}

if (!empty($_POST['role'])) {
    try {
        $editingUser->changeRole($_POST['role']);
    } catch (UserNotLoggedInException $exception) {
        header('Location: /redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
        exit();
    } catch (UserNotPermittedException $exception) {
        header('Location: /redirect.php?message=' . urlencode('You\'re Not Permitted...'));
        exit();
    } catch (UserArchivedException $exception) {
        header('Location: /redirect.php?message=' . urlencode('This User is Archived...'));
        exit();
    } catch (InvalidUserDataException $exception) {
        $errors []= 'errors[role-invalid]=' . urlencode($_POST['role']);
    }
}

if (!empty($_POST['oldPassword'])) {
    $validPasswordInputs = true;
    
    if (empty($_POST['newPassword'])) {
        $validPasswordInputs = false;
        $errors []= 'errors[new-password-missing]=true';
    }
    
    if (empty($_POST['confirmNewPassword'])) {
        $validPasswordInputs = false;
        $errors []= 'errors[confirm-new-password-missing]=true';
    } else if ($_POST['newPassword'] !== $_POST['confirmNewPassword']) {
        $validPasswordInputs = false;
        $errors []= 'errors[confirm-new-password-invalid]=true';
    }

    if ($validPasswordInputs) {
        try {
            $editingUser->changePassword($_POST['oldPassword'], $_POST['newPassword']);
        } catch (UserNotLoggedInException $exception) {
            header('Location: /redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
            exit();
        } catch (UserNotPermittedException $exception) {
            header('Location: /redirect.php?message=' . urlencode('You\'re Not Permitted...'));
            exit();
        } catch (UserArchivedException $exception) {
            header('Location: /redirect.php?message=' . urlencode('This User is Archived...'));
            exit();
        } catch (InvalidUserCredentialsException $exception) {
            $errors []= 'errors[old-password-invalid]=true';
        }
    }
}

if (!empty($errors)) {
    header('Location: /user/edit.php?uuid=' . urlencode($editingUser->getUUID()) . '&' . implode('&', $errors));
    exit();
}

if ($currentUser->is($editingUser)) {
    header('Location: /');
} else if ($currentUser->isAdministrator()) {
    header('Location: /user/view.php?uuid=' . urlencode($editingUser->getUUID()));
} else {
    header('Location: /');
}
?>
<?php break ?>
<?php default: ?>
<?php header('Location: /redirect.php'); ?>
<?php endswitch ?>