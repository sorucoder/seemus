<?php
declare(strict_types=1);

final class UserNotPermittedException extends RuntimeException {
    public function __construct(?string $message = 'user not permitted', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>