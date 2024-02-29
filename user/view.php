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
$viewingUserUUID = filter_var(
    $_GET['uuid'],
    FILTER_VALIDATE_REGEXP,
    [
        'options' => [
            'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        ],
        'flags' => FILTER_NULL_ON_FAILURE
    ]
);

$viewingUser = NULL;
if ($viewingUserUUID) {
    $viewingUser = User::fromUUID($viewingUserUUID);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Seemus | View <?php if ($viewingUser): ?>User<?php else: ?>Users<?php endif ?></title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/template/metadata.template.php'; ?>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/template/bootstrap.template.php'; ?>
</head>
<body>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/template/header.template.php'; ?>

    <main class="container">
        <?php if ($viewingUser): ?>
        <h2 class="my-3">
            <?php if ($currentUser->is($viewingUser)): ?>
            Your Details
            <?php else: ?>
            <?= htmlspecialchars($viewingUser->getName('first-possessive')) ?> Details
            <?php endif ?>
        </h2>
        <ul class="list-unstyled my-3">
            <li>Full Name: <?= htmlspecialchars($viewingUser->getName()) ?></li>
            <li>
                Email:
                <a href="mailto:<?= urlencode($viewingUser->getEmail()) ?>">
                    <?= htmlspecialchars($viewingUser->getEmail()) ?>
                </a>
            </li>
            <li>Role: <?= htmlspecialchars($viewingUser->getRole('name')) ?></li>
            <li>Created: <?= htmlspecialchars($viewingUser->getCreatedDateTime('l, F j, Y g:i:s A')) ?></li>
            <li>
                Last Login:
                <?php if ($viewingUser->getLastLoginDateTime()): ?>
                <?= htmlspecialchars($viewingUser->getLastLoginDateTime('l, F j, Y g:i:s A')) ?>
                <?php else: ?>
                Never
                <?php endif ?>
            </li>
            <?php if ($viewingUser->isArchived()): ?>
            <li>Archived</li>
            <?php endif ?>
        </ul>
        <?php if (!$viewingUser->isArchived()): ?>
        <a class="btn btn-primary" href="/user/edit.php?uuid=<?= urlencode($viewingUser->getUUID()) ?>">
            Edit
        </a>
        <a class="btn btn-warning" href="/user/archive.php?uuid=<?= urlencode($viewingUser->getUUID()) ?>">
            Archive
        </a>
        <a class="btn btn-warning disabled" aria-disabled="true" tabindex="-1">
            Unarchive
        </a>
        <?php else: ?>
        <a class="btn btn-primary disabled" href="/user/edit.php?uuid=<?= urlencode($viewingUser->getUUID()) ?>" aria-disabled="true" tabindex="-1">
            Edit
        </a>
        <a class="btn btn-warning disabled" aria-disabled="true" tabindex="-1">
            Archive
        </a>
        <a class="btn btn-warning" href="/user/unarchive.php?uuid=<?= urlencode($viewingUser->getUUID()) ?>">
            Unarchive
        </a>
        <?php endif ?>
        <?php else: ?>
        <h2 class="my-3">Users</h2>
        <a class="btn btn-primary" href="/user/create.php">Create</a>
        <table class="table table-hover align-middle my-3">
            <thead>
                <tr>
                    <th>UUID</th>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (User::all() as $_ => $user): ?>
                <?php if (!$user->is($currentUser)): ?>
                <tr>
                    <td><?= htmlspecialchars($user->getUUID()) ?></td>
                    <td><?= htmlspecialchars($user->getName()) ?></td>
                    <td>
                        <a class="btn btn-info" href="/user/view.php?uuid=<?= urlencode($user->getUUID()) ?>">
                            View
                        </a>
                        <?php if (!$user->isArchived()): ?>
                        <a class="btn btn-primary" href="/user/edit.php?uuid=<?= urlencode($user->getUUID()) ?>">
                            Edit
                        </a>
                        <a class="btn btn-warning" href="/user/archive.php?uuid=<?= urlencode($user->getUUID()) ?>">
                            Archive
                        </a>
                        <a class="btn btn-warning disabled" aria-disabled="true" tabindex="-1">
                            Unarchive
                        </a>
                        <?php else: ?>
                        <a class="btn btn-primary disabled" href="/user/edit.php?uuid=<?= urlencode($user->getUUID()) ?>" aria-disabled="true" tabindex="-1">
                            Edit
                        </a>
                        <a class="btn btn-warning disabled" aria-disabled="true" tabindex="-1">
                            Archive
                        </a>
                        <a class="btn btn-warning" href="/user/unarchive.php?uuid=<?= urlencode($user->getUUID()) ?>">
                            Unarchive
                        </a>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endif ?>
                <?php endforeach ?>
            </tbody>
        </table>
        <?php endif ?>
    </main>
</body>
</html>
<?php break ?>
<?php default: ?>
<?php header('Location: /redirect.php'); ?>
<?php endswitch ?>