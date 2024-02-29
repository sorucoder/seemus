<?php
declare(strict_types=1);

require_once $_SERVER['ROOT_PATH'] . '/class/database.class.php';
require_once $_SERVER['ROOT_PATH'] . '/class/user.class.php';
require_once $_SERVER['ROOT_PATH'] . '/class/content.class.php';
require_once $_SERVER['ROOT_PATH'] . '/class/file.class.php';
require_once $_SERVER['ROOT_PATH'] . '/class/audit.class.php';

require_once $_SERVER['ROOT_PATH'] . '/class/exception/media/invalid_permissions.exception.php';
require_once $_SERVER['ROOT_PATH'] . '/class/exception/user/user_archived.exception.php';
require_once $_SERVER['ROOT_PATH'] . '/class/exception/user/user_not_logged_in.exception.php';
require_once $_SERVER['ROOT_PATH'] . '/class/exception/user/user_permitted.exception.php';
require_once $_SERVER['ROOT_PATH'] . '/class/exception/user/user_not_permitted.exception.php';

final class Permissions {
    private int $id;
    private User $user;
    private Content|File $media;
    private bool $read;
    private bool $write;
    private bool $archive;
    private bool $delete;

    private function __construct(int $id, User $user, Content|File $media, bool $read, bool $write, bool $archive, bool $delete) {
        $this->id = $id;
        $this->user = $user;
        $this->media = $media;
        $this->read = $read;
        $this->write = $write;
        $this->archive = $archive;
        $this->delete = $delete;
    }

    public static function establish(User $user, Content|File $media, bool $read, bool $write, bool $archive, bool $delete): self {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        } else if ($user->isAdministrator()) {
            throw new UserPermittedException();
        } else if ($user->isArchived()) {
            throw new UserArchivedException();
        }

        if (!$read && $write) {
            throw new InvalidPermissionsException('writeable but not readable');
        } else if (!$archive && $delete) {
            throw new InvalidPermissionsException('deleteable but not archiveable');
        }

        $currentUserID = $currentUser->getID();
        $mediaID = $media->getID();

