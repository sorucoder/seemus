<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/user.class.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/content.class.php';

$currentUser = User::current();
if (!$currentUser) {
    header('Location: /redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
    exit();
}
?>
<?php switch ($_SERVER['REQUEST_METHOD']): ?>
<?php case 'GET': ?>
<?php
$deletingContentUUID = filter_var(
    $_GET['uuid'],
    FILTER_VALIDATE_REGEXP,
    [
        'options' => [
            'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        ],
        'flags' => FILTER_NULL_ON_FAILURE
    ]
);
if (!$deletingContentUUID) {
    header('Location: /redirect.php');
    exit();
}

$deletingContent = Content::fromUUID($deletingContentUUID);
if (!$deletingContent) {
    header('Location: /redirect.php');
    exit();
} else if (!$deletingContent->isArchived()) {
    header('Location: /redirect.php?destination=content&message=' . urlencode('This Content is Unarchived...'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Seemus | Delete Content</title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/template/metadata.template.php'; ?>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/template/bootstrap.template.php'; ?>
</head>
<body>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/template/header.template.php'; ?>

    <main class="container">
        <h2 class="my-3">You're About to Delete "<?= htmlspecialchars($deletingContent->getTitle()) ?>"...</h2>
        <p class="lead">Are you sure you want to do that? You can't undo this.</p>
        <form id="deleteContentForm" action="/content/delete.php" method="POST">
            <input type="hidden" name="uuid" value="<?= htmlspecialchars($deletingContent->getUUID()) ?>" />
            <button class="btn btn-success" type="submit">Yes</button>
            <a class="btn btn-danger" href="/content/details.php?uuid=<?= urlencode($deletingContent->getUUID()) ?>">No</a>
        </form>
    </main>
</body>
</html>
<?php break ?>
<?php case 'POST': ?>
<?php
$deletingContentUUID = filter_var(
    $_POST['uuid'],
    FILTER_VALIDATE_REGEXP,
    [
        'options' => [
            'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        ],
        'flags' => FILTER_NULL_ON_FAILURE
    ]
);

if (!$deletingContentUUID) {
    header('Location: /redirect.php');
    exit();
}

$deletingContent = Content::fromUUID($deletingContentUUID);
if (!$deletingContent) {
    header('Location: /redirect.php');
    exit();
}

try {
    $deletingContent->delete();
} catch (UserNotLoggedInException $exception) {
    header('Location: /redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
    exit();
} catch (UserNotPermittedException $exception) {
    header('Location: /redirect.php?message=' . urlencode('You\'re Not Permitted...'));
    exit();
} catch (ContentNotArchivedException $exception) {
    header('Location: /redirect.php?destination=users&message=' . urlencode('This Content is Not Archived...'));
    exit();
}

header('Location: /content/view.php');
?>
<?php break ?>
<?php default: ?>
<?php header('Location: /redirect.php'); ?>
<?php endswitch ?>