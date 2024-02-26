<?php
declare(strict_types=1);

final class FileNotArchivedException extends RuntimeException {
    public function __construct(?string $message = 'file not archived', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>