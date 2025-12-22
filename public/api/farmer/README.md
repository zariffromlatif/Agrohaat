# Farmer Product API Documentation

This document describes the Farmer Product CRUD API endpoints for the AgroHaat platform.

## Authentication

All farmer API endpoints require:
- Valid farmer session (user must be logged in as FARMER)
- Session cookie must be present in request

**Authentication Error Response:**
```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "Farmer authentication required"
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

### Product Management

#### GET /api/farmer/products.php
List all products for the authenticated farmer.

**Query Parameters:**
- `id` (optional): Get specific product by ID

**Example Request:**
```
GET /api/farmer/products.php
GET /api/farmer/products.php?id=123
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
        "farmer_id": 5,
        "title": "Organic Rice",
        "description": "Fresh organic rice",
        "quantity_available": 100.00,
        "unit": "kg",
        "price_per_unit": 50.00,
        "quality_grade": "A",
        "trace_id": "TRC123456789",
        "qr_code_url": "https://api.qrserver.com/...",
        "status": "ACTIVE"
      }
    ],
    "total": 1
  }
}
```

---

#### POST /api/farmer/products.php
Create a new product.

**Request Body (JSON):**
```json
{
  "category_id": 1,
  "title": "Organic Rice",
  "description": "Fresh organic rice from local farm",
  "quantity_available": 100.00,
  "unit": "kg",
  "price_per_unit": 50.00,
  "quality_grade": "A",
  "harvest_date": "2025-01-15",
  "batch_number": "BATCH-001"
}
```

**Request Body (Multipart Form Data):**
- All fields as form fields
- `image` (file): Product image (optional, max 5MB, JPEG/PNG/GIF/WebP)

**Required Fields:**
- `category_id` (integer)
- `title` (string)
- `description` (string)
- `quantity_available` (number, > 0)
- `unit` (string)
- `price_per_unit` (number, > 0)
- `quality_grade` (enum: A, B, C, EXPORT_QUALITY)
- `harvest_date` (date: YYYY-MM-DD)
- `batch_number` (string)

**Optional Fields:**
- `image` (file): Product image

**Example Response:**
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "product_id": 123,
    "farmer_id": 5,
    "title": "Organic Rice",
    "trace_id": "TRC123456789",
    "qr_code_url": "https://api.qrserver.com/...",
    "status": "ACTIVE",
    "created_at": "2025-01-27 10:00:00"
  }
}
```

**Error Cases:**
- Missing required fields (400)
- Invalid quality grade (400)
- Invalid numeric values (400)
- Image upload failed (500)

---

#### PUT /api/farmer/products.php
Update an existing product.

**Request Body (JSON):**
```json
{
  "id": 123,
  "title": "Updated Product Title",
  "price_per_unit": 55.00,
  "quantity_available": 150.00
}
```

**Request Body (Multipart Form Data):**
- `id` (required): Product ID
- Any fields to update as form fields
- `image` (file): New product image (optional)

**Required Fields:**
- `id` (integer): Product ID

**Optional Fields (update any combination):**
- `category_id` (integer)
- `title` (string)
- `description` (string)
- `quantity_available` (number, > 0)
- `unit` (string)
- `price_per_unit` (number, > 0)
- `quality_grade` (enum: A, B, C, EXPORT_QUALITY)
- `harvest_date` (date)
- `batch_number` (string)
- `image` (file): New product image

**Example Response:**
```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {
    "product_id": 123,
    "title": "Updated Product Title",
    "price_per_unit": 55.00,
    "quantity_available": 150.00
  }
}
```

**Error Cases:**
- Product ID missing (400)
- Product not found (404)
- Product doesn't belong to farmer (404)
- Invalid field values (400)
- No fields to update (400)

---

#### PATCH /api/farmer/products.php
Partial update of a product (same as PUT).

**Note:** PATCH and PUT work identically in this implementation.

---

#### DELETE /api/farmer/products.php
Delete a product (soft delete).

**Request Body (JSON):**
```json
{
  "id": 123
}
```

**Example Response:**
```json
{
  "success": true,
  "message": "Product deleted successfully"
}
```

**Error Cases:**
- Product ID missing (400)
- Product not found (404)
- Product doesn't belong to farmer (404)

---

## Error Codes

- `400` - Bad Request (invalid input, missing required fields)
- `401` - Unauthorized (not logged in as farmer)
- `404` - Not Found (product doesn't exist or doesn't belong to farmer)
- `405` - Method Not Allowed (wrong HTTP method)
- `500` - Internal Server Error

## Image Upload

**Supported Formats:**
- JPEG/JPG
- PNG
- GIF
- WebP

**Limits:**
- Maximum file size: 5MB
- Files are stored in `public/uploads/product_images/`
- Filename format: `{timestamp}_{original_filename}`

## Product Information

- Products include quality grade, harvest date, and batch number
- All product details are stored in the database
- Product images are uploaded and stored in `public/uploads/product_images/`

## Testing

**Example cURL Requests:**

```bash
# List all products (requires farmer session cookie)
curl -X GET "http://localhost/Agrohaat/public/api/farmer/products.php" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json"

# Create product (JSON)
curl -X POST "http://localhost/Agrohaat/public/api/farmer/products.php" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{
    "category_id": 1,
    "title": "Organic Rice",
    "description": "Fresh organic rice",
    "quantity_available": 100.00,
    "unit": "kg",
    "price_per_unit": 50.00,
    "quality_grade": "A",
    "harvest_date": "2025-01-15",
    "batch_number": "BATCH-001"
  }'

# Create product with image (multipart)
curl -X POST "http://localhost/Agrohaat/public/api/farmer/products.php" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -F "category_id=1" \
  -F "title=Organic Rice" \
  -F "description=Fresh organic rice" \
  -F "quantity_available=100.00" \
  -F "unit=kg" \
  -F "price_per_unit=50.00" \
  -F "quality_grade=A" \
  -F "harvest_date=2025-01-15" \
  -F "batch_number=BATCH-001" \
  -F "image=@/path/to/image.jpg"

# Update product
curl -X PUT "http://localhost/Agrohaat/public/api/farmer/products.php" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{
    "id": 123,
    "title": "Updated Title",
    "price_per_unit": 55.00
  }'

# Delete product
curl -X DELETE "http://localhost/Agrohaat/public/api/farmer/products.php" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{"id": 123}'
```

---

**Last Updated:** 2025-01-27

