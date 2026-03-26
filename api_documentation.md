# 💎 Jewellery Shop Architecture - Complete Technical Documentation

Welcome to the full technical reference for the Jewellery Database API. This documentation covers every endpoint, exact JSON payloads, parameter types, authentication routing, and exact error responses.

---

## 🚀 1. Getting Started

### Starting the Development Router
The built-in PHP development server (`php -S`) ignores Apache `.htaccess` rules. To automatically route requests through your `index.php` API router (which handles CORS headers globally), start your server exactly like this from the project root:

```bash
php -S localhost:8000 index.php
```

With the router running, your base URL is: `http://localhost:8000/api`

*(Note: If you are NOT using the router, you must append `.php` to all endpoints, e.g., `http://localhost:8000/api/health.php`)*

---

## 🔑 2. Authentication Flow

Protected endpoints require a Bearer token. Upon successful login, you receive a generated string token.
You must attach it to all protected HTTP requests using the `Authorization` header.

**Format Pattern**: `<random_token>-<table_id>-<role_type>`
**User Example**: `Authorization: Bearer mytokenstring123-5-user`
**Admin Example**: `Authorization: Bearer adminsecurekey-1-admin`

---

## 🏥 3. Server Health Monitoring (`/api/health`)

Checks database connectivity and API runtime health.

#### `GET /api/health`
- **Requires Auth**: ❌ No
- **Query Params**: None
- **cURL Example**:
  ```bash
  curl -X GET http://localhost:8000/api/health
  ```
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "message": "API health status.",
    "services": { "database": "connected" },
    "server": {
      "php_version": "8.x.x",
      "memory_usage": "2.15 MB"
    },
    "timestamp": "2026-03-21 12:00:00"
  }
  ```
- **Error Response (500 Internal Server Error)**: Returned if the `config/database.php` connection to MySQL fails entirely.

---

## 👥 4. Account Management (`/api/auth`)

### 🔹 Register Customer
Creates a brand new customer in the `users` table.

#### `POST /api/auth/register`
- **Requires Auth**: ❌ No
- **Headers**: `Content-Type: application/json`
- **Body Parameters**:
  - `name` (String, Required): Customer full name
  - `email` (String, Required): Unique email address
  - `password` (String, Required): Raw password (automatically hashed via BCRYPT)
  - `phone` (String, Optional): Phone contact
  - `address` (String, Optional): Physical delivery location
- **Success Response (201 Created)**:
  ```json
  {
    "status": "success",
    "message": "User registered successfully",
    "user_id": 12
  }
  ```
- **Error Responses**:
  - `400 Bad Request`: "Incomplete data" or "Email may already exist."

### 🔹 Customer Login
Authenticates standard users.

#### `POST /api/auth/login`
- **Requires Auth**: ❌ No
- **Body Parameters**:
  - `email` (String, Required)
  - `password` (String, Required)
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "message": "Successful login.",
    "token": "tokenstring456-12-user",
    "role": "user",
    "user_id": 12
  }
  ```
- **Error Responses**:
  - `404 Not Found`: "User not found."
  - `401 Unauthorized`: "Incorrect password."

### 🔹 Admin Login
Authenticates staff against the dedicated `admin` table.

#### `POST /api/auth/admin_login`
- **Requires Auth**: ❌ No
- **Body Parameters**:
  - `username` (String, Required)
  - `password` (String, Required)
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "message": "Successful admin login.",
    "token": "adminsecret-1-admin",
    "role": "admin",
    "admin_id": 1
  }
  ```

### 🔹 Logout
#### `POST /api/auth/logout`
- **Requires Auth**: ❌ No
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "message": "Logged out successfully."
  }
  ```

---

## 💍 5. Customer Catalog (`/api/products`)

Public-facing API to view the jewelry catalog.

### 🔹 List Library
#### `GET /api/products`
- **Requires Auth**: ❌ No
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": [
      {
        "id": 1,
        "name": "Diamond Ring",
        "category": "Rings",
        "price": 999.99,
        "description": "A beautiful engagement ring",
        "image": "/uploads/ring1.jpg"
      }
    ]
  }
  ```

### 🔹 Detail Lookup
#### `GET /api/products/{id}`
- **Requires Auth**: ❌ No
- **Success Response (200 OK)**: Single product object matching the array above.
- **Error Response**: `404 Not Found`

---

## 🛒 6. Shopping Cart (`/api/cart`)

Manages the active sessions via the `cart` associative table.
**ALL Endpoints Require**: `Authorization: Bearer <token>-<user_id>-user`

### 🔹 View Subtotal
#### `GET /api/cart`
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": [
      {
        "cart_item_id": 5,
        "product_id": 1,
        "product_name": "Diamond Ring",
        "price": 999.99,
        "quantity": 2,
        "total_item_price": 1999.98
      }
    ],
    "cart_subtotal": 1999.98
  }
  ```

### 🔹 Add Item
#### `POST /api/cart`
- **Body Parameters**:
  - `product_id` (Integer, Required)
  - `quantity` (Integer, Required)
- **Logic**: Automatically handles combining quantities if the product already exists inside the cart. 
- **Success Response (201 or 200)**: `"cart_item_id": 5`

### 🔹 Update Item Amount
#### `PUT /api/cart/{cart_item_id}`
- **Body Parameters**: `quantity` (Integer, Required)
- **Success Response (200 OK)**: `"message": "Cart item updated successfully"`

### 🔹 Delete Item
#### `DELETE /api/cart/{cart_item_id}`
- **Success Response (200 OK)**: `"message": "Item removed from cart"`

---

## 📦 7. Orders & Payments (`/api/orders`)

Converts the shopping cart into permanent history.
**ALL Endpoints Require**: `Authorization: Bearer <token>-<user_id>-user`

### 🔹 Checkout Request
#### `POST /api/orders/checkout`
- **Description**: Highly complex transaction. Moves all `cart` items for this user to `orders`, generates line items in `order_items`, creates a pending entry in `payments`, and flushes the standard `cart`.
- **Body Parameters**:
  - `address` (String, Required): Final delivery destination.
  - `payment_method` (String, Required): E.g., "Credit Card", "PayPal".
- **Success Response (201 Created)**: `"order_id": 102`

### 🔹 Order History List
#### `GET /api/orders`
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": [
      {
        "order_id": 102,
        "total_amount": 1999.98,
        "order_date": "2026-03-21 12:05:00",
        "payment_status": "Pending"
      }
    ]
  }
  ```

### 🔹 Order Receipt
#### `GET /api/orders/{order_id}`
- **Success Response (200 OK)**: Deep-fetch showing exact breakdown of line items (`order_items`), `address`, and full `payment_method` mapping.

---

## 🛡️ 8. Admin Control Panel (`/api/admin`)

Privileged control actions.
**ALL Endpoints Require**: `Authorization: Bearer <token>-<user_id>-admin`

### 🔹 Statistics Dashboard
#### `GET /api/admin/dashboard`
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": {
      "total_products": 25,
      "total_users": 150,
      "total_orders": 300,
      "total_revenue": 15000.50,
      "recent_orders": [
        {
          "id": 300,
          "total_amount": 150.00,
          "order_date": "2026-03-21 12:00:00",
          "payment_status": "Paid"
        }
      ]
    }
  }
  ```

### 🔹 Create Product
#### `POST /api/admin/products`
- **Content-Type**: `multipart/form-data` (Not JSON! Must use form-data to pass physical images).
- **Body Keys**:
  - `name`, `category`, `price`, `description`
  - `image` (Type: File Upload)

### 🔹 Edit Content
#### `PUT /api/admin/products/{id}`
- **Body Parameters**: Any combination of `name`, `category`, `price`, `description`, or `image`.
- **Success Response (200 OK)**: `"message": "Product updated successfully"`

### 🔹 Delete Product
#### `DELETE /api/admin/products/{id}`
- **Success Response (200 OK)**: `"message": "Product deleted successfully"`

### 🔹 List System Orders
#### `GET /api/admin/orders`
- **Overview**: Returns standard history combining `orders`, `users`, and `payments` tables.

### 🔹 Order Receipt (Admin)
#### `GET /api/admin/orders/{id}`
- **Success Response (200 OK)**: Returns full details of the order, including customer details (`customer_name`, `customer_email`, `customer_phone`), `address`, `payment_status`, `payment_method`, `total_amount`, and all line `items`.

### 🔹 Modify Order Billing Status
#### `PUT /api/admin/orders/{id}/payment_status`
- **Body Parameters**: `payment_status` (String, Required). E.g., "Paid", "Failed", "Refunded".
- **Success Response (200 OK)**: `"message": "Payment status updated successfully"`
