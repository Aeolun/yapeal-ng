-- Sql/Corp/CreateBlueprints.sql
-- version 20160629053412.885
CREATE TABLE "{database}"."{table_prefix}corpBlueprints" LIKE "{database}"."{table_prefix}charBlueprints";
START TRANSACTION;
INSERT INTO "{database}"."{table_prefix}utilDatabaseVersion" ("version")
VALUES
('20160629053412.885')
ON DUPLICATE KEY UPDATE "version" = VALUES("version");
COMMIT;
