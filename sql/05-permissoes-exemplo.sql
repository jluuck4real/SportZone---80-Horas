-- Execute como DBA depois de criar o login por meio seguro.
-- Exemplo: CREATE USER sportzone_app FOR LOGIN sportzone_app;
USE SportZone;
GO
IF DATABASE_PRINCIPAL_ID('sportzone_runtime') IS NULL CREATE ROLE sportzone_runtime;
GRANT SELECT,INSERT,UPDATE,DELETE ON SCHEMA::dbo TO sportzone_runtime;
-- Se já existir o usuário dedicado:
-- ALTER ROLE sportzone_runtime ADD MEMBER sportzone_app;
-- Não conceder db_owner, ALTER ou CREATE TABLE para a conta de runtime.
