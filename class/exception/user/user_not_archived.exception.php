<?php
declare(strict_types=1);

final class UserNotArchivedException extends RuntimeException {
    public function __construct(?string $message = 'user not archived', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>