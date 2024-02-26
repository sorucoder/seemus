CREATE TABLE `AUDIT` (
    `AUDIT_ID`          SERIAL,
    `AUDIT_DESCRIPTION` TEXT NOT NULL,
    `USER_ID`           BIGINT UNSIGNED NOT NULL,
    `CONTENT_ID`        BIGINT UNSIGNED,
    `FILE_ID`           BIGINT UNSIGNED,
    `AUDIT_DATETIME`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `AUDIT_ACTION`      TEXT NOT NULL,
    CONSTRAINT CHECK (NOT (`CONTENT_ID` IS NOT NULL AND `FILE_ID` IS NOT NULL)),
    PRIMARY KEY (`AUDIT_ID`),
    FOREIGN KEY (`USER_ID`) REFERENCES `USER`(`USER_ID`),
    FOREIGN KEY (`CONTENT_ID`) REFERENCES `CONTENT`(`CONTENT_ID`),
    FOREIGN KEY (`FILE_ID`) REFERENCES `FILE`(`FILE_ID`)
);