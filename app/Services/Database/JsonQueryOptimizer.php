<?php

namespace App\Services\Database;

use Illuminate\Database\Eloquent\Builder;

class JsonQueryOptimizer
{
    /**
     * Optimize JSON field queries based on database type
     */
    public static function optimizeJsonQuery(Builder $query, string $jsonField, string $path, $value, string $operator = '='): Builder
    {
        $databaseType = config('database.default');

        switch ($databaseType) {
            case 'mysql':
                return self::optimizeMySQLJsonQuery($query, $jsonField, $path, $value, $operator);
            case 'pgsql':
                return self::optimizePostgreSQLJsonQuery($query, $jsonField, $path, $value, $operator);
            case 'sqlite':
                return self::optimizeSQLiteJsonQuery($query, $jsonField, $path, $value, $operator);
            default:
                // Fallback to Laravel's default JSON query
                return $query->where("{$jsonField}->{$path}", $operator, $value);
        }
    }

    /**
     * Optimize MySQL JSON queries
     */
    private static function optimizeMySQLJsonQuery(Builder $query, string $jsonField, string $path, $value, string $operator): Builder
    {
        // Use JSON_EXTRACT for better performance with indexes
        $jsonPath = self::buildMySQLJsonPath($path);

        switch ($operator) {
            case '=':
                return $query->whereRaw("JSON_EXTRACT({$jsonField}, ?) = ?", [$jsonPath, $value]);
            case '!=':
            case '<>':
                return $query->whereRaw("JSON_EXTRACT({$jsonField}, ?) != ?", [$jsonPath, $value]);
            case '>':
                return $query->whereRaw("JSON_EXTRACT({$jsonField}, ?) > ?", [$jsonPath, $value]);
            case '>=':
                return $query->whereRaw("JSON_EXTRACT({$jsonField}, ?) >= ?", [$jsonPath, $value]);
            case '<':
                return $query->whereRaw("JSON_EXTRACT({$jsonField}, ?) < ?", [$jsonPath, $value]);
            case '<=':
                return $query->whereRaw("JSON_EXTRACT({$jsonField}, ?) <= ?", [$jsonPath, $value]);
            case 'like':
                return $query->whereRaw("JSON_EXTRACT({$jsonField}, ?) LIKE ?", [$jsonPath, $value]);
            case 'contains':
                return $query->whereRaw("JSON_CONTAINS({$jsonField}, ?, ?)", [json_encode($value), $jsonPath]);
            default:
                return $query->whereRaw("JSON_EXTRACT({$jsonField}, ?) {$operator} ?", [$jsonPath, $value]);
        }
    }

    /**
     * Optimize PostgreSQL JSON queries
     */
    private static function optimizePostgreSQLJsonQuery(Builder $query, string $jsonField, string $path, $value, string $operator): Builder
    {
        // Use ->> for text extraction or -> for JSON extraction
        $jsonPath = self::buildPostgreSQLJsonPath($path);

        switch ($operator) {
            case '=':
                return $query->whereRaw("{$jsonField}{$jsonPath} = ?", [$value]);
            case '!=':
            case '<>':
                return $query->whereRaw("{$jsonField}{$jsonPath} != ?", [$value]);
            case '>':
                return $query->whereRaw("({$jsonField}{$jsonPath})::numeric > ?", [$value]);
            case '>=':
                return $query->whereRaw("({$jsonField}{$jsonPath})::numeric >= ?", [$value]);
            case '<':
                return $query->whereRaw("({$jsonField}{$jsonPath})::numeric < ?", [$value]);
            case '<=':
                return $query->whereRaw("({$jsonField}{$jsonPath})::numeric <= ?", [$value]);
            case 'like':
                return $query->whereRaw("{$jsonField}{$jsonPath} LIKE ?", [$value]);
            case 'contains':
                return $query->whereRaw("{$jsonField} @> ?", [json_encode([$path => $value])]);
            default:
                return $query->whereRaw("{$jsonField}{$jsonPath} {$operator} ?", [$value]);
        }
    }

    /**
     * Optimize SQLite JSON queries
     */
    private static function optimizeSQLiteJsonQuery(Builder $query, string $jsonField, string $path, $value, string $operator): Builder
    {
        // SQLite JSON1 extension
        $jsonPath = self::buildSQLiteJsonPath($path);

        switch ($operator) {
            case '=':
                return $query->whereRaw("json_extract({$jsonField}, ?) = ?", [$jsonPath, $value]);
            case '!=':
            case '<>':
                return $query->whereRaw("json_extract({$jsonField}, ?) != ?", [$jsonPath, $value]);
            case '>':
                return $query->whereRaw("CAST(json_extract({$jsonField}, ?) AS REAL) > ?", [$jsonPath, $value]);
            case '>=':
                return $query->whereRaw("CAST(json_extract({$jsonField}, ?) AS REAL) >= ?", [$jsonPath, $value]);
            case '<':
                return $query->whereRaw("CAST(json_extract({$jsonField}, ?) AS REAL) < ?", [$jsonPath, $value]);
            case '<=':
                return $query->whereRaw("CAST(json_extract({$jsonField}, ?) AS REAL) <= ?", [$jsonPath, $value]);
            case 'like':
                return $query->whereRaw("json_extract({$jsonField}, ?) LIKE ?", [$jsonPath, $value]);
            default:
                return $query->whereRaw("json_extract({$jsonField}, ?) {$operator} ?", [$jsonPath, $value]);
        }
    }

    /**
     * Build MySQL JSON path
     */
    private static function buildMySQLJsonPath(string $path): string
    {
        $parts = explode('.', $path);
        $jsonPath = '$';

        foreach ($parts as $part) {
            if (is_numeric($part)) {
                $jsonPath .= "[{$part}]";
            } else {
                $jsonPath .= ".{$part}";
            }
        }

        return $jsonPath;
    }

    /**
     * Build PostgreSQL JSON path
     */
    private static function buildPostgreSQLJsonPath(string $path): string
    {
        $parts = explode('.', $path);
        $jsonPath = '';

        foreach ($parts as $part) {
            if (is_numeric($part)) {
                $jsonPath .= "->{$part}";
            } else {
                $jsonPath .= "->'{$part}'";
            }
        }

        // Use ->> for the last element to get text
        $jsonPath = preg_replace('/->\'([^\']+)\'$/', '->>\'$1\'', $jsonPath);

        return $jsonPath;
    }

    /**
     * Build SQLite JSON path
     */
    private static function buildSQLiteJsonPath(string $path): string
    {
        $parts = explode('.', $path);
        $jsonPath = '$';

        foreach ($parts as $part) {
            if (is_numeric($part)) {
                $jsonPath .= "[{$part}]";
            } else {
                $jsonPath .= ".{$part}";
            }
        }

        return $jsonPath;
    }

    /**
     * Search in JSON array
     */
    public static function searchJsonArray(Builder $query, string $jsonField, string $arrayPath, $value): Builder
    {
        $databaseType = config('database.default');

        switch ($databaseType) {
            case 'mysql':
                $jsonPath = self::buildMySQLJsonPath($arrayPath);

                return $query->whereRaw("JSON_SEARCH({$jsonField}, 'one', ?, NULL, ?) IS NOT NULL", [$value, $jsonPath]);
            case 'pgsql':
                return $query->whereRaw("{$jsonField} @> ?", [json_encode([$arrayPath => [$value]])]);
            case 'sqlite':
                // SQLite doesn't have native JSON array search, use a workaround
                $jsonPath = self::buildSQLiteJsonPath($arrayPath);

                return $query->whereRaw("json_extract({$jsonField}, ?) LIKE ?", [$jsonPath, "%{$value}%"]);
            default:
                return $query->whereJsonContains($jsonField, $value, $arrayPath);
        }
    }

    /**
     * Order by JSON field
     */
    public static function orderByJson(Builder $query, string $jsonField, string $path, string $direction = 'asc'): Builder
    {
        $databaseType = config('database.default');

        switch ($databaseType) {
            case 'mysql':
                $jsonPath = self::buildMySQLJsonPath($path);

                return $query->orderByRaw("JSON_EXTRACT({$jsonField}, ?) {$direction}", [$jsonPath]);
            case 'pgsql':
                $jsonPath = self::buildPostgreSQLJsonPath($path);

                return $query->orderByRaw("{$jsonField}{$jsonPath} {$direction}");
            case 'sqlite':
                $jsonPath = self::buildSQLiteJsonPath($path);

                return $query->orderByRaw("json_extract({$jsonField}, ?) {$direction}", [$jsonPath]);
            default:
                return $query->orderBy("{$jsonField}->{$path}", $direction);
        }
    }

    /**
     * Group by JSON field
     */
    public static function groupByJson(Builder $query, string $jsonField, string $path): Builder
    {
        $databaseType = config('database.default');

        switch ($databaseType) {
            case 'mysql':
                $jsonPath = self::buildMySQLJsonPath($path);

                return $query->groupByRaw("JSON_EXTRACT({$jsonField}, ?)", [$jsonPath]);
            case 'pgsql':
                $jsonPath = self::buildPostgreSQLJsonPath($path);

                return $query->groupByRaw("{$jsonField}{$jsonPath}");
            case 'sqlite':
                $jsonPath = self::buildSQLiteJsonPath($path);

                return $query->groupByRaw("json_extract({$jsonField}, ?)", [$jsonPath]);
            default:
                return $query->groupBy("{$jsonField}->{$path}");
        }
    }

    /**
     * Count distinct JSON values
     */
    public static function countDistinctJson(Builder $query, string $jsonField, string $path): int
    {
        $databaseType = config('database.default');

        switch ($databaseType) {
            case 'mysql':
                $jsonPath = self::buildMySQLJsonPath($path);

                return $query->selectRaw("COUNT(DISTINCT JSON_EXTRACT({$jsonField}, ?)) as count", [$jsonPath])
                    ->value('count') ?? 0;
            case 'pgsql':
                $jsonPath = self::buildPostgreSQLJsonPath($path);

                return $query->selectRaw("COUNT(DISTINCT {$jsonField}{$jsonPath}) as count")
                    ->value('count') ?? 0;
            case 'sqlite':
                $jsonPath = self::buildSQLiteJsonPath($path);

                return $query->selectRaw("COUNT(DISTINCT json_extract({$jsonField}, ?)) as count", [$jsonPath])
                    ->value('count') ?? 0;
            default:
                return $query->distinct()->count("{$jsonField}->{$path}");
        }
    }
}
