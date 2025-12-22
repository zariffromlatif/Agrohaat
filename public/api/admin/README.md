# Admin API Documentation

This document describes the Admin API endpoints for the AgroHaat platform.

## Authentication

All admin API endpoints require:
- Valid admin session (user must be logged in as ADMIN)
- Session cookie must be present in request

**Authentication Error Response:**
```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "Admin authentication required"
}
```

## Response Format

All endpoints return JSON responses with the following structure:

**Success Response:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Error message",
  "errors": { ... }  // Optional, for validation errors
}
```

## Endpoints

### User Management

#### GET /api/admin/users.php
List all users with pagination.

**Query Parameters:**
- `limit` (optional): Number of results per page (default: 50, max: 100)
- `offset` (optional): Number of results to skip (default: 0)
- `role` (optional): Filter by role (FARMER, BUYER, TRANSPORTER, ADMIN)
- `id` (optional): Get specific user by ID

**Example Request:**
```
GET /api/admin/users.php?limit=20&offset=0&role=FARMER
```

**Example Response:**
```json
{
  "success": true,
  "message": "Users retrieved successfully",
  "data": {
    "users": [
      {
        "user_id": 1,
        "full_name": "John Doe",
        "email": "john@example.com",
        "phone_number": "+8801234567890",
        "role": "FARMER",
        "district": "Dhaka",
        "upazila": "Dhanmondi",
        "is_verified": 1,
        "is_deleted": 0,
        "created_at": "2025-01-01 10:00:00"
      }
    ],
    "pagination": {
      "total": 100,
      "limit": 20,
      "offset": 0,
      "has_more": true
    }
  }
}
```

#### POST /api/admin/users/approve.php
Approve a user account.

**Request Body:**
```json
{
  "user_id": 123
}
```

**Example Response:**
```json
{
  "success": true,
  "message": "User approved successfully",
  "data": {
    "user_id": 123
  }
}
```

#### POST /api/admin/users/suspend.php
Suspend or unsuspend a user account.

**Request Body:**
```json
{
  "user_id": 123,
  "action": "suspend"  // or "unsuspend"
}
```

**Example Response:**
```json
{
  "success": true,
  "message": "User suspended successfully",
  "data": {
    "user_id": 123,
    "action": "suspend"
  }
}
```

---

### Product Management

#### GET /api/admin/products.php
List all products with pagination.

**Query Parameters:**
- `limit` (optional): Number of results per page (default: 50, max: 100)
- `offset` (optional): Number of results to skip (default: 0)
- `status` (optional): Filter by status (ACTIVE, INACTIVE, SOLD_OUT)
- `id` (optional): Get specific product by ID

**Example Request:**
```
GET /api/admin/products.php?limit=20&status=ACTIVE
```

**Example Response:**
```json
{
  "success": true,
  "message": "Products retrieved successfully",
  "data": {
    "products": [
      {
        "product_id": 1,
        "title": "Organic Rice",
        "farmer_name": "John Doe",
        "price_per_unit": 50.00,
        "quantity_available": 100.00,
        "unit": "kg",
        "quality_grade": "A",
        "status": "ACTIVE"
      }
    ],
    "pagination": {
      "total": 50,
      "limit": 20,
      "offset": 0,
      "has_more": true
    }
  }
}
```

#### POST /api/admin/products/delete.php
Delete a product (soft delete).

**Request Body:**
```json
{
  "product_id": 123
}
```

**Example Response:**
```json
{
  "success": true,
  "message": "Product deleted successfully",
  "data": {
    "product_id": 123
  }
}
```

---

### Dispute Management

#### GET /api/admin/disputes.php
List all disputes.

**Query Parameters:**
- `status` (optional): Filter by status (OPEN, RESOLVED, REFUNDED, REJECTED)
- `id` (optional): Get specific dispute by ID

**Example Request:**
```
GET /api/admin/disputes.php?status=OPEN
```

**Example Response:**
```json
{
  "success": true,
  "message": "Disputes retrieved successfully",
  "data": {
    "disputes": [
      {
        "dispute_id": 1,
        "order_id": 10,
        "complainant_name": "Buyer Name",
        "description": "Product quality issue",
        "status": "OPEN",
        "created_at": "2025-01-15 10:00:00"
      }
    ],
    "total": 5
  }
}
```

#### POST /api/admin/disputes/resolve.php
Resolve a dispute.

**Request Body:**
```json
{
  "dispute_id": 123,
  "resolution": "RESOLVED"  // Options: "RESOLVED", "REFUNDED", "REJECTED"
}
```

**Example Response:**
```json
{
  "success": true,
  "message": "Dispute resolved successfully",
  "data": {
    "dispute_id": 123,
    "resolution": "RESOLVED"
  }
}
```

---

### Payment Management

#### GET /api/admin/payments.php
List pending payments.

**Query Parameters:**
- `status` (optional): Filter by status (PENDING, PROCESSING, COMPLETED, FAILED)
- `id` (optional): Get specific payment by ID

**Example Request:**
```
GET /api/admin/payments.php?status=PENDING
```

**Example Response:**
```json
{
  "success": true,
  "message": "Payments retrieved successfully",
  "data": {
    "payments": [
      {
        "payment_id": 1,
        "order_id": 10,
        "buyer_name": "Buyer Name",
        "method_name": "bKash",
        "amount": 1000.00,
        "status": "PENDING",
        "transaction_id": "TXN123456",
        "created_at": "2025-01-15 10:00:00"
      }
    ],
    "total": 3
  }
}
```

#### POST /api/admin/payments/approve.php
Approve or reject a payment.

**Request Body:**
```json
{
  "payment_id": 123,
  "action": "approve",  // or "reject"
  "notes": "Payment verified successfully"  // Optional for approve, required for reject
}
```

**Example Response:**
```json
{
  "success": true,
  "message": "Payment approved successfully",
  "data": {
    "payment_id": 123,
    "action": "approved"
  }
}
```

---

### Review Management

#### GET /api/admin/reviews.php
List all reviews.

**Query Parameters:**
- `rating` (optional): Filter by rating (1-5)
- `id` (optional): Get specific review by ID

**Example Request:**
```
GET /api/admin/reviews.php?rating=5
```

**Example Response:**
```json
{
  "success": true,
  "message": "Reviews retrieved successfully",
  "data": {
    "reviews": [
      {
        "review_id": 1,
        "reviewer": "Buyer Name",
        "reviewee": "Farmer Name",
        "rating": 5,
        "comment": "Excellent product quality",
        "order_id": 10,
        "created_at": "2025-01-15 10:00:00"
      }
    ],
    "total": 20
  }
}
```

#### POST /api/admin/reviews/delete.php
Delete a review.

**Request Body:**
```json
{
  "review_id": 123
}
```

**Example Response:**
```json
{
  "success": true,
  "message": "Review deleted successfully",
  "data": {
    "review_id": 123
  }
}
```

---

## Error Codes

- `400` - Bad Request (invalid input, missing required fields)
- `401` - Unauthorized (not logged in as admin)
- `403` - Forbidden (e.g., trying to suspend admin account)
- `404` - Not Found (resource doesn't exist)
- `405` - Method Not Allowed (wrong HTTP method)
- `500` - Internal Server Error

## Testing

You can test the API endpoints using tools like:
- Postman
- cURL
- JavaScript fetch API
- PHP cURL

**Example cURL Request:**
```bash
# List users (requires admin session cookie)
curl -X GET "http://localhost/Agrohaat/public/api/admin/users.php?limit=10" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json"

# Approve user (requires admin session cookie)
curl -X POST "http://localhost/Agrohaat/public/api/admin/users/approve.php" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{"user_id": 123}'
```

---

**Last Updated:** 2025-01-27

