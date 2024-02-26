<?php
declare(strict_types=1);

final class UserArchivedException extends RuntimeException {
    public function __construct(?string $message = 'user archived', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>