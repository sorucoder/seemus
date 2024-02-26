<?php
declare(strict_types=1);

final class ContentNotArchivedException extends RuntimeException {
    public function __construct(?string $message = 'content not archived', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>