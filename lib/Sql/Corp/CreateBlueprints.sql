-- Sql/Corp/CreateBlueprints.sql
-- version 20160629053412.885
CREATE TABLE "{database}"."{table_prefix}corpBlueprints" (
    "flagID" BIGINT(20) UNSIGNED NOT NULL,
    "itemID" BIGINT(20) UNSIGNED NOT NULL,
    "locationID" BIGINT(20) UNSIGNED NOT NULL,
    "materialEfficiency" VARCHAR(255) DEFAULT '',
    "ownerID" BIGINT(20) UNSIGNED NOT NULL,
    "quantity" VARCHAR(255) DEFAULT '',
    "runs" VARCHAR(255) DEFAULT '',
    "timeEfficiency" TINYINT(3) UNSIGNED NOT NULL,
    "typeID" BIGINT(20) UNSIGNED NOT NULL,
    "typeName" CHAR(100) NOT NULL,
    PRIMARY KEY ("ownerID","itemID")
);
START TRANSACTION;
INSERT INTO "{database}"."{table_prefix}utilDatabaseVersion" ("version")
VALUES
('20160629053412.885')
ON DUPLICATE KEY UPDATE "version" = VALUES("version");
COMMIT;
