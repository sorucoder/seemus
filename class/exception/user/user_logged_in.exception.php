<?php
declare(strict_types=1);

final class UserLoggedInException extends RuntimeException {
    public function __construct(?string $message = 'user logged in', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>