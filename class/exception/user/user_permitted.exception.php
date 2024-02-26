<?php
declare(strict_types=1);

class UserPermittedException extends RuntimeException {
    public function __construct(?string $message = 'user permitted', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>