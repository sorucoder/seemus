<?php
declare(strict_types=1);

final class InvalidFileDataException extends RuntimeException {
    public function __construct(?string $message = 'invalid file data', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>