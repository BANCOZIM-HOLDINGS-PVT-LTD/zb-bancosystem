# Fly.io CLI Installation and Login Helper
# Run this script to install flyctl and login to Fly.io

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  Fly.io CLI Installation & Login" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Check if flyctl is already available
$flyctlFound = $false
$flyctlPaths = @(
    (Get-Command flyctl -ErrorAction SilentlyContinue).Source,
    (Get-Command fly -ErrorAction SilentlyContinue).Source,
    "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\Flyio.flyctl_Microsoft.Winget.Source_8wekyb3d8bbwe\flyctl.exe",
    "$env:USERPROFILE\.fly\bin\flyctl.exe",
    "$env:USERPROFILE\.fly\bin\fly.exe"
)

foreach ($path in $flyctlPaths) {
    if ($path -and (Test-Path $path)) {
        Write-Host "✓ Found flyctl at: $path" -ForegroundColor Green
        $flyctlCmd = $path
        $flyctlFound = $true
        break
    }
}

if (-not $flyctlFound) {
    Write-Host "flyctl not found. Installing..." -ForegroundColor Yellow
    Write-Host ""

    try {
        Write-Host "Downloading and installing Fly.io CLI..." -ForegroundColor Cyan
        $ProgressPreference = 'SilentlyContinue'
        Invoke-RestMethod -Uri https://fly.io/install.ps1 -UseBasicParsing | Invoke-Expression

        Write-Host "Installation complete!" -ForegroundColor Green
        Write-Host ""
        Write-Host "IMPORTANT: Please close and reopen this terminal window, then run this script again." -ForegroundColor Yellow
        Write-Host "This is necessary for the PATH to be updated." -ForegroundColor Yellow
        Write-Host ""

        $restart = Read-Host "Press Enter to exit (then reopen terminal and run again)"
        exit 0

    } catch {
        Write-Host "Installation failed: $_" -ForegroundColor Red
        Write-Host ""
        Write-Host "Please try installing manually:" -ForegroundColor Yellow
        Write-Host "1. Open PowerShell as Administrator" -ForegroundColor White
        Write-Host "2. Run: iwr https://fly.io/install.ps1 -useb | iex" -ForegroundColor Cyan
        Write-Host "3. Close and reopen your terminal" -ForegroundColor White
        Write-Host "4. Run this script again" -ForegroundColor White
        exit 1
    }
}

# Try to get version
Write-Host "Checking flyctl version..." -ForegroundColor Cyan
try {
    $version = & $flyctlCmd version 2>&1
    Write-Host "Version: $version" -ForegroundColor Green
} catch {
    Write-Host "Could not get version, but continuing..." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  Logging in to Fly.io" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Check if already logged in
Write-Host "Checking authentication status..." -ForegroundColor Cyan
$authCheck = & $flyctlCmd auth whoami 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Already logged in as: $authCheck" -ForegroundColor Green
    Write-Host ""

    $relogin = Read-Host "Do you want to login again? (y/n)"
    if ($relogin -ne 'y') {
        Write-Host "Keeping current login." -ForegroundColor Green

        # Show apps
        Write-Host ""
        Write-Host "Your Fly.io apps:" -ForegroundColor Cyan
        & $flyctlCmd apps list

        Write-Host ""
        Write-Host "✓ Ready to deploy!" -ForegroundColor Green
        Write-Host "Run: .\deploy-to-flyio.ps1" -ForegroundColor Cyan
        exit 0
    }
}

# Login
Write-Host ""
Write-Host "Opening browser for authentication..." -ForegroundColor Yellow
Write-Host "If browser doesn't open, copy the URL and paste it in your browser." -ForegroundColor Yellow
Write-Host ""

try {
    & $flyctlCmd auth login

    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "✓✓✓ Successfully logged in to Fly.io! ✓✓✓" -ForegroundColor Green
        Write-Host ""

        # Show current user
        $currentUser = & $flyctlCmd auth whoami
        Write-Host "Logged in as: $currentUser" -ForegroundColor Cyan

        # Show apps
        Write-Host ""
        Write-Host "Your Fly.io apps:" -ForegroundColor Cyan
        & $flyctlCmd apps list

        Write-Host ""
        Write-Host "=====================================" -ForegroundColor Cyan
        Write-Host "  Next Steps" -ForegroundColor Cyan
        Write-Host "=====================================" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "1. Deploy your app:" -ForegroundColor Yellow
        Write-Host "   .\deploy-to-flyio.ps1" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "2. Or manually create and deploy:" -ForegroundColor Yellow
        Write-Host "   flyctl apps create bancosystem" -ForegroundColor Cyan
        Write-Host "   flyctl deploy" -ForegroundColor Cyan
        Write-Host ""

    } else {
        Write-Host ""
        Write-Host "Login failed or was cancelled." -ForegroundColor Red
        Write-Host "Please try again by running this script again." -ForegroundColor Yellow
    }

} catch {
    Write-Host ""
    Write-Host "Error during login: $_" -ForegroundColor Red
    Write-Host "Please try running: flyctl auth login" -ForegroundColor Yellow
    exit 1
}

