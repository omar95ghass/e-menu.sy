# E-Menu Backend System

A complete PHP backend system for restaurant menu management with OOP architecture, MySQL database, and RESTful API integration.

## Features

- **Object-Oriented PHP 8+** with clean, modular architecture
- **RESTful API** with comprehensive endpoints
- **MySQL Database** with well-defined relationships
- **Session-based Authentication** with CSRF protection
- **Subscription Management** with plan-based permissions
- **Automatic Subdomain Generation** for restaurants
- **File Upload Management** with validation
- **Review System** with moderation
- **Analytics & Statistics** with detailed reporting
- **Admin Panel** for system management
- **Multilingual Support** (Arabic/English)
- **Security Features** with input validation and sanitization

## System Requirements

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)
- PDO MySQL extension
- GD extension (for image processing)
- JSON extension
- OpenSSL extension

## Installation

### 1. Clone/Download the Project

```bash
git clone <repository-url>
cd e-menu
```

### 2. Configure Database

1. Create a MySQL database for the project
2. Update database credentials in `config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'e_menu');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. Run Installation Script

1. Navigate to `http://your-domain/install.php`
2. Click "Start Installation"
3. The script will:
   - Create database tables
   - Insert default data
   - Create necessary directories
   - Set proper permissions

### 4. Security Setup

1. Delete `install.php` after installation
2. Change default admin password (admin@e-menu.sy / admin123)
3. Update `JWT_SECRET` in `config/config.php`
4. Configure HTTPS in production

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - Restaurant registration
- `POST /api/auth/logout` - User logout
- `GET /api/auth/csrf-token` - Get CSRF token

### Restaurants
- `GET /api/restaurants` - Get all restaurants
- `GET /api/restaurant/{slug}` - Get restaurant by slug
- `GET /api/restaurant/dashboard` - Get current restaurant dashboard
- `PUT /api/restaurant/update` - Update restaurant profile
- `GET /api/restaurant/stats` - Get restaurant statistics
- `POST /api/restaurant/upload-logo` - Upload restaurant logo
- `POST /api/restaurant/upload-cover` - Upload cover image

### Menu Management
- `GET /api/menu/{restaurant_id}` - Get restaurant menu
- `POST /api/menu/add-category` - Add new category
- `POST /api/menu/add-item` - Add new menu item
- `PUT /api/menu/update-item` - Update menu item
- `DELETE /api/menu/delete-item/{id}` - Delete menu item
- `POST /api/menu/upload-image` - Upload item image

### Subscriptions
- `GET /api/subscriptions` - Get all subscription plans
- `POST /api/subscriptions/assign/{restaurant_id}` - Assign plan to restaurant
- `GET /api/subscriptions/limits/{restaurant_id}` - Get restaurant limits

### Reviews
- `POST /api/review/add` - Add new review
- `GET /api/review/{restaurant_id}` - Get restaurant reviews

### Admin (Admin Only)
- `GET /api/admin/restaurants` - Get all restaurants for admin
- `PUT /api/admin/activate/{restaurant_id}` - Activate restaurant
- `DELETE /api/admin/remove/{restaurant_id}` - Delete restaurant
- `POST /api/admin/add-plan` - Create subscription plan
- `PUT /api/admin/edit-plan/{id}` - Update subscription plan
- `DELETE /api/admin/delete-plan/{id}` - Delete subscription plan

### Analytics
- `GET /api/analytics/restaurant` - Get restaurant analytics
- `GET /api/analytics/system` - Get system analytics (admin only)

### Settings
- `GET /api/settings/get` - Get public settings
- `PUT /api/settings/update` - Update settings (admin only)

## Database Schema

The system includes the following main tables:

- `users` - User accounts (restaurant owners and admins)
- `restaurants` - Restaurant information and settings
- `subscription_plans` - Available subscription plans
- `categories` - Menu categories
- `menu_items` - Individual menu items
- `reviews` - Customer reviews
- `statistics` - Analytics data
- `sessions` - User sessions
- `csrf_tokens` - CSRF protection tokens
- `system_settings` - System configuration
- `file_uploads` - File upload tracking
- `activity_logs` - System activity logs

## Core Classes

### Database.php
- Manages database connections via PDO
- Provides secure query execution
- Includes helper methods for common operations

### Auth.php
- Handles authentication and authorization
- Manages sessions and CSRF tokens
- Generates unique subdomains for restaurants

### Restaurant.php
- Manages restaurant profiles and settings
- Handles file uploads (logos, cover images)
- Provides restaurant statistics

### Subscription.php
- Manages subscription plans and limits
- Enforces plan-based permissions
- Handles subscription expiry and renewal

### Menu.php
- CRUD operations for categories and items
- Handles menu item images
- Enforces subscription limits

### Review.php
- Manages customer reviews
- Calculates average ratings
- Handles review moderation

### Statistics.php
- Collects and analyzes usage data
- Provides detailed analytics
- Exports data in various formats

### Admin.php
- System administration functions
- Restaurant and user management
- Subdomain activation/deactivation

### Settings.php
- System configuration management
- Public and private settings
- Settings import/export

## Configuration

### Main Configuration (`config/config.php`)

Key configuration options:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'e_menu');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application
define('APP_NAME', 'E-Menu');
define('APP_DOMAIN', 'e-menu.sy');
define('APP_SUBDOMAIN_BASE', 'e-menu.sy');

// Security
define('JWT_SECRET', 'your-secret-key');
define('PASSWORD_MIN_LENGTH', 8);

// File Uploads
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
```

## Subdomain Management

The system automatically generates subdomains for restaurants in the format:
`restaurant-name.e-menu.sy`

### DNS Configuration

For production deployment, configure your DNS server to:
1. Create a wildcard A record: `*.e-menu.sy` → `your-server-ip`
2. Configure your web server to handle subdomain routing
3. Set up SSL certificates for subdomains

### Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{HTTP_HOST} ^([^.]+)\.e-menu\.sy$ [NC]
RewriteRule ^(.*)$ /restaurant.php?subdomain=%1 [QSA,L]
```

#### Nginx
```nginx
server {
    listen 80;
    server_name *.e-menu.sy;
    
    location / {
        try_files $uri $uri/ /restaurant.php?subdomain=$subdomain;
    }
}
```

## Security Features

- **CSRF Protection** - All forms protected with CSRF tokens
- **SQL Injection Prevention** - All queries use prepared statements
- **XSS Protection** - Input sanitization and output escaping
- **File Upload Security** - Type validation and size limits
- **Session Security** - Secure session configuration
- **Password Hashing** - bcrypt password hashing
- **Input Validation** - Comprehensive input validation
- **Rate Limiting** - API rate limiting (configurable)

## API Usage Examples

### Register Restaurant
```javascript
const response = await fetch('/api/auth/register', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        email: 'owner@restaurant.com',
        password: 'securepassword',
        owner_name: 'Restaurant Owner',
        restaurant_name: 'My Restaurant',
        phone: '+963 11 123 4567',
        address: 'Damascus, Syria',
        city: 'Damascus',
        cuisine_type: 'Syrian'
    })
});
```

### Login
```javascript
const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        email: 'owner@restaurant.com',
        password: 'securepassword'
    })
});
```

### Add Menu Item
```javascript
const response = await fetch('/api/menu/add-item', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify({
        category_id: 1,
        name: 'Kebab',
        name_ar: 'كباب',
        description: 'Grilled meat skewers',
        price: 50000,
        is_vegetarian: false,
        is_halal: true
    })
});
```

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check database credentials in `config/config.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **File Upload Issues**
   - Check directory permissions (755)
   - Verify `upload_max_filesize` in php.ini
   - Ensure upload directories exist

3. **Session Issues**
   - Check session directory permissions
   - Verify session configuration
   - Clear browser cookies

4. **API Endpoints Not Working**
   - Check .htaccess configuration
   - Verify mod_rewrite is enabled
   - Check web server error logs

### Debug Mode

Enable debug mode in `config/config.php`:
```php
define('DEBUG_MODE', true);
define('SHOW_SQL_QUERIES', true);
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation

## Changelog

### Version 1.0.0
- Initial release
- Complete backend system
- RESTful API implementation
- Admin panel functionality
- Subdomain management
- Subscription system
- Review system
- Analytics and statistics