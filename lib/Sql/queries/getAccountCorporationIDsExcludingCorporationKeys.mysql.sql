-- Sql/queries/getAccountCorporationIDsExcludingCorporationKeys.mysql.sql
-- version 20160810093043.788
-- noinspection SqlResolveForFile
-- @formatter:off
SELECT DISTINCT acc."corporationID"
 FROM "{schema}"."{tablePrefix}accountCharacters" AS acc
 WHERE
 acc."corporationID" NOT IN (
 SELECT ac."corporationID"
 FROM "{schema}"."{tablePrefix}accountCharacters" AS ac
 JOIN "{schema}"."{tablePrefix}accountKeyBridge" AS akb
 ON (ac."characterID" = akb."characterID")
 JOIN "{schema}"."{tablePrefix}accountAPIKeyInfo" AS aaki
 ON (akb."keyID" = aaki."keyID")
 WHERE
 aaki."type"='Corporation'
 AND acc."corporationID" = ac."corporationID"
 );