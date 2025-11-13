#!/bin/bash

# Bancozim Application Deployment Script
# Usage: ./scripts/deploy.sh [environment] [version]
# Example: ./scripts/deploy.sh production v1.2.3

set -e  # Exit on any error

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
ENVIRONMENT="${1:-staging}"
VERSION="${2:-latest}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Validate environment
validate_environment() {
    if [[ ! "$ENVIRONMENT" =~ ^(staging|production)$ ]]; then
        log_error "Invalid environment: $ENVIRONMENT. Must be 'staging' or 'production'"
        exit 1
    fi
    
    log_info "Deploying to environment: $ENVIRONMENT"
    log_info "Version: $VERSION"
}

# Check prerequisites
check_prerequisites() {
    log_info "Checking prerequisites..."
    
    # Check if Docker is installed and running
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed"
        exit 1
    fi
    
    if ! docker info &> /dev/null; then
        log_error "Docker is not running"
        exit 1
    fi
    
    # Check if Docker Compose is installed
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose is not installed"
        exit 1
    fi
    
    # Check if environment file exists
    if [[ ! -f ".env.$ENVIRONMENT" ]]; then
        log_error "Environment file .env.$ENVIRONMENT not found"
        exit 1
    fi
    
    log_success "Prerequisites check passed"
}

# Backup current deployment
backup_current_deployment() {
    log_info "Creating backup of current deployment..."
    
    BACKUP_DIR="backups/$ENVIRONMENT/$TIMESTAMP"
    mkdir -p "$BACKUP_DIR"
    
    # Backup database
    if docker-compose ps database | grep -q "Up"; then
        log_info "Backing up database..."
        docker-compose exec -T database mysqldump -u root -p"$DB_ROOT_PASSWORD" bancozim > "$BACKUP_DIR/database.sql"
        gzip "$BACKUP_DIR/database.sql"
        log_success "Database backup created"
    fi
    
    # Backup storage
    if [[ -d "storage" ]]; then
        log_info "Backing up storage..."
        tar -czf "$BACKUP_DIR/storage.tar.gz" storage/
        log_success "Storage backup created"
    fi
    
    # Backup environment file
    cp ".env.$ENVIRONMENT" "$BACKUP_DIR/.env"
    
    log_success "Backup completed: $BACKUP_DIR"
}

# Pull latest images
pull_images() {
    log_info "Pulling latest Docker images..."
    
    # Set environment
    export COMPOSE_FILE="docker-compose.yml"
    if [[ -f "docker-compose.$ENVIRONMENT.yml" ]]; then
        export COMPOSE_FILE="docker-compose.yml:docker-compose.$ENVIRONMENT.yml"
    fi
    
    # Copy environment file
    cp ".env.$ENVIRONMENT" .env
    
    # Pull images
    docker-compose pull
    
    log_success "Images pulled successfully"
}

# Run database migrations
run_migrations() {
    log_info "Running database migrations..."
    
    # Wait for database to be ready
    log_info "Waiting for database to be ready..."
    timeout=60
    while ! docker-compose exec -T database mysqladmin ping -h localhost --silent; do
        sleep 1
        timeout=$((timeout - 1))
        if [[ $timeout -eq 0 ]]; then
            log_error "Database failed to start within 60 seconds"
            exit 1
        fi
    done
    
    # Run migrations
    docker-compose exec -T app php artisan migrate --force
    
    log_success "Database migrations completed"
}

# Deploy application
deploy_application() {
    log_info "Deploying application..."
    
    # Start services
    docker-compose up -d
    
    # Wait for application to be ready
    log_info "Waiting for application to be ready..."
    timeout=120
    while ! curl -f http://localhost/health &> /dev/null; do
        sleep 2
        timeout=$((timeout - 2))
        if [[ $timeout -eq 0 ]]; then
            log_error "Application failed to start within 120 seconds"
            exit 1
        fi
    done
    
    log_success "Application deployed successfully"
}

# Optimize application
optimize_application() {
    log_info "Optimizing application..."
    
    # Clear and cache configurations
    docker-compose exec -T app php artisan config:cache
    docker-compose exec -T app php artisan route:cache
    docker-compose exec -T app php artisan view:cache
    
    # Optimize Composer autoloader
    docker-compose exec -T app composer dump-autoload --optimize
    
    # Clear application cache
    docker-compose exec -T app php artisan cache:clear
    
    log_success "Application optimization completed"
}

# Restart services
restart_services() {
    log_info "Restarting services..."
    
    # Restart queue workers
    docker-compose restart queue
    
    # Restart scheduler
    docker-compose restart scheduler
    
    log_success "Services restarted"
}

# Run health checks
run_health_checks() {
    log_info "Running health checks..."
    
    # Application health check
    if curl -f http://localhost/health &> /dev/null; then
        log_success "Application health check passed"
    else
        log_error "Application health check failed"
        exit 1
    fi
    
    # Database health check
    if docker-compose exec -T database mysqladmin ping -h localhost --silent; then
        log_success "Database health check passed"
    else
        log_error "Database health check failed"
        exit 1
    fi
    
    # Redis health check
    if docker-compose exec -T redis redis-cli ping | grep -q "PONG"; then
        log_success "Redis health check passed"
    else
        log_error "Redis health check failed"
        exit 1
    fi
    
    # Queue health check
    if docker-compose exec -T app php artisan queue:monitor &> /dev/null; then
        log_success "Queue health check passed"
    else
        log_warning "Queue health check failed (non-critical)"
    fi
    
    log_success "All health checks completed"
}

# Cleanup old backups
cleanup_old_backups() {
    log_info "Cleaning up old backups..."
    
    # Keep only last 10 backups
    if [[ -d "backups/$ENVIRONMENT" ]]; then
        cd "backups/$ENVIRONMENT"
        ls -t | tail -n +11 | xargs -r rm -rf
        cd - > /dev/null
    fi
    
    # Clean up old Docker images
    docker image prune -f
    
    log_success "Cleanup completed"
}

# Send notification
send_notification() {
    local status=$1
    local message=$2
    
    if [[ -n "$SLACK_WEBHOOK_URL" ]]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"🚀 Deployment $status: $ENVIRONMENT - $message\"}" \
            "$SLACK_WEBHOOK_URL" &> /dev/null || true
    fi
    
    if [[ -n "$DISCORD_WEBHOOK_URL" ]]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"content\":\"🚀 Deployment $status: $ENVIRONMENT - $message\"}" \
            "$DISCORD_WEBHOOK_URL" &> /dev/null || true
    fi
}

# Rollback function
rollback() {
    log_error "Deployment failed. Starting rollback..."
    
    # Find latest backup
    LATEST_BACKUP=$(ls -t backups/$ENVIRONMENT/ | head -n 1)
    
    if [[ -n "$LATEST_BACKUP" ]]; then
        log_info "Rolling back to backup: $LATEST_BACKUP"
        
        # Restore database
        if [[ -f "backups/$ENVIRONMENT/$LATEST_BACKUP/database.sql.gz" ]]; then
            gunzip -c "backups/$ENVIRONMENT/$LATEST_BACKUP/database.sql.gz" | \
                docker-compose exec -T database mysql -u root -p"$DB_ROOT_PASSWORD" bancozim
        fi
        
        # Restore storage
        if [[ -f "backups/$ENVIRONMENT/$LATEST_BACKUP/storage.tar.gz" ]]; then
            tar -xzf "backups/$ENVIRONMENT/$LATEST_BACKUP/storage.tar.gz"
        fi
        
        # Restore environment
        cp "backups/$ENVIRONMENT/$LATEST_BACKUP/.env" ".env.$ENVIRONMENT"
        cp ".env.$ENVIRONMENT" .env
        
        # Restart services
        docker-compose up -d
        
        log_success "Rollback completed"
        send_notification "ROLLED BACK" "Deployment failed and was rolled back to $LATEST_BACKUP"
    else
        log_error "No backup found for rollback"
        send_notification "FAILED" "Deployment failed and no backup available for rollback"
    fi
}

# Main deployment function
main() {
    log_info "Starting deployment process..."
    
    # Trap errors for rollback
    trap 'rollback' ERR
    
    validate_environment
    check_prerequisites
    backup_current_deployment
    pull_images
    deploy_application
    run_migrations
    optimize_application
    restart_services
    run_health_checks
    cleanup_old_backups
    
    log_success "Deployment completed successfully!"
    send_notification "SUCCESS" "Version $VERSION deployed successfully"
    
    # Remove error trap
    trap - ERR
}

# Run main function
main "$@"
