# Deployment Testing Instructions

## Deployment Complete ✅

Your application has been deployed to Fly.io with all known issues fixed.

**App URL**: https://bancosystem.fly.dev

## What Was Fixed

1. ✅ **Docker & Supervisord Issues** - Fixed permission errors
2. ✅ **Database Connection** - PostgreSQL connection working
3. ✅ **Migration Compatibility** - All migrations PostgreSQL-compatible
4. ✅ **Transaction Handling** - Fixed PostgreSQL transaction cascade failures

## Test Your Deployment

### 1. Basic Health Check
Open your browser and visit:
```
https://bancosystem.fly.dev
```

**Expected Result**: The homepage should load without errors

### 2. Database Connectivity Test
If the homepage loads, the database connection is working properly.

### 3. Check Migration Status (if needed)
If you encounter any database errors, you can SSH into the machine:

```powershell
flyctl ssh console --app bancosystem
cd /var/www/html
php artisan migrate:status
```

### 4. Run Migrations Manually (if needed)
If migrations didn't run during deployment:

```powershell
flyctl ssh console --app bancosystem
cd /var/www/html
php artisan migrate --force
```

### 5. Check Application Logs
To see what's happening in your application:

```powershell
flyctl logs --app bancosystem
```

Or check Laravel logs directly:
```powershell
flyctl ssh console --app bancosystem
tail -f /var/www/html/storage/logs/laravel.log
```

## Expected Behavior

### ✅ Working Features:
- Homepage loads
- Database queries execute
- User authentication works
- Static assets load correctly
- Health checks pass

### If You See Errors:

#### 500 Internal Server Error
This usually means migrations need to run. Try:
```powershell
flyctl ssh console --app bancosystem
cd /var/www/html
php artisan migrate --force
php artisan cache:clear
php artisan config:clear
```

#### Connection Timeout
The machine may be starting up. Wait 30 seconds and try again.

#### Database Connection Error
Check if DATABASE_URL is set:
```powershell
flyctl secrets list --app bancosystem
```

Should show `DATABASE_URL` in the list.

## Monitoring Your App

### View Dashboard
https://fly.io/apps/bancosystem/monitoring

### View Metrics
```powershell
flyctl dashboard bancosystem
```

### Restart the App
```powershell
flyctl machine restart 48ed047cd6d668 --app bancosystem
```

### Scale the App
```powershell
# View current status
flyctl status --app bancosystem

# Scale up (if needed)
flyctl scale count 2 --app bancosystem
```

## Environment Variables

To add or update environment variables:

```powershell
# Set a secret
flyctl secrets set KEY=value --app bancosystem

# List all secrets
flyctl secrets list --app bancosystem

# Unset a secret
flyctl secrets unset KEY --app bancosystem
```

## Common Issues & Solutions

### Issue: App not responding
**Solution**: The machine may be asleep. Access the URL to wake it up.

### Issue: Migrations failing
**Solution**: Run migrations manually with `--force` flag

### Issue: Static assets not loading
**Solution**: Run `php artisan storage:link` via SSH

### Issue: Permission errors
**Solution**: Check file permissions:
```bash
flyctl ssh console --app bancosystem
cd /var/www/html
chown -R www:www storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## Production Checklist

Before going live, ensure:
- [ ] All migrations ran successfully
- [ ] Homepage loads correctly
- [ ] User registration works
- [ ] User login works
- [ ] Database queries execute properly
- [ ] File uploads work (if applicable)
- [ ] Email sending works (if configured)
- [ ] API endpoints respond correctly
- [ ] SSL certificate is valid
- [ ] Health checks pass
- [ ] Logs are clean (no critical errors)

## Need to Rollback?

If something goes wrong, you can rollback to a previous deployment:

```powershell
# List deployments
flyctl releases --app bancosystem

# Rollback to previous version
flyctl releases rollback --app bancosystem
```

## Database Management

### Backup Database
```powershell
# SSH into machine
flyctl ssh console --app bancosystem

# Create backup
pg_dump $DATABASE_URL > /tmp/backup.sql

# Download backup (from local machine)
flyctl ssh sftp shell --app bancosystem
get /tmp/backup.sql ./backup.sql
```

### Reset Database (⚠️ DESTRUCTIVE)
```powershell
flyctl ssh console --app bancosystem
cd /var/www/html
php artisan migrate:fresh --force --seed
```

## Performance Optimization

Once everything works, consider:
1. Enable caching: `php artisan cache:cache`
2. Optimize autoloader: Already done in Dockerfile
3. Enable OPcache: Already configured
4. Add CDN for static assets
5. Configure queue workers (if needed)

## Support

- **Fly.io Docs**: https://fly.io/docs
- **Laravel Docs**: https://laravel.com/docs
- **Check Logs**: `flyctl logs --app bancosystem`
- **Community**: https://community.fly.io

---

**Last Updated**: November 14, 2025
**Deployment**: registry.fly.io/bancosystem:deployment-01K9ZP9YANZXKVVKQE6W4BBDVX

