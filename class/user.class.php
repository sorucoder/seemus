<?php
declare(strict_types=1);

require_once './class/role.class.php';

class User {
    private string $id;
    private string $name;
    private string $email;
    private string $password;
    private Role $role;
    private DateTime $createdDate;
    private DateTime $loginDate;
    private int $passwordChanges;
    private DateTime $passwordChangeDate;
    private bool $archived;

    private function __construct(string $id, string $name, string $email, string $password, Role $role, DateTime $createdDate, DateTime $loginDate, int $passwordChanges, DateTime $passwordChangeDate, bool $archived) {
        // TODO: stub
    }

    public static function create(string $name, string $email, string $password, Role $role): ?self {
        // TODO: stub
    }

    public static function fromId(string $id): ?self {
        // TODO: stub
    }

    public static function login(string $email, string $password): ?self {
        // TODO: stub
    }

    public function getId(): string {
        // TODO: stub
    }

    public function getName(): string {
        // TODO: stub
    }

    public function setEmail(string $email) {
        // TODO: stub
    }

    public function getRole(): Role {
        // TODO: stub
    }

    public function getCreatedDate(): DateTime {
        // TODO: stub
    }

    public function getLoginDate(): DateTime {
        // TODO: stub
    }

    public function getPasswordChanges(): int {
        // TODO: stub
    }

    public function getPasswordChangeDate(): DateTime {
        // TODO: stub
    }

    public function isArchived(): bool {
        // TODO: stub
    }

    public function logout(): void {
        // TODO: stub
    }

    public function notify(array $headers, string $subject, string $message) {
        // TODO: stub
    }

    public function changePassword(string $oldPassword, string $newPassword) {
        // TODO: stub
    }

    public function archive(): void {
        // TODO: stub
    }
}
?>