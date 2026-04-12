# Team Coding Guidelines 🛠️

These guidelines ensure that our 4-person team writes consistent, secure, and maintainable code. Following these now will make the transition to Laravel much easier later.

## 1. Security First 🔒
- **Database:** NEVER use `mysqli_query()` or variable interpolation in strings. Use **PDO Prepared Statements** for every single query to prevent SQL Injection.
- **Passwords:** NEVER store plain text. Use `password_hash($pass, PASSWORD_BCRYPT)` on registration and `password_verify()` on login.
- **XSS Prevention:** Always wrap echo statements in `htmlspecialchars()` when displaying user-inputted data (e.g., `<?= htmlspecialchars($user['name']) ?>`).
- **File Uploads:** Validate file types (JPG, PNG only) and rename files to unique IDs (using `uniqid()`) before saving them to `/public/uploads`.

## 2. Naming Conventions 📛
- **Variables & Functions:** `camelCase` (e.g., `$orderItems`, `getUserByEmail()`).
- **Classes:** `PascalCase` (e.g., `ProductController`, `Database`).
- **Database Columns:** `snake_case` (e.g., `created_at`, `total_price`).
- **File Names:** Match the class name (e.g., `UserController.php`).

## 3. Architecture Rules (Mini-MVC) 🏗️
- **Models:** Only place SQL queries here. No HTML.
- **Controllers:** Handle logic, validation, and session management. No SQL.
- **Views:** Only HTML and simple PHP `foreach/if` loops. No SQL.
- **Routing:** All requests should go through `public/index.php`. Use a `GET` parameter like `?action=products` to route requests to the correct controller.

## 4. Git Workflow 🌿
- **Main Branch:** Always stable. No one codes directly on `main`.
- **Feature Branches:** Create a branch for your task (e.g., `feature/auth`, `feature/products`).
- **Pull Requests:** One other person must review your code before it merges into `main`.
- **Commits:** Write clear messages (e.g., `feat: implement product image upload validation`).

## 5. UI & UX Standards 🎨
- **Framework:** Use [Bootstrap 5](https://getbootstrap.com/) for layout and components to save time.
- **Consistency:** Use the same Navbar and Sidebar across all pages (include them as `header.php` and `footer.php`).
- **Feedback:** Always show success/error messages (e.g., "Product added successfully!") using `$_SESSION['flash_message']`.
- **AJAX:** Use JavaScript `fetch()` for the shopping cart on the Home page to avoid page refreshes when adding items.
