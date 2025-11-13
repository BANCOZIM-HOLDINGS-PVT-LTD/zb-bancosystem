# Deploying Bancosystem to Fly.io

This guide will help you deploy the bancosystem application to Fly.io.

## Prerequisites

1. **Fly.io Account**: Sign up at [https://fly.io/app/sign-up](https://fly.io/app/sign-up)
2. **flyctl CLI**: Install the Fly.io command-line tool

### Installing flyctl on Windows

Run PowerShell as Administrator and execute:

```powershell
iwr https://fly.io/install.ps1 -useb | iex
```

After installation, close and reopen your terminal.

## Quick Start

We've created an automated deployment script for you. Simply run:

```powershell
cd C:\xampp\htdocs\bancosystem
.\deploy-to-flyio.ps1
```

The script will guide you through the entire deployment process.

## Manual Deployment Steps

If you prefer to deploy manually, follow these steps:

### 1. Authenticate with Fly.io

```powershell
flyctl auth login
```

This will open your browser for authentication.

### 2. Navigate to Project Directory

```powershell
cd C:\xampp\htdocs\bancosystem
```

### 3. Create the App (if it doesn't exist)

```powershell
flyctl apps create bancosystem --org personal
```

### 4. Set Up Environment Variables (Secrets)

```powershell
flyctl secrets set `
    APP_KEY="base64:yk/5kzJqKtKci2oYbkBOJbOgWCefD3ipl1cMetdY7Js=" `
    APP_ENV="production" `
    APP_DEBUG="false" `
    DB_CONNECTION="mysql" `
    DB_HOST="your-database-host" `
    DB_DATABASE="bancozim" `
    DB_USERNAME="your-database-username" `
    DB_PASSWORD="your-database-password"
```

**Important**: Replace the database credentials with your actual values.

### 5. Database Options

#### Option A: Use Fly.io PostgreSQL (Recommended)

```powershell
# Create database
flyctl postgres create --name bancosystem-db --region jnb

# Attach to your app
flyctl postgres attach bancosystem-db --app bancosystem
```

**Note**: This will create a PostgreSQL database. You'll need to update your Laravel app to support PostgreSQL or use an external MySQL database instead.

#### Option B: Use External MySQL Database

Use services like:
- [PlanetScale](https://planetscale.com/) - Free MySQL hosting
- [AWS RDS](https://aws.amazon.com/rds/)
- [DigitalOcean Managed Databases](https://www.digitalocean.com/products/managed-databases)

Set the credentials in the secrets (step 4).

### 6. Deploy the Application

```powershell
flyctl deploy
```

This will:
- Build your Docker image
- Push it to Fly.io
- Start your application

First deployment may take 5-10 minutes.

### 7. Open Your App

```powershell
flyctl open
```

This opens your deployed application in the browser.

## Configuration Files

### fly.toml

The `fly.toml` file contains your app configuration:
- **app**: Your app name (bancosystem)
- **primary_region**: jnb (Johannesburg - closest to Zimbabwe)
- **internal_port**: 80 (nginx listens on this port)

### Dockerfile

Multi-stage Docker build that:
1. Builds frontend assets with Node.js/Vite
2. Sets up PHP 8.2 with necessary extensions
3. Installs Composer dependencies
4. Configures Nginx and PHP-FPM
5. Optimizes Laravel for production

### Docker Configuration Files

Located in `/docker`:
- `nginx.conf` - Nginx web server configuration
- `php-fpm.conf` - PHP-FPM process manager configuration
- `php.ini` - PHP runtime settings
- `supervisord.conf` - Process supervisor configuration

## Useful Commands

### App Management

```powershell
# List all your apps
flyctl apps list

# Check app status
flyctl status

# View app info
flyctl info
```

### Logs and Monitoring

```powershell
# View real-time logs
flyctl logs

# View logs with timestamps
flyctl logs --timestamps

# Follow logs (like tail -f)
flyctl logs -f
```

### Secrets Management

```powershell
# List all secrets (values are hidden)
flyctl secrets list

# Set a single secret
flyctl secrets set SECRET_NAME="value"

# Remove a secret
flyctl secrets unset SECRET_NAME
```

### Scaling

```powershell
# Show current scaling
flyctl scale show

# Scale to specific machine size
flyctl scale vm shared-cpu-2x

# Scale memory
flyctl scale memory 1024

# Set number of instances
flyctl scale count 2
```

### Database Operations

```powershell
# List databases
flyctl postgres list

# Connect to database
flyctl postgres connect -a bancosystem-db

# View database credentials
flyctl postgres db show bancosystem-db
```

### SSH and Debugging

```powershell
# SSH into your app container
flyctl ssh console

# Run Laravel commands
flyctl ssh console -C "php /var/www/html/artisan migrate"
flyctl ssh console -C "php /var/www/html/artisan cache:clear"
```

### Deployments

```powershell
# Deploy with remote builder (faster, recommended for large apps)
flyctl deploy --remote-only

# Deploy without cache
flyctl deploy --no-cache

# List deployment history
flyctl releases
```

## Troubleshooting

### Build Failures

If the Docker build fails:

1. Check Dockerfile syntax
2. Ensure all referenced files exist
3. Try building locally first:
   ```powershell
   docker build -t bancosystem .
   ```

### Database Connection Issues

1. Verify secrets are set correctly:
   ```powershell
   flyctl secrets list
   ```

2. Check database host is reachable from Fly.io region

3. Ensure database allows connections from Fly.io IPs

### Application Errors

1. Check logs:
   ```powershell
   flyctl logs
   ```

2. SSH into container and check Laravel logs:
   ```powershell
   flyctl ssh console
   cat /var/www/html/storage/logs/laravel.log
   ```

3. Run Laravel diagnostics:
   ```powershell
   flyctl ssh console -C "php /var/www/html/artisan about"
   ```

### Slow Performance

1. Check current scaling:
   ```powershell
   flyctl scale show
   ```

2. Upgrade machine size:
   ```powershell
   flyctl scale vm shared-cpu-2x
   flyctl scale memory 1024
   ```

3. Enable multi-region deployment:
   ```powershell
   flyctl regions add jnb ams fra
   ```

## Post-Deployment Tasks

After successful deployment:

1. **Run Migrations**:
   ```powershell
   flyctl ssh console -C "php /var/www/html/artisan migrate --force"
   ```

2. **Seed Database** (if needed):
   ```powershell
   flyctl ssh console -C "php /var/www/html/artisan db:seed --force"
   ```

3. **Clear Caches**:
   ```powershell
   flyctl ssh console -C "php /var/www/html/artisan optimize"
   ```

4. **Set Up Custom Domain** (optional):
   ```powershell
   flyctl certs add yourdomain.com
   ```

## Cost Estimates

Fly.io pricing (as of 2024):

- **Free tier**: Includes 3 shared-cpu-1x VMs with 256MB RAM each
- **Shared CPU**: ~$5/month per VM
- **Dedicated CPU**: Starting at ~$25/month
- **PostgreSQL**: ~$10/month for small instance

Your current configuration (`shared-cpu-1x` with 512MB) should fit within the free tier for development/testing.

## Support

- **Fly.io Docs**: [https://fly.io/docs/](https://fly.io/docs/)
- **Fly.io Community**: [https://community.fly.io/](https://community.fly.io/)
- **Laravel Deployment**: [https://fly.io/docs/laravel/](https://fly.io/docs/laravel/)

## Security Recommendations

1. Always use HTTPS (Fly.io provides free SSL certificates)
2. Keep `APP_DEBUG=false` in production
3. Regularly update dependencies
4. Use strong database passwords
5. Enable database backups
6. Monitor logs for suspicious activity

---

**Ready to deploy?** Run the deployment script:

```powershell
.\deploy-to-flyio.ps1
```

Good luck with your deployment! 🚀

