# FazzTrack - Work Order Management System

<div align="center">
  <h3>🎯 Streamlined Workflow Management for Shirt Printing Business</h3>
  <p>A comprehensive solution for managing orders, production, payments, and tracking</p>
</div>

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Documentation](#api-documentation)
- [Database Schema](#database-schema)
- [User Roles](#user-roles)
- [Workflow](#workflow)
- [Security](#security)
- [Testing](#testing)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [Support](#support)
- [License](#license)
- [Company Information](#company-information)
- [Developer Information](#developer-information)

---

## 🎯 Overview

FazzTrack is a modern, full-stack work order management system specifically designed for shirt printing businesses. It provides end-to-end workflow management from order creation to delivery, featuring QR code-based job tracking, real-time status updates, comprehensive payment management, and detailed analytics.

### Key Benefits
- **Streamlined Operations**: Automated workflow management
- **Real-time Tracking**: QR code-based job tracking system
- **Payment Management**: Comprehensive payment tracking and approval
- **Role-based Access**: Secure, role-based user management
- **Analytics & Reporting**: Detailed insights and performance metrics
- **Mobile-friendly**: Responsive design for all devices

---

## ✨ Features

### 🏢 Order Management
- Create and manage customer orders
- Automatic tracking ID generation
- Order status progression tracking
- Due date management
- Client relationship management

### 🎨 Production Workflow
- Multi-phase production tracking (Design → Print → Press → Cut → Sew → QC → Packing)
- QR code generation for each job
- Real-time job status updates
- Time tracking and duration calculation
- Production efficiency metrics

### 💰 Payment System
- Multiple payment types (Design Deposit, Production Deposit, Balance Payment)
- Various payment methods (Cash, Bank Transfer, Credit/Debit Card, Check, Other)
- Payment approval workflow
- Receipt management
- Payment status tracking

### 📱 QR Code Integration
- Unique QR codes for each production job
- Mobile scanning for job updates
- Automatic time tracking
- Status progression via QR scanning

### 🔐 User Management
- Role-based access control (SuperAdmin, Admin, Sales Manager, Designer, Production Staff)
- Department-based permissions
- Secure authentication
- User activity tracking

### 📊 Dashboard & Analytics
- Role-specific dashboards
- Real-time metrics and KPIs
- Production efficiency reports
- Sales analytics
- Due date tracking

### 🔍 Public Tracking
- Customer order tracking portal
- Real-time status updates
- Estimated delivery dates
- Contact information display

---

## 🛠 Technology Stack

### Backend
- **Framework**: Laravel 10.x (PHP 8.1+)
- **Database**: MySQL 8.0+
- **Authentication**: JWT (Laravel Sanctum)
- **Queue System**: Redis/Database
- **File Storage**: Local/Cloud Storage
- **API**: RESTful API design

### Frontend
- **Framework**: Next.js 14.x (React 18+)
- **Styling**: Tailwind CSS
- **State Management**: React Context/Redux
- **HTTP Client**: Axios
- **UI Components**: Custom components

### Development Tools
- **Code Quality**: Laravel Pint, ESLint, Prettier
- **Testing**: PHPUnit, Jest
- **Version Control**: Git
- **Package Management**: Composer, npm/yarn

---

## 📋 System Requirements

### Backend Requirements
- PHP 8.1 or higher
- MySQL 8.0 or higher
- Composer 2.x
- Redis (optional, for queues)
- Web server (Apache/Nginx)

### Frontend Requirements
- Node.js 18.x or higher
- npm 9.x or yarn 1.22+
- Modern web browser

---

## 🚀 Installation

### Backend Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd fazztrack/backend
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Storage setup**
   ```bash
   php artisan storage:link
   ```

6. **Start development server**
   ```bash
   php artisan serve
   ```

### Frontend Setup

1. **Navigate to frontend directory**
   ```bash
   cd ../frontend
   ```

2. **Install dependencies**
   ```bash
   npm install
   # or
   yarn install
   ```

3. **Environment configuration**
   ```bash
   cp .env.example .env.local
   ```

4. **Start development server**
   ```bash
   npm run dev
   # or
   yarn dev
   ```

---

## ⚙️ Configuration

### Environment Variables

#### Backend (.env)
```env
APP_NAME=FazzTrack
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fazztrack
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

#### Frontend (.env.local)
```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_APP_NAME=FazzTrack
```

---

## 📖 Usage

### Getting Started

1. **Access the application**
   - Backend API: `http://localhost:8000`
   - Frontend: `http://localhost:3000`
   - Public Tracking: `http://localhost:3000/track`

2. **Default Admin Credentials**
   - Email: `admin@fazztrack.com`
   - Password: `password`

3. **Create your first order**
   - Login as Sales Manager
   - Navigate to Orders → Create New Order
   - Fill in client and order details
   - Submit for approval

### QR Code Workflow

1. **Generate QR Codes**
   - QR codes are automatically generated for each job
   - Access via Jobs → View Job → QR Code

2. **Scan QR Codes**
   - Use mobile device to scan QR codes
   - Update job status in real-time
   - Track time automatically

---

## 📚 API Documentation

### Authentication
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

### Orders
```http
# Get all orders
GET /api/orders
Authorization: Bearer {token}

# Create new order
POST /api/orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "client_id": 1,
  "job_name": "Custom T-Shirts",
  "due_date_design": "2025-01-20",
  "due_date_production": "2025-01-25"
}
```

### Public Tracking
```http
# Track order by tracking ID
GET /api/track/{tracking_id}

# Track order by order ID
GET /api/track/order/{order_id}
```

For complete API documentation, visit `/api/documentation` when the server is running.

---

## 🗄️ Database Schema

### Core Tables
- **users**: User accounts and authentication
- **clients**: Customer information
- **orders**: Order management
- **products**: Product catalog
- **order_items**: Order line items
- **jobs**: Production jobs
- **payments**: Payment tracking
- **file_attachments**: File management
- **departments**: Department structure
- **sections**: Production sections

For detailed schema information, see `database/migrations/` directory.

---

## 👥 User Roles

### SuperAdmin
- Full system access
- User management
- System configuration
- All reports and analytics

### Admin
- Order approval
- Payment approval
- User management (limited)
- Operational reports

### Sales Manager
- Client management
- Order creation and management
- Payment tracking
- Sales reports

### Designer
- Design job management
- File uploads
- Design status updates
- Design-related reports

### Production Staff
- Job scanning (QR codes)
- Job status updates
- Time tracking
- Production reports

---

## 🔄 Workflow

For detailed workflow documentation, see [flow.md](flow.md).

### Order Lifecycle
```
Order Creation → Approval → Production → Quality Control → Delivery → Completion
```

### Production Phases
```
DESIGN → PRINT → PRESS → CUT → SEW → QC → PACKING
```

---

## 🔒 Security

### Authentication & Authorization
- JWT-based authentication
- Role-based access control (RBAC)
- Permission-based route protection
- Session management

### Data Protection
- Input validation and sanitization
- SQL injection prevention
- XSS protection
- CSRF protection
- Secure file uploads

### Best Practices
- Regular security updates
- Environment variable protection
- Secure API endpoints
- Rate limiting
- Audit logging

---

## 🧪 Testing

### Backend Testing
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### Frontend Testing
```bash
# Run all tests
npm test

# Run tests in watch mode
npm test:watch

# Run tests with coverage
npm test:coverage
```

### Code Quality
```bash
# Backend linting
./vendor/bin/pint

# Frontend linting
npm run lint
npm run lint:fix
```

---

## 🚀 Deployment

### Production Setup

1. **Server Requirements**
   - Linux server (Ubuntu 20.04+ recommended)
   - PHP 8.1+ with required extensions
   - MySQL 8.0+
   - Nginx/Apache
   - SSL certificate

2. **Environment Configuration**
   ```bash
   # Set production environment
   APP_ENV=production
   APP_DEBUG=false
   
   # Configure database
   DB_HOST=your-db-host
   DB_DATABASE=your-db-name
   DB_USERNAME=your-db-user
   DB_PASSWORD=your-secure-password
   ```

3. **Optimization**
   ```bash
   # Cache configuration
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   
   # Optimize autoloader
   composer install --optimize-autoloader --no-dev
   ```

### Docker Deployment
```bash
# Build and run with Docker Compose
docker-compose up -d
```

---

## 🤝 Contributing

### Development Guidelines

1. **Code Standards**
   - Follow PSR-12 for PHP
   - Use ESLint/Prettier for JavaScript
   - Write meaningful commit messages
   - Include tests for new features

2. **Pull Request Process**
   - Fork the repository
   - Create feature branch
   - Write tests
   - Submit pull request
   - Code review process

3. **Issue Reporting**
   - Use issue templates
   - Provide detailed reproduction steps
   - Include environment information
   - Add relevant labels

---

## 📞 Support

### Getting Help

- **Documentation**: Check this README and [flow.md](flow.md)
- **Issues**: Report bugs via GitHub issues
- **Discussions**: Use GitHub discussions for questions
- **Email**: Contact developer directly (see below)

### Troubleshooting

#### Common Issues

1. **Database Connection Error**
   ```bash
   # Check database credentials in .env
   # Ensure MySQL service is running
   sudo systemctl status mysql
   ```

2. **Permission Errors**
   ```bash
   # Fix storage permissions
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

3. **QR Code Generation Issues**
   ```bash
   # Ensure GD extension is installed
   php -m | grep -i gd
   ```

---

## 📄 License

This project is proprietary software owned by **FazzPrint Sdn Bhd**. All rights reserved.

**Important Notice**: This software is developed exclusively for FazzPrint Sdn Bhd and is not licensed for distribution, modification, or use by third parties without explicit written permission from the copyright holder.

---

## 🏢 Company Information

**FazzPrint Sdn Bhd**  
📍 **Address**: 22, 22-1 & 22-2 Jalan Bestari 6D, Bandar Bestari, 41200 Klang, Selangor, Malaysia  
📞 **Phone**: +60 10-456 0817  
📧 **Email**: fazzprint@gmail.com  
🕒 **Business Hours**: Monday - Friday, 9:00 AM - 5:00 PM  

### About FazzPrint
FazzPrint Sdn Bhd is a leading shirt printing company based in Klang, Selangor, Malaysia. We specialize in high-quality custom shirt printing services, serving both individual customers and corporate clients. Our commitment to excellence and customer satisfaction drives us to continuously improve our processes and technology.

---

## 👨‍💻 Developer Information

**⚠️ IMPORTANT NOTICE: The following developer information is permanent and unchangeable. Any attempt to modify, remove, or alter this information is strictly prohibited and may result in legal action.**

---

### **Primary Developer & System Architect**

**Faiz Nasir**  
📞 **Phone**: +60 19-459 6236  
📧 **Email**: faizhiruko00@gmail.com  
🌐 **Portfolio**: https://faiznasirweb.netlify.app/  
💼 **Role**: Lead Full-Stack Developer & System Architect  

### About the Developer
Faiz Nasir is an experienced full-stack developer specializing in modern web technologies including Laravel, React, Next.js, and mobile application development. With expertise in creating scalable business solutions, Faiz has designed and developed the FazzTrack system from the ground up, ensuring it meets the specific needs of the shirt printing industry.

### Development Expertise
- **Backend Development**: Laravel, PHP, MySQL, API Design
- **Frontend Development**: React, Next.js, TypeScript, Tailwind CSS
- **Mobile Development**: React Native, Flutter
- **DevOps**: Docker, CI/CD, Cloud Deployment
- **Database Design**: MySQL, PostgreSQL, MongoDB
- **System Architecture**: Microservices, RESTful APIs, Real-time Systems

### Contact for Technical Support
For technical issues, feature requests, or system modifications, please contact Faiz Nasir directly using the contact information provided above.

---

**© 2025 FazzPrint Sdn Bhd. All rights reserved.**  
**Developed by Faiz Nasir**

---

*This README.md file contains comprehensive information about the FazzTrack system. For additional technical details, please refer to the [flow.md](flow.md) file and the inline code documentation.*
