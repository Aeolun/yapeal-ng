-- Sql/Corp/CreateWalletJournal.sql
-- version 20160629053500.715
CREATE TABLE "{schema}"."{tablePrefix}corpWalletJournal" (
    "amount"       DECIMAL(17, 2)       NOT NULL,
    "argID1"       BIGINT(20) UNSIGNED  NOT NULL,
    "argName1"     CHAR(100)            NOT NULL,
    "balance"      DECIMAL(17, 2)       NOT NULL,
    "date"         DATETIME             NOT NULL,
    "owner1TypeID" BIGINT(20) UNSIGNED  NOT NULL,
    "owner2TypeID" BIGINT(20) UNSIGNED  NOT NULL,
    "ownerID"      BIGINT(20) UNSIGNED  NOT NULL,
    "ownerID1"     BIGINT(20) UNSIGNED  NOT NULL,
    "ownerID2"     BIGINT(20) UNSIGNED  NOT NULL,
    "ownerName1"   CHAR(100)            NOT NULL,
    "ownerName2"   CHAR(100)            NOT NULL,
    "reason"       SMALLINT(3) UNSIGNED NOT NULL,
    "refID"        BIGINT(20) UNSIGNED  NOT NULL,
    "refTypeID"    SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY ("ownerID", "refID")
);
START TRANSACTION;
INSERT INTO "{schema}"."{tablePrefix}utilDatabaseVersion" ("version")
VALUES ('20160629053500.715')
ON DUPLICATE KEY UPDATE "version" = VALUES("version");
COMMIT;