        $database = Database::connect();
        $insertedPermissionsRow = NULL;
        try {
            $insertedPermissionsRow = $database->insertRow(
                'PERMISSIONS',
                [
                    'USER_ID' => ':userID',
                    'CONTENT_ID' => $media instanceof Content ? ':mediaID' : 'NULL',
                    'FILE_ID' => $media instanceof File ? ':mediaID' : 'NULL',
                    'PERMISSIONS_READ' => ':read',
                    'PERMISSIONS_WRITE' => ':write',
                    'PERMISSIONS_ARCHIVE' => ':archive',
                    'PERMISSIONS_DELETE' => ':delete'
                ],
                [
                    ':userID' => $currentUserID,
                    ':mediaID' => $mediaID,
                    ':read' => $read,
                    ':write' => $write,
                    ':archive' => $archive,
                    ':delete' => $delete
                ],
                [
                    'PERMISSIONS_ID' => 'id'
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }
        $id = $insertedPermissionsRow['id'];

        $permissions = new self(
            $id,
            $user,
            $media,
            $read,
            $write,
            $archive,
            $delete
        );

        $userName = $user->getName();
        $userUUID = $user->getUUID();
        $userRole = $user->getRole('name');
        $mediaType = $media::class;
        $mediaUUID = $media->getUUID();
        $mediaTitle = $media->getTitle();

        // TODO: Specify action
        Audit::log(
            "Created permissions for $userRole user \"$userName\" ($userUUID) regarding $mediaType \"$mediaTitle\" ($mediaUUID)",
            $media,
            ''
        );

        return $permissions;
    }

    public static function fromID(int $id): ?self {
        $database = Database::connect();
        $selectedPermissionsRow = NULL;
        try {
            $selectedPermissionsRow = $database->selectRow(
                'PERMISSIONS',
                [
                    'USER_ID' => 'userID',
                    'CONTENT_ID' => 'contentID',
                    'FILE_ID' => 'fileID',
                    'PERMISSIONS_READABLE' => 'readableValue',
                    'PERMISSIONS_WRITEABLE' => 'writeableValue',
                    'PERMISSIONS_ARCHIVEABLE' => 'archiveableValue',
                    'PERMISSIONS_DELETEABLE' => 'deleteableValue'
                ],
                '`PERMISSIONS_ID` = :id',
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
        if (!$selectedPermissionsRow) {
            return NULL;
        }
        $user = User::fromID($selectedPermissionsRow['userID']);
        $media = NULL;
        if ($selectedPermissionsRow['contentID']) {
            $media = Content::fromID($selectedPermissionsRow['contentID']);
        } else if ($selectedPermissionsRow['fileID']) {
            $media = File::fromID($selectedPermissionsRow['fileID']);
        }
        $readable = $selectedPermissionsRow['readableValue'] !== 0;
        $writeable = $selectedPermissionsRow['writeableValue'] !== 0;
        $archiveable = $selectedPermissionsRow['archiveableValue'] !== 0;
        $deleteable = $selectedPermissionsRow['deleteableValue'] !== 0;

        $permissions = new self(
            $id,
            $user,
            $media,
            $readable,
            $writeable,
            $archiveable,
            $deleteable
        );

        return $permissions;
    }

    public static function between(User $user, Content|File $media): ?self {
        $userID = $user->getID();
        $mediaID = $media->getID();
        
        $database = Database::connect();
        $selectedPermissionsRow = NULL;
        try {
            $selectedPermissionsRow = $database->selectRow(
                'PERMISSIONS',
                [
                    'PERMISSIONS_ID' => 'id',
                    'USER_ID' => 'userID',
                    'CONTENT_ID' => 'contentID',
                    'FILE_ID' => 'fileID',
                    'PERMISSIONS_READ' => 'readableValue',
                    'PERMISSIONS_WRITE' => 'writeableValue',
                    'PERMISSIONS_ARCHIVE' => 'archiveableValue',
                    'PERMISSIONS_DELETE' => 'deleteableValue'
                ],
                '`USER_ID` = :userID AND ' .
                ($media instanceof Content ? '`CONTENT_ID` = :mediaID' : '`CONTENT_ID` IS NULL') .
                ' AND ' .
                ($media instanceof File ? '`FILE_ID` = :mediaID' : '`FILE_ID` IS NULL'),
                [
                    ':userID' => $userID,
                    ':mediaID' => $mediaID
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions
            default:
                throw $exception;
            }
        }
        if (!$selectedPermissionsRow) {
            return NULL;
        }
        $id = $selectedPermissionsRow['id'];
        $readable = $selectedPermissionsRow['readableValue'] !== 0;
        $writeable = $selectedPermissionsRow['writeableValue'] !== 0;
        $archiveable = $selectedPermissionsRow['archiveableValue'] !== 0;
        $deleteable = $selectedPermissionsRow['deleteableValue'] !== 0;

        $permissions = new self(
            $id,
            $user,
            $media,
            $readable,
            $writeable,
            $archiveable,
            $deleteable
        );

        return $permissions;
    }

    public function canRead(): bool {
        return $this->read;
    }
    
    public function canWrite(): bool {
        return $this->write;
    }

    public function canArchive(): bool {
        return $this->archive;
    }

    public function canDelete(): bool {
        return $this->delete;
    }

    public function changeRead(bool $newRead): void {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        } else if (!($currentUser->is($this->user) || $currentUser->isAdministrator())) {
            throw new UserNotPermittedException();
        }

        $id = $this->id;
        $oldRead = $this->read;
        if ($oldRead === $newRead) {
            return;
        }

        $database = Database::connect();
        $permissionsUpdationData = [
            'PERMISSIONS_READ' => ':newRead'
        ];
        if (!$newRead) {
            $permissionsUpdationData['PERMISSIONS_WRITE'] = 'FALSE';
        }

        try {
            $database->updateRow(
                'PERMISSIONS',
                $permissionsUpdationData,
                '`PERMISSIONS_ID` = :id',
                [
                    ':id' => $id,
                    ':newRead' => $newRead
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions here
            default:
                throw $exception;
            }
        }

        $media = $this->media;
        $mediaType = $this->media::class;
        $mediaUUID = $this->media->getUUID();
        $mediaTitle = $this->media->getTitle();
        $userUUID = $this->user->getUUID();
        $userName = $this->user->getName();
        $userRole = $this->user->getRole('name');
        $which = $newRead ? 'read' : 'read and write';
        $state = $newRead ? 'on' : 'off';

        // TODO: Specify action
        Audit::log(
            "Changed $which permissions regarding $mediaType \"$mediaTitle\" ($mediaUUID) for $userRole \"$userName\" ($userUUID) to $state",
            $media,
            ''
        );

        $this->read = $newRead;
    }

    public function changeWrite(bool $newWrite): void {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        } else if (!($currentUser->is($this->user) || $currentUser->isAdministrator())) {
            throw new UserNotPermittedException();
        }

        $id = $this->id;
        $oldWrite = $this->read;
        if ($oldWrite === $newWrite) {
            return;
        }

        $database = Database::connect();

        try {
            $database->updateRow(
                'PERMISSIONS',
                [
                    'PERMISSIONS_WRITE' => ':newWrite'
                ],
                '`PERMISSIONS_ID` = :id',
                [
                    ':id' => $id,
                    ':newWrite' => $newWrite
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions here
            default:
                throw $exception;
            }
        }

        $media = $this->media;
        $mediaType = $this->media::class;
        $mediaUUID = $this->media->getUUID();
        $mediaTitle = $this->media->getTitle();
        $userUUID = $this->user->getUUID();
        $userName = $this->user->getName();
        $userRole = $this->user->getRole('name');
        $state = $oldWrite ? 'on' : 'off';

        // TODO: Specify action
        Audit::log(
            "Changed write permissions regarding $mediaType \"$mediaTitle\" ($mediaUUID) for $userRole \"$userName\" ($userUUID) to $state",
            $media,
            ''
        );

        $this->write = $newWrite;
    }

    public function changeArchive(bool $newArchive): void {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        } else if (!($currentUser->is($this->user) || $currentUser->isAdministrator())) {
            throw new UserNotPermittedException();
        }

        $id = $this->id;
        $oldArchive = $this->read;
        if ($oldArchive === $newArchive) {
            return;
        }

        $database = Database::connect();
        $permissionsUpdationData = [
            'PERMISSIONS_ARCHIVE' => ':newArchive'
        ];
        if (!$newArchive) {
            $permissionsUpdationData['PERMISSIONS_DELETE'] = 'FALSE';
        }

        try {
            $database->updateRow(
                'PERMISSIONS',
                $permissionsUpdationData,
                '`PERMISSIONS_ID` = :id',
                [
                    ':id' => $id,
                    ':newArchive' => $newArchive
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions here
            default:
                throw $exception;
            }
        }

        $media = $this->media;
        $mediaType = $this->media::class;
        $mediaUUID = $this->media->getUUID();
        $mediaTitle = $this->media->getTitle();
        $userUUID = $this->user->getUUID();
        $userName = $this->user->getName();
        $userRole = $this->user->getRole('name');
        $which = $newArchive ? 'archive' : 'archive and delete';
        $state = $newArchive ? 'on' : 'off';

        // TODO: Specify action
        Audit::log(
            "Changed $which permissions regarding $mediaType \"$mediaTitle\" ($mediaUUID) for $userRole \"$userName\" ($userUUID) to $state",
            $media,
            ''
        );

        $this->archive = $newArchive;
    }

    public function changeDelete(bool $newDelete): void {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        } else if (!($currentUser->is($this->user) || $currentUser->isAdministrator())) {
            throw new UserNotPermittedException();
        }

        $id = $this->id;
        $oldDelete = $this->read;
        if ($oldDelete === $newDelete) {
            return;
        }

        $database = Database::connect();

        try {
            $database->updateRow(
                'PERMISSIONS',
                [
                    'PERMISSIONS_DELETE' => ':newDelete'
                ],
                '`PERMISSIONS_ID` = :id',
                [
                    ':id' => $id,
                    ':newDelete' => $newDelete
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions here
            default:
                throw $exception;
            }
        }

        $media = $this->media;
        $mediaType = $this->media::class;
        $mediaUUID = $this->media->getUUID();
        $mediaTitle = $this->media->getTitle();
        $userUUID = $this->user->getUUID();
        $userName = $this->user->getName();
        $userRole = $this->user->getRole('name');
        $state = $newDelete ? 'on' : 'off';

        // TODO: Specify action
        Audit::log(
            "Changed delete permissions regarding $mediaType \"$mediaTitle\" ($mediaUUID) for $userRole \"$userName\" ($userUUID) to $state",
            $media,
            ''
        );

        $this->delete = $newDelete;
    }
}
?>