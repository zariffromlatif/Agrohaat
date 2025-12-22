# Delivery Workflow Sequence Diagram

This document describes the complete delivery workflow sequence for the AgroHaat platform.

## Sequence Diagram (Mermaid Format)

```mermaid
sequenceDiagram
    participant Buyer
    participant System
    participant Farmer
    participant Transporter
    participant Database

    Note over Buyer,Database: Order Placement & Payment Phase
    Buyer->>System: Place Order
    System->>Database: Create Order (status: PENDING)
    Buyer->>System: Submit Payment
    System->>Database: Update Order (payment_status: PAID)
    System->>Database: Create DeliveryJob (status: OPEN)
    System->>Buyer: Order Confirmed

    Note over Buyer,Database: Job Marketplace Phase
    System->>Transporter: Display Available Jobs
    Transporter->>System: Browse Jobs
    System->>Database: Query DeliveryJobs (status: OPEN)
    Database-->>System: Return Available Jobs
    System-->>Transporter: Show Job List

    Note over Buyer,Database: Bidding Phase
    Transporter->>System: View Job Details
    System->>Database: Get Job Info
    Database-->>System: Job Details
    System-->>Transporter: Display Job Info
    
    Transporter->>System: Submit Bid
    System->>Database: Check Existing Bid
    Database-->>System: No Existing Bid
    System->>Database: Create DeliveryBid (status: PENDING)
    System->>Database: Update DeliveryJob (status: BIDDING)
    System->>Database: Create Notification (Buyer)
    System-->>Transporter: Bid Submitted Successfully
    
    Note over Buyer,Database: Bid Acceptance Phase
    Buyer->>System: View Bids for Order
    System->>Database: Get Bids for Job
    Database-->>System: List of Bids
    System-->>Buyer: Display Bids
    
    Buyer->>System: Accept Bid
    System->>Database: Update Bid (status: ACCEPTED)
    System->>Database: Reject Other Bids (status: REJECTED)
    System->>Database: Update DeliveryJob (status: ASSIGNED)
    System->>Database: Update Order (status: PROCESSING)
    System->>Database: Create Delivery Record (status: ASSIGNED)
    System->>Database: Create Notification (Transporter)
    System-->>Buyer: Bid Accepted
    System-->>Transporter: Notification: Bid Accepted

    Note over Buyer,Database: Delivery Execution Phase
    Transporter->>System: View Assigned Deliveries
    System->>Database: Get Deliveries (transporter_id, status: ASSIGNED)
    Database-->>System: Delivery List
    System-->>Transporter: Show Deliveries
    
    Transporter->>System: Update Status: PICKED_UP
    System->>Database: Update DeliveryJob (status: PICKED_UP)
    System->>Database: Update Delivery (status: PICKED_UP, pickup_time: NOW())
    System->>Database: Create Notification (Buyer)
    System-->>Transporter: Status Updated
    System-->>Buyer: Notification: Order Picked Up
    
    Transporter->>System: Update Status: IN_TRANSIT
    System->>Database: Update DeliveryJob (status: IN_TRANSIT)
    System->>Database: Update Delivery (status: IN_TRANSIT)
    System->>Database: Update Order (status: SHIPPED)
    System->>Database: Create Notification (Buyer)
    System-->>Transporter: Status Updated
    System-->>Buyer: Notification: Order In Transit
    
    Transporter->>System: Update Status: DELIVERED
    System->>Database: Update DeliveryJob (status: DELIVERED)
    System->>Database: Update Delivery (status: DELIVERED, delivery_time: NOW())
    System->>Database: Update Order (status: DELIVERED)
    System->>Database: Create Notification (Buyer)
    System->>Database: Create Notification (Farmer)
    System-->>Transporter: Status Updated
    System-->>Buyer: Notification: Order Delivered
    System-->>Farmer: Notification: Order Delivered
```

## Workflow Steps

### 1. Order Placement & Payment
1. Buyer places order
2. Buyer submits payment
3. System creates delivery job (status: OPEN)
4. Order status: PENDING → PAID → CONFIRMED

### 2. Job Marketplace
1. System displays available jobs to transporters
2. Transporters browse jobs with filters
3. Transporter views job details

### 3. Bidding Phase
1. Transporter submits bid with amount and optional message
2. System validates bid (no duplicate, capacity check)
3. System creates bid (status: PENDING)
4. System updates job status to BIDDING
5. System notifies buyer of new bid

### 4. Bid Acceptance
1. Buyer views all bids for their order
2. Buyer selects and accepts a bid
3. System accepts selected bid (status: ACCEPTED)
4. System rejects all other bids (status: REJECTED)
5. System updates job status to ASSIGNED
6. System creates delivery record
7. System notifies transporter

### 5. Delivery Execution
1. **PICKED_UP**: Transporter collects products from farmer
   - Updates status to PICKED_UP
   - Records pickup_time
   - Notifies buyer

2. **IN_TRANSIT**: Transporter starts delivery
   - Updates status to IN_TRANSIT
   - Updates order status to SHIPPED
   - Notifies buyer

3. **DELIVERED**: Transporter completes delivery
   - Updates status to DELIVERED
   - Records delivery_time
   - Updates order status to DELIVERED
   - Notifies buyer and farmer

## Status Flow Diagram

```
Order Status Flow:
PENDING → PAID → CONFIRMED → PROCESSING → SHIPPED → DELIVERED

Delivery Job Status Flow:
OPEN → BIDDING → ASSIGNED → PICKED_UP → IN_TRANSIT → DELIVERED

Bid Status Flow:
PENDING → ACCEPTED (or REJECTED)

Delivery Record Status Flow:
ASSIGNED → PICKED_UP → IN_TRANSIT → DELIVERED
```

## Database Tables Involved

1. **orders** - Order information and payment status
2. **deliveryjobs** - Delivery job creation and status
3. **deliverybids** - Transporter bids on jobs
4. **deliveries** - Detailed delivery tracking
5. **notifications** - User notifications
6. **transporter_profiles** - Transporter vehicle and capacity info

## API Endpoints Used

- `GET /api/transporter/jobs.php` - List available jobs
- `POST /api/transporter/bids/create.php` - Create bid
- `POST /api/transporter/bids/accept.php` - Accept bid (buyer)
- `POST /api/transporter/deliveries/update.php` - Update delivery status
- `GET /api/transporter/deliveries.php` - List deliveries

## Key Validations

1. **Bid Creation:**
   - Job must be OPEN or BIDDING
   - Order payment must be PAID
   - Transporter must not have existing bid
   - Job weight must not exceed vehicle capacity

2. **Bid Acceptance:**
   - Buyer must own the order
   - Bid must be PENDING
   - Job must be OPEN or BIDDING

3. **Status Updates:**
   - Transporter must have ACCEPTED bid
   - Status must follow progression order
   - Cannot skip or go backwards

---

**Last Updated:** 2025-01-27

