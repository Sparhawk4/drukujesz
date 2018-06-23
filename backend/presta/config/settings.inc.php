<?php
define('_DB_SERVER_', getenv('MYSQL_HOSTNAME'));
define('_DB_NAME_', getenv('MYSQL_DATABASE'));
define('_DB_USER_', getenv('MYSQL_USERNAME'));
define('_DB_PASSWD_', getenv('MYSQL_ROOT_PASSWORD'));
define('_DB_PREFIX_', 'ps_');
define('_MYSQL_ENGINE_', 'InnoDB');
define('_PS_CACHING_SYSTEM_', 'CacheMemcache');
define('_PS_CACHE_ENABLED_', '0');
define('_COOKIE_KEY_', 'hE0E6CxeTTOGE1sOMULg5T6LCMGJxHmSpHbZCeD013drW3wBxbHr52jG');
define('_COOKIE_IV_', 'fWtK1OSg');
define('_PS_CREATION_DATE_', '2018-06-23');
if (!defined('_PS_VERSION_'))
	define('_PS_VERSION_', '1.6.1.19');
define('_RIJNDAEL_KEY_', 'kgkXfgYLN3s97oIEjzxzyVvzOTgJpTUU');
define('_RIJNDAEL_IV_', 'bV3s3LcYnDE6Rbn5JjVKgw==');
