@echo off
echo ========================================
echo Diagnosing Fly.io Deployment Issues
echo ========================================
echo.

echo Checking app status...
flyctl status --app bancosystem
echo.

echo Checking migration status...
flyctl ssh console --app bancosystem -C "php /var/www/html/artisan migrate:status" 2>&1
echo.

echo Running migrations manually...
flyctl ssh console --app bancosystem -C "php /var/www/html/artisan migrate --force" 2>&1
echo.

echo Checking Laravel logs...
flyctl ssh console --app bancosystem -C "tail -30 /var/www/html/storage/logs/laravel.log" 2>&1
echo.

echo Testing app response...
powershell -Command "try { $r = Invoke-WebRequest -Uri 'https://bancosystem.fly.dev' -UseBasicParsing -TimeoutSec 30; Write-Host 'SUCCESS: HTTP' $r.StatusCode } catch { Write-Host 'ERROR:' $_.Exception.Message }"
echo.

echo ========================================
echo Diagnosis Complete
echo ========================================
pause

