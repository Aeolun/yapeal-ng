-- Sql/Char/CreateMailingLists.sql
-- version 20160629053436.736
CREATE TABLE "{database}"."{table_prefix}charMailingLists" (
    "displayName" CHAR(100)           NOT NULL,
    "listID"      BIGINT(20) UNSIGNED NOT NULL,
    "ownerID"     BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY ("ownerID", "listID")
);
START TRANSACTION;
INSERT INTO "{database}"."{table_prefix}utilDatabaseVersion" ("version")
VALUES ('20160629053436.736')
ON DUPLICATE KEY UPDATE "version" = VALUES("version");
COMMIT;