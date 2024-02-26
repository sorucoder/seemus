<?php
declare(strict_types=1);

final class InvalidParameterDataException extends LogicException {
    public function __construct(?string $message = 'parameter data is invalid', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>