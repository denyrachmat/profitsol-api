<?php

namespace App\Support;

/**
 * Central place for database-engine differences.
 *
 * The application is built primarily for SQL Server (sqlsrv). A handful of
 * features only make sense on certain engines (T-SQL stored procedures,
 * cross-database joins, `USE db`). Instead of hardcoding `sqlsrv` all over
 * the codebase, ask this class, so a non-SQL-Server deployment can either
 * use the correct syntax or fail gracefully.
 *
 * The driver is resolved from the connection's config (config/database.php),
 * which is env-driven for the core connections thanks to the installer.
 */
class SqlDialect
{
    public const SQLSERVER = 'sqlsrv';
    public const MYSQL = 'mysql';
    public const MARIADB = 'mariadb';
    public const POSTGRES = 'pgsql';
    public const SQLITE = 'sqlite';

    /**
     * Driver name for a connection (defaults to the default connection).
     */
    public static function driver(?string $connection = null): string
    {
        try {
            $connection = $connection ?: config('database.default');

            return (string) config("database.connections.{$connection}.driver", '');
        } catch (\Throwable $e) {
            return '';
        }
    }

    public static function isSqlServer(?string $connection = null): bool
    {
        return static::driver($connection) === self::SQLSERVER;
    }

    public static function isMySql(?string $connection = null): bool
    {
        return in_array(static::driver($connection), [self::MYSQL, self::MARIADB], true);
    }

    public static function isPostgres(?string $connection = null): bool
    {
        return static::driver($connection) === self::POSTGRES;
    }

    public static function isSqlite(?string $connection = null): bool
    {
        return static::driver($connection) === self::SQLITE;
    }

    /*
    |--------------------------------------------------------------------------
    | Capabilities
    |--------------------------------------------------------------------------
    */

    /** T-SQL `EXEC <procedure>` style stored procedures. */
    public static function supportsStoredProcedures(?string $connection = null): bool
    {
        return static::isSqlServer($connection);
    }

    /** Raw dynamic SQL executed with the `SET NOCOUNT ON; ...` T-SQL preamble. */
    public static function supportsDynamicSql(?string $connection = null): bool
    {
        return static::isSqlServer($connection);
    }

    /** `db.schema.table` (SQL Server) / `db.table` (MySQL) cross-database refs. */
    public static function supportsCrossDatabaseQueries(?string $connection = null): bool
    {
        return static::isSqlServer($connection) || static::isMySql($connection);
    }

    /** `USE <database>` session switching. */
    public static function supportsUseDatabase(?string $connection = null): bool
    {
        return static::isSqlServer($connection) || static::isMySql($connection);
    }

    /** `SET ROWCOUNT n` row capping (SQL Server only). */
    public static function supportsSetRowcount(?string $connection = null): bool
    {
        return static::isSqlServer($connection);
    }

    /*
    |--------------------------------------------------------------------------
    | Expression builders
    |--------------------------------------------------------------------------
    */

    /** Engine equivalent of `ISNULL(expr, default)` / `IFNULL` / `COALESCE`. */
    public static function coalesce(string $expr, string $default, ?string $connection = null): string
    {
        if (static::isSqlServer($connection)) {
            return "ISNULL({$expr}, {$default})";
        }

        if (static::isMySql($connection)) {
            return "IFNULL({$expr}, {$default})";
        }

        return "COALESCE({$expr}, {$default})";
    }

    /** Engine "current timestamp" expression. */
    public static function now(?string $connection = null): string
    {
        if (static::isSqlServer($connection)) {
            return 'GETDATE()';
        }

        if (static::isSqlite($connection)) {
            return "datetime('now')";
        }

        return 'NOW()';
    }

    /** Cast an expression to an unbounded text type. */
    public static function castText(string $expr, ?string $connection = null): string
    {
        if (static::isSqlServer($connection)) {
            return "CAST({$expr} AS NVARCHAR(MAX))";
        }

        return "CAST({$expr} AS TEXT)";
    }

    /** Quote a single identifier for the engine. */
    public static function quoteIdentifier(string $identifier, ?string $connection = null): string
    {
        if (static::isSqlServer($connection)) {
            return '[' . str_replace(']', ']]', $identifier) . ']';
        }

        if (static::isMySql($connection)) {
            return '`' . str_replace('`', '``', $identifier) . '`';
        }

        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * Apply a row cap to a SELECT using the engine syntax: `TOP n` on SQL
     * Server, `LIMIT n` elsewhere. Returns the SQL unchanged when it cannot
     * be rewritten safely (e.g. CTEs / UNIONs) — callers should still cap
     * results with a fetch loop.
     */
    public static function limit(string $sql, int $limit, ?string $connection = null): string
    {
        $limit = max(1, $limit);

        if (static::isSqlServer($connection)) {
            if (preg_match('/^\s*SELECT\s+(?:DISTINCT\s+)?TOP\b/i', $sql)) {
                return $sql;
            }
            if (preg_match('/^(\s*SELECT\s+(?:DISTINCT\s+)?)/i', $sql, $m)) {
                return $m[1] . 'TOP ' . $limit . ' ' . substr($sql, strlen($m[1]));
            }

            return $sql;
        }

        if (preg_match('/\bLIMIT\b/i', $sql)) {
            return $sql;
        }

        if (preg_match('/^\s*SELECT\b/i', $sql)) {
            return rtrim(rtrim($sql), ';') . ' LIMIT ' . $limit;
        }

        return $sql;
    }
}
