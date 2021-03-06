-- Sql/Create/Corp/CustomsOffices.sql
-- version 20161202044339.031
CREATE TABLE "{schema}"."{tablePrefix}corpCustomsOffices" (
    "allowAlliance"           TINYINT(1) UNSIGNED      NOT NULL,
    "allowStandings"          TINYINT(1) UNSIGNED      NOT NULL,
    "itemID"                  BIGINT(20) UNSIGNED      NOT NULL,
    "ownerID"                 BIGINT(20) UNSIGNED      NOT NULL,
    "reinforceHour"           TINYINT(2) UNSIGNED      NOT NULL,
    "solarSystemID"           BIGINT(20) UNSIGNED      NOT NULL,
    "solarSystemName"         CHAR(100)                NOT NULL,
    "standingLevel"           DECIMAL(5, 2)            NOT NULL,
    "taxRateAlliance"         DECIMAL(17, 16) UNSIGNED NOT NULL,
    "taxRateCorp"             DECIMAL(17, 16) UNSIGNED NOT NULL,
    "taxRateStandingBad"      DECIMAL(17, 16) UNSIGNED NOT NULL,
    "taxRateStandingGood"     DECIMAL(17, 16) UNSIGNED NOT NULL,
    "taxRateStandingHigh"     DECIMAL(17, 16) UNSIGNED NOT NULL,
    "taxRateStandingHorrible" DECIMAL(17, 16) UNSIGNED NOT NULL,
    "taxRateStandingNeutral"  DECIMAL(17, 16) UNSIGNED NOT NULL,
    PRIMARY KEY ("ownerID", "itemID")
);
START TRANSACTION;
-- @formatter:off
INSERT INTO "{schema}"."{tablePrefix}yapealSchemaVersion" ("version")
    VALUES ('20161202044339.031');
COMMIT;
