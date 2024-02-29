<?php
declare(strict_types=1);

require_once $_SERVER['ROOT_PATH'] . '/class/user.class.php';
require_once $_SERVER['ROOT_PATH'] . '/class/content.class.php';
require_once $_SERVER['ROOT_PATH'] . '/class/permissions.class.php';

$currentUser = User::current();
if (!$currentUser) {
    header('Location: /marcus/seemus/redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
    exit();
}
?>
<?php switch ($_SERVER['REQUEST_METHOD']): ?>
<?php case 'GET': ?>
<?php
$viewingContentUUID = filter_var(
    $_GET['uuid'],
    FILTER_VALIDATE_REGEXP,
    [
        'options' => [
            'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        ],
        'flags' => FILTER_NULL_ON_FAILURE
    ]
);
if (!$viewingContentUUID) {
    header('Location: /marcus/seemus/redirect.php');
    exit();
}

$viewingContent = Content::fromUUID($viewingContentUUID);
if (!$viewingContent) {
    header('Location: /marcus/seemus/redirect.php');
    exit();
} else if (!$currentUser->isAdministrator()) {
    $permissions = Permissions::between($currentUser, $viewingContent);
    if (!($permissions && $permissions->canRead())) {
        header('Location: /marcus/seemus/redirect.php?message=' . urlencode('You\'re Not Permitted...'));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Seemus | View Content Details</title>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/metadata.template.php'; ?>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/bootstrap.template.php'; ?>
</head>
<body>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/header.template.php'; ?>

    <main class="container">
        <h2 class="my-3">"<?= htmlspecialchars($viewingContent->getTitle()) ?>" Details</h2>
        <ul class="list-unstyled my-3">
            <li>Description: <?= htmlspecialchars($viewingContent->getDescription()) ?></li>
            <li>Created: <?= htmlspecialchars($viewingContent->getCreatedDateTime('l, F j, Y g:i:s A')) ?></li>
            <li>
                Last Updated:
                <?php if ($viewingContent->getLastUpdatedDateTime()): ?>
                <?= htmlspecialchars($viewingContent->getLastUpdatedDateTime('l, F j, Y g:i:s A')) ?>
                <?php else: ?>
                Never
                <?php endif ?>
            </li>
            <?php if ($viewingContent->isArchived()): ?>
            <li>Archived</li>
            <?php endif ?>
        </ul>
        <a class="btn btn-primary" href="/marcus/seemus/content/view.php?uuid=<?= urlencode($viewingContent->getUUID()) ?>">
            View
        </a>
        <a class="btn btn-primary" href="/marcus/seemus/content/edit.php?uuid=<?= urlencode($viewingContent->getUUID()) ?>">
            Edit
        </a>
        <?php if (!$viewingContent->isArchived()): ?>
        <a class="btn btn-warning" href="/marcus/seemus/content/archive.php?uuid=<?= urlencode($viewingContent->getUUID()) ?>">
            Archive
        </a>
        <a class="btn btn-warning disabled" aria-disabled="true" tabindex="-1">
            Unarchive
        </a>
        <a class="btn btn-danger disabled" aria-disabled="true" tabindex="-1">
            Delete
        </a>
        <?php else: ?>
        <a class="btn btn-warning disabled" aria-disabled="true" tabindex="-1">
            Archive
        </a>
        <a class="btn btn-warning" href="/marcus/seemus/content/unarchive.php?uuid=<?= urlencode($viewingContent->getUUID()) ?>">
            Unarchive
        </a>
        <a class="btn btn-danger" href="/marcus/seemus/content/delete.php?uuid=<?= urlencode($viewingContent->getUUID()) ?>">
            Delete
        </a>
        <?php endif ?>
    </main>
</body>
</html>
<?php break ?>
<?php default: ?>
<?php header('Location: /marcus/seemus/redirect.php'); ?>
<?php endswitch ?>