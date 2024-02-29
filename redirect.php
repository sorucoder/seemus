<?php
declare(strict_types=1);

$message = $_GET['message'] ?? 'Something Went Wrong...';

$destination = match ($_GET['destination']) {
    'login' => [
        'url' => '/marcus/seemus/user/login.php',
        'name' => 'Login'
    ],
    'users' => [
        'url' => '/marcus/seemus/user/view.php',
        'name' => 'Users'
    ],
    'content' => [
        'url' => '/marcus/seemus/content/view.php',
        'name' => 'Content'
    ],
    default => [
        'url' => '/marcus/seemus/',
        'name' => 'Home'
    ]
};

header("Refresh: 3;" . $destination['url']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Seemus | Logout</title>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/metadata.template.php'; ?>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/bootstrap.template.php'; ?>
</head>
<body>
    <?php include_once $_SERVER['ROOT_PATH'] . '/template/header.template.php'; ?>

    <main class="container">
        <h2 class="my-3"><?= htmlspecialchars($message) ?></h2>
        <p class="lead my-3">You will be redirected to <a href="<?= htmlspecialchars($destination['url']) ?>"><?= htmlspecialchars($destination['name']) ?></a> in 3 seconds...</p>
    </main>
</body>
</html>