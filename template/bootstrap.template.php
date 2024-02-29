<?php
declare(strict_types=1);
?>
<link rel="stylesheet" href="/style/bootstrap.min.css" />
<script src="/script/bootstrap.bundle.js"></script>
<script>
if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
    document.documentElement.setAttribute('data-bs-theme', 'dark');
} else {
    document.documentElement.setAttribute('data-bs-theme', 'light');
}
</script>
