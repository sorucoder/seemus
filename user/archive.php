<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/user.class.php';

$currentUser = User::current();
if (!$currentUser) {
    header('Location: /redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
    exit();
} else if (!$currentUser->isAdministrator()) {
    header('Location: /redirect.php?message=' . urlencode('You\'re Not Permitted...'));
    exit();
}
?>
<?php switch ($_SERVER['REQUEST_METHOD']): ?>
<?php case 'GET': ?>
<?php
$archivingUserUUID = filter_var(
    $_GET['uuid'],
    FILTER_VALIDATE_REGEXP,
    [
        'options' => [
            'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        ],
        'flags' => FILTER_NULL_ON_FAILURE
    ]
);
if (!$archivingUserUUID) {
    header('Location: /redirect.php');
    exit();
}

$archivingUser = User::fromUUID($archivingUserUUID);
if (!$archivingUser) {
    header('Location: /redirect.php');
    exit();
} else if ($archivingUser->is($currentUser)) {
    header('Location: /redirect.php?message=' . urlencode('You\'re Not Permitted...'));
    exit();
} else if ($archivingUser->isArchived()) {
    header('Location: /redirect.php?destination=users&message=' . urlencode('This User is Archived...'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Seemus | Archive User</title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/template/metadata.template.php'; ?>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/template/bootstrap.template.php'; ?>
</head>
<body>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/template/header.template.php'; ?>

    <main class="container">
        <h2 class="my-3">You're About to Archive <?= htmlspecialchars($archivingUser->getName('first')) ?>...</h2>
        <p class="lead">Are you sure you want to do that?</p>
        <form id="archiveUserForm" action="/user/archive.php" method="POST">
            <input type="hidden" name="uuid" value="<?= htmlspecialchars($archivingUser->getUUID()) ?>" />
            <button class="btn btn-success" type="submit">Yes</button>
            <a class="btn btn-danger" href="/user/view.php?<?= urlencode($archivingUser->getUUID()) ?>">No</a>
        </form>
    </main>
</body>
</html>
<?php break ?>
<?php case 'POST': ?>
<?php
$archivingUserUUID = filter_var(
    $_POST['uuid'],
    FILTER_VALIDATE_REGEXP,
    [
        'options' => [
            'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        ],
        'flags' => FILTER_NULL_ON_FAILURE
    ]
);

if (!$archivingUserUUID) {
    header('Location: /redirect.php');
    exit();
}

$archivingUser = User::fromUUID($archivingUserUUID);
if (!$archivingUser) {
    header('Location: /redirect.php');
    exit();
}

try {
    $archivingUser->archive();
} catch (UserNotLoggedInException $exception) {
    header('Location: /redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
    exit();
} catch (UserNotPermittedException $exception) {
    header('Location: /redirect.php?message=' . urlencode('You\'re Not Permitted...'));
    exit();
} catch (UserArchivedException $exception) {
    header('Location: /redirect.php?destination=users&message=' . urlencode('This User is Archived...'));
    exit();
}

header('Location: /user/view.php?uuid=' . urlencode($archivingUser->getUUID()));
?>
<?php break ?>
<?php default: ?>
<?php header('Location: /redirect.php'); ?>
<?php endswitch ?>