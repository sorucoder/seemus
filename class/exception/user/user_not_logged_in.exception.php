<?php
declare(strict_types=1);

final class UserNotLoggedInException extends RuntimeException {
    public function __construct(?string $message = 'user not logged in', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>