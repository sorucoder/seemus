<?php
declare(strict_types=1);

require_once $_SERVER['ROOT_PATH'] . '/class/user.class.php';
require_once $_SERVER['ROOT_PATH'] . '/class/content.class.php';

$currentUser = User::current();
if (!$currentUser) {
    header('Location: /redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
    exit();
}
?>
<?php switch ($_SERVER['REQUEST_METHOD']): ?>
<?php case 'GET': ?>
<?php
$unarchivingContentUUID = filter_var(
    $_GET['uuid'],
    FILTER_VALIDATE_REGEXP,
    [
        'options' => [
            'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        ],
        'flags' => FILTER_NULL_ON_FAILURE
    ]
);
if (!$unarchivingContentUUID) {
    header('Location: /redirect.php');
    exit();
}

$unarchivingContent = Content::fromUUID($unarchivingContentUUID);
if (!$unarchivingContent) {
    header('Location: /redirect.php');
    exit();
} else if (!$unarchivingContent->isArchived()) {
    header('Location: /redirect.php?destination=content&message=' . urlencode('This Content is Unarchived...'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Seemus | Unarchive Content</title>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/metadata.template.php'; ?>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/bootstrap.template.php'; ?>
</head>
<body>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/header.template.php'; ?>

    <main class="container">
        <h2 class="my-3">You're About to Unarchive "<?= htmlspecialchars($unarchivingContent->getTitle()) ?>"...</h2>
        <p class="lead">Are you sure you want to do that?</p>
        <form id="archiveContentForm" action="/content/archive.php" method="POST">
            <input type="hidden" name="uuid" value="<?= htmlspecialchars($unarchivingContent->getUUID()) ?>" />
            <button class="btn btn-success" type="submit">Yes</button>
            <a class="btn btn-danger" href="/content/details.php?<?= urlencode($unarchivingContent->getUUID()) ?>">No</a>
        </form>
    </main>
</body>
</html>
<?php break ?>
<?php case 'POST': ?>
<?php
$unarchivingContentUUID = filter_var(
    $_POST['uuid'],
    FILTER_VALIDATE_REGEXP,
    [
        'options' => [
            'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        ],
        'flags' => FILTER_NULL_ON_FAILURE
    ]
);

if (!$unarchivingContentUUID) {
    header('Location: /redirect.php');
    exit();
}

$unarchivingContent = Content::fromUUID($unarchivingContentUUID);
if (!$unarchivingContent) {
    header('Location: /redirect.php');
    exit();
}

try {
    $unarchivingContent->unarchive();
} catch (UserNotLoggedInException $exception) {
    header('Location: /redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
    exit();
} catch (UserNotPermittedException $exception) {
    header('Location: /redirect.php?message=' . urlencode('You\'re Not Permitted...'));
    exit();
} catch (ContentNotArchivedException $exception) {
    header('Location: /redirect.php?destination=users&message=' . urlencode('This Content is Unarchived...'));
    exit();
}

header('Location: /content/details.php?uuid=' . urlencode($unarchivingContent->getUUID()));
?>
<?php break ?>
<?php default: ?>
<?php header('Location: /redirect.php'); ?>
<?php endswitch ?>