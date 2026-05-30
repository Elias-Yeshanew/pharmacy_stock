# PharmStock — Pharmacy Stock Management System

A full-stack pharmacy stock management application built with **Laravel 11** (backend API) and **Vue 3 + Vite** (frontend).

---

## Features

| Module | Description |
|---|---|
| 🏠 Dashboard | Live stats, low stock alerts, expiry warnings, recent movements |
| 💊 Medicines | Full CRUD, search/filter, stock status, barcode, prescription flag |
| 📦 Stock Movements | In/Out/Adjustment/Return/Expired with full audit trail |
| 🛒 Sales / Dispensing | POS-style sale screen, invoice generation, stock auto-deduction |
| 📋 Purchase Orders | Create orders, receive stock, update quantities automatically |
| 🏭 Suppliers | Supplier management with contact details |
| 🏷️ Categories | Medicine category management |
| 📊 Reports | Stock report, sales report, expiry report with CSV export |
| 🔐 Auth | JWT-style auth via Laravel Sanctum with role support |

---

## Tech Stack

**Backend**
- Laravel 11
- Laravel Sanctum (API token auth)
- MySQL
- Eloquent ORM with soft deletes

**Frontend**
- Vue 3 (Composition API + `<script setup>`)
- Vue Router 4
- Pinia (state management)
- Axios
- Tailwind CSS 3
- date-fns
- @heroicons/vue
- @vueuse/core (debounce)

---

## Project Structure

```
pharmacy-stock/
├── backend/                  # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── MedicineController.php
│   │   │   ├── SaleController.php
│   │   │   ├── PurchaseOrderController.php
│   │   │   ├── SupplierController.php
│   │   │   └── CategoryController.php
│   │   └── Models/
│   │       ├── Medicine.php
│   │       ├── Category.php
│   │       ├── Supplier.php
│   │       ├── StockMovement.php
│   │       ├── PurchaseOrder.php
│   │       ├── PurchaseOrderItem.php
│   │       ├── Sale.php
│   │       ├── SaleItem.php
│   │       └── User.php
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/DatabaseSeeder.php
│   └── routes/api.php
│
└── frontend/                 # Vue 3 SPA
    └── src/
        ├── components/
        │   ├── AppLayout.vue
        │   ├── NavItem.vue
        │   ├── StatCard.vue
        │   └── Modal.vue
        ├── views/
        │   ├── LoginView.vue
        │   ├── DashboardView.vue
        │   ├── MedicinesView.vue
        │   ├── MedicineFormView.vue
        │   ├── StockView.vue
        │   ├── SalesView.vue
        │   ├── NewSaleView.vue
        │   ├── PurchaseOrdersView.vue
        │   ├── NewPurchaseOrderView.vue
        │   ├── SuppliersView.vue
        │   ├── CategoriesView.vue
        │   └── ReportsView.vue
        ├── stores/auth.js
        ├── composables/useApi.js
        └── router/index.js
```

---

## Setup Instructions

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8+

---

### Backend Setup

```bash
# 1. Navigate to backend
cd pharmacy-stock/backend

# 2. Install PHP dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Configure your database in .env
# DB_DATABASE=pharmacy_stock
# DB_USERNAME=root
# DB_PASSWORD=your_password

# 6. Run migrations
php artisan migrate

# 7. Seed demo data
php artisan db:seed

# 8. Start the development server
php artisan serve
# → Running on http://localhost:8000
```

---

### Frontend Setup

```bash
# 1. Navigate to frontend
cd pharmacy-stock/frontend

# 2. Install dependencies
npm install

# 3. Start development server
npm run dev
# → Running on http://localhost:5173
```

---

## Default Login Credentials

| Role | Email | Password |
|---|---|---|
| Admin | admin@pharmacy.com | password |
| Pharmacist | pharmacist@pharmacy.com | password |

---

## API Endpoints

### Auth
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/auth/login` | Login |
| POST | `/api/auth/register` | Register |
| POST | `/api/auth/logout` | Logout |
| GET | `/api/auth/me` | Current user |

### Medicines
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/medicines` | List (search, filter by category/status) |
| POST | `/api/medicines` | Create |
| GET | `/api/medicines/{id}` | Show |
| PUT | `/api/medicines/{id}` | Update |
| DELETE | `/api/medicines/{id}` | Soft delete |
| POST | `/api/medicines/{id}/adjust-stock` | Adjust stock level |

### Sales
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/sales` | List |
| POST | `/api/sales` | Create (auto-deducts stock) |
| GET | `/api/sales/{id}` | Show |

### Purchase Orders
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/purchase-orders` | List |
| POST | `/api/purchase-orders` | Create |
| GET | `/api/purchase-orders/{id}` | Show |
| POST | `/api/purchase-orders/{id}/receive` | Receive stock |
| PATCH | `/api/purchase-orders/{id}/status` | Update status |

### Other
| Method | Endpoint |
|---|---|
| GET/POST/PUT/DELETE | `/api/categories` |
| GET/POST/PUT/DELETE | `/api/suppliers` |
| GET | `/api/dashboard` |
| GET | `/api/stock-movements` |

---

## Database Schema

```
users               → id, name, email, password, role
categories          → id, name, description
suppliers           → id, name, contact_person, phone, email, address, is_active
medicines           → id, name, generic_name, barcode, sku, category_id, supplier_id,
                      dosage_form, strength, unit, purchase_price, selling_price,
                      stock_quantity, reorder_level, expiry_date, requires_prescription
stock_movements     → id, medicine_id, type, quantity, quantity_before, quantity_after,
                      unit_price, reference_number, batch_number, user_id
purchase_orders     → id, order_number, supplier_id, status, order_date, total_amount, user_id
purchase_order_items→ id, purchase_order_id, medicine_id, quantity_ordered, quantity_received, unit_price
sales               → id, invoice_number, customer_name, subtotal, discount, total,
                      payment_method, prescription_required, user_id
sale_items          → id, sale_id, medicine_id, quantity, unit_price, total_price
```

---

## Production Build

```bash
# Frontend
cd frontend
npm run build
# Output in frontend/dist/

# Configure nginx to serve dist/ and proxy /api to Laravel
```

---
