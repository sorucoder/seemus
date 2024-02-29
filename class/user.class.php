<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/database.class.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/audit.class.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/exception/user/invalid_user_credentials.exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/exception/user/invalid_user_data.exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/exception/user/user_archived.exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/exception/user/user_not_archived.exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/exception/user/user_logged_in.exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/exception/user/user_not_logged_in.exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/exception/user/user_not_permitted.exception.php';

final class User {
    private int $id;
    private string $uuid;
    private string $name;
    private string $email;
    private string $passwordHash;
    private string $role;
    private DateTime $createdDateTime;
    private ?DateTime $lastLoginDateTime;
    private int $passwordChanges;
    private ?DateTime $lastPasswordChangeDateTime;
    private bool $archived;

    public const ROLES = [
        'admin' => 'Administrator',
        'user' => 'User'
    ];

    private function __construct(int $id, string $uuid, string $name, string $email, string $passwordHash, string $role, DateTime $createdDateTime, ?DateTime $loginDateTime, int $passwordChanges, ?DateTime $passwordChangeDateTime, bool $archived) {
        $this->id = $id;
        $this->uuid = $uuid;
        $this->name = $name;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->createdDateTime = $createdDateTime;
        $this->lastLoginDateTime = $loginDateTime;
        $this->passwordChanges = $passwordChanges;
        $this->lastPasswordChangeDateTime = $passwordChangeDateTime;
        $this->archived = $archived;
    }

    private static function validateName(string $name): ?string {
        $name = preg_replace(
            [
                '/[\r\n\t]+/u',
                '/(\p{Z})\p{Z}+/u',
                '/^\p{Z}/u',
                '/\p{Z}$/u'
            ],
            [
                ' ',
                '${1}'
            ],
            $name
        );
        $name = filter_var(
            $name,
            FILTER_VALIDATE_REGEXP,
            [
                'options' => [
                    'regexp' => '/^[\p{L}\p{N}]+(?:\p{Z}[\p{L}\p{N}]+)*$/u'
                ],
                'flags' => FILTER_NULL_ON_FAILURE
            ]
        );
        return $name;
    }

    private static function validateEmail(string $email): ?string {
        $email = preg_replace(
            [
                '/[\r\n\t]+/u',
                '/(\p{Z})\p{Z}+/u',
                '/^\p{Z}/u',
                '/\p{Z}$/u'
            ],
            '',
            $email
        );
        $email = filter_var(
            $email,
            FILTER_VALIDATE_EMAIL,
            FILTER_NULL_ON_FAILURE
        );
        return $email;
    }

    private static function validateRole(string $role): ?string {
        $role = preg_replace(
            [
                '/[\r\n\t]+/u',
                '/(\p{Z})\p{Z}+/u',
                '/^\p{Z}/u',
                '/\p{Z}$/u'
            ],
            '',
            $role
        );
        if (!isset(self::ROLES[$role])) {
            return NULL;
        }
        return $role;
    }

    public static function current(): ?self {
        if (!session_id()) {
            session_start();
        }
        
        if (isset($_SESSION['user'])) {
            return unserialize($_SESSION['user']);
        }

        return NULL;
    }

    public static function create(string $name, string $email, string $password, string $role): self {
        $currentUser = self::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        } else if (!$currentUser->isAdministrator()) {
            throw new UserNotPermittedException();
        }

