#!/bin/bash
# ================================================================
# NDOSA.STORE - Automated Deployment Script
# ================================================================
# This script automates the deployment process for ndosa.store
# Run this on your DirectAdmin server
# ================================================================

set -e  # Exit on error

echo "=========================================="
echo "NDOSA.STORE Deployment Script"
echo "=========================================="
echo ""

# Configuration
DOMAIN="ndosa.store"
USER="ndosa"
PUBLIC_HTML="/home/$USER/public_html"
BACKUP_DIR="/home/$USER/backups"
LOG_DIR="/home/$USER/logs"
DB_NAME="ndosa_store"
DB_USER="ndosa_user"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_step() {
    echo ""
    echo "=========================================="
    echo "$1"
    echo "=========================================="
}

# Check if running as correct user
if [ "$USER" != "$USER" ]; then
    print_error "Please run this script as user: $USER"
    exit 1
fi

# Step 1: Create required directories
print_step "Step 1: Creating Required Directories"
mkdir -p $PUBLIC_HTML
mkdir -p $BACKUP_DIR
mkdir -p $LOG_DIR
mkdir -p $PUBLIC_HTML/uploads/{products,customers,temp,documents}
print_status "Directories created"

# Step 2: Check if database exists
print_step "Step 2: Database Setup"
read -p "Enter database password for $DB_USER: " -s DB_PASS
echo ""

# Test database connection
if mysql -u $DB_USER -p$DB_PASS -e "USE $DB_NAME;" 2>/dev/null; then
    print_status "Database $DB_NAME exists"
else
    print_warning "Database does not exist. Please create it first:"
    echo "  1. Log into DirectAdmin"
    echo "  2. Go to MySQL Management"
    echo "  3. Create database: $DB_NAME"
    echo "  4. Create user: $DB_USER"
    echo "  5. Grant all privileges"
    read -p "Press Enter once database is created..."
fi

# Step 3: Import database if SQL file exists
print_step "Step 3: Database Import"
SQL_FILE="$PUBLIC_HTML/database/complete-deployment.sql"
if [ -f "$SQL_FILE" ]; then
    print_status "Found database file, importing..."
    mysql -u $DB_USER -p$DB_PASS $DB_NAME < $SQL_FILE
    print_status "Database imported successfully"
else
    print_warning "Database file not found at: $SQL_FILE"
    print_warning "Please import manually: mysql -u $DB_USER -p $DB_NAME < complete-deployment.sql"
fi

# Step 4: Configure environment file
print_step "Step 4: Environment Configuration"
if [ ! -f "$PUBLIC_HTML/.env" ]; then
    if [ -f "$PUBLIC_HTML/.env.production" ]; then
        cp $PUBLIC_HTML/.env.production $PUBLIC_HTML/.env
        print_status ".env file created from .env.production"
    else
        print_error ".env.production not found!"
        exit 1
    fi
fi

# Generate secure keys
SECRET_KEY=$(openssl rand -hex 32)
ENCRYPTION_KEY=$(openssl rand -hex 16)

# Update .env file
sed -i "s/DB_NAME=.*/DB_NAME=$DB_NAME/" $PUBLIC_HTML/.env
sed -i "s/DB_USER=.*/DB_USER=$DB_USER/" $PUBLIC_HTML/.env
sed -i "s/DB_PASS=.*/DB_PASS=$DB_PASS/" $PUBLIC_HTML/.env
sed -i "s/SECRET_KEY=.*/SECRET_KEY=$SECRET_KEY/" $PUBLIC_HTML/.env
sed -i "s/ENCRYPTION_KEY=.*/ENCRYPTION_KEY=$ENCRYPTION_KEY/" $PUBLIC_HTML/.env
sed -i "s|SITE_URL=.*|SITE_URL=https://$DOMAIN|" $PUBLIC_HTML/.env

print_status "Environment configured"
print_status "SECRET_KEY: $SECRET_KEY"
print_status "ENCRYPTION_KEY: $ENCRYPTION_KEY"

# Step 5: Set proper permissions
print_step "Step 5: Setting File Permissions"
chmod 755 $PUBLIC_HTML
chmod 644 $PUBLIC_HTML/.env
chmod -R 755 $PUBLIC_HTML/includes
chmod -R 755 $PUBLIC_HTML/admin
chmod -R 777 $PUBLIC_HTML/uploads
chmod 644 $PUBLIC_HTML/.htaccess
print_status "Permissions set"

# Step 6: Create .htaccess if not exists
print_step "Step 6: Apache Configuration"
if [ ! -f "$PUBLIC_HTML/.htaccess" ]; then
    cat > $PUBLIC_HTML/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Remove www
    RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
    RewriteRule ^(.*)$ https://%1/$1 [R=301,L]
    
    # Block sensitive files
    RewriteRule ^\.env$ - [F,L]
    RewriteRule ^includes/ - [F,L]
    RewriteRule ^database/ - [F,L]
    
    php_flag display_errors Off
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
</IfModule>

<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

Options -Indexes
EOF
    print_status ".htaccess created"
else
    print_status ".htaccess already exists"
fi

# Step 7: Setup backup script
print_step "Step 7: Backup Configuration"
cat > /home/$USER/backup.sh << EOF
#!/bin/bash
DATE=\$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$BACKUP_DIR"
DB_NAME="$DB_NAME"
DB_USER="$DB_USER"
DB_PASS="$DB_PASS"

# Create backup directory if not exists
mkdir -p \$BACKUP_DIR

# Backup database
mysqldump -u \$DB_USER -p\$DB_PASS \$DB_NAME | gzip > \$BACKUP_DIR/db_\$DATE.sql.gz

# Backup uploads
tar -czf \$BACKUP_DIR/files_\$DATE.tar.gz $PUBLIC_HTML/uploads

# Delete old backups (30 days)
find \$BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup completed: \$DATE" >> $LOG_DIR/backup.log
EOF

chmod +x /home/$USER/backup.sh
print_status "Backup script created"

# Setup cron job
print_status "Setting up daily backup cron job..."
(crontab -l 2>/dev/null; echo "0 2 * * * /home/$USER/backup.sh") | crontab -
print_status "Cron job configured (runs daily at 2 AM)"

# Step 8: Create log files
print_step "Step 8: Log Files Setup"
touch $LOG_DIR/error.log
touch $LOG_DIR/access.log
touch $LOG_DIR/backup.log
chmod 644 $LOG_DIR/*.log
print_status "Log files created"

# Step 9: Verify installation
print_step "Step 9: Verification"

# Check if PHP is available
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    print_status "PHP Version: $PHP_VERSION"
    
    if (( $(echo "$PHP_VERSION >= 8.0" | bc -l) )); then
        print_status "PHP version is compatible"
    else
        print_warning "PHP version should be 8.0 or higher"
    fi
else
    print_warning "PHP not found in PATH"
fi

# Check database connection
if mysql -u $DB_USER -p$DB_PASS -e "USE $DB_NAME; SELECT COUNT(*) FROM settings;" &> /dev/null; then
    print_status "Database connection successful"
else
    print_error "Database connection failed"
fi

# Check uploads directory
if [ -w "$PUBLIC_HTML/uploads" ]; then
    print_status "Uploads directory is writable"
else
    print_error "Uploads directory is not writable"
fi

# Final Summary
print_step "Deployment Complete!"
echo ""
echo "Site URL: https://$DOMAIN"
echo "Admin URL: https://$DOMAIN/admin/"
echo ""
echo "Default Admin Credentials:"
echo "  Username: admin"
echo "  Password: Admin@2025!"
echo ""
print_warning "IMPORTANT: Change the admin password immediately!"
echo ""
echo "Next Steps:"
echo "  1. Visit https://$DOMAIN to verify site is working"
echo "  2. Log into admin panel and change password"
echo "  3. Configure payment gateways in Admin → Settings"
echo "  4. Setup SMTP email settings"
echo "  5. Add products and content"
echo "  6. Test complete checkout flow"
echo ""
echo "Configuration saved to:"
echo "  Environment: $PUBLIC_HTML/.env"
echo "  Backups: $BACKUP_DIR"
echo "  Logs: $LOG_DIR"
echo ""
echo "Backup script runs daily at 2 AM"
echo "To run manual backup: /home/$USER/backup.sh"
echo ""
print_status "Deployment completed successfully!"
echo ""
echo "=========================================="
