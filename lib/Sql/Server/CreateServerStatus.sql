-- Sql/Server/CreateServerStatus.sql
-- version 20160201053949.169
START TRANSACTION;
INSERT INTO "{database}"."{table_prefix}utilDatabaseVersion" ("version")
VALUES
('20160201053949.169')
ON DUPLICATE KEY UPDATE "version" = VALUES("version");
COMMIT;
