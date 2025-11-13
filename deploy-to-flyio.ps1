# Deploy to Fly.io Script
# This script helps deploy the bancosystem application to fly.io

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  Bancosystem Fly.io Deployment" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Step 1: Check if flyctl is installed
Write-Host "Step 1: Checking for flyctl installation..." -ForegroundColor Yellow
$flyctlInstalled = Get-Command flyctl -ErrorAction SilentlyContinue

if (-not $flyctlInstalled) {
    Write-Host "  flyctl is not installed!" -ForegroundColor Red
    Write-Host "  Please install flyctl first:" -ForegroundColor Yellow
    Write-Host "  1. Visit: https://fly.io/docs/hands-on/install-flyctl/" -ForegroundColor White
    Write-Host "  2. Or run (PowerShell as Admin):" -ForegroundColor White
    Write-Host "     iwr https://fly.io/install.ps1 -useb | iex" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  After installation, run this script again." -ForegroundColor Yellow
    exit 1
} else {
    Write-Host "  ✓ flyctl is installed" -ForegroundColor Green
    flyctl version
    Write-Host ""
}

# Step 2: Check authentication
Write-Host "Step 2: Checking Fly.io authentication..." -ForegroundColor Yellow
$authStatus = flyctl auth whoami 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "  You are not logged in to Fly.io" -ForegroundColor Red
    Write-Host "  Running 'flyctl auth login'..." -ForegroundColor Yellow
    flyctl auth login
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  Authentication failed. Please try again." -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "  ✓ Already authenticated as: $authStatus" -ForegroundColor Green
    Write-Host ""
}

# Step 3: Navigate to project directory
Write-Host "Step 3: Navigating to project directory..." -ForegroundColor Yellow
Set-Location C:\xampp\htdocs\bancosystem
Write-Host "  ✓ Current directory: $(Get-Location)" -ForegroundColor Green
Write-Host ""

# Step 4: Check if app exists
Write-Host "Step 4: Checking if app 'bancosystem' exists..." -ForegroundColor Yellow
$appExists = flyctl apps list 2>&1 | Select-String "bancosystem"

if (-not $appExists) {
    Write-Host "  App 'bancosystem' does not exist. Creating..." -ForegroundColor Yellow

    $createApp = Read-Host "  Create app 'bancosystem' in region 'jnb' (Johannesburg)? (y/n)"
    if ($createApp -eq 'y') {
        flyctl apps create bancosystem --org personal
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  ✓ App created successfully" -ForegroundColor Green
        } else {
            Write-Host "  Failed to create app. Continuing..." -ForegroundColor Yellow
        }
    }
} else {
    Write-Host "  ✓ App 'bancosystem' already exists" -ForegroundColor Green
}
Write-Host ""

# Step 5: Set up secrets/environment variables
Write-Host "Step 5: Setting up environment variables (secrets)..." -ForegroundColor Yellow
Write-Host "  Current APP_KEY from .env: base64:yk/5kzJqKtKci2oYbkBOJbOgWCefD3ipl1cMetdY7Js=" -ForegroundColor Cyan
Write-Host ""

$setupSecrets = Read-Host "  Do you want to set up secrets now? (y/n)"
if ($setupSecrets -eq 'y') {
    Write-Host "  Please provide the following information:" -ForegroundColor Yellow
    Write-Host ""

    # Get database information
    $dbHost = Read-Host "  Database Host (or press Enter to skip and use local for now)"
    $dbDatabase = Read-Host "  Database Name (default: bancozim)"
    if ([string]::IsNullOrWhiteSpace($dbDatabase)) { $dbDatabase = "bancozim" }

    $dbUsername = Read-Host "  Database Username (default: root)"
    if ([string]::IsNullOrWhiteSpace($dbUsername)) { $dbUsername = "root" }

    $dbPassword = Read-Host "  Database Password" -AsSecureString
    $dbPasswordText = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($dbPassword))

    Write-Host ""
    Write-Host "  Setting secrets..." -ForegroundColor Yellow

    # Set secrets
    flyctl secrets set `
        APP_KEY="base64:yk/5kzJqKtKci2oYbkBOJbOgWCefD3ipl1cMetdY7Js=" `
        APP_ENV="production" `
        APP_DEBUG="false" `
        DB_CONNECTION="mysql" `
        DB_HOST="$dbHost" `
        DB_DATABASE="$dbDatabase" `
        DB_USERNAME="$dbUsername" `
        DB_PASSWORD="$dbPasswordText"

    if ($LASTEXITCODE -eq 0) {
        Write-Host "  ✓ Secrets set successfully" -ForegroundColor Green
    } else {
        Write-Host "  Failed to set secrets. You can set them manually later." -ForegroundColor Yellow
    }
} else {
    Write-Host "  Skipping secrets setup. You can set them manually with:" -ForegroundColor Yellow
    Write-Host "  flyctl secrets set APP_KEY='base64:yk/5kzJqKtKci2oYbkBOJbOgWCefD3ipl1cMetdY7Js=' ..." -ForegroundColor Cyan
}
Write-Host ""

# Step 6: Create MySQL database (optional)
Write-Host "Step 6: Database setup..." -ForegroundColor Yellow
Write-Host "  Options:" -ForegroundColor Cyan
Write-Host "  1. Create a PostgreSQL database on Fly.io (recommended)" -ForegroundColor White
Write-Host "  2. Use an external MySQL database (e.g., PlanetScale, AWS RDS)" -ForegroundColor White
Write-Host "  3. Skip for now" -ForegroundColor White
Write-Host ""

$dbChoice = Read-Host "  Enter your choice (1/2/3)"

if ($dbChoice -eq '1') {
    Write-Host "  Creating PostgreSQL database on Fly.io..." -ForegroundColor Yellow
    $dbName = Read-Host "  Database name (default: bancosystem-db)"
    if ([string]::IsNullOrWhiteSpace($dbName)) { $dbName = "bancosystem-db" }

    flyctl postgres create --name $dbName --region jnb

    if ($LASTEXITCODE -eq 0) {
        Write-Host "  ✓ Database created. Now attach it to your app:" -ForegroundColor Green
        flyctl postgres attach $dbName --app bancosystem
    }
} elseif ($dbChoice -eq '2') {
    Write-Host "  Please ensure you've set the correct database credentials in secrets." -ForegroundColor Yellow
} else {
    Write-Host "  Skipping database setup." -ForegroundColor Yellow
}
Write-Host ""

# Step 7: Deploy the application
Write-Host "Step 7: Deploying the application..." -ForegroundColor Yellow
Write-Host "  This will build and deploy your Docker container to Fly.io" -ForegroundColor Cyan
Write-Host ""

$deploy = Read-Host "  Ready to deploy? (y/n)"
if ($deploy -eq 'y') {
    Write-Host "  Starting deployment..." -ForegroundColor Yellow
    flyctl deploy

    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "  ✓✓✓ Deployment successful! ✓✓✓" -ForegroundColor Green
        Write-Host ""

        # Step 8: Open the app
        Write-Host "Step 8: Opening deployed application..." -ForegroundColor Yellow
        $openApp = Read-Host "  Open the app in your browser? (y/n)"
        if ($openApp -eq 'y') {
            flyctl open
        } else {
            Write-Host "  You can open it later with: flyctl open" -ForegroundColor Cyan
        }
    } else {
        Write-Host ""
        Write-Host "  Deployment failed. Check the errors above." -ForegroundColor Red
        Write-Host "  Common issues:" -ForegroundColor Yellow
        Write-Host "  - Dockerfile errors: Check C:\xampp\htdocs\bancosystem\Dockerfile" -ForegroundColor White
        Write-Host "  - Missing dependencies: Ensure all packages are in composer.json" -ForegroundColor White
        Write-Host "  - Build timeouts: Try deploying with --remote-only flag" -ForegroundColor White
    }
} else {
    Write-Host "  Deployment cancelled. You can deploy later with: flyctl deploy" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  Deployment Script Complete" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Useful Commands:" -ForegroundColor Yellow
Write-Host "  flyctl apps list              - List all your apps" -ForegroundColor White
Write-Host "  flyctl status                 - Check app status" -ForegroundColor White
Write-Host "  flyctl logs                   - View app logs" -ForegroundColor White
Write-Host "  flyctl ssh console            - SSH into your app" -ForegroundColor White
Write-Host "  flyctl secrets list           - List all secrets" -ForegroundColor White
Write-Host "  flyctl scale show             - Show current scaling" -ForegroundColor White
Write-Host "  flyctl deploy                 - Deploy updates" -ForegroundColor White
Write-Host "  flyctl open                   - Open app in browser" -ForegroundColor White
Write-Host ""

