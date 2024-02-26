<?php
declare(strict_types=1);

require_once './class/exception/database/connection.exception.php';
require_once './class/exception/database/invalid_column_data.exception.php';
require_once './class/exception/database/invalid_group_by_data.exception.php';
require_once './class/exception/database/invalid_identifier.exception.php';
require_once './class/exception/database/invalid_limit_data.exception.php';
require_once './class/exception/database/invalid_order_by_data.exception.php';
require_once './class/exception/database/invalid_parameter_data.exception.php';
require_once './class/exception/database/invalid_table_row_data.exception.php';
require_once './class/exception/database/invalid_value.exception.php';
require_once './class/exception/database/unsupported_driver.exception.php';

final class Database {
    private readonly string $driver;
    private readonly string $host;
    private readonly string $username;
    private readonly string $password;
    private readonly string $schema;
    private readonly PDO $connection;

    private function __construct(string $driver, string $host, string $user, string $password, string $schema) {
        $this->driver = $driver;
        $this->host = $host;
        $this->username = $user;
        $this->password = $password;
        $this->schema = $schema;

        $this->connection = new PDO("$driver:host=$host;dbname=$schema", $user, $password);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function connect(): self {
        if (!isset($_SESSION['database'])) {
            $driver = $_SERVER['DATABASE_DRIVER'] ?? throw new ConnectionException('driver is not set');
            $host = $_SERVER['DATABASE_HOST'] ?? throw new ConnectionException('host is not set');
            $user = $_SERVER['DATABASE_USER'] ?? throw new ConnectionException('user is not set');
            $password = $_SERVER['DATABASE_PASSWORD'] ?? throw new ConnectionException('password is not set');
            $schema = $_SERVER['DATABASE_SCHEMA'] ?? throw new ConnectionException('schema is not set');

            $_SESSION['database'] = new self(
                $driver,
                $host,
                $user,
                $password,
                $schema
            );
        }
        return $_SESSION['database'];
    }

    private function sanitizeIdentifier(mixed $identifier): string {
        if (!is_string($identifier)) {
            throw new InvalidIdentifierException('invalid type');
        }

        switch ($this->driver) {
        case 'mysql':
            $validatedIdentifier = filter_var(
                $identifier,
                FILTER_VALIDATE_REGEXP,
                [
                    'options' => [
                        'regexp' => '/^[^\0`\x{10000}-\x{10FFFF}]{1,64}$/u'
                    ],
                    'flags' => FILTER_NULL_ON_FAILURE
                ]
            );
            if (!$validatedIdentifier) {
                throw new InvalidIdentifierException("invalid identifier \"$identifier\"");
            }
            $sanitizedIdentifier = "`$validatedIdentifier`";
            return $sanitizedIdentifier;
        default:
            throw new UnsupportedDriverException("cannot sanitize identifier for \"{$this->driver}\" driver");
        }
    }

    private function sanitizeNullValue(null $value, ?string $type = NULL): ?string {
        switch ($this->driver) {
        case 'mysql':
            $type ??= 'NULL';
            if ($type !== 'NULL') {
                throw new InvalidValueException("invalid type \"$type\" for null value");
            }
            return 'NULL';
        default:
            throw new UnsupportedDriverException("cannot sanitize null value for \"{$this->driver}\" driver");
        }
    }

    private function sanitizeBooleanValue(bool $value, ?string $type = NULL): string {
        switch ($this->driver) {
        case 'mysql':
            $type ??= 'BOOLEAN';
            if (!preg_match('/^(?:BOOL|BOOLEAN)$/i', $type)) {
                throw new InvalidValueException("invalid type \"$type\" for boolean");
            }
            return $value ? 'TRUE' : 'FALSE';
        default:
            throw new UnsupportedDriverException("cannot sanitize boolean value for \"{$this->driver}\" driver");
        }
    }

    private function sanitizeIntegerValue(int $value, ?string $type = NULL): string {
        switch ($this->driver) {
        case 'mysql':
            $type ??= 'BIGINT';
            $attributes = [];
            if (preg_match('/^BIT(?:\(([0-9]{1,2})\))?$/i', $type, $attributes)) {
                $size = (int)($attributes[1] ?? 1);
                if ($size > 64) {
                    throw new InvalidValueException("invalid type \"$type\" for integer value");
                }

                if ($value < 0 || $value > (1<<$size)-1) {
                    throw new InvalidValueException("invalid integer value ($value) for type \"$type\"");
                }
                
                return sprintf('b\'%0*b\'', $size, $value);
            } else if (preg_match('/^TINYINT(?:\([0-9]{1,3}\))?$/i', $type)) {
                if ($value < -128 || $value > 127) {
                    throw new InvalidValueException("invalid integer value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^TINYINT(?:\([0-9]{1,3}\))? UNSIGNED(?: ZEROFILL)$/i', $type)) {
                if ($value < 0 || $value > 255) {
                    throw new InvalidValueException("invalid integer value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^SMALLINT(?:\([0-9]{1,3}\))?$/i', $type)) {
                if ($value < -32768 || $value > 32767) {
                    throw new InvalidValueException("invalid integer value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^SMALLINT(?:\([0-9]{1,3}\))? UNSIGNED(?: ZEROFILL)?$/i', $type)) {
                if ($value < 0 || $value > 65535) {
                    throw new InvalidValueException("invalid integer value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^MEDIUMINT(?:\([0-9]{1,3}\))?$/i', $type)) {
                if ($value < -8388608 || $value > 8388607) {
                    throw new InvalidValueException("invalid integer value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^MEDIUMINT(?:\([0-9]{1,3}\))? UNSIGNED(?: ZEROFILL)?$/i', $type)) {
                if ($value < 0 || $value > 16777215) {
                    throw new InvalidValueException("invalid integer value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^(?:INT|INTEGER)(?:\([0-9]{1,3}\))?$/i', $type)) {
                if ($value < -2147483648 || $value > 2147483647) {
                    throw new InvalidValueException("invalid integer value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^(?:INT|INTEGER)(?:\([0-9]{1,3}\))? UNSIGNED(?: ZEROFILL)?$/i', $type)) {
                if ($value < 0 || $value > 4294967295) {
                    throw new InvalidValueException("invalid integer value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^(?:BIGINT(?:\([0-9]{1,3}\))?(?: UNSIGNED)?(?: ZEROFILL)?|SERIAL)$/i', $type)) {
                return sprintf('%d', $value);
            } else if (preg_match('/^(?:DECIMAL|DEC|NUMERIC|FIXED)(?:\(([0-9]{1,2}(?:,([0-9]{1,2}))?)\))?$/i', $type, $attributes)) {
                $precision = (int)($attributes[1] ?? 10);
                if ($precision > 65) {
                    throw new InvalidValueException("invalid type \"$type\" for integer value");
                }

                $scale = (int)($attributes[2] ?? 0);
                if ($scale > 30) {
                    throw new InvalidValueException("invalid type \"$type\" for integer value");
                }

                return substr(sprintf('%.*f', $scale, $value), -$precision);
            } else if (preg_match('/^(?:FLOAT|DOUBLE|DOUBLE PRECISION|REAL)$/i', $type)) {
                return sprintf('%G', $value);
            } else {
                throw new InvalidValueException("invalid type \"$type\" for integer value");
            }
        default:
            throw new UnsupportedDriverException("cannot sanitize integer value for \"{$this->driver}\" driver");
        }
    }

    private function sanitizeFloatValue(float $value, ?string $type = NULL): string {
        switch ($this->driver) {
        case 'mysql':
            $type ??= 'DOUBLE';
            $attributes = [];
            if (preg_match('/^BIT(?:\(([0-9]{1,2})\))?$/i', $type, $attributes)) {
                $size = (int)($attributes[1] ?? 1);
                if ($size > 64) {
                    throw new InvalidValueException("invalid type \"$type\" for float value");
                }

                $value = $value >= 0 ? floor($value) : ceil($value);
                if ($value < 0 || $value > (1<<$size)-1) {
                    throw new InvalidValueException("invalid float value ($value) for type \"$type\"");
                }
                
                return sprintf('b\'%0*b\'', $size, $value);
            } else if (preg_match('/^TINYINT(?:\([0-9]{1,3}\))?$/i', $type)) {
                $value = $value >= 0 ? floor($value) : ceil($value);
                if ($value < -128 || $value > 127) {
                    throw new InvalidValueException("invalid float value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^TINYINT(?:\([0-9]{1,3}\))? UNSIGNED(?: ZEROFILL)$/i', $type)) {
                $value = $value >= 0 ? floor($value) : ceil($value);
                if ($value < 0 || $value > 255) {
                    throw new InvalidValueException("invalid float value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^SMALLINT(?:\([0-9]{1,3}\))?$/i', $type)) {
                $value = $value >= 0 ? floor($value) : ceil($value);
                if ($value < -32768 || $value > 32767) {
                    throw new InvalidValueException("invalid float value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^SMALLINT(?:\([0-9]{1,3}\))? UNSIGNED(?: ZEROFILL)?$/i', $type)) {
                $value = $value >= 0 ? floor($value) : ceil($value);
                if ($value < 0 || $value > 65535) {
                    throw new InvalidValueException("invalid float value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^MEDIUMINT(?:\([0-9]{1,3}\))?$/i', $type)) {
                $value = $value >= 0 ? floor($value) : ceil($value);
                if ($value < -8388608 || $value > 8388607) {
                    throw new InvalidValueException("invalid float value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^MEDIUMINT(?:\([0-9]{1,3}\))? UNSIGNED(?: ZEROFILL)?$/i', $type)) {
                $value = $value >= 0 ? floor($value) : ceil($value);
                if ($value < 0 || $value > 16777215) {
                    throw new InvalidValueException("invalid float value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^(?:INT|INTEGER)(?:\([0-9]{1,3}\))?$/i', $type)) {
                $value = $value >= 0 ? floor($value) : ceil($value);
                if ($value < -2147483648 || $value > 2147483647) {
                    throw new InvalidValueException("invalid float value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^(?:INT|INTEGER)(?:\([0-9]{1,3}\))? UNSIGNED(?: ZEROFILL)?$/i', $type)) {
                $value = $value >= 0 ? floor($value) : ceil($value);
                if ($value < 0 || $value > 4294967295) {
                    throw new InvalidValueException("invalid float value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^BIGINT(?:\([0-9]{1,3}\))?$/i', $type)) {
                $value = $value >= 0 ? floor($value) : ceil($value);
                if ($value < -9223372036854775808 || $value > 9223372036854775807) {
                    throw new InvalidValueException("invalid float value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^(?:BIGINT(?:\([0-9]{1,3}\))? UNSIGNED(?: ZEROFILL)?|SERIAL)$/i', $type)) {
                $value = $value >= 0 ? floor($value) : ceil($value);
                if ($value < 0 || $value > 18446744073709551615) {
                    throw new InvalidValueException("invalid float value ($value) for type \"$type\"");
                }

                return sprintf('%d', $value);
            } else if (preg_match('/^(?:DECIMAL|DEC|NUMERIC|FIXED)(?:\(([0-9]{1,2}(?:,([0-9]{1,2}))?)\))?$/i', $type, $attributes)) {
                $precision = (int)($attributes[1] ?? 10);
                if ($precision > 65) {
                    throw new InvalidValueException("invalid type \"$type\" for float value");
                }

                $scale = (int)($attributes[2] ?? 0);
                if ($scale > 30) {
                    throw new InvalidValueException("invalid type \"$type\" for float value");
                }

                return substr(sprintf('%.*f', $scale, $value), -$precision);
            } else if (preg_match('/^FLOAT$/i', $type)) {
                if (
                    ($value < 0 && $value < -3.402823466E+38 || $value > -1.175494351E-38) || 
                    ($value > 0 && $value < 1.175494351E-38 || $value > 3.402823466E+38)
                ) {
                    throw new InvalidValueException("invalid float value ($value) for type \"$type\"");
                }

                return sprintf('%G', $value);
            } else if (preg_match('/^(?:DOUBLE|DOUBLE PRECISION|REAL)$/i', $type)) {
                return sprintf('%G', $value);
            } else {
                throw new InvalidValueException("invalid type \"$type\" for integer value");
            }
        default:
            throw new UnsupportedDriverException("cannot sanitize float value for \"{$this->driver}\" driver");
        }
    }

    private function sanitizeStringValue(string $value, ?string $type = NULL): string {
        switch ($this->driver) {
        case 'mysql':
            $type ??= 'VARCHAR';
            $attributes = [];
            if (preg_match('/^(?:CHAR|CHARACTER)(?:\(([0-9]{1,3})\))?$/i', $type, $attributes)) {
                $size = (int)($attributes[1] ?? 1);
                if ($size > 255) {
                    throw new InvalidValueException("invalid type \"$type\" for string value");
                }

                if (mb_strlen($value) > $size) {
                    throw new InvalidValueException("invalid string value ('$value') for type \"$type\"");
                }

                $value = str_pad($value, $size);

                return $this->connection->quote($value);
            } else if (preg_match('/^(?:NCHAR|NATIONAL CHAR|NATIONAL CHARACTER)(?:\(([0-9]{1,3})\))?$/i', $type, $attributes)) {
                $size = (int)($attributes[1] ?? 1);
                if ($size > 255) {
                    throw new InvalidValueException("invalid type \"$type\" for string value");
                }

                if (mb_strlen($value) > $size) {
                    throw new InvalidValueException("invalid string value ('$value') for type \"$type\"");
                }

                $value = str_pad($value, $size);

                return $this->connection->quote($value, PDO::PARAM_STR_NATL);
            } else if (preg_match('/^(?:VARCHAR|CHARACTER VARYING)(?:\(([0-9]{1,3})\))?$/i', $type)) {
                $size = (int)($attributes[1] ?? 65535);
                if ($size > 65535) {
                    throw new InvalidValueException("invalid type \"$type\" for string value");
                }

                $value = mb_substr($value, 0, $size);

                return $this->connection->quote($value);
            } else if (preg_match('/^(?:NVARCHAR|NATIONAL VARCHAR|NATIONAL CHARACTER VARYING)(?:\(([0-9]{1,3})\))?$/i', $type)) {
                $size = (int)($attributes[1] ?? 65535);
                if ($size > 65535) {
                    throw new InvalidValueException("invalid type \"$type\" for string value");
                }

                if (mb_strlen($value) > $size) {
                    throw new InvalidValueException("invalid string value ('$value') for type \"$type\"");
                }

                return $this->connection->quote($value, PDO::PARAM_STR_NATL);
            } else if (preg_match('/^BINARY(?:\(([0-9]+)\))?$/i', $type, $attributes)) {
                $size = (int)($attributes[1] ?? 1);
                if ($size > 255) {
                    throw new InvalidValueException("invalid type \"$type\" for string value");
                }

                if (mb_strlen($value) > $size) {
                    throw new InvalidValueException("invalid string value ('$value') for type \"$type\"");
                }

                $value = str_pad($value, $size, "\0");
                $value = bin2hex($value);
                $value = strtoupper($value);

                return "0x$value";
            } else if (preg_match('/^VARBINARY(?:\(([0-9]+)\))?$/i', $type, $attributes)) {
                $size = (int)($attributes[1] ?? 65535);
                if ($size > 65535) {
                    throw new InvalidValueException("invalid type \"$type\" for string value");
                }

                if (strlen($value) > $size) {
                    throw new InvalidValueException("invalid string value ('$value') for type \"$type\"");
                }

                $value = bin2hex($value);
                $value = strtoupper($value);

                return "0x$value";
            } else if (preg_match('/^TINYTEXT$/i', $type)) {
                if (mb_strlen($value) > 255) {
                    throw new InvalidValueException("invalid string value ('$value') for type \"$type\"");
                }

                return $this->connection->quote($value);
            } else if (preg_match('/^TINYBLOB$/i', $type)) {
                if (strlen($value) > 255) {
                    throw new InvalidValueException("invalid string value ('$value') for type \"$type\"");
                }

                $value = bin2hex($value);
                $value = strtoupper($value);

                return "0x$value";
            } else if (preg_match('/^BLOB(?:\(([0-9]{1,5})\))?$/i', $type, $attributes)) {
                $size = (int)($attributes[1] ?? 65535);
                if ($size > 65535) {
                    throw new InvalidValueException("invalid type \"$type\" for string value");
                }

                if (strlen($value) > $size) {
                    throw new InvalidValueException("invalid string value ('$value') for type \"$type\"");
                }

                $value = bin2hex($value);
                $value = strtoupper($value);

                return "0x$value";
            } else if (preg_match('/^TEXT(?:\(([0-9]{1,5})\))?$/i', $type, $attributes)) {
                $size = (int)($attributes[1] ?? 65535);
                if ($size > 65535) {
                    throw new InvalidValueException("invalid type \"$type\" for string value");
                }

                if (mb_strlen($value) > $size) {
                    throw new InvalidValueException("invalid string value ('$value') for type \"$type\"");
                }

                return $this->connection->quote($value);
            } else if (preg_match('/^MEDIUMTEXT$/i', $type)) {
                if (mb_strlen($value) > 16777215) {
                    throw new InvalidValueException("invalid string value ('$value') for type \"$type\"");
                }

                return $this->connection->quote($value);
            } else if (preg_match('/^MEDIUMBLOB$/i', $type)) {
                if (strlen($value) > 16777215) {
                    throw new InvalidValueException("invalid string value ('$value') for type \"$type\"");
                }

                $value = bin2hex($value);
                $value = strtoupper($value);

                return "0x$value";
            } else if (preg_match('/^LONGTEXT$/i', $type)) {
                if (mb_strlen($value) > 4294967295) {
                    throw new InvalidValueException("invalid string value ('$value') for type \"$type\"");
                }

                return $this->connection->quote($value);
            } else if (preg_match('/^LONGBLOB$/i', $type)) {
                if (strlen($value) > 4294967295) {
                    throw new InvalidValueException("invalid string value ('$value') for type \"$type\"");
                }

                $value = bin2hex($value);
                $value = strtoupper($value);

                return "0x$value";
            } else if (preg_match('/^ENUM\((\'.+?\'(?:,\'.+?\')*)\)$/i', $type, $attributes)) {
                $typeMembers = preg_split('/(?<!\\\\)\'(?:,(?<!\\\\)\')?/', $attributes[1], -1, PREG_SPLIT_NO_EMPTY);
                if (count($typeMembers) > 65535) {
                    throw new InvalidValueException("invalid type \"$type\" for string value");
                }

                if (!in_array($value, $typeMembers, true)) {
                    throw new InvalidValueException("invalid string value ('$value') for type \"$type\"");
                }

                return $this->connection->quote($value);
            } else if (preg_match('/^SET\((\'.+?\'(?:,\'.+?\')*})\)$/i', $type, $attributes)) {
                $typeMembers = preg_split('/(?<!\\\\)\'(?:,(?<!\\\\)\')?/', $attributes[1], -1, PREG_SPLIT_NO_EMPTY);
                if (count($typeMembers) > 64) {
                    throw new InvalidValueException("invalid type \"$type\" for string value");
                }

                $valueMembers = preg_split('/(?<!\\\\)\'(?:,(?<!\\\\)\')?/', $value, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($valueMembers as $_ => $valueMember) {
                    if (!in_array($valueMember, $typeMembers, true)) {
                        throw new InvalidValueException("invalid member ('$valueMember') in string value ('$value') for type \"$type\"");
                    }
                }

                return $this->connection->quote($value);
            } else {
                throw new InvalidValueException("invalid type \"$type\" for string value");
            }
        default:
            throw new UnsupportedDriverException("cannot sanitize string value for \"{$this->driver}\" driver");
        }
    }

    private function sanitizeArrayValue(array $value, ?string $type = NULL): string {
        switch ($this->driver) {
        case 'mysql':
            $type ??= '?';
            $attributes = [];
            if (preg_match('/^SET\((\'.+?\'(?:,\'.+?\'){0,63})\)$/i', $type, $attributes)) {
                $typeMembers = preg_split('/(?<!\\\\)\'(?:,(?<!\\\\)\')?/', $attributes[1], -1, PREG_SPLIT_NO_EMPTY);

                if (!array_is_list($value)) {
                    throw new InvalidValueException("invalid list array for type \"$type\"");
                }

                foreach ($value as $_ => $valueMember) {
                    if (!in_array($valueMember, $typeMembers, true)) {
                        throw new InvalidValueException("invalid member ('$valueMember') in list array for type \"$type\"");
                    }
                }

                return $this->connection->quote(implode(',', $value));
            } else {
                throw new InvalidValueException("invalid type \"$type\" for array value");
            }
        default:
            throw new UnsupportedDriverException("cannot sanitize array value for \"{$this->driver}\" driver");
        }
    }

    private function sanitizeObjectValue(object $value, ?string $type = NULL): string {
        $valueClass = get_class($value);
        switch ($this->driver) {
        case 'mysql':
            if ($value instanceof DateTime || $value instanceof DateTimeImmutable) {
                // TODO: Evaluate limits of the various date and time types
                $type ??= 'DATETIME';
                $attributes = [];
                if (preg_match('/^DATE$/i', $type)) {
                    return $this->connection->quote($value->format('Y-m-d'));
                } else if (preg_match('/^DATETIME(?:\(([0-9])\))?/', $type, $attributes)) {
                    $fractionalSecondPrecision = (int)($attributes[1] ?? 0);
                    if ($fractionalSecondPrecision > 6) {
                        throw new InvalidValueException("invalid type \"$type\" for $valueClass value");
                    }

                    $fractionalSeconds = '';
                    if ($fractionalSecondPrecision > 0) {
                        $fractionalSeconds = substr($value->format('.u'), 0, $fractionalSecondPrecision);
                    }

                    return $this->connection->quote($value->format('Y-m-d H:i:s') . $fractionalSeconds);
                } else if (preg_match('/^TIMESTAMP(?:\(([0-9])\))?/', $type, $attributes)) {
                    $fractionalSecondPrecision = (int)($attributes[1] ?? 0);
                    if ($fractionalSecondPrecision > 6) {
                        throw new InvalidValueException("invalid type \"$type\" for $valueClass value");
                    }

                    $fractionalSeconds = '';
                    if ($fractionalSecondPrecision > 0) {
                        $fractionalSeconds = substr($value->format('.u'), 0, $fractionalSecondPrecision);
                    }

                    return $this->connection->quote($value->format('Y-m-d H:i:s') . $fractionalSeconds);
                } else if (preg_match('/^TIME(?:\(([0-9])\))?/', $type, $attributes)) {
                    $fractionalSecondPrecision = (int)($attributes[1] ?? 0);
                    if ($fractionalSecondPrecision > 6) {
                        throw new InvalidValueException("invalid type \"$type\" for $valueClass value");
                    }

                    $fractionalSeconds = '';
                    if ($fractionalSecondPrecision > 0) {
                        $fractionalSeconds = substr($value->format('.u'), 0, $fractionalSecondPrecision);
                    }

                    return $this->connection->quote($value->format('H:i:s') . $fractionalSeconds);
                } else {
                    throw new InvalidValueException("invalid type \"$type\" for $valueClass value");
                }
            } else {
                throw new InvalidValueException("cannot sanitize $valueClass value for \"{$this->driver}\" driver");
            }
        default:
            throw new UnsupportedDriverException("cannot sanitize $valueClass value for \"{$this->driver}\" driver");   
        }
    }

    // TODO: Handle sanitizing resource type

    private function sanitizeValue(mixed $value, ?string $type = NULL): string {
        switch ($this->driver) {
        case 'mysql':
            if (is_null($value)) {
                return $this->sanitizeNullValue($value, $type);
            } else if (is_bool($value)) {
                return $this->sanitizeBooleanValue($value, $type);
            } else if (is_int($value)) {
                return $this->sanitizeIntegerValue($value, $type);
            } else if (is_float($value)) {
                return $this->sanitizeFloatValue($value, $type);
            } else if (is_string($value)) {
                return $this->sanitizeStringValue($value, $type);
            } else if (is_array($value)) {
                return $this->sanitizeArrayValue($value, $type);
            } else if (is_object($value)) {
                return $this->sanitizeObjectValue($value, $type);
            } else {
                $valueType = gettype($value);
                throw new InvalidValueException("cannot sanitize $valueType value for \"{$this->driver}\" driver.");
            }
        default:
            $valueType = gettype($value);
            throw new InvalidValueException("cannot sanitize $valueType value for \"{$this->driver}\" driver.");
        }
    }

    private function buildSelectClause(null|string|array $columnData): string {
        switch ($this->driver) {
        case 'mysql':
            if (is_null($columnData)) {
                return 'SELECT *';
            } else if (is_string($columnData)) {
                return "SELECT $columnData";
            } else if (is_array($columnData)) {
                if (empty($columnData)) {
                    return 'SELECT *';
                }

                $expressions = [];
                foreach ($columnData as $columnDataKey => $columnDataValue) {
                    $expression = NULL;
                    if (is_int($columnDataKey) && is_string($columnDataValue)) {
                        $expression = $columnDataValue;
                    } else if (is_string($columnDataKey) && is_string($columnDataValue)) {
                        $sanitizedAlias = $this->sanitizeIdentifier($columnDataValue);
                        $expression = "$columnDataKey AS $sanitizedAlias";
                    } else {
                        throw new InvalidColumnDataException('invalid column data array key/value type combination');
                    }

                    $expressions []= $expression;
                }

                return 'SELECT ' . implode(', ', $expressions);
            } else {
                throw new InvalidColumnDataException('invalid column data type');
            }
        default:
            throw new UnsupportedDriverException("SELECT clause is not supported for driver \"{$this->driver}\"");
        }
    }

    private function buildFromClause(string $tableName): string {
        switch ($this->driver) {
        case 'mysql':
            $sanitizedTableName = $this->sanitizeIdentifier($tableName);
            return "FROM $sanitizedTableName";
        default:
            throw new UnsupportedDriverException("FROM clause is not supported for driver \"{$this->driver}\"");
        }
    }

    private function buildWhereClause(string $where): string {
        switch ($this->driver) {
        case 'mysql':
            $whereExpression = $where;
            return "WHERE $whereExpression";
        default:
            throw new UnsupportedDriverException("WHERE clause is not supported for driver \"{$this->driver}\"");
        }
    }

    private function buildGroupByClause(string|array $groupByData): string {
        switch ($this->driver) {
        case 'mysql':
            if (is_string($groupByData)) {
                $sanitizedColumnName = $this->sanitizeIdentifier($groupByData);
                return "GROUP BY $sanitizedColumnName";
            } else if (is_array($groupByData)) {
                if (empty($groupByData)) {
                    throw new InvalidGroupByDataException('missing group by column data');
                }

                $criteria = [];
                foreach ($groupByData as $groupByDataKey => $groupByDataValue) {
                    $criterion = NULL;
                    if (is_int($groupByDataKey) && is_string($groupByDataValue)) {
                        $criterion = $this->sanitizeIdentifier($groupByDataValue);
                    } else {
                        throw new InvalidGroupByDataException('invalid group by array key/value type combination');
                    }

                    $criteria []= $criterion;
                }
                return 'GROUP BY ' . implode(', ', $criteria);
            } else {
                throw new InvalidGroupByDataException('invalid group by data type');
            }
        default:
            throw new UnsupportedDriverException("GROUP BY clause is not supported for driver \"{$this->driver}\"");
        }
    }

    private function buildHavingClause(string $having): string {
        switch ($this->driver) {
        case 'mysql':
            return "HAVING $having";
        default:
            throw new UnsupportedDriverException("HAVING clause is not supported for driver \"{$this->driver}\"");
        }
    }

    private function buildOrderByClause(string|array $orderByData): string {
        switch ($this->driver) {
        case 'mysql':
            if (is_string($orderByData)) {
                $sanitizedColumnName = $this->sanitizeIdentifier($orderByData);
                return "ORDER BY $sanitizedColumnName";
            } else if (is_array($orderByData)) {
                if (empty($orderByData)) {
                    throw new InvalidOrderByDataException('missing order by column data');
                }

                $criteria = [];
                foreach ($orderByData as $orderByDataKey => $orderByDataValue) {
                    $criterionColumn = NULL;
                    $criterionDirection = 'ASC';
                    if (is_int($orderByDataKey) && is_string($orderByDataValue)) {
                        $criterionColumn = $orderByDataValue;
                        $criterionDirection = 'ASC';
                    } else if (is_int($orderByData) && is_array($orderByData)) {
                        $criterionColumn = $this->sanitizeIdentifier($orderByDataValue['column'] ?? throw new InvalidOrderByDataException('missing order by array data column name'));
                        if (in_array('ascending', $orderByDataValue, true) || isset($orderByData['ascending'])) {
                            $criterionDirection = 'ASC';
                        } else if (in_array('descending', $orderByData, true) || isset($orderByData['descending'])) {
                            $criterionDirection = 'DESC';
                        }
                    } else {
                        throw new InvalidOrderByDataException('invalid order by array key/value type combination');
                    }

                    $criteria []= "$criterionColumn $criterionDirection";
                }
                return 'ORDER BY ' . implode(', ', $criteria);
            } else {
                throw new InvalidOrderByDataException('invalid order by data type');
            }
        default:
            throw new UnsupportedDriverException("ORDER BY clause is not supported for driver \"{$this->driver}\"");
        }
    }

    private function buildLimitClause(int|array $limitData): string {
        switch ($this->driver) {
        case 'mysql':
            if (is_int($limitData)) {
                $sanitizedLimitCount = $this->sanitizeIntegerValue($limitData);
                return "LIMIT $sanitizedLimitCount";
            } else if (is_array($limitData)) {
                $sanitizedLimitCount = $this->sanitizeIntegerValue($limitData['count'] ?? throw new InvalidLimitDataException('missing limit array data count'));
                $sanitizedLimitOffset = $this->sanitizeIntegerValue($limitData['offset'] ?? throw new InvalidLimitDataException('missing limit array data offset'));
                return "LIMIT $sanitizedLimitOffset, $sanitizedLimitCount";
            } else {
                throw new InvalidLimitDataException('invalid limit data type');
            }
        default:
            throw new UnsupportedDriverException("LIMIT clause is not supported for driver \"{$this->driver}\"");
        }   
    }

    private function buildInsertClause(string $tableName, array $insertionData): string {
        switch ($this->driver) {
        case 'mysql':
            $sanitizedTableName = $this->sanitizeIdentifier($tableName);
            $sanitizedColumnNames = [];
            foreach ($insertionData as $columnName => $_) {
                if (!is_string($columnName)) {
                    throw new InvalidTableRowDataException('invalid column name');
                }
                $sanitizedColumnNames []= $this->sanitizeIdentifier($columnName);
            }
            return "INSERT INTO $sanitizedTableName (" . implode(', ', $sanitizedColumnNames) . ")";
        default:
            throw new UnsupportedDriverException("INSERT clause is not supported for driver \"{$this->driver}\"");
        }
    }

    private function buildValueClause(array $insertionData): string {
        switch ($this->driver) {
        case 'mysql':
            $parameterNames = [];
            foreach ($insertionData as $_ => $expression) {
                if (!is_string($expression)) {
                    throw new InvalidTableRowDataException('invalid parameter name');
                }
                $parameterNames []= $expression;
            }
            return 'VALUE (' . implode(', ', $parameterNames) . ')';
        default:
            throw new UnsupportedDriverException("LIMIT clause is not supported for driver \"{$this->driver}\"");
        }
    }

    private function buildUpdateClause(string $tableName): string {
        switch ($this->driver) {
        case 'mysql':
            $sanitizedTableName = $this->sanitizeIdentifier($tableName);
            return "UPDATE $sanitizedTableName";
        default:
            throw new UnsupportedDriverException("UPDATE clause is not supported for driver \"{$this->driver}\"");
        }
    }

    private function buildSetClause(array $updationData): string {
        switch ($this->driver) {
        case 'mysql':
            $assignments = [];
            foreach ($updationData as $columnName => $expression) {
                if (!is_string($columnName)) {
                    throw new InvalidTableRowDataException('invalid column name');
                } else if (!is_string($expression)) {
                    throw new InvalidTableRowDataException('invalid parameter name');
                }

                $assignments []= $this->sanitizeIdentifier($columnName) . ' = ' . $expression;
            }
            return 'SET ' . implode(', ', $assignments);
        default:
            throw new UnsupportedDriverException("SET clause is not supported for driver \"{$this->driver}\"");
        }
    }

    private function buildSelectStatement(string|array $tableData, null|string|array $columnData = NULL, ?string $where = NULL, null|string|array $groupByData = NULL, ?string $having = NULL, null|string|array $orderByData = NULL, null|int|array $limitData = NULL): string {
        switch ($this->driver) {
        case 'mysql':
            $clauses = [];
            $clauses []= $this->buildSelectClause($columnData);
            $clauses []= $this->buildFromClause($tableData);
            if ($where) {
                $clauses []= $this->buildWhereClause($where);
            }
            if ($groupByData) {
                $clauses []= $this->buildGroupByClause($groupByData);
            }
            if ($having) {
                $clauses []= $this->buildHavingClause($having);
            }
            if ($orderByData) {
                $clauses []= $this->buildOrderByClause($orderByData);
            }
            if ($limitData) {
                $clauses []= $this->buildLimitClause($limitData);
            }
            return implode(' ', $clauses) . ';';
        default:
            throw new UnsupportedDriverException("SELECT statement is not supported for driver \"{$this->driver}\"");
        }
    }

    private function buildInsertStatement(string $tableName, array $insertionData): string {
        switch ($this->driver) {
        case 'mysql':
            $clauses = [];
            $clauses []= $this->buildInsertClause($tableName, $insertionData);
            $clauses []= $this->buildValueClause($insertionData);
            return implode(' ', $clauses) . ';';
        default:
            throw new UnsupportedDriverException("INSERT statement is not supported for driver \"{$this->driver}\"");
        }
    }

    private function buildUpdateStatement(string $tableName, array $updationData, ?string $where = NULL, ?int $limit = NULL): string {
        switch ($this->driver) {
        case 'mysql':
            $clauses = [];
            $clauses []= $this->buildUpdateClause($tableName);
            $clauses []= $this->buildSetClause($updationData);
            if ($where) {
                $clauses []= $this->buildWhereClause($where);
            }
            if ($limit) {
                $clauses []= $this->buildLimitClause($limit);
            }
            return implode(' ', $clauses) . ';';
        default:
            throw new UnsupportedDriverException("UPDATE statement is not supported for driver \"{$this->driver}\"");
        }
    }

    private function buildDeleteStatement(string $tableName, ?string $where = NULL, ?int $limit = NULL): string {
        switch ($this->driver) {
        case 'mysql':
            $clauses = [];
            $clauses []= 'DELETE';
            $clauses []= $this->buildFromClause($tableName);
            if ($where) {
                $clauses []= $this->buildWhereClause($where);
            }
            if ($limit) {
                $clauses []= $this->buildLimitClause($limit);
            }
            return implode(' ', $clauses);
        default:
            throw new UnsupportedDriverException("DELETE statement is not supported for driver \"{$this->driver}\"");
        }
    }

    private function buildShowColumnsStatement(string $tableName, ?string $where = NULL): string {
        switch ($this->driver) {
        case 'mysql':
            $clauses = [];
            $clauses []= 'SHOW COLUMNS';
            $clauses []= $this->buildFromClause($tableName);
            if ($where) {
                $clauses []= $this->buildWhereClause($where);
            }
            return implode(' ', $clauses) . ';';
        default:
            throw new UnsupportedDriverException("SHOW COLUMNS statement is not supported for driver \"{$this->driver}\"");
        }
    }

    private function bindParameters(string &$statement, ?array $parameters = NULL): string {
        if (!$parameters) {
            return $statement;
        }

        switch ($this->driver) {
        case 'mysql':
            foreach ($parameters as $parameterName => $parameterData) {
                if (!is_string($parameterName)) {
                    throw new InvalidParameterDataException('invalid type for parameter name');
                } else if (!preg_match('/^:[A-Za-z0-9]+$/', $parameterName)) {
                    throw new InvalidParameterDataException('invalid parameter name');
                }

                $sanitizedParameterValue = NULL;
                if (is_array($parameterData)) {
                    if (!array_is_list($parameterData)) {
                        $parameterValue = $parameterData['value'] ?? throw new InvalidParameterDataException('missing parameter array data value to sanitize');
                        $parameterType = $parameterData['type'] ?? throw new InvalidParameterDataException('missing parameter array data SQL type');
                        $sanitizedParameterValue = $this->sanitizeValue($parameterValue, $parameterType);
                    } else {
                        $sanitizedParameterValue = $this->sanitizeArrayValue($parameterData);
                    }
                } else {
                    $sanitizedParameterValue = $this->sanitizeValue($parameterData);
                }

                $statement = str_replace($parameterName, $sanitizedParameterValue, $statement);
            }
            return $statement;
        default:
            throw new UnsupportedDriverException("cannot bind parameters for driver \"{$this->driver}\"");
        }
    }

    private function getPrimaryKeyColumn(string $tableName): array {
        switch ($this->driver) {
        case 'mysql':
            $showColumnsStatement = $this->buildShowColumnsStatement(
                $tableName,
                '`Key` = \'PRI\''
            );
            $columnInformationResult = $this->connection->query($showColumnsStatement);
            $columnInformationRow = $columnInformationResult->fetch();
            return $columnInformationRow;
        default:
            throw new UnsupportedDriverException("cannot get primary key column information with \"{$this->driver}\" driver");
        }
    }

    private function getPrimaryKeyValues(string $tableName, string $where, ?array $parameters): array {
        switch ($this->driver) {
        case 'mysql':
            $primaryKeyColumn = $this->getPrimaryKeyColumn($tableName);
            $primaryKeyName = $primaryKeyColumn['Field'];
            $sanitizedPrimaryKeyName = $this->sanitizeIdentifier($primaryKeyName);

            $selectPrimaryKeyValuesStatement = $this->buildSelectStatement(
                $tableName,
                $sanitizedPrimaryKeyName,
                $where
            );
            $this->bindParameters($selectPrimaryKeyValuesStatement, $parameters);
            $selectPrimaryKeyValuesResult = $this->connection->query($selectPrimaryKeyValuesStatement);
            $primaryKeyValues = [];
            while ($selectPrimaryKeyValuesRow = $selectPrimaryKeyValuesResult->fetch()) {
                $primaryKeyValues []= $selectPrimaryKeyValuesRow[$primaryKeyName];
            }
            return $primaryKeyValues;
        default:
            throw new UnsupportedDriverException("cannot primary key values with \"{$this->driver}\" driver");
        }
    }

    private function simplifyWhere(string $tableName, string $where, ?array $parameters): ?string {
        switch ($this->driver) {
        case 'mysql':
            $primaryKeyColumn = $this->getPrimaryKeyColumn($tableName);
            $sanitizedPrimaryKeyName = $this->sanitizeIdentifier($primaryKeyColumn['Field']);
            $primaryKeyType = $primaryKeyColumn['Type'];
            
            $primaryKeyValues = $this->getPrimaryKeyValues($tableName, $where, $parameters);
            $sanitizedPrimaryKeyValues = [];
            foreach ($primaryKeyValues as $_ => $primaryKeyValue) {
                $sanitizedPrimaryKeyValues []= $this->sanitizeValue($primaryKeyValue, $primaryKeyType);
            }

            switch (count($sanitizedPrimaryKeyValues)) {
            case 0:
                return NULL;
            case 1:
                return "$sanitizedPrimaryKeyName = $sanitizedPrimaryKeyValues[0]";
            default:
                return "$sanitizedPrimaryKeyName IN (" . implode(', ', $sanitizedPrimaryKeyValues) . ')';
            }
        default:
            throw new UnsupportedDriverException("cannot simplify WHERE clause with \"{$this->driver}\" driver");
        }
    }

    // TODO: With functions that return row data, account for the various data types to be converted.

    public function selectRow(string|array $tableData, null|string|array $selectionData = NULL, ?string $where = NULL, ?array $parameters = NULL, int $offset = 0): ?array {
        switch ($this->driver) {
        case 'mysql':
            $selectionStatement = $this->buildSelectStatement(
                $tableData,
                $selectionData,
                $where,
                NULL,
                NULL,
                NULL,
                [
                    'count' => 1,
                    'offset' => $offset
                ]
            );
            $this->bindParameters($selectionStatement, $parameters);
            $selectionResult = $this->connection->query($selectionStatement);
            $selectedRow = $selectionResult->fetch();
            if (!$selectedRow) {
                return NULL;
            }
            return $selectedRow;
        default:
            throw new UnsupportedDriverException("cannot select row with \"{$this->driver}\"");
        }
    }

    public function insertRow(string $tableName, array $insertionData, ?array $parameters = NULL, null|string|array $returningColumnData = NULL): ?array {
        switch ($this->driver) {
        case 'mysql':
            $insertionStatement = $this->buildInsertStatement(
                $tableName,
                $insertionData
            );
            $this->bindParameters($insertionStatement, $parameters);
            $this->connection->exec($insertionStatement);
            
            if ($returningColumnData) {
                $primaryKeyColumn = $this->getPrimaryKeyColumn($tableName);
                $sanitizedPrimaryKeyName = $this->sanitizeIdentifier($primaryKeyColumn['Field']);
                $primaryKeyType = $primaryKeyColumn['Type'];
                $selectionOfInsertionStatement = $this->buildSelectStatement(
                    $tableName,
                    $returningColumnData,
                    "$sanitizedPrimaryKeyName = LAST_INSERT_ID()",
                    NULL,
                    NULL,
                    NULL,
                    1
                );
                $this->bindParameters($selectionOfInsertionStatement, $parameters);
                $selectionOfInsertionResult = $this->connection->query($selectionOfInsertionStatement);
                $insertedRow = $selectionOfInsertionResult->fetch();
                if ($insertedRow) {
                    return $insertedRow;
                }
            }

            return NULL;
        default:
            throw new UnsupportedDriverException("cannot insert row with \"{$this->driver}\"");
        }
    }

    public function updateRow(string $tableName, array $updationData, string $where, array $parameters = NULL, null|string|array $returningColumnData = NULL): ?array {
        switch ($this->driver) {
        case 'mysql':
            $simplifiedWhere = $this->simplifyWhere($tableName, $where, $parameters);
            if (!$simplifiedWhere) {
                return NULL;
            }

            $updationStatement = $this->buildUpdateStatement(
                $tableName,
                $updationData,
                $simplifiedWhere,
                1
            );
            $this->bindParameters($updationStatement, $parameters);
            $this->connection->exec($updationStatement);

            if ($returningColumnData) {
                $selectionOfUpdationStatement = $this->buildSelectStatement(
                    $tableName,
                    $returningColumnData,
                    $simplifiedWhere,
                    NULL,
                    NULL,
                    NULL,
                    1
                );
                $this->bindParameters($selectionOfUpdationStatement, $parameters);
                $updationOfSelectionResult = $this->connection->query($selectionOfUpdationStatement);
                $updatedRow = $updationOfSelectionResult->fetch();
                if ($updatedRow) {
                    return $updatedRow;
                }
            }

            return NULL;
        default:
            throw new UnsupportedDriverException("cannot update row with \"{$this->driver}\"");
        }
    }

    public function deleteRow(string $tableName, string $where, ?array $parameters = NULL, null|string|array $returningColumnData = NULL): ?array {
        switch ($this->driver) {
        case 'mysql':
            $deletedRow = NULL;
            if ($returningColumnData) {
                $selectionOfDeletionStatement = $this->buildSelectStatement(
                    $tableName,
                    $returningColumnData,
                    $where,
                    NULL,
                    NULL,
                    NULL,
                    1
                );
                $this->bindParameters($selectionOfDeletionStatement, $parameters);
                $selectionOfDeletionResult = $this->connection->query($selectionOfDeletionStatement);
                $deletedRow = $selectionOfDeletionResult->fetch();
                if (!$deletedRow) {
                    return NULL;
                }
            }

            $deletionStatement = $this->buildDeleteStatement(
                $tableName,
                $where,
                1
            );
            $this->bindParameters($deletionStatement, $parameters);
            $this->connection->exec($deletionStatement);

            return $deletedRow;
        default:
            throw new UnsupportedDriverException("cannot delete row with \"{$this->driver}\"");
        }
    }
}
?>