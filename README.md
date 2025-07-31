# Laravel Purchase Order Approval System

## 📋 Project Overview
A comprehensive Laravel-based Purchase Order (PO) Approval System with digital signature capabilities, designed specifically for Roma Industrial Company Limited.

## ✨ Key Features

### 🔐 Authentication & Authorization
- **Multi-level user authentication** with role-based access control
- **Approval hierarchy** (User Level → Manager Level → MD Level)
- **Session management** with secure login/logout

### 📄 Purchase Order Management
- **PO Listing** with advanced filtering and pagination
- **Detailed PO View** with complete item breakdown
- **Approval Workflow** with status tracking
- **Bulk Approval** for multiple POs simultaneously

### ✍️ Digital Signature System
- **Upload digital signatures** (PNG/JPG format)
- **Signature management** (activate/deactivate/delete)
- **Automatic signature integration** in approval documents
- **Base64 encoding** for PDF/HTML integration

### 🖨️ Advanced Printing System
- **HTML-based printing** (replaces PDF for better performance)
- **Company branding** with logo integration
- **Signature display** in printed documents
- **Print history tracking**
- **Performance optimizations** with caching

### 📊 Database Integration
- **Dual database support**:
  - **Legacy Database**: SQL Server 2008 (existing PO data)
  - **Modern Database**: SQLite (approval workflows, signatures)
- **SQL Server 2008 compatible** queries
- **Optimized joins** and query performance

## 🛠️ Technical Stack

### Backend
- **Laravel 12.x** (Latest version)
- **PHP 8.x**
- **SQL Server 2008** (Legacy data)
- **SQLite** (Modern features)

### Frontend
- **Bootstrap 5.x** for responsive UI
- **Font Awesome** for icons
- **jQuery** for interactive features
- **Blade Templates** for views

### Architecture
- **Service Layer Pattern** for business logic
- **Repository Pattern** for data access
- **Observer Pattern** for notifications
- **Caching strategies** for performance

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.0 or higher
- Composer
- SQL Server 2008+ with SQLSRV drivers
- Node.js & NPM (for asset compilation)

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/l3arracuda/Romar_purchase_Approval.git
   cd Romar_purchase_Approval
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database configuration**
   Edit `.env` file:
   ```env
   # Legacy Database (SQL Server)
   DB_CONNECTION_LEGACY=sqlsrv
   DB_HOST_LEGACY=your-sql-server
   DB_DATABASE_LEGACY=Romar1
   DB_USERNAME_LEGACY=your-username
   DB_PASSWORD_LEGACY=your-password

   # Modern Database (SQLite)
   DB_CONNECTION=sqlite
   DB_DATABASE=database/database.sqlite
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Create storage directories**
   ```bash
   php artisan storage:link
   mkdir -p storage/app/public/signatures
   ```

7. **Start development server**
   ```bash
   php artisan serve
   ```

## 📞 Support & Contact

### Development Team
- **Project Lead**: Roma Industrial IT Team
- **Repository**: https://github.com/l3arracuda/Romar_purchase_Approval.git

## 📄 License

This project is proprietary software developed for Roma Industrial Company Limited.

---

**Built with ❤️ for Roma Industrial Company Limited**

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
