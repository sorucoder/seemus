<?php
declare(strict_types=1);

require_once $_SERVER['ROOT_PATH'] . '/class/user.class.php';
?>
<?php switch ($_SERVER['REQUEST_METHOD']): ?>
<?php case 'GET': ?>
<?php
$message = 'Logging Out...';

try {
    User::logout();
} catch (UserNotLoggedInException $exception) {
    $message = 'You\'re Already Logged Out...';
}

header('Location: /marcus/seemus/redirect.php?message=' . urlencode($message));
?>
<?php break ?>
<?php default: ?>
<?php header('Location: /marcus/seemus/redirect.php'); ?>
<?php endswitch ?>