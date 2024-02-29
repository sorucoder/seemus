<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/user.class.php';

$currentURL = $_SERVER['REQUEST_URI'];
$currentUser = User::current();
?>
<header id="mainHeader" class="text-bg-primary text-center">
    <h1 class="display-1">Seemus</h1>
    <nav id="mainNavigation" class="navbar navbar-expand-lg text-bg-secondary">
        <div class="container">
            <span class="navbar-brand"></span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigationMenu" aria-controls="mainNavigationMenu" aria-expanded="false" aria-label="Toggle main navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div id="mainNavigationMenu" class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <?php if ($currentURL === '/' || $currentURL === '/index.php'): ?>
                        <a class="nav-link active" aria-current="page" href="/">Home</a>
                        <?php else: ?>
                        <a class="nav-link" href="/">Home</a>
                        <?php endif ?>
                    </li>
                    <?php if ($currentUser): ?>
                    <li class="nav-item">
                        <?php if (str_starts_with($currentURL, '/content/')): ?>
                        <a class="nav-link active" aria-current="page" href="/content/view.php">Content</a>
                        <?php else: ?>
                        <a class="nav-link" href="/content/view.php">Content</a>
                        <?php endif ?>
                    </li>
                    <!-- TODO: Implement file interface
                    <li class="nav-item">
                        <?php if (str_starts_with($currentURL, '/file/')): ?>
                        <a class="nav-link active" aria-current="page" href="/file/view.php">Files</a>
                        <?php else: ?>
                        <a class="nav-link" href="/file/view.php">Files</a>
                        <?php endif ?>
                    </li>
                    -->
                    <?php if ($currentUser->isAdministrator()): ?>
                    <li class="nav-item">
                        <?php if (str_starts_with($currentURL, '/user/')): ?>
                        <a class="nav-link active" aria-current="page" href="/user/view.php">Users</a>
                        <?php else: ?>
                        <a class="nav-link" href="/user/view.php">Users</a>
                        <?php endif ?>
                    </li>
                    <!-- TODO: Implement audit interface
                    <li class="nav-item">
                        <?php if (str_starts_with($currentURL, '/audit/')): ?>
                        <a class="nav-link active" aria-current="page" href="/audit/view.php">Audit</a>
                        <?php else: ?>
                        <a class="nav-link" href="/audit/view.php">Audit</a>
                        <?php endif ?>
                    </li>
                    -->
                    <?php endif ?>
                    <?php endif ?>
                </ul>
                <?php if ($currentUser): ?>
                <span class="navbar-text">Hi, <?= htmlspecialchars($currentUser->getName('first')) ?>!</span>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <?php if ($currentURL === '/user/edit.php'): ?>
                        <a class="nav-link active" aria-current="page" href="/user/edit.php">Settings</a>
                        <?php else: ?>
                        <a class="nav-link" href="/user/edit.php">Settings</a>
                        <?php endif ?>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/user/logout.php">Logout</a>
                    </li>
                </ul>
                <?php else: ?>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <?php if ($currentURL === '/user/login.php'): ?>
                        <a class="nav-link active" aria-current="page" href="/user/login.php">Login</a>
                        <?php else: ?>
                        <a class="nav-link" href="/user/login.php">Login</a>
                        <?php endif ?>
                    </li>
                </ul>
                <?php endif ?>
            </div>
        </div>
    </nav>
</header>