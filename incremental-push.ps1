# Incremental Push Script
# This script pushes commits to GitHub incrementally to avoid timeouts

Write-Host "Starting incremental push to GitHub..." -ForegroundColor Green

# Get all commits
$commits = git rev-list --reverse HEAD

$count = 0
foreach ($commit in $commits) {
    $count++
    Write-Host "Pushing commit $count of $($commits.Count): $commit" -ForegroundColor Cyan

    try {
        git push origin ${commit}:refs/heads/main 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  Success!" -ForegroundColor Green
        } else {
            Write-Host "  Failed, retrying..." -ForegroundColor Yellow
            Start-Sleep -Seconds 5
            git push origin ${commit}:refs/heads/main 2>&1
        }
    } catch {
        Write-Host "  Error: $_" -ForegroundColor Red
        break
    }
}

Write-Host "`nFinalizing push..." -ForegroundColor Green
git push -u origin main

Write-Host "Push complete!" -ForegroundColor Green

