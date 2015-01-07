CREATE TABLE "{database}"."{table_prefix}utilCachedUntil" (
    "apiName"     CHAR(32)            NOT NULL,
    "expires"     DATETIME            NOT NULL,
    "ownerID"     BIGINT(20) UNSIGNED NOT NULL,
    "sectionName" CHAR(8)             NOT NULL,
    PRIMARY KEY ("apiName","ownerID")
)
ENGINE ={ engine}
COLLATE utf8_unicode_ci;
CREATE TABLE "{database}"."{table_prefix}utilDatabaseVersion" (
    "version" CHAR(12) NOT NULL,
    PRIMARY KEY ("version")
)
ENGINE ={ engine}
COLLATE utf8_unicode_ci;
INSERT INTO "{database}"."{table_prefix}utilDatabaseVersion" ("version")
VALUES
    ('201501071713');
CREATE TABLE "{database}"."{table_prefix}utilEveApi" (
    "apiName"     CHAR(32)            NOT NULL,
    "interval"    INT(10) UNSIGNED    NOT NULL,
    "isActive"    TINYINT(1)          NOT NULL,
    "mask"        BIGINT(20) UNSIGNED NOT NULL,
    "sectionName" CHAR(8)             NOT NULL,
    PRIMARY KEY ("apiName","sectionName")
)
ENGINE ={ engine}
COLLATE utf8_unicode_ci;
START TRANSACTION;
INSERT INTO "{database}"."{table_prefix}utilEveApi" ("sectionName","apiName","mask","interval","isActive")
VALUES
    ('Account','AccountStatus',33554432,3600,1),
    ('Account','YapealCorporationSheet',0,21600,1),
    ('Api','CallList',1,86400,1),
    ('Char','AccountBalance',1,900,1),
    ('Char','AssetList',2,21600,1),
    ('Char','Blueprints',2,43200,1),
    ('Char','CalendarEventAttendees',4,3600,1),
    ('Char','CharacterSheet',8,3600,1),
    ('Char','ContactList',16,900,1),
    ('Char','ContactNotifications',32,21600,1),
    ('Char','Contracts',67108864,900,1),
    ('Char','FacWarStats',64,3600,1),
    ('Char','IndustryJobs',128,900,1),
    ('Char','IndustryJobsHistory',128,21600,1),
    ('Char','KillMails',256,1800,1),
    ('Char','Locations',134217728,3600,1),
    ('Char','MailBodies',512,1800,1),
    ('Char','MailingLists',1024,21600,1),
    ('Char','MailMessages',2048,1800,1),
    ('Char','MarketOrders',4096,3600,1),
    ('Char','Medals',8192,3600,1),
    ('Char','Notifications',16384,1800,1),
    ('Char','NotificationTexts',32768,1800,1),
    ('Char','Research',65536,900,1),
    ('Char','SkillInTraining',131072,300,1),
    ('Char','SkillQueue',262144,900,1),
    ('Char','Standings',524288,3600,1),
    ('Char','UpcomingCalendarEvents',1048576,900,1),
    ('Char','WalletJournal',2097152,1800,1),
    ('Char','WalletTransactions',4194304,3600,1),
    ('Corp','AccountBalance',1,900,1),
    ('Corp','AssetList',2,21600,1),
    ('Corp','Blueprints',2,43200,1),
    ('Corp','ContactList',16,900,1),
    ('Corp','ContainerLog',32,3600,1),
    ('Corp','Contracts',8388608,900,1),
    ('Corp','CorporationSheet',8,21600,1),
    ('Corp','Facilities',64,900,1),
    ('Corp','FacWarStats',64,3600,1),
    ('Corp','IndustryJobs',128,900,1),
    ('Corp','IndustryJobsHistory',128,21600,1),
    ('Corp','KillMails',256,1800,1),
    ('Corp','Locations',16777216,3600,1),
    ('Corp','MarketOrders',4096,3600,1),
    ('Corp','Medals',8192,3600,1),
    ('Corp','MemberMedals',4,3600,1),
    ('Corp','MemberSecurity',512,3600,1),
    ('Corp','MemberSecurityLog',1024,3600,1),
    ('Corp','MemberTrackingExtended',33554432,21600,1),
    ('Corp','MemberTrackingLimited',2048,3600,1),
    ('Corp','OutpostList',16384,3600,1),
    ('Corp','OutpostServiceDetail',32768,3600,1),
    ('Corp','Shareholders',65536,3600,1),
    ('Corp','Standings',262144,3600,1),
    ('Corp','StarbaseDetail',131072,3600,1),
    ('Corp','StarbaseList',524288,3600,1),
    ('Corp','Titles',4194304,3600,1),
    ('Corp','WalletJournal',1048576,1800,1),
    ('Corp','WalletTransactions',2097152,3600,1),
    ('Eve','AllianceList',1,3600,1),
    ('Eve','CertificateTree',2,86400,1),
    ('Eve','CharacterID',4,3600,1),
    ('Eve','CharacterInfo',0,3600,0),
    ('Eve','CharacterInfoPrivate',16777216,3600,1),
    ('Eve','CharacterInfoPublic',8388608,3600,1),
    ('Eve','CharacterName',8,3600,1),
    ('Eve','ConquerableStationList',16,3600,1),
    ('Eve','ErrorList',32,86400,1),
    ('Eve','FacWarStats',64,3600,1),
    ('Eve','FacWarTopStats',128,3600,1),
    ('Eve','RefTypes',256,86400,1),
    ('Eve','SkillTree',512,86400,1),
    ('Eve','YapealCorporationSheet',0,86400,0),
    ('Map','FacWarSystems',1,3600,1),
    ('Map','Jumps',2,3600,1),
    ('Map','Kills',4,3600,1),
    ('Map','Sovereignty',8,3600,1),
    ('Server','ServerStatus',1,300,1);
COMMIT;
CREATE TABLE "{database}"."{table_prefix}utilRegisteredKey" (
    "activeAPIMask" BIGINT(20) UNSIGNED DEFAULT NULL,
    "isActive"      TINYINT(1)          DEFAULT NULL,
    "keyID"         BIGINT(20) UNSIGNED NOT NULL,
    "vCode"         VARCHAR(64)         NOT NULL,
    PRIMARY KEY ("keyID")
)
ENGINE ={ engine}
COLLATE utf8_unicode_ci;
INSERT INTO "{database}"."{table_prefix}utilRegisteredKey" ("activeAPIMask","isActive","keyID","vCode")
VALUES
    (8388608,1,1156,'abc123');
CREATE TABLE "{database}"."{table_prefix}utilRegisteredUploader" (
    "isActive"            TINYINT(1)   DEFAULT NULL,
    "key"                 VARCHAR(255) DEFAULT NULL,
    "ownerID"             BIGINT(20) UNSIGNED NOT NULL,
    "uploadDestinationID" BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY ("ownerID","uploadDestinationID")
)
ENGINE ={ engine}
COLLATE utf8_unicode_ci;
CREATE TABLE "{database}"."{table_prefix}utilUploadDestination" (
    "isActive"            TINYINT(1)   DEFAULT NULL,
    "name"                VARCHAR(25)  DEFAULT NULL,
    "uploadDestinationID" BIGINT(20) UNSIGNED NOT NULL,
    "url"                 VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY ("uploadDestinationID")
)
ENGINE ={ engine}
COLLATE utf8_unicode_ci;
CREATE TABLE "{database}"."{table_prefix}utilXmlCache" (
    "hash"        CHAR(40)  NOT NULL,
    "apiName"     CHAR(32)  NOT NULL,
    "modified"    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    "sectionName" CHAR(8)   NOT NULL,
    "xml"         LONGTEXT,
    PRIMARY KEY ("hash")
)
ENGINE ={ engine}
COLLATE utf8_unicode_ci;
ALTER TABLE "{database}"."{table_prefix}utilXmlCache" ADD INDEX "utilXmlCache1" ("sectionName");
ALTER TABLE "{database}"."{table_prefix}utilXmlCache" ADD INDEX "utilXmlCache2" ("apiName");
