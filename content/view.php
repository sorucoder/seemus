<?php
declare(strict_types=1);

require_once $_SERVER['ROOT_PATH'] . '/class/user.class.php';
require_once $_SERVER['ROOT_PATH'] . '/class/content.class.php';

$currentUser = User::current();
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

$viewingContent = NULL;
if ($viewingContentUUID) {
    $viewingContent = Content::fromUUID($viewingContentUUID);
    if (!$viewingContent) {
        header('Location: /redirect.php');
        exit();
    }

    if ($currentUser && !$currentUser->isAdministrator()) {
        $permissions = Permissions::between($currentUser, $viewingContent);
        if (!($permissions && $permissions->canRead())) {
            header('Location: /redirect.php?message=' . urlencode('You\'re Not Permitted...'));
            exit();
        }
    }
}
?>
<?php if ($viewingContent): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= htmlspecialchars($viewingContent->getTitle()) ?></title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="author" content="<?= htmlspecialchars($viewingContent->getAuthor()->getName()) ?>" />
    <meta name="description" content="<?= htmlspecialchars($viewingContent->getDescription()) ?>" />
    <meta name="generator" content="Seemus" />
</head>
<body><?= $viewingContent->getBody() ?></body>
</html>
<?php else: ?>
<?php
$currentUser = User::current();
if (!$currentUser) {
    header('Location: /redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Seemus | View Content</title>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/metadata.template.php'; ?>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/bootstrap.template.php'; ?>
</head>
<body>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/header.template.php'; ?>

    <main class="container">
        <?php if ($currentUser->isAdministrator()): ?>
        <h2 class="my-3">Content</h2>
        <a class="btn btn-primary" href="/content/create.php">Create</a>
        <table class="table table-hover align-middle my-3">
            <thead>
                <tr>
                    <th>UUID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (Content::all() as $_ => $content): ?>
                <tr>
                    <td><?= htmlspecialchars($content->getUUID()) ?></td>
                    <td><?= htmlspecialchars($content->getTitle()) ?></td>
                    <td>
                        <?php if (!$content->getAuthor()->is($currentUser)): ?>
                        <a href="/user/view.php?uuid=<?= urlencode($content->getAuthor()->getUUID()) ?>">
                            <?= htmlspecialchars($content->getAuthor()->getName()) ?>
                        </a>
                        <?php else: ?>
                        You
                        <?php endif ?>
                    </td>
                    <td>
                        <a class="btn btn-primary" href="/content/view.php?uuid=<?= urlencode($content->getUUID()) ?>">
                            View
                        </a>
                        <a class="btn btn-info" href="/content/details.php?uuid=<?= urlencode($content->getUUID()) ?>">
                            Details
                        </a>
                        <a class="btn btn-primary" href="/content/edit.php?uuid=<?= urlencode($content->getUUID()) ?>">
                            Edit
                        </a>
                        <?php if (!$content->isArchived()): ?>
                        <a class="btn btn-warning" href="/content/archive.php?uuid=<?= urlencode($content->getUUID()) ?>">
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
                        <a class="btn btn-warning" href="/content/unarchive.php?uuid=<?= urlencode($content->getUUID()) ?>">
                            Unarchive
                        </a>
                        <a class="btn btn-danger" href="/content/delete.php?uuid=<?= urlencode($content->getUUID()) ?>">
                            Delete
                        </a>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <?php else: ?>
        <h2 class="my-3">Your Content</h2>
        <a class="btn btn-primary" href="/content/create.php">Create</a>
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>UUID</th>
                    <th>Title</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (Content::allByAuthor($currentUser) as $_ => $content): ?>
                <tr>
                    <td><?= htmlspecialchars($content->getUUID()) ?></td>
                    <td><?= htmlspecialchars($content->getTitle()) ?></td>
                    <td>
                        <a class="btn btn-primary" href="/content/view.php?uuid=<?= urlencode($content->getUUID()) ?>">
                            View
                        </a>
                        <a class="btn btn-info" href="/content/details.php?uuid=<?= urlencode($content->getUUID()) ?>">
                            Details
                        </a>
                        <a class="btn btn-primary" href="/content/edit.php?uuid=<?= urlencode($content->getUUID()) ?>">
                            Edit
                        </a>
                        <?php if (!$content->isArchived()): ?>
                        <a class="btn btn-warning" href="/content/archive.php?uuid=<?= urlencode($content->getUUID()) ?>">
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
                        <a class="btn btn-warning" href="/content/unarchive.php?uuid=<?= urlencode($content->getUUID()) ?>">
                            Unarchive
                        </a>
                        <a class="btn btn-danger" href="/content/delete.php?uuid=<?= urlencode($content->getUUID()) ?>">
                            Delete
                        </a>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <?php endif ?>
    </main>
</body>
</html>
<?php endif ?>
<?php break ?>
<?php default: ?>
<?php header('Location: /redirect.php'); ?>
<?php endswitch ?>