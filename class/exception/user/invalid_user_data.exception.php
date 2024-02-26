<?php
declare(strict_types=1);

final class InvalidUserDataException extends RuntimeException {
    public function __construct(?string $message = 'invalid user data', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>