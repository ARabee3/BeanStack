# Cafeteria Management System - Project Plan ☕️

This project is a Native PHP implementation of a Cafeteria Ordering System, designed with a "Mini-MVC" architecture to bridge the gap between procedural PHP and the Laravel framework.

## 🎯 Project Goals
- Master **Native PHP 8.x** fundamentals.
- Understand **MVC (Model-View-Controller)** architecture.
- Implement secure **CRUD** operations using **PDO** and Prepared Statements.
- Manage team collaboration via Git.

---

## 🏗 Architecture & Tech Stack
- **Backend:** Native PHP 8.x (No frameworks).
- **Database:** MySQL / MariaDB using **PDO**.
- **Frontend:** HTML5, CSS3 (Bootstrap/Tailwind recommended), Vanilla JavaScript/AJAX.
- **File Structure (Mini-MVC):**
  ```text
  /cafeteria
  ├── config/             # DB Connection (Database.php)
  ├── app/
  │   ├── Models/         # Database logic (User.php, Product.php, Order.php)
  │   ├── Controllers/    # Business logic (AuthController.php, OrderController.php)
  │   └── Helpers/        # Validation, Session helpers, File uploader
  ├── views/              # UI Templates (login.php, dashboard.php, layout.php)
  ├── public/             # Entry point
  │   ├── index.php       # Front Controller (Routing)
  │   ├── assets/         # CSS, JS, Images
  │   └── uploads/        # User/Product uploaded pictures
  └── .htaccess           # For clean URLs (Optional)
  ```

---

## 👥 Team Roles & Task Distribution

### 👨‍💻 Developer 1: Foundation & Auth (Team Lead)
*Core Responsibility: Setting the standard and managing access.*
- [ ] Initialize Git repository and project structure.
- [ ] Create `Database.php` using PDO (Singleton pattern recommended).
- [ ] **Authentication System:** Login, Logout, and "Remember Me" (using Cookies/Sessions).
- [ ] **Middleware:** Logic to check if user is logged in and if they are `admin` or `user`.
- [ ] Master Layout: Create the header/sidebar navigation that everyone will use.

### 👨‍💻 Developer 2: Admin - User & Product Management
*Core Responsibility: Resource CRUD and File Handling.*
- [ ] **User CRUD:** Add/Edit/Delete/List Users (Name, Email, Room, Ext, Profile Pic).
- [ ] **Product CRUD:** Add/Edit/Delete/List Products (Name, Price, Category, Product Pic).
- [ ] **Category Management:** Simple CRUD for product categories (Hot Drinks, Cold Drinks, etc.).
- [ ] **File Uploader Helper:** A reusable class to handle image validation and secure uploads.

### 👨‍💻 Developer 3: Ordering System & Cart
*Core Responsibility: The "Customer Experience" and AJAX.*
- [ ] **User Home Page:** Display available products with search functionality.
- [ ] **Shopping Cart:** Manage cart state (using `$_SESSION` or LocalStorage).
- [ ] **Checkout Logic:** Validate order (is product available?), save to `orders` and `order_items`.
- [ ] **Latest Order:** Display the last order on the user's home page for quick re-ordering.
- [ ] **Manual Order (Admin):** Adapt the user ordering page for admins to order for others.

### 👨‍💻 Developer 4: Tracking, Status & Reporting
*Core Responsibility: Data aggregation and complex SQL.*
- [ ] **Admin Orders Page:** List of all pending orders with a "Deliver" button (Update status).
- [ ] **User "My Orders":** History of personal orders with date filtering and "Cancel" button.
- [ ] **Admin "Checks" (Reports):** 
    - List users and their total spent in a date range.
    - Expandable rows to see individual orders and items.
- [ ] **Pagination:** Implement pagination for all tables (Users, Products, Orders).

---

## 🗄 Database Schema (SQL - Robust Standalone)

```sql
-- 1. Rooms Table (Populates the Room No. dropdowns)
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(50) UNIQUE NOT NULL
);

-- 2. Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Will use password_hash()
    room_id INT,
    ext VARCHAR(50),
    profile_pic VARCHAR(255),
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
);

-- 3. Categories Table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

-- 4. Products Table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    category_id INT,
    image VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    -- 'is_deleted' allows hiding products from the menu without breaking old orders
    is_deleted BOOLEAN DEFAULT FALSE, 
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- 5. Orders Table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    status ENUM('processing', 'out_for_delivery', 'done') DEFAULT 'processing',
    total_price DECIMAL(10, 2) NOT NULL,
    notes TEXT,
    room_id INT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- 6. Order Items
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price_at_purchase DECIMAL(10, 2) NOT NULL, -- Critical for financial history
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

### Key Logic for the Team:
1.  **Deleting a Product:** Instead of `DELETE FROM products`, use `UPDATE products SET is_deleted = 1`. Your "Home Page" query should only fetch products where `is_deleted = 0`.
2.  **Snapshotting:** When saving an order, grab the current price from the `products` table and save it into `order_items.price_at_purchase`. This is standard for all E-commerce/POS systems.
3.  **Room Selection:** Fetching from the `rooms` table makes the "Room No." dropdown dynamic and prevents typos.

---

## 🚀 Getting Started Checklist
1. [ ] **Step 1:** Create a shared database in phpMyAdmin/MySQL Workbench.
2. [ ] **Step 2:** Decide on a CSS framework (Bootstrap is fastest for this layout).
3. [ ] **Step 3:** Setup the Folder Structure as defined above.
4. [ ] **Step 4:** Code the `Database.php` and `index.php` (Router).
5. [ ] **Step 5:** Start your assigned features!

---
*Note: Always use password_hash() for passwords and PDO prepared statements for all queries!*
