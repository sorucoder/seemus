<?php
declare(strict_types=1);

final class InvalidPermissionsException extends RuntimeException {
    public function __construct(?string $message = 'invalid permissiomns', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>