# Production Deployment Guide for bancosystem.co.zw

## Prerequisites

1. **Server Requirements**:
   - PHP 8.1 or higher
   - MySQL 5.7+ or MariaDB
   - Composer
   - Node.js & NPM
   - SSL certificate (required for Twilio webhooks)

2. **Domain Setup**:
   - Domain: https://bancosystem.co.zw
   - SSL certificate installed (Let's Encrypt recommended)
   - Server configured with proper PHP extensions

## Step 1: Prepare Application for Production

### 1.1 Update Environment Variables

Create `.env` file on production server:

```env
APP_NAME="Banco System"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://bancosystem.co.zw

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bancosystem_prod
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=1440
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

# Twilio Production
TWILIO_ACCOUNT_SID=ACcc8a7b869428e05081183eeba60933dd
TWILIO_AUTH_TOKEN=a5ac8fc29bb36f3663b53bf391c4266f
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886

# Other settings...
```

### 1.2 Generate Application Key

```bash
php artisan key:generate
```

### 1.3 Optimize for Production

```bash
# Clear and cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Build frontend assets
npm run build
```

## Step 2: Database Setup

### 2.1 Create Production Database

```sql
CREATE DATABASE bancosystem_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2.2 Run Migrations

```bash
php artisan migrate --force
```

### 2.3 Seed Initial Data (if needed)

```bash
php artisan db:seed --force
```

## Step 3: Configure Web Server

### 3.1 Apache Configuration

Create virtual host configuration:

```apache
<VirtualHost *:443>
    ServerName bancosystem.co.zw
    DocumentRoot /var/www/bancosystem/public

    SSLEngine on
    SSLCertificateFile /path/to/ssl/cert.pem
    SSLCertificateKeyFile /path/to/ssl/key.pem
    SSLCertificateChainFile /path/to/ssl/chain.pem

    <Directory /var/www/bancosystem/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/bancosystem-error.log
    CustomLog ${APACHE_LOG_DIR}/bancosystem-access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName bancosystem.co.zw
    Redirect permanent / https://bancosystem.co.zw/
</VirtualHost>
```

### 3.2 Nginx Configuration (Alternative)

```nginx
server {
    listen 443 ssl http2;
    server_name bancosystem.co.zw;
    root /var/www/bancosystem/public;

    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/key.pem;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name bancosystem.co.zw;
    return 301 https://$server_name$request_uri;
}
```

## Step 4: Configure Twilio Webhooks

### 4.1 Update Webhook URLs in Twilio Console

1. **Log in to Twilio Console**: https://console.twilio.com

2. **Navigate to WhatsApp Sandbox** (for testing) or **WhatsApp Business Profile** (for production)

3. **Set Webhook URLs**:
   - **When a message comes in**: `https://bancosystem.co.zw/api/webhooks/whatsapp`
   - **Status callback URL**: `https://bancosystem.co.zw/api/webhooks/whatsapp/status`
   - **Method**: POST for both

### 4.2 Configure Webhook Security

The application already validates Twilio signatures, but ensure your production URL matches exactly:

```php
// In WhatsAppWebhookController.php
private function validateTwilioSignature(Request $request): bool
{
    // The URL must match exactly what's configured in Twilio
    $url = $request->fullUrl(); // This should be https://bancosystem.co.zw/api/webhooks/whatsapp
    // ... rest of validation
}
```

### 4.3 Test Webhook Connection

Create a test endpoint temporarily:

```bash
# routes/api.php - Add temporarily for testing
Route::get('/webhooks/test', function() {
    return response()->json(['status' => 'ok', 'ssl' => request()->secure()]);
});
```

Test it:
```bash
curl https://bancosystem.co.zw/api/webhooks/test
```

## Step 5: Deploy Application Files

### 5.1 Using Git

```bash
# On production server
cd /var/www
git clone https://github.com/yourusername/bancozim.git bancosystem
cd bancosystem

# Copy environment file
cp .env.example .env
# Edit .env with production values

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 5.2 Using FTP/SFTP

1. Upload all files except:
   - `.env.local`
   - `node_modules/`
   - `vendor/`
   - `.git/`

2. Run on server:
```bash
composer install --optimize-autoloader --no-dev
npm install && npm run build
```

## Step 6: Set Up Cron Jobs

Add to crontab:

```bash
# Clean expired states daily
0 2 * * * cd /var/www/bancosystem && php artisan states:clean >> /dev/null 2>&1

# Laravel scheduler (if using)
* * * * * cd /var/www/bancosystem && php artisan schedule:run >> /dev/null 2>&1
```

## Step 7: Production Checklist

### 7.1 Security Checklist

- [ ] SSL certificate installed and working
- [ ] `.env` file has production values
- [ ] `APP_DEBUG=false` in production
- [ ] Database credentials are secure
- [ ] File permissions are correct
- [ ] Firewall configured (allow 80, 443)

### 7.2 Twilio Checklist

- [ ] Webhook URLs updated in Twilio Console
- [ ] Webhook signature validation working
- [ ] Test message sent and received
- [ ] Status callbacks working

### 7.3 Application Checklist

- [ ] Database migrations run successfully
- [ ] Assets compiled for production
- [ ] Configuration cached
- [ ] Error logging configured
- [ ] Session handling working

## Step 8: Testing Production Webhooks

### 8.1 Send Test WhatsApp Message

1. Send "hi" to your Twilio WhatsApp number
2. Check Laravel logs:
   ```bash
   tail -f /var/www/bancosystem/storage/logs/laravel.log
   ```

### 8.2 Monitor Webhook Delivery

In Twilio Console, check:
- **Monitor** → **Logs** → **Webhooks**
- Look for delivery status and any errors

### 8.3 Common Webhook Issues

1. **SSL Certificate Issues**:
   - Ensure SSL is properly installed
   - Certificate must be valid (not self-signed)

2. **URL Mismatch**:
   - Webhook URL in Twilio must match exactly
   - Include/exclude trailing slashes consistently

3. **Signature Validation Fails**:
   - Ensure TWILIO_AUTH_TOKEN is correct in .env
   - URL in validation must match Twilio's webhook URL

## Step 9: Monitoring and Maintenance

### 9.1 Set Up Logging

Create custom WhatsApp log channel:

```php
// config/logging.php
'whatsapp' => [
    'driver' => 'daily',
    'path' => storage_path('logs/whatsapp.log'),
    'level' => 'debug',
    'days' => 14,
],
```

### 9.2 Monitor Application Health

```bash
# Create health check endpoint
Route::get('/health', function() {
    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::has('health_check') ? 'working' : 'not working',
        'ssl' => request()->secure() ? 'enabled' : 'disabled'
    ]);
});
```

### 9.3 Backup Strategy

```bash
# Daily database backup
mysqldump -u your_user -p bancosystem_prod | gzip > backup_$(date +%Y%m%d).sql.gz

# Application files backup
tar -czf bancosystem_files_$(date +%Y%m%d).tar.gz /var/www/bancosystem --exclude=node_modules --exclude=vendor
```

## Step 10: Go Live!

1. **Update DNS**: Point bancosystem.co.zw to your server IP
2. **Test Everything**: 
   - Visit https://bancosystem.co.zw
   - Send WhatsApp message
   - Complete a test application
3. **Monitor First 24 Hours**: Watch logs for any issues

## Troubleshooting

### WhatsApp Not Receiving Messages

1. Check Twilio webhook logs
2. Verify SSL certificate: `curl -I https://bancosystem.co.zw`
3. Check Laravel logs: `tail -f storage/logs/laravel.log`
4. Test webhook manually:
   ```bash
   curl -X POST https://bancosystem.co.zw/api/webhooks/whatsapp \
     -d "From=whatsapp:+1234567890" \
     -d "Body=test"
   ```

### SSL Issues

```bash
# Check SSL certificate
openssl s_client -connect bancosystem.co.zw:443 -servername bancosystem.co.zw

# Renew Let's Encrypt
certbot renew
```

## Support Contacts

- **Twilio Support**: https://support.twilio.com
- **Laravel Deployment**: https://laravel.com/docs/deployment

---

**Important**: Keep this document updated with any changes to the production environment!