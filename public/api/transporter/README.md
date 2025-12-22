# Transporter API Documentation

This document describes the Transporter API endpoints for the AgroHaat platform.

## Authentication

All transporter API endpoints require:
- Valid transporter session (user must be logged in as TRANSPORTER)
- Completed transporter profile (vehicle info must be set up)
- Session cookie must be present in request

**Authentication Error Response:**
```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "Transporter authentication required"
}
```

**Profile Required Error:**
```json
{
  "success": false,
  "error": "Profile Required",
  "message": "Please complete your transporter profile before using the API"
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

### Job Management

#### GET /api/transporter/jobs.php
List available delivery jobs with pagination and filters.

**Query Parameters:**
- `limit` (optional): Number of results per page (default: 50, max: 100)
- `offset` (optional): Number of results to skip (default: 0)
- `pickup_district` (optional): Filter by pickup district
- `dropoff_district` (optional): Filter by delivery district
- `status` (optional): Filter by job status (default: OPEN)
- `search` (optional): Search in locations and names
- `id` (optional): Get specific job by ID

**Example Request:**
```
GET /api/transporter/jobs.php?limit=20&pickup_district=Dhaka&status=OPEN
```

**Example Response:**
```json
{
  "success": true,
  "message": "Jobs retrieved successfully",
  "data": {
    "jobs": [
      {
        "job_id": 1,
        "order_id": 10,
        "pickup_location": "Farmer Address",
        "dropoff_location": "Buyer Address",
        "status": "OPEN",
        "total_amount": 5000.00,
        "buyer_name": "John Doe",
        "buyer_district": "Dhaka",
        "total_products": 3,
        "total_weight": 50.5,
        "bid_count": 2,
        "lowest_bid": 300.00
      }
    ],
    "pagination": {
      "total": 25,
      "limit": 20,
      "offset": 0,
      "has_more": true
    }
  }
}
```

---

### Bid Management

#### POST /api/transporter/bids/create.php
Create a bid on a delivery job.

**Request Body:**
```json
{
  "job_id": 123,
  "bid_amount": 500.00,
  "message": "Can deliver within 24 hours"  // Optional
}
```

**Example Response:**
```json
{
  "success": true,
  "message": "Bid created successfully",
  "data": {
    "bid_id": 45,
    "job_id": 123,
    "bid_amount": 500.00,
    "status": "PENDING"
  }
}
```

**Error Cases:**
- Job not found (404)
- Job not available for bidding (400)
- Already placed a bid (400)
- Weight exceeds vehicle capacity (400)

---

#### POST /api/transporter/bids/accept.php
Accept a bid (Buyer endpoint - requires BUYER role).

**Request Body:**
```json
{
  "bid_id": 45
}
```

**Example Response:**
```json
{
  "success": true,
  "message": "Bid accepted successfully",
  "data": {
    "bid_id": 45,
    "job_id": 123,
    "transporter_id": 5,
    "status": "ACCEPTED"
  }
}
```

**Note:** This endpoint requires BUYER authentication, not TRANSPORTER.

---

#### GET /api/transporter/bids.php
List all bids placed by the transporter.

**Query Parameters:**
- `status` (optional): Filter by bid status (PENDING, ACCEPTED, REJECTED, WITHDRAWN)
- `id` (optional): Get specific bid by ID

**Example Request:**
```
GET /api/transporter/bids.php?status=PENDING
```

**Example Response:**
```json
{
  "success": true,
  "message": "Bids retrieved successfully",
  "data": {
    "bids": [
      {
        "bid_id": 45,
        "job_id": 123,
        "bid_amount": 500.00,
        "message": "Can deliver within 24 hours",
        "status": "PENDING",
        "created_at": "2025-01-15 10:00:00",
        "job_status": "BIDDING",
        "buyer_name": "John Doe",
        "total_products": 3,
        "total_weight": 50.5
      }
    ],
    "total": 5
  }
}
```

---

### Delivery Management

#### POST /api/transporter/deliveries/update.php
Update delivery status.

**Request Body:**
```json
{
  "job_id": 123,
  "status": "PICKED_UP",
  "notes": "Optional delivery notes"  // Optional, only for DELIVERED status
}
```

**Status Options:**
- `ASSIGNED` - Bid accepted, job assigned
- `PICKED_UP` - Products collected from farmer
- `IN_TRANSIT` - On the way to buyer
- `DELIVERED` - Successfully delivered

**Example Response:**
```json
{
  "success": true,
  "message": "Delivery status updated successfully",
  "data": {
    "job_id": 123,
    "status": "PICKED_UP",
    "order_id": 10
  }
}
```

**Status Progression:**
- Must follow order: ASSIGNED → PICKED_UP → IN_TRANSIT → DELIVERED
- Cannot move backwards or skip steps

---

#### GET /api/transporter/deliveries.php
List all deliveries assigned to the transporter.

**Query Parameters:**
- `status` (optional): Filter by delivery status
- `id` (optional): Get specific delivery by job_id

**Example Request:**
```
GET /api/transporter/deliveries.php?status=IN_TRANSIT
```

**Example Response:**
```json
{
  "success": true,
  "message": "Deliveries retrieved successfully",
  "data": {
    "deliveries": [
      {
        "job_id": 123,
        "order_id": 10,
        "pickup_location": "Farmer Address",
        "dropoff_location": "Buyer Address",
        "status": "IN_TRANSIT",
        "buyer_name": "John Doe",
        "bid_amount": 500.00,
        "total_products": 3,
        "total_weight": 50.5,
        "pickup_time": "2025-01-15 14:00:00",
        "delivery_time": null
      }
    ],
    "total": 3
  }
}
```

---

## Error Codes

- `400` - Bad Request (invalid input, missing required fields, invalid status)
- `401` - Unauthorized (not logged in as transporter)
- `403` - Forbidden (profile not completed, not assigned to job)
- `404` - Not Found (resource doesn't exist)
- `405` - Method Not Allowed (wrong HTTP method)
- `500` - Internal Server Error

## Testing

**Example cURL Request:**
```bash
# List available jobs (requires transporter session cookie)
curl -X GET "http://localhost/Agrohaat/public/api/transporter/jobs.php?limit=10" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json"

# Create a bid (requires transporter session cookie)
curl -X POST "http://localhost/Agrohaat/public/api/transporter/bids/create.php" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{"job_id": 123, "bid_amount": 500.00, "message": "Can deliver today"}'

# Update delivery status (requires transporter session cookie)
curl -X POST "http://localhost/Agrohaat/public/api/transporter/deliveries/update.php" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -H "Content-Type: application/json" \
  -d '{"job_id": 123, "status": "PICKED_UP"}'
```

---

**Last Updated:** 2025-01-27

