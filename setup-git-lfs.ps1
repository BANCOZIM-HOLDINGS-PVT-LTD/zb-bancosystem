# Git LFS Setup Script
# This script helps migrate large files to Git LFS

Write-Host "Setting up Git LFS for large files..." -ForegroundColor Green

# Install Git LFS (if not already installed)
# Run: git lfs install

# Track large file patterns
git lfs track "*.zip"
git lfs track "*.pdf"
git lfs track "*.mp4"
git lfs track "*.avi"
git lfs track "public/models/**"
git lfs track "storage/app/**"

Write-Host "Git LFS patterns configured in .gitattributes" -ForegroundColor Green
Write-Host "Now run:" -ForegroundColor Yellow
Write-Host "  git add .gitattributes" -ForegroundColor White
Write-Host "  git add ." -ForegroundColor White
Write-Host "  git commit -m 'Configure Git LFS for large files'" -ForegroundColor White
Write-Host "  git push -u origin main" -ForegroundColor White

