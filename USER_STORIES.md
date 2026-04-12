# User Stories & Acceptance Criteria 📑

This document details exactly what each part of the Cafeteria system must do, as shown in the wireframes.

---

## 👤 Actor: General User

### Story 1: Authentication (Page 1)
**"As a user/admin, I want to log in so that I can access my account."**
- [ ] **AC 1.1:** Show a login form with Email and Password fields.
- [ ] **AC 1.2:** Validate credentials against the `users` table.
- [ ] **AC 1.3:** Redirect to Home (User) or Products (Admin) upon success.
- [ ] **AC 1.4:** Show "Forget Password?" link (redirects to a reset page).

### Story 2: Home Page & Ordering (Page 2)
**"As a user, I want to browse products and place an order."**
- [ ] **AC 2.1:** Display a search bar to filter products by name.
- [ ] **AC 2.2:** Show "Latest Order" icons for the last 3 items ordered by the user.
- [ ] **AC 2.3:** List all available products with their images and prices.
- [ ] **AC 2.4:** **Shopping Cart:** Adding a product should update the cart on the left.
- [ ] **AC 2.5:** Cart should allow updating quantities (`+` or `-`) and deleting items (`X`).
- [ ] **AC 2.6:** Cart must have a "Notes" textarea and a "Room" selection dropdown.
- [ ] **AC 2.7:** "Confirm" button should save the order and clear the cart.

### Story 3: Order History (Page 4)
**"As a user, I want to view my past orders and their status."**
- [ ] **AC 3.1:** Display a list of orders with Date, Status, and Total Amount.
- [ ] **AC 3.2:** Provide "Date From" and "Date To" filters.
- [ ] **AC 3.3:** **Expandable Rows:** Clicking `+` should show the items (icons and quantities) for that specific order.
- [ ] **AC 3.4:** Show a "CANCEL" button *only* if the status is "Processing".

---

## 🔑 Actor: Admin

### Story 4: Manual Ordering (Page 3)
**"As an admin, I want to place an order for a specific user."**
- [ ] **AC 4.1:** Same layout as User Home, but with a "Add to user" dropdown at the top.
- [ ] **AC 4.2:** Admin selects a user, then adds items to the cart and confirms.

### Story 5: Product Management (Page 5, 8)
**"As an admin, I want to manage the cafeteria's product catalog."**
- [ ] **AC 5.1:** List all products with Price, Image, and Actions (Available/Edit/Delete).
- [ ] **AC 5.2:** **Add Product Page:** Form for Name, Price, Category (dropdown), and Image upload.
- [ ] **AC 5.3:** "Add Category" link should open a way to create a new category (e.g., "Hot Drinks").
- [ ] **AC 5.4:** Soft-delete products when "Delete" is clicked (do not remove from DB).

### Story 6: User Management (Page 6, 7)
**"As an admin, I want to manage system users."**
- [ ] **AC 6.1:** List all users with Name, Room, Image, Ext, and Actions (Edit/Delete).
- [ ] **AC 6.2:** **Add User Page:** Form with Name, Email, Password, Confirm Password, Room No (dropdown), Ext, and Profile picture upload.
- [ ] **AC 6.3:** Validate that the email is unique in the database.

### Story 7: Reports/Checks (Page 9)
**"As an admin, I want to see how much each user has spent."**
- [ ] **AC 7.1:** Filter reports by "Date From", "Date To", and "User".
- [ ] **AC 7.2:** **Triple-Level Accordion:**
    - Level 1: User name and their total amount spent in that period.
    - Level 2: Click User `+` to see all their individual orders (Date and Amount).
    - Level 3: Click Order `+` to see the actual products and quantities in that order.

### Story 8: Live Orders Queue (Page 10)
**"As an admin, I want to see pending orders and deliver them."**
- [ ] **AC 8.1:** Display all "Processing" orders across the system.
- [ ] **AC 8.2:** Show Order Date, User Name, Room (from `rooms` table), Ext, and "Deliver" action.
- [ ] **AC 8.3:** Clicking "Deliver" changes the order status to "Done" (or "Delivered") and removes it from this view.
- [ ] **AC 8.4:** Display the "Total Price" for each order card as shown in the wireframe.
- [ ] **AC 8.5:** Display item icons and quantities directly inside each order card.
