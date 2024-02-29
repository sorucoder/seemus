<?php
declare(strict_types=1);

require_once $_SERVER['ROOT_PATH'] . '/class/user.class.php';
require_once $_SERVER['ROOT_PATH'] . '/class/content.class.php';

$currentUser = User::current();
if (!$currentUser) {
    header('Location: /marcus/seemus/redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
    exit();
}
?>
<?php switch ($_SERVER['REQUEST_METHOD']): ?>
<?php case 'GET': ?>
<?php $errors = $_GET['errors'] ?? []; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Seemus | Create Content</title>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/metadata.template.php'; ?>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/bootstrap.template.php'; ?>
    <style>
    #descriptionTextarea {
        height: 10vh;
    }

    #bodyTextarea {
        height: 50vh;
        font-family: monospace;
    }
    </style>
</head>
<body>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/header.template.php'; ?>

    <main class="container">
        <h2 class="my-3">Create New Content</h2>
        
        <form id="createContentForm" class="needs-validation" action="/marcus/seemus/content/create.php" method="POST" novalidate>
            <div class="form-floating my-3">
                <?php if (isset($errors['title-missing'])): ?>
                <input id="titleInput" class="form-control is-invalid" type="text" name="title" placeholder required aria-describedby="#titleFeedback" />
                <label class="form-label" for="titleInput">Title</label>
                <div id="titleFeedback" class="invalid-feedback">Please enter a title.</div>
                <?php elseif (isset($errors['title-invalid'])): ?>
                <input id="titleInput" class="form-control is-invalid" type="text" name="title" placeholder required aria-describedby="#titleFeedback" />
                <label class="form-label" for="titleInput">Title</label>
                <div id="titleFeedback" class="invalid-feedback">The title you used was invalid.</div>
                <?php else: ?>
                <input id="titleInput" class="form-control" type="text" name="title" placeholder required aria-describedby="#titleFeedback" />
                <label class="form-label" for="titleInput">Title</label>
                <div id="titleFeedback"></div>
                <?php endif ?>
            </div>
            <div class="form-floating my-3">
                <?php if (isset($errors['description-missing'])): ?>
                <textarea id="descriptionTextarea" class="form-control is-invalid" name="description" placeholder required aria-describedby="#descriptionFeedback"></textarea>
                <label class="form-label" for="descriptionInput">Description</label>
                <div id="descriptionFeedback" class="invalid-feedback">Please enter a description.</div>
                <?php elseif (isset($errors['description-invalid'])): ?>
                <textarea id="descriptionTextarea" class="form-control is-invalid" name="description" placeholder required aria-describedby="#descriptionFeedback"></textarea>
                <label class="form-label" for="descriptionInput">Description</label>
                <div id="descriptionFeedback" class="invalid-feedback">The description you used was invalid.</div>
                <?php else: ?>
                <textarea id="descriptionTextarea" class="form-control" name="description" placeholder required aria-describedby="#descriptionFeedback"></textarea> 
                <label class="form-label" for="descriptionInput">Description</label>
                <div id="descriptionFeedback"></div>
                <?php endif ?>
            </div>
            <div class="form-floating my-3">
                <?php if (isset($errors['body-missing'])): ?>
                <textarea id="bodyTextarea" class="form-control is-invalid" name="body" placeholder required aria-describedby="#bodyFeedback"></textarea>
                <label class="form-label" for="bodyInput">Body</label>
                <div id="bodyFeedback" class="invalid-feedback">Please enter some content.</div>
                <?php elseif (isset($errors['body-invalid'])): ?>
                <textarea id="bodyTextarea" class="form-control is-invalid" name="body" placeholder required aria-describedby="#bodyFeedback"></textarea>
                <label class="form-label" for="bodyInput">Body</label>
                <div id="bodyFeedback" class="invalid-feedback">The content you wrote contained invalid markup.</div>
                <?php else: ?>
                <textarea id="bodyTextarea" class="form-control" name="body" placeholder required aria-describedby="#bodyFeedback"></textarea> 
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

if (empty($_POST['title'])) {
    $errors []= 'errors[title-missing]=true';
}

if (empty($_POST['description'])) {
    $errors []= 'errors[description-missing]=true';
}

if (empty($_POST['body'])) {
    $errors []= 'errors[body-missing]=true';
}

if (empty($errors)) {
    $creatingContent = NULL;
    try {
        $creatingContent = Content::create(
            $_POST['title'],
            $_POST['description'],
            $_POST['body']
        );
    } catch (UserNotLoggedInException $exception) {
        header('Location: /marcus/seemus/redirect.php?destination=login&message=' . urlencode('You\'re Not Logged In...'));
        exit();
    } catch (InvalidContentDataException $exception) {
        $invalidFields = $exception->getInvalidFields();
        if (in_array('title', $invalidFields)) {
            $errors []= 'errors[invalid-title]=true';
        }
        if (in_array('description', $invalidFields)) {
            $errors []= 'errors[invalid-description]=true';
        }
        if (in_array('body', $invalidFields)) {
            $errors []= 'errors[invalid-body]=true';
        }
    }
    header('Location: /marcus/seemus/content/details.php?uuid=' . urlencode($creatingContent->getUUID()));
} else {
    header('Location: /marcus/seemus/content/create.php?' . implode('&', $errors));
}
?>
<?php break ?>
<?php default: ?>
<?php header('Location: /marcus/seemus/redirect.php'); ?>
<?php endswitch ?>