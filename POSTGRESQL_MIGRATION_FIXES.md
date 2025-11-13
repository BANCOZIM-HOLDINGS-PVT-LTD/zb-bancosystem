# PostgreSQL Migration Fixes - Complete Summary

## Problem Overview
When deploying to Fly.io with PostgreSQL, several Laravel migrations failed due to syntax incompatibilities between MySQL and PostgreSQL.

## Root Cause
Laravel's `->enum()` method generates different SQL for MySQL vs PostgreSQL:
- **MySQL**: Uses native ENUM type
- **PostgreSQL**: Creates CHECK constraints

When using `->enum()->change()` to modify existing columns, Laravel generates SQL that tries to add CHECK constraints inline with ALTER COLUMN, which PostgreSQL doesn't support.

## Migrations Fixed

### 1. ✅ 2025_07_20_000002_add_database_constraints.php
**Issue**: Used MySQL syntax with double quotes and REGEXP operator
**Fix**: 
- Changed to single quotes for string values
- Used `~` operator instead of `REGEXP` for PostgreSQL
- Added driver detection for different syntax

### 2. ✅ 2025_07_20_000003_add_performance_indexes.php
**Issue**: Tried to create btree index on JSON column
**Fix**:
- Used GIN index for PostgreSQL JSON columns
- Disabled transactions (`$withinTransaction = false`) to prevent cascade failures
- Wrapped helper methods in try-catch blocks

### 3. ✅ 2025_08_28_100000_update_agent_types.php
**Issue**: Used `->enum()->change()` which generates invalid PostgreSQL syntax
**Fix**:
```php
if ($driver === 'pgsql') {
    // 1. Drop old CHECK constraint
    DB::statement("ALTER TABLE agents DROP CONSTRAINT IF EXISTS agents_type_check");
    
    // 2. Update data
    DB::statement("UPDATE agents SET type = 'field' WHERE type IN ('individual', 'corporate')");
    
    // 3. Add new CHECK constraint separately
    DB::statement("ALTER TABLE agents ADD CONSTRAINT agents_type_check CHECK (type IN ('field', 'online', 'direct'))");
    
    // 4. Set default
    DB::statement("ALTER TABLE agents ALTER COLUMN type SET DEFAULT 'field'");
}
```

### 4. ✅ 2025_10_28_000000_update_agents_type_enum.php
**Issue**: Only handled MySQL, ignored PostgreSQL
**Fix**: Added PostgreSQL branch using same pattern as above

## Pattern for Future Enum Modifications

When modifying enum columns in migrations, use this pattern:

```php
public function up(): void
{
    $driver = DB::getDriverName();
    
    if ($driver === 'mysql') {
        // MySQL: Use native ENUM
        DB::statement("ALTER TABLE `table` MODIFY `column` ENUM('val1','val2') NOT NULL DEFAULT 'val1'");
    } elseif ($driver === 'pgsql') {
        // PostgreSQL: Manage CHECK constraints separately
        DB::statement("ALTER TABLE table DROP CONSTRAINT IF EXISTS table_column_check");
        DB::statement("UPDATE table SET column = 'default' WHERE column NOT IN ('val1', 'val2')");
        DB::statement("ALTER TABLE table ADD CONSTRAINT table_column_check CHECK (column IN ('val1', 'val2'))");
        DB::statement("ALTER TABLE table ALTER COLUMN column SET DEFAULT 'val1'");
    }
}
```

## Why CREATE TABLE Works But ALTER TABLE Doesn't

**CREATE TABLE with enum** (works on both):
```php
Schema::create('agents', function (Blueprint $table) {
    $table->enum('type', ['field', 'online'])->default('field');
});
```
- PostgreSQL creates the CHECK constraint as part of table creation
- Syntax: `CREATE TABLE agents (..., type varchar CHECK (type IN ('field', 'online')))`

**ALTER TABLE with enum** (breaks on PostgreSQL):
```php
Schema::table('agents', function (Blueprint $table) {
    $table->enum('type', ['field', 'online'])->default('field')->change();
});
```
- Laravel tries: `ALTER TABLE agents ALTER COLUMN type TYPE varchar(255) CHECK (...)`
- PostgreSQL syntax error: CHECK constraints cannot be inline with ALTER COLUMN

## Testing Migrations Locally

To test migrations against PostgreSQL before deploying:

1. **Start PostgreSQL locally**:
```bash
docker run --name postgres-test -e POSTGRES_PASSWORD=secret -p 5432:5432 -d postgres:15
```

2. **Update .env**:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=bancosystem
DB_USERNAME=postgres
DB_PASSWORD=secret
```

3. **Run migrations**:
```bash
php artisan migrate:fresh
```

## Deployment Checklist

Before deploying Laravel to PostgreSQL:

- [ ] Search for `->enum(` in migrations
- [ ] Check if any use `->change()` to modify existing columns
- [ ] Add driver detection for MySQL vs PostgreSQL
- [ ] Test locally with PostgreSQL before deploying
- [ ] Check for JSON column indexes (use GIN, not btree)
- [ ] Verify CHECK constraints are added separately
- [ ] Look for MySQL-specific syntax (REGEXP, double quotes in strings)

## Current Deployment Status

All identified migrations have been fixed for PostgreSQL compatibility. The application should now deploy successfully to Fly.io.

**Deployment Command**:
```bash
flyctl deploy --app bancosystem --ha=false
```

**Verify Migrations**:
```bash
flyctl ssh console --app bancosystem -C "php /var/www/html/artisan migrate:status"
```

**Check Logs**:
```bash
flyctl logs --app bancosystem
```

## Files Modified

1. `config/database.php` - Added DATABASE_URL support
2. `docker/supervisord.conf` - Fixed permissions
3. `docker/entrypoint.sh` - Fixed shell syntax
4. `database/migrations/2025_07_20_000002_add_database_constraints.php`
5. `database/migrations/2025_07_20_000003_add_performance_indexes.php`
6. `database/migrations/2025_08_28_100000_update_agent_types.php`
7. `database/migrations/2025_10_28_000000_update_agents_type_enum.php`

---
**Last Updated**: November 14, 2025
**Status**: All known PostgreSQL compatibility issues resolved ✅

