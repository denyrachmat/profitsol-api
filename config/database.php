<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_dms' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_DMS_HOST', 'localhost'),
            'port' => env('DB_DMS_PORT', '1433'),
            'database' => env('DB_DMS_DATABASE', 'forge'),
            'username' => env('DB_DMS_USERNAME', 'forge'),
            'password' => env('DB_DMS_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_dms_old' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_DMS_OLD_HOST', 'localhost'),
            'port' => env('DB_DMS_OLD_PORT', '1433'),
            'database' => env('DB_DMS_OLD_DATABASE', 'forge'),
            'username' => env('DB_DMS_OLD_USERNAME', 'forge'),
            'password' => env('DB_DMS_OLD_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_cms' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_CMS_HOST', 'localhost'),
            'port' => env('DB_CMS_PORT', '1433'),
            'database' => env('DB_CMS_DATABASE', 'forge'),
            'username' => env('DB_CMS_USERNAME', 'forge'),
            'password' => env('DB_CMS_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_mrs' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_MRS_HOST', 'localhost'),
            'port' => env('DB_MRS_PORT', '1433'),
            'database' => env('DB_MRS_DATABASE', 'forge'),
            'username' => env('DB_MRS_USERNAME', 'forge'),
            'password' => env('DB_MRS_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_ems2' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_EMS2_HOST', 'localhost'),
            'port' => env('DB_EMS2_PORT', '1433'),
            'database' => env('DB_EMS2_DATABASE', 'forge'),
            'username' => env('DB_EMS2_USERNAME', 'forge'),
            'password' => env('DB_EMS2_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_log' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_LOG_HOST', 'localhost'),
            'port' => env('DB_LOG_PORT', '1433'),
            'database' => env('DB_LOG_DATABASE', 'forge'),
            'username' => env('DB_LOG_USERNAME', 'forge'),
            'password' => env('DB_LOG_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_bim' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_BIM_HOST', 'localhost'),
            'port' => env('DB_BIM_PORT', '1433'),
            'database' => env('DB_BIM_DATABASE', 'forge'),
            'username' => env('DB_BIM_USERNAME', 'forge'),
            'password' => env('DB_BIM_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_pu' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_PU_HOST', 'localhost'),
            'port' => env('DB_PU_PORT', '1433'),
            'database' => env('DB_PU_DATABASE', 'forge'),
            'username' => env('DB_PU_USERNAME', 'forge'),
            'password' => env('DB_PU_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_itinv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_CRRPT_HOST', 'localhost'),
            'port' => env('DB_CRRPT_PORT', '1433'),
            'database' => env('DB_CRRPT_DATABASE', 'forge'),
            'username' => env('DB_CRRPT_USERNAME', 'forge'),
            'password' => env('DB_CRRPT_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_ceisa40' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_CEISA40_HOST', 'localhost'),
            'port' => env('DB_CEISA40_PORT', '1433'),
            'database' => env('DB_CEISA40_DATABASE', 'forge'),
            'username' => env('DB_CEISA40_USERNAME', 'forge'),
            'password' => env('DB_CEISA40_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_mega_ska' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_MEGA_SKA_HOST', 'localhost'),
            'port' => env('DB_MEGA_SKA_PORT', '1433'),
            'database' => env('DB_MEGA_SKA_DATABASE', 'forge'),
            'username' => env('DB_MEGA_SKA_USERNAME', 'forge'),
            'password' => env('DB_MEGA_SKA_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_mega_tyo' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_MEGA_TYO_HOST', 'localhost'),
            'port' => env('DB_MEGA_TYO_PORT', '1433'),
            'database' => env('DB_MEGA_TYO_DATABASE', 'forge'),
            'username' => env('DB_MEGA_TYO_USERNAME', 'forge'),
            'password' => env('DB_MEGA_TYO_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],


        'sqlsrv_mega_sme' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_MEGA_SME_HOST', 'localhost'),
            'port' => env('DB_MEGA_SME_PORT', '1433'),
            'database' => env('DB_MEGA_SME_DATABASE', 'forge'),
            'username' => env('DB_MEGA_SME_USERNAME', 'forge'),
            'password' => env('DB_MEGA_SME_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_mega_exim' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_MEGA_EXIM_HOST', 'localhost'),
            'port' => env('DB_MEGA_EXIM_PORT', '1433'),
            'database' => env('DB_MEGA_EXIM_DATABASE', 'forge'),
            'username' => env('DB_MEGA_EXIM_USERNAME', 'forge'),
            'password' => env('DB_MEGA_EXIM_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_psi_eng' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_PSI_ENG_HOST', 'localhost'),
            'port' => env('DB_PSI_ENG_PORT', '1433'),
            'database' => env('DB_PSI_ENG_DATABASE', 'forge'),
            'username' => env('DB_PSI_ENG_USERNAME', 'forge'),
            'password' => env('DB_PSI_ENG_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_conn_dyn' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_CD_HOST', 'localhost'),
            'port' => env('DB_CD_PORT', '1433'),
            'database' => env('DB_CD_DATABASE', 'forge'),
            'username' => env('DB_CD_USERNAME', 'forge'),
            'password' => env('DB_CD_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'predis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
