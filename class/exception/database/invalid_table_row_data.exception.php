<?php
declare(strict_types=1);

final class InvalidTableRowDataException extends LogicException {
    public function __construct(?string $message = 'invalid table row data', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>