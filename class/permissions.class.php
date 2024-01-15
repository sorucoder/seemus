<?php
declare(strict_types=1);

require_once './class/user.class.php';
require_once './class/content.class.php';
require_once './class/file.class.php';

class Permissions {
    private User $user;
    private Content|File $media;
    private bool $readable;
    private bool $writeable;
    private bool $archiveable;
    private bool $deleteable;

    private function __construct(User $user, Content|File $media, bool $readable, bool $writeable, bool $archiveable, bool $deleteable) {
        // TODO: stub
    }

    public static function create(User $user, Content|File $media, bool $readable, bool $writeable, bool $archiveable, bool $deleteable): ?self {
        // TODO: stub
    }

    public static function fromUserAndMedia(User $user, Content|File $media): ?self {
        // TODO: stub
    }

    public function permitsReading(): bool {
        // TODO: stub
    }

    public function changeReadability(bool $readable) {
        // TODO: stub
    }

    public function permitsWriting(): bool {
        // TODO: stub
    }

    public function changeWriteability(bool $writable) {
        // TODO: stub
    }

    public function permitsArchiving(): bool {
        // TODO: stub
    }

    public function changeArchiveability(bool $archiveable) {
        // TODO: stub
    }

    public function permitsDeleting(): bool {
        // TODO: stub
    }

    public function changeDeleteable(bool $deleteable) {
        // TODO: stub
    }
}

?>