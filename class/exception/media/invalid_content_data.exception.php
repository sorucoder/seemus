<?php
declare(strict_types=1);

final class InvalidContentDataException extends RuntimeException {
    public function __construct(?string $message = 'invalid content data', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>