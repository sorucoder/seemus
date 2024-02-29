<?php
declare(strict_types=1);

final class InvalidContentDataException extends RuntimeException {
    private array $invalidFields;
    
    public function __construct(string|array $invalidFields = [], ?string $message = 'invalid content data', ?int $code = 0, ?Throwable $previous = NULL) {
        $invalidFields = is_string($invalidFields) ? [$invalidFields] : $invalidFields;
        $message = $message ?? match(count($invalidFields)) {
            0 => 'invalid content data',
            1 => 'invalid ' . $invalidFields[0] . ' field',
            default => 'invalid' . implode(', ', array_slice($invalidFields, 0, -2)) . ' and ' . $invalidFields[count($invalidFields)-1] . ' fields'
        };
        parent::__construct($message, $code, $previous);
    }

    public function getInvalidFields(): array {
        return $this->invalidFields;
    }
}
?>