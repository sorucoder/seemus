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
<?php
$unarchivingUserUUID = filter_var(
    $_GET['uuid'],
    FILTER_VALIDATE_REGEXP,
    [
        'options' => [
            'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        ],
        'flags' => FILTER_NULL_ON_FAILURE
    ]
);
if (!$unarchivingUserUUID) {
    header('Location: /marcus/seemus/redirect.php');
    exit();
}

$unarchivingUser = User::fromUUID($unarchivingUserUUID);
if (!$unarchivingUser) {
    header('Location: /marcus/seemus/redirect.php');
    exit();
} else if ($unarchivingUser->is($currentUser)) {
    header('Location: /marcus/seemus/redirect.php?message=' . urlencode('You\'re Not Permitted...'));
    exit();
} else if (!$unarchivingUser->isArchived()) {
    header('Location: /marcus/seemus/redirect.php?destination=users&message=' . urlencode('This User is Unarchived...'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Seemus | Unarchive User</title>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/metadata.template.php'; ?>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/bootstrap.template.php'; ?>
</head>
<body>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/header.template.php'; ?>

    <main class="container">
        <h2 class="my-3">You're About to Unarchive <?= htmlspecialchars($unarchivingUser->getName('first')) ?>...</h2>
        <p class="lead">Are you sure you want to do that?</p>
        <form id="unarchiveUserForm" action="/marcus/seemus/user/unarchive.php" method="POST">
            <input type="hidden" name="uuid" value="<?= htmlspecialchars($unarchivingUser->getUUID()) ?>" />
            <button class="btn btn-success" type="submit">Yes</button>
            <a class="btn btn-danger" href="/marcus/seemus/user/view.php?<?= urlencode($unarchivingUser->getUUID()) ?>">No</a>
        </form>
    </main>
</body>
</html>
<?php break ?>
<?php case 'POST': ?>
<?php
$unarchivingUserUUID = filter_var(
    $_POST['uuid'],
    FILTER_VALIDATE_REGEXP,
    [
        'options' => [
            'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        ],
        'flags' => FILTER_NULL_ON_FAILURE
    ]
);

if (!$unarchivingUserUUID) {
    header('Location: /marcus/seemus/redirect.php');
    exit();
}

$unarchivingUser = User::fromUUID($unarchivingUserUUID);

try {
    $unarchivingUser->unarchive();
} catch (UserNotLoggedInException $exception) {
    header('Location: /marcus/seemus/redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
    exit();
} catch (UserNotPermittedException $exception) {
    header('Location: /marcus/seemus/redirect.php?message=' . urlencode('You\'re Not Permitted...'));
    exit();
} catch (UserNotArchivedException $exception) {
    header('Location: /marcus/seemus/redirect.php?destination=users&message=' . urlencode('This User is Not Archived...'));
    exit();
}

header('Location: /marcus/seemus/user/view.php?uuid=' . urlencode($unarchivingUser->getUUID()));
?>
<?php break ?>
<?php default: ?>
<?php header('Location: /marcus/seemus/redirect.php'); ?>
<?php endswitch ?>