<?php
declare(strict_types=1);

final class FileArchivedException extends RuntimeException {
    public function __construct(?string $message = 'file archived', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>