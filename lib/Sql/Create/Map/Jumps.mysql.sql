-- Sql/Create/Map/Jumps.sql
-- version 20161202044339.050
CREATE TABLE "{schema}"."{tablePrefix}mapJumps" (
    "shipJumps"     BIGINT(20) UNSIGNED NOT NULL,
    "solarSystemID" BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY ("solarSystemID")
);
START TRANSACTION;
-- @formatter:off
INSERT INTO "{schema}"."{tablePrefix}yapealSchemaVersion" ("version")
    VALUES ('20161202044339.050');
COMMIT;