        $invalidFields = [];
        $name = self::validateName($name);
        if (!$name) {
            $invalidFields []= 'name';
        }
        $email = self::validateEmail($email);
        if (!$email) {
            $invalidFields []= 'email';
        }
        $role = self::validateRole($role);
        if (!$role) {
            $invalidFields []= 'role';
        }
        if (!empty($invalidFields)) {
            throw new InvalidUserDataException($invalidFields);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $database = Database::connect();
        $insertedUserRow = NULL;
        try {
            $insertedUserRow = $database->insertRow(
                'USER',
                [
                    'USER_NAME' => ':name',
                    'USER_EMAIL' => ':email',
                    'USER_PASSWORD_HASH' => ':passwordHash',
                    'USER_ROLE' => ':role'
                ],
                [
                    ':name' => $name,
                    ':email' => $email,
                    ':passwordHash' => $passwordHash,
                    ':role' => $role
                ],
                [
                    'USER_ID' => 'id',
                    'USER_UUID' => 'uuid',
                    'USER_CREATED_DATETIME' => 'createdDateTimeValue',
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }
        $id = $insertedUserRow['id'];
        $uuid = $insertedUserRow['uuid'];
        $createdDateTime = new DateTime($insertedUserRow['createdDateTimeValue']);

        $user = new self(
            $id,
            $uuid,
            $name,
            $email,
            $passwordHash,
            $role,
            $createdDateTime,
            NULL,
            0,
            NULL,
            false
        );

        $roleName = self::ROLES[$role];

        Audit::log(
            "Created $roleName \"$name\" ($uuid)",
            NULL,
            ''
        );

        return $user;
    }

    public static function fromID(int $id): ?self {
        $database = Database::connect();
        $selectedUserRow = NULL;
        try {
            $selectedUserRow = $database->selectRow(
                'USER',
                [
                    'USER_UUID' => 'uuid',
                    'USER_NAME' => 'name',
                    'USER_EMAIL' => 'email',
                    'USER_PASSWORD_HASH' => 'passwordHash',
                    'USER_ROLE' => 'role',
                    'USER_CREATED_DATETIME' => 'createdDateTimeValue',
                    'USER_LAST_LOGIN_DATETIME' => 'lastLoginDateTimeValue',
                    'USER_PASSWORD_CHANGES' => 'passwordChanges',
                    'USER_LAST_PASSWORD_CHANGE_DATETIME' => 'lastPasswordChangeDateTimeValue',
                    'USER_ARCHIVED' => 'archivedValue'
                ],
                '`USER_ID` = :id',
                [
                    ':id' => $id
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }
        if (!$selectedUserRow) {
            return NULL;
        }
        $uuid = $selectedUserRow['uuid'];
        $name = $selectedUserRow['name'];
        $email = $selectedUserRow['email'];
        $passwordHash = $selectedUserRow['passwordHash'];
        $role = $selectedUserRow['role'];
        $createdDateTime = new DateTime($selectedUserRow['createdDateTimeValue']);
        $lastLoginDateTime = NULL;
        if ($selectedUserRow['lastLoginDateTimeValue']) {
            $lastLoginDateTime = new DateTime($selectedUserRow['lastLoginDateTimeValue']);
        }
        $passwordChanges = $selectedUserRow['passwordChanges'];
        $lastPasswordChangeDateTime = NULL;
        if ($selectedUserRow['passwordChangeDateTimeValue']) {
            $lastPasswordChangeDateTime = new DateTime($selectedUserRow['lastPasswordChangeDateTimeValue']);
        }
        $archived = $selectedUserRow['archivedValue'] !== 0;

        $user = new self(
            $id,
            $uuid,
            $name,
            $email, 
            $passwordHash,
            $role,
            $createdDateTime,
            $lastLoginDateTime,
            $passwordChanges,
            $lastPasswordChangeDateTime,
            $archived
        );

        return $user;
    }

    public static function fromUUID(string $uuid): ?self {
        $database = Database::connect();
        $selectedUserRow = NULL;
        try {
            $selectedUserRow = $database->selectRow(
                'USER',
                [
                    'USER_ID' => 'id',
                    'USER_NAME' => 'name',
                    'USER_EMAIL' => 'email',
                    'USER_PASSWORD_HASH' => 'passwordHash',
                    'USER_ROLE' => 'role',
                    'USER_CREATED_DATETIME' => 'createdDateTimeValue',
                    'USER_LAST_LOGIN_DATETIME' => 'lastLoginDateTimeValue',
                    'USER_PASSWORD_CHANGES' => 'passwordChanges',
                    'USER_LAST_PASSWORD_CHANGE_DATETIME' => 'lastPasswordChangeDateTimeValue',
                    'USER_ARCHIVED' => 'archivedValue'
                ],
                '`USER_UUID` = :uuid',
                [
                    ':uuid' => $uuid
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }
        if (!$selectedUserRow) {
            return NULL;
        }
        $id = $selectedUserRow['id'];
        $name = $selectedUserRow['name'];
        $email = $selectedUserRow['email'];
        $passwordHash = $selectedUserRow['passwordHash'];
        $role = $selectedUserRow['role'];
        $createdDateTime = new DateTime($selectedUserRow['createdDateTimeValue']);
        $lastLoginDateTime = NULL;
        if ($selectedUserRow['lastLoginDateTimeValue']) {
            $lastLoginDateTime = new DateTime($selectedUserRow['lastLoginDateTimeValue']);
        }
        $passwordChanges = $selectedUserRow['passwordChanges'];
        $lastPasswordChangeDateTime = NULL;
        if ($selectedUserRow['lastPasswordChangeDateTimeValue']) {
            $lastPasswordChangeDateTime = new DateTime($selectedUserRow['lastPasswordChangeDateTimeValue']);
        }
        $archived = $selectedUserRow['archivedValue'] !== 0;

        $user = new self(
            $id,
            $uuid,
            $name,
            $email, 
            $passwordHash,
            $role,
            $createdDateTime,
            $lastLoginDateTime,
            $passwordChanges,
            $lastPasswordChangeDateTime,
            $archived
        );

        return $user;
    }

    public static function all(): array {
        $database = Database::connect();
        $selectedUserRows = NULL;
        try {
            $selectedUserRows = $database->selectRows(
                'USER',
                [
                    'USER_ID' => 'id',
                    'USER_UUID' => 'uuid',
                    'USER_NAME' => 'name',
                    'USER_EMAIL' => 'email',
                    'USER_PASSWORD_HASH' => 'passwordHash',
                    'USER_ROLE' => 'role',
                    'USER_CREATED_DATETIME' => 'createdDateTimeValue',
                    'USER_LAST_LOGIN_DATETIME' => 'lastLoginDateTimeValue',
                    'USER_PASSWORD_CHANGES' => 'passwordChanges',
                    'USER_LAST_PASSWORD_CHANGE_DATETIME' => 'lastPasswordChangeDateTimeValue',
                    'USER_ARCHIVED' => 'archivedValue'
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }
        $users = [];
        foreach ($selectedUserRows as $_ => $selectedUserRow) {
            $id = $selectedUserRow['id'];
            $uuid = $selectedUserRow['uuid'];
            $name = $selectedUserRow['name'];
            $email = $selectedUserRow['email'];
            $passwordHash = $selectedUserRow['passwordHash'];
            $role = $selectedUserRow['role'];
            $createdDateTime = new DateTime($selectedUserRow['createdDateTimeValue']);
            $lastLoginDateTime = NULL;
            if ($selectedUserRow['lastLoginDateTimeValue']) {
                $lastLoginDateTime = new DateTime($selectedUserRow['lastLoginDateTimeValue']);
            }
            $passwordChanges = $selectedUserRow['passwordChanges'];
            $lastPasswordChangeDateTime = NULL;
            if ($selectedUserRow['lastPasswordChangeDateTimeValue']) {
                $lastPasswordChangeDateTime = new DateTime($selectedUserRow['lastPasswordChangeDateTimeValue']);
            }
            $archived = $selectedUserRow['archivedValue'] !== 0;

            $users []= new self(
                $id,
                $uuid,
                $name,
                $email, 
                $passwordHash,
                $role,
                $createdDateTime,
                $lastLoginDateTime,
                $passwordChanges,
                $lastPasswordChangeDateTime,
                $archived
            );
        }

        return $users;
    }

    public static function allAdministators(): array {
        $database = Database::connect();
        $selectedUserRows = NULL;
        try {
            $selectedUserRows = $database->selectRows(
                'USER',
                [
                    'USER_ID' => 'id',
                    'USER_UUID' => 'uuid',
                    'USER_NAME' => 'name',
                    'USER_EMAIL' => 'email',
                    'USER_PASSWORD_HASH' => 'passwordHash',
                    'USER_CREATED_DATETIME' => 'createdDateTimeValue',
                    'USER_LAST_LOGIN_DATETIME' => 'lastLoginDateTimeValue',
                    'USER_PASSWORD_CHANGES' => 'passwordChanges',
                    'USER_LAST_PASSWORD_CHANGE_DATETIME' => 'lastPasswordChangeDateTimeValue',
                    'USER_ARCHIVED' => 'archivedValue'
                ],
                '`USER_ROLE` = \'admin\''
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }
        $users = [];
        foreach ($selectedUserRows as $_ => $selectedUserRow) {
            $id = $selectedUserRow['id'];
            $uuid = $selectedUserRow['uuid'];
            $name = $selectedUserRow['name'];
            $email = $selectedUserRow['email'];
            $passwordHash = $selectedUserRow['passwordHash'];
            $createdDateTime = new DateTime($selectedUserRow['createdDateTimeValue']);
            $lastLoginDateTime = NULL;
            if ($selectedUserRow['lastLoginDateTimeValue']) {
                $lastLoginDateTime = new DateTime($selectedUserRow['lastLoginDateTimeValue']);
            }
            $passwordChanges = $selectedUserRow['passwordChanges'];
            $lastPasswordChangeDateTime = NULL;
            if ($selectedUserRow['lastPasswordChangeDateTimeValue']) {
                $lastPasswordChangeDateTime = new DateTime($selectedUserRow['lastPasswordChangeDateTimeValue']);
            }
            $archived = $selectedUserRow['archivedValue'] !== 0;

            $users []= new self(
                $id,
                $uuid,
                $name,
                $email, 
                $passwordHash,
                'admin',
                $createdDateTime,
                $lastLoginDateTime,
                $passwordChanges,
                $lastPasswordChangeDateTime,
                $archived
            );
        }

        return $users;
    }

    public static function allUsers(): array {
        $database = Database::connect();
        $selectedUserRows = NULL;
        try {
            $selectedUserRows = $database->selectRows(
                'USER',
                [
                    'USER_ID' => 'id',
                    'USER_UUID' => 'uuid',
                    'USER_NAME' => 'name',
                    'USER_EMAIL' => 'email',
                    'USER_PASSWORD_HASH' => 'passwordHash',
                    'USER_CREATED_DATETIME' => 'createdDateTimeValue',
                    'USER_LAST_LOGIN_DATETIME' => 'lastLoginDateTimeValue',
                    'USER_PASSWORD_CHANGES' => 'passwordChanges',
                    'USER_LAST_PASSWORD_CHANGE_DATETIME' => 'lastPasswordChangeDateTimeValue',
                    'USER_ARCHIVED' => 'archivedValue'
                ],
                '`USER_ROLE` = \'user\''
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }
        $users = [];
        foreach ($selectedUserRows as $_ => $selectedUserRow) {
            $id = $selectedUserRow['id'];
            $uuid = $selectedUserRow['uuid'];
            $name = $selectedUserRow['name'];
            $email = $selectedUserRow['email'];
            $passwordHash = $selectedUserRow['passwordHash'];
            $createdDateTime = new DateTime($selectedUserRow['createdDateTimeValue']);
            $lastLoginDateTime = NULL;
            if ($selectedUserRow['lastLoginDateTimeValue']) {
                $lastLoginDateTime = new DateTime($selectedUserRow['lastLoginDateTimeValue']);
            }
            $passwordChanges = $selectedUserRow['passwordChanges'];
            $lastPasswordChangeDateTime = NULL;
            if ($selectedUserRow['lastPasswordChangeDateTimeValue']) {
                $lastPasswordChangeDateTime = new DateTime($selectedUserRow['lastPasswordChangeDateTimeValue']);
            }
            $archived = $selectedUserRow['archivedValue'] !== 0;

            $users []= new self(
                $id,
                $uuid,
                $name,
                $email, 
                $passwordHash,
                'user',
                $createdDateTime,
                $lastLoginDateTime,
                $passwordChanges,
                $lastPasswordChangeDateTime,
                $archived
            );
        }

        return $users;
    }

    public static function login(string $email, string $password): self {
        $currentUser = self::current();
        if ($currentUser) {
            throw new UserLoggedInException();
        }
        
        $email = self::validateEmail($email) ?? throw new InvalidUserCredentialsException('invalid email');

        $database = Database::connect();
        $selectedUserRow = NULL;
        try {
            $selectedUserRow = $database->selectRow(
                'USER',
                [
                    'USER_PASSWORD_HASH' => 'passwordHash',
                    'USER_ARCHIVED' => 'archivedValue'
                ],
                '`USER_EMAIL` = :email',
                [
                    ':email' => $email
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }
        if (!$selectedUserRow) {
            throw new InvalidUserCredentialsException('email not found');
        }
        $passwordHash = $selectedUserRow['passwordHash'];
        $archived = $selectedUserRow['archivedValue'] !== 0;

        if ($archived) {
            throw new UserArchivedException();
        }

        if (!password_verify($password, $passwordHash)) {
            throw new InvalidUserCredentialsException('password is invalid');
        }

        $updatedUserRow = NULL;
        try {
            $updatedUserRow = $database->updateRow(
                'USER',
                [
                    'USER_LAST_LOGIN_DATETIME' => 'NOW()'
                ],
                '`USER_EMAIL` = :email',
                [
                    ':email' => $email
                ],
                [
                    'USER_ID' => 'id',
                    'USER_UUID' => 'uuid',
                    'USER_NAME' => 'name',
                    'USER_ROLE' => 'role',
                    'USER_CREATED_DATETIME' => 'createdDateTimeValue',
                    'USER_LAST_LOGIN_DATETIME' => 'lastLoginDateTimeValue',
                    'USER_PASSWORD_CHANGES' => 'passwordChanges',
                    'USER_LAST_PASSWORD_CHANGE_DATETIME' => 'lastPasswordChangeDateTimeValue'
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }
        $id = $updatedUserRow['id'];
        $uuid = $updatedUserRow['uuid'];
        $name = $updatedUserRow['name'];
        $role = $updatedUserRow['role'];
        $createdDateTime = new DateTime($updatedUserRow['createdDateTimeValue']);
        $lastLoginDateTime = new DateTime($updatedUserRow['lastLoginDateTimeValue']);
        $passwordChanges = $updatedUserRow['passwordChanges'];
        $lastPasswordChangeDateTime = NULL;
        if ($updatedUserRow['lastPasswordChangeDateTimeValue']) {
            $lastPasswordChangeDateTime = new DateTime($updatedUserRow['lastPasswordChangeDateTimeValue']);
        }

        $user = new self(
            $id,
            $uuid,
            $name,
            $email, 
            $passwordHash,
            $role,
            $createdDateTime,
            $lastLoginDateTime,
            $passwordChanges,
            $lastPasswordChangeDateTime,
            false
        );

        if (!session_id()) {
            session_start();
        }
        $_SESSION['user'] = serialize($user);
        
        $roleName = self::ROLES[$role];
        
        // TODO: Specify action
        Audit::log(
            "Logged in $roleName \"$name\" ($uuid)",
            NULL,
            ''
        );
        
        return $user;
    }

    public static function logout(): void {
        $currentUser = self::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }

        $uuid = $currentUser->uuid;
        $name = $currentUser->name;
        $role = $currentUser->role;

        $roleName = self::ROLES[$role];

        // TODO: Specify action
        Audit::log(
            "Logged out $roleName \"$name\" ($uuid)",
            NULL,
            ''
        );

        unset($_SESSION['user']);
        session_destroy();
    }

    public function getID(): int {
        return $this->id;
    }

    public function getUUID(): string {
        return $this->uuid;
    }

    public function getName(string $format = 'full'): string {
        $names = preg_split('/\p{Z}/', $this->name);
        return match ($format) {
            'first' => $names[0],
            'last' => $names[count($names)-1],
            'first-possessive' =>
                str_ends_with($names[0], 's') || str_ends_with($names[0], 'S') ?
                "$names[0]'" :
                "$names[0]'s",
            default =>$this->name
        };
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getRole(string $format = 'value'): string {
        return match($format) {
            'name' => self::ROLES[$this->role],
            default => $this->role
        };
    }

    public function getCreatedDateTime(string $format = 'value'): string|DateTime {
        return match($format) {
            'value' => $this->createdDateTime,
            default => $this->createdDateTime->format($format)
        };
    }

    public function getLastLoginDateTime(string $format = 'value'): null|string|DateTime {
        return match($format) {
            'value' => $this->lastLoginDateTime,
            default => $this->lastLoginDateTime->format($format)
        };
    }

    public function getPasswordChanges(): int {
        return $this->passwordChanges;
    }

    public function getLastPasswordChangeDateTime(string $format = 'value'): null|string|DateTime {
        return match($format) {
            'value' => $this->lastPasswordChangeDateTime,
            default => $this->lastPasswordChangeDateTime->format($format)
        };
    }

    public function is(self $user): ?bool {
        return $this->id === $user->id;
    }

    public function isAdministrator(): bool {
        return $this->role === 'admin';
    }

    public function isUser(): bool {
        return $this->role === 'user';
    }

    public function isArchived(): bool {
        return $this->archived;
    }

    public function notify(array $headers, string $subject, string $message): void {
        mail($this->email, $subject, $message, $headers);
    }

    public function changeName(string $newName): void {
        $currentUser = self::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }
        
        if (!($currentUser->is($this) || $currentUser->isAdministrator())) {
            throw new UserNotPermittedException();
        }
        
        if ($this->isArchived()) {
            throw new UserArchivedException();
        }

        $id = $this->id;
        $oldName = $this->name;
        $newName = self::validateName($newName) ?? throw new InvalidUserDataException('name');
        if ($oldName === $newName) {
            return;
        }

        $database = Database::connect();
        try {
            $database->updateRow(
                'USER',
                [
                    'USER_NAME' => ':newName'
                ],
                '`USER_ID` = :id',
                [
                    ':id' => $id,
                    ':newName' => $newName
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }

        $uuid = $this->uuid;
        $roleName = self::ROLES[$this->role];

        // TODO: Specify action
        Audit::log(
            "Changed $roleName User \"$oldName\" ($uuid) name",
            NULL,
            ''
        );

        $this->name = $newName;
    }

    public function changeEmail(string $newEmail): void {
        $currentUser = self::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }
        
        if (!($currentUser->is($this) || $currentUser->isAdministrator())) {
            throw new UserNotPermittedException();
        }
        
        if ($this->isArchived()) {
            throw new UserArchivedException();
        }

        $id = $this->id;
        $oldEmail = $this->email;
        $newEmail = self::validateEmail($newEmail) ?? throw new InvalidUserDataException('email');
        if ($oldEmail === $newEmail) {
            return;
        }

        $database = Database::connect();
        try {
            $database->updateRow(
                'USER',
                [
                    'USER_EMAIL' => ':newEmail'
                ],
                '`USER_ID` = :id',
                [
                    ':id' => $id,
                    ':newEmail' => $newEmail
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }

        $uuid = $this->uuid;
        $name = $this->name;
        $roleName = self::ROLES[$this->role];

        // TODO: Specify action
        Audit::log(
            "Changed $roleName User \"$name\" ($uuid) email",
            NULL,
            ''
        );

        $this->email = $newEmail;
    }

    public function changeRole(string $newRole): void {
        $currentUser = self::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }
        
        if ($currentUser->is($this) || !$currentUser->isAdministrator()) {
            throw new UserNotPermittedException();
        }

        if ($this->isArchived()) {
            throw new UserArchivedException();
        }

        $newRole = self::validateRole($newRole) ?? throw new InvalidUserDataException('role');

        $id = $this->id;
        $oldRole = $this->role;
        if ($oldRole === $newRole) {
            return;
        }

        $database = Database::connect();
        try {
            $database->updateRow(
                'USER',
                [
                    'USER_ROLE' => ':newRole'
                ],
                '`USER_ID` = :id',
                [
                    ':id' => $id,
                    ':newRole' => $newRole
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }

        $uuid = $this->uuid;
        $name = $this->name;
        $oldRoleName = self::ROLES[$oldRole];
        $newRoleName = self::ROLES[$newRole];

        // TODO: Specify action
        Audit::log(
            "Changed $oldRoleName user \"$name\" ($uuid) role",
            NULL,
            ''
        );        

        $this->role = $newRole;
    }

    public function changePassword(string $oldPassword, string $newPassword): void {
        $currentUser = self::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }
        
        if (!($currentUser->is($this) || $currentUser->isAdministrator())) {
            throw new UserNotPermittedException();
        }
        
        if ($this->isArchived()) {
            throw new UserArchivedException();
        }

        if (!password_verify($oldPassword, $this->passwordHash)) {
            throw new InvalidUserCredentialsException('old password does not match');
        }

        $id = $this->id;
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $database = Database::connect();
        $updatedPasswordRow = NULL;
        try {
            $updatedPasswordRow = $database->updateRow(
                'USER',
                [
                    'USER_PASSWORD_HASH' => ':newPasswordHash',
                    'USER_PASSWORD_CHANGES' => '`USER_PASSWORD_CHANGES` + 1',
                    'USER_LAST_PASSWORD_CHANGE_DATETIME' => 'NOW()'
                ],
                '`USER_ID` = :id',
                [
                    ':id' => $id,
                    ':newPasswordHash' => $newPasswordHash
                ],
                [
                    'USER_LAST_PASSWORD_CHANGE_DATETIME' => 'lastPasswordChangeDateTimeValue'
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }
        $newLastPasswordChangeDateTime = new DateTime($updatedPasswordRow['lastPasswordChangeDateTimeValue']);

        $uuid = $this->uuid;
        $roleName = self::ROLES[$this->role];
        $name = $this->name;

        // TODO: Specify action
        Audit::log(
            "Changed $roleName User \"$name\" ($uuid) password",
            NULL,
            ''
        );

        $this->passwordHash = $newPasswordHash;
        $this->passwordChanges++;
        $this->lastPasswordChangeDateTime = $newLastPasswordChangeDateTime;
    }

    public function archive(): void {
        $currentUser = self::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }
        
        if ($currentUser->is($this) || !$currentUser->isAdministrator()) {
            throw new UserNotPermittedException();
        }
        
        if ($this->isArchived()) {
            throw new UserArchivedException();
        }

        $id = $this->id;

        $database = Database::connect();
        try {
            $database->updateRow(
                'USER',
                [
                    'USER_ARCHIVED' => 'TRUE'
                ],
                '`USER_ID` = :id',
                [
                    ':id' => $id
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }

        $uuid = $this->uuid;
        $name = $this->name;
        $roleName = self::ROLES[$this->role];

        // TODO: Specify action
        Audit::log(
            "Archived \"$roleName\" user \"$name\" ($uuid)",
            NULL,
            ''
        );

        $this->archived = true;
    }

    public function unarchive(): void {
        $currentUser = self::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }
        
        if ($currentUser->is($this) || !$currentUser->isAdministrator()) {
            throw new UserNotPermittedException();
        }
        
        if (!$this->isArchived()) {
            throw new UserNotArchivedException();
        }

        $id = $this->id;

        $database = Database::connect();
        try {
            $database->updateRow(
                'USER',
                [
                    'USER_ARCHIVED' => 'FALSE'
                ],
                '`USER_ID` = :id',
                [
                    ':id' => $id
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }

        $uuid = $this->uuid;
        $name = $this->name;
        $roleName = self::ROLES[$this->role];

        // TODO: Specify action
        Audit::log(
            "Unarchived $roleName User \"$name\" ($uuid)",
            NULL,
            ''
        );

        $this->archived = false;
    }
}
?>