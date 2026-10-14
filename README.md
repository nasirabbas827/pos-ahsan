# POS System

pos-ahsan is a point-of-sale application for products and sales.

![PHP](https://img.shields.io/badge/PHP-2563EB?style=flat-square) ![MySQL](https://img.shields.io/badge/MySQL-0F172A?style=flat-square)

## Overview

The data model covers `admins`, `cart`, `cashiers`, `categories`, `products`, `returns`, `sales`, `sale_items`.

## Application areas

| Area | Pages in the repository |
| --- | --- |
| Admin | [`admin/add_cashier.php`](admin/add_cashier.php), [`admin/add_product.php`](admin/add_product.php), [`admin/admin_category.php`](admin/admin_category.php), [`admin/admin_home.php`](admin/admin_home.php), [`admin/admin_login.php`](admin/admin_login.php) |
| Cashier | [`cashier/home.php`](cashier/home.php), [`cashier/process_sale.php`](cashier/process_sale.php), [`cashier/reports.php`](cashier/reports.php), [`cashier/returns.php`](cashier/returns.php) |
| Root Pages | [`login.php`](login.php) |

## Getting started

### 1. Get the project

```bash
git clone https://github.com/nasirabbas827/pos-ahsan.git
cd pos-ahsan
```

### 2. Configure PHP and the database

1. Use a local PHP server with MySQL or MariaDB, such as XAMPP.
2. Import [`Database/pos_db.sql`](Database/pos_db.sql) into your local database server.
3. Set your local database connection details in [`admin/config.php`](admin/config.php), [`cashier/config.php`](cashier/config.php), [`config.php`](config.php).
4. Keep the existing asset and upload directory paths intact.

### 3. Start the application

With the database running, start a local development server from the repository root:

```bash
php -S 127.0.0.1:8000
```

Open http://127.0.0.1:8000/index.php. If using Apache/XAMPP instead, place the project in its document root and open the matching localhost folder.

## Repository guide

| Path | Purpose |
| --- | --- |
| [`Database`](Database) | Database scripts |
| [`config.php`](config.php) | Application/database configuration |
| [`css`](css) | Stylesheets |
| [`index.php`](index.php) | Web entry page |

## Development notes

These setup instructions are based on the repository source. The application has not been executed as part of this documentation update; verify dependencies and configuration in your local environment.
