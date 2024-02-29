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
$editingContentUUID = filter_var(
    $_GET['uuid'],
    FILTER_VALIDATE_REGEXP,
    [
        'options' => [
            'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        ],
        'flags' => FILTER_NULL_ON_FAILURE
    ]
);
if (!$editingContentUUID) {
    header('Location: /marcus/seemus/redirect.php');
    exit();
}

$editingContent = Content::fromUUID($editingContentUUID);
if (!$editingContent) {
    header('Location: /marcus/seemus/redirect.php');
    exit();
} else if (!$currentUser->isAdministrator()) {
    $permissions = Permissions::between($currentUser, $editingContent);
    if (!($permissions && $permissions->canWrite())) {
        header('Location: /marcus/seemus/redirect.php?message=' . urlencode('You\'re Not Permitted...'));
        exit();
    }
}

$errors = $_GET['errors'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Seemus | Edit Content</title>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/metadata.template.php'; ?>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/bootstrap.template.php'; ?>
</head>
<body>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/header.template.php'; ?>

    <main class="container">
        <h2 class="my-3">Edit "<?= htmlspecialchars($editingContent->getTitle()) ?>"</h2>
        
        <form id="editContentForm" class="needs-validation" action="/marcus/seemus/content/edit.php" method="POST" novalidate>
            <input type="hidden" name="uuid" value="<?= htmlspecialchars($editingContent->getUUID()) ?>" />
            <div class="form-floating my-3">
                <?php if (isset($errors['title-missing'])): ?>
                <input id="titleInput" class="form-control is-invalid" type="text" name="title" value="<?= htmlspecialchars($editingContent->getTitle()) ?>" placeholder required aria-describedby="#titleFeedback" />
                <label class="form-label" for="titleInput">Title</label>
                <div id="titleFeedback" class="invalid-feedback">Please enter a title.</div>
                <?php elseif (isset($errors['title-invalid'])): ?>
                <input id="titleInput" class="form-control is-invalid" type="text" name="title" value="<?= htmlspecialchars($editingContent->getTitle()) ?>" placeholder required aria-describedby="#titleFeedback" />
                <label class="form-label" for="titleInput">Title</label>
                <div id="titleFeedback" class="invalid-feedback">The title you used was invalid.</div>
                <?php else: ?>
                <input id="titleInput" class="form-control" type="text" name="title" value="<?= htmlspecialchars($editingContent->getTitle()) ?>" placeholder required aria-describedby="#titleFeedback" />
                <label class="form-label" for="titleInput">Title</label>
                <div id="titleFeedback"></div>
                <?php endif ?>
            </div>
            <div class="form-floating my-3">
                <?php if (isset($errors['description-missing'])): ?>
                <textarea id="descriptionTextarea" class="form-control is-invalid" style="height: 10vh;" name="description" placeholder required aria-describedby="#descriptionFeedback"><?= htmlspecialchars($editingContent->getDescription()) ?></textarea>
                <label class="form-label" for="descriptionInput">Description</label>
                <div id="descriptionFeedback" class="invalid-feedback">Please enter a description.</div>
                <?php elseif (isset($errors['description-invalid'])): ?>
                <textarea id="descriptionTextarea" class="form-control is-invalid" style="height: 10vh;" name="description" placeholder required aria-describedby="#descriptionFeedback"><?= htmlspecialchars($editingContent->getDescription()) ?></textarea>
                <label class="form-label" for="descriptionInput">Description</label>
                <div id="descriptionFeedback" class="invalid-feedback">The description you used was invalid.</div>
                <?php else: ?>
                <textarea id="descriptionTextarea" class="form-control" style="height: 10vh;" name="description" placeholder required aria-describedby="#descriptionFeedback"><?= htmlspecialchars($editingContent->getDescription()) ?></textarea> 
                <label class="form-label" for="descriptionInput">Description</label>
                <div id="descriptionFeedback"></div>
                <?php endif ?>
            </div>
            <div class="form-floating my-3">
                <?php if (isset($errors['body-missing'])): ?>
                <textarea id="bodyTextarea" class="form-control is-invalid" style="height: 50vh;" name="body" placeholder required aria-describedby="#bodyFeedback"><?= htmlspecialchars($editingContent->getBody()); ?></textarea>
                <label class="form-label" for="bodyInput">Body</label>
                <div id="bodyFeedback" class="invalid-feedback">Please enter some content.</div>
                <?php elseif (isset($errors['body-invalid'])): ?>
                <textarea id="bodyTextarea" class="form-control is-invalid" style="height: 50vh;" name="body" placeholder required aria-describedby="#bodyFeedback"><?= htmlspecialchars($editingContent->getBody()); ?></textarea>
                <label class="form-label" for="bodyInput">Body</label>
                <div id="bodyFeedback" class="invalid-feedback">The content you wrote contained invalid markup.</div>
                <?php else: ?>
                <textarea id="bodyTextarea" class="form-control" style="height: 50vh;" name="body" placeholder required aria-describedby="#bodyFeedback"><?= htmlspecialchars($editingContent->getBody()); ?></textarea> 
                <label class="form-label" for="bodyInput">Body</label>
                <div id="bodyFeedback"></div>
                <?php endif ?>
            </div>
            <button class="btn btn-success" type="submit">Create</button>
            <a class="btn btn-danger" href="/marcus/seemus/content/view.php">Cancel</a>
        </form>

        <!-- Implement client-side validation -->
    </main>
</body>
</html>
<?php break ?>
<?php case 'POST': ?>
<?php
$errors = [];

$editingContentUUID = filter_var(
    $_GET['uuid'],
    FILTER_VALIDATE_REGEXP,
    [
        'options' => [
            'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        ],
        'flags' => FILTER_NULL_ON_FAILURE
    ]
);
if (!$editingContentUUID) {
    header('Location: /marcus/seemus/redirect.php');
    exit();
}

$editingContent = Content::fromUUID($editingContentUUID);

if (!empty($_POST['title'])) {
    try {
        $editingContent->changeTitle($_POST['title']);
    } catch (UserNotLoggedInException $exception) {
        header('Location: /marcus/seemus/redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
        exit();
    } catch (UserNotPermittedException $exception) {
        header('Location: /marcus/seemus/redirect.php?message=' . urlencode('You\'re Not Permitted...'));
        exit();
    } catch (InvalidContentDataException $exception) {
        $errors []= 'errors[title-invalid]=true';
    }
} else {
    $errors []= 'errors[title-missing]=true';
}

if (!empty($_POST['description'])) {
    try {
        $editingContent->changeDescription($_POST['description']);
    } catch (UserNotLoggedInException $exception) {
        header('Location: /marcus/seemus/redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
        exit();
    } catch (UserNotPermittedException $exception) {
        header('Location: /marcus/seemus/redirect.php?message=' . urlencode('You\'re Not Permitted...'));
        exit();
    } catch (InvalidContentDataException $exception) {
        $errors []= 'errors[description-invalid]=true';
    }
} else {
    $errors []= 'errors[description-missing]=true';
}

if (!empty($_POST['body'])) {
    try {
        $editingContent->changeBody($_POST['body']);
    } catch (UserNotLoggedInException $exception) {
        header('Location: /marcus/seemus/redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
        exit();
    } catch (UserNotPermittedException $exception) {
        header('Location: /marcus/seemus/redirect.php?message=' . urlencode('You\'re Not Permitted...'));
        exit();
    } catch (InvalidContentDataException $exception) {
        $errors []= 'errors[body-invalid]=true';
    }
} else {
    $errors []= 'errors[body-missing]=true';
}

if (empty($errors)) {
    header('Location: /marcus/seemus/content/view.php?uuid=' . urlencode($editingContent->getUUID()));
} else {
    header('Location: /marcus/seemus/content/edit.php?uuid=' . urlencode($editingContent->getUUID()) . '&' . implode('&', $errors));
}
?>
<?php break ?>
<?php default: ?>
<?php header('Location: /marcus/seemus/redirect.php'); ?>
<?php endswitch ?>