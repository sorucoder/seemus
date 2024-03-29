CREATE TABLE `PERMISSIONS` (
    `PERMISSIONS_ID`      SERIAL,
    `USER_ID`             BIGINT UNSIGNED NOT NULL,
    `CONTENT_ID`          BIGINT UNSIGNED,
    `FILE_ID`             BIGINT UNSIGNED,
    `PERMISSIONS_READ`    BOOLEAN NOT NULL,
    `PERMISSIONS_WRITE`   BOOLEAN NOT NULL,
    `PERMISSIONS_ARCHIVE` BOOLEAN NOT NULL,
    `PERMISSIONS_DELETE`  BOOLEAN NOT NULL,
    CONSTRAINT UNIQUE (`USER_ID`, `CONTENT_ID`, `FILE_ID`),
    CONSTRAINT CHECK (
        (`CONTENT_ID` IS NOT NULL AND `FILE_ID` IS NULL) OR
        (`CONTENT_ID` IS NULL AND `FILE_ID` IS NOT NULL)
    ),
    CONSTRAINT CHECK (
        NOT (
            (NOT `PERMISSIONS_READ` AND `PERMISSIONS_WRITE`) OR
            (NOT `PERMISSIONS_ARCHIVE` AND `PERMISSIONS_DELETE`)
        )
    ),
    PRIMARY KEY (`PERMISSIONS_ID`),
    FOREIGN KEY (`USER_ID`) REFERENCES `USER`(`USER_ID`),
    FOREIGN KEY (`CONTENT_ID`) REFERENCES `CONTENT`(`CONTENT_ID`) ON DELETE CASCADE,
    FOREIGN KEY (`FILE_ID`) REFERENCES `FILE`(`FILE_ID`) ON DELETE CASCADE
);