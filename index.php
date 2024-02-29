<?php
declare(strict_types=1);
$_SESSION['user'] = NULL;
session_destroy();
?>
<?php switch ($_SERVER['REQUEST_METHOD']): ?>
<?php case 'GET': ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Seemus</title>
    <?php include_once './template/metadata.template.php'; ?>
    <?php include_once './template/bootstrap.template.php'; ?>
</head>
<body class="bg-body">
    <?php include_once './template/header.template.php'; ?>
    <main class="container">
        <h2 class="my-3">Welcome to Seemus!</h2>
    </main>
</body>
</html>
<?php break ?>
<?php default: ?>
<?php http_response_code(405); ?>
<?php endswitch ?>