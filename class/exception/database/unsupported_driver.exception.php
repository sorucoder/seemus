<?php
declare(strict_types=1);

final class UnsupportedDriverException extends RuntimeException {
    public function __construct(?string $message = 'unsupported driver', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>