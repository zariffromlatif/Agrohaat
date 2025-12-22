# Unit Tests for AgroHaat Platform

This directory contains unit tests for the AgroHaat platform, focusing on the Transporter Module logistics functions.

## Setup

### Prerequisites
- PHP 8.2 or higher
- PHPUnit 10.x
- MySQL/MariaDB test database

### Installation

1. **Install PHPUnit via Composer:**
```bash
composer require --dev phpunit/phpunit
```

Or download PHPUnit PHAR:
```bash
wget https://phar.phpunit.de/phpunit-10.phar
chmod +x phpunit-10.phar
```

2. **Create Test Database:**
```sql
CREATE DATABASE agrohaat_test_db;
```

3. **Import Schema:**
Import the same database schema as production:
```bash
mysql -u root agrohaat_test_db < database/agrohaat_schema.sql
mysql -u root agrohaat_test_db < database/transporter_delivery_tables.sql
```

## Running Tests

### Run All Tests
```bash
./vendor/bin/phpunit tests/
```

Or with PHAR:
```bash
php phpunit-10.phar tests/
```

### Run Specific Test Suite
```bash
./vendor/bin/phpunit tests/Transporter/
```

### Run Specific Test Class
```bash
./vendor/bin/phpunit tests/Transporter/TransporterProfileTest.php
```

### Run with Coverage
```bash
./vendor/bin/phpunit --coverage-html coverage/ tests/
```

## Test Structure

```
tests/
├── bootstrap.php                    # Test bootstrap file
├── phpunit.xml                     # PHPUnit configuration
├── README.md                       # This file
├── Transporter/
│   ├── TransporterProfileTest.php  # Profile management tests
│   ├── BidManagementTest.php      # Bid creation and validation tests
│   └── DeliveryStatusTest.php      # Delivery status update tests
└── Farmer/
    ├── ProductModelTest.php        # Product model CRUD tests
    ├── ProductControllerTest.php   # Product controller tests
    └── OrderModelTest.php          # Order model and status update tests
```

## Test Coverage

### Transporter Module Tests

#### TransporterProfileTest
- ✅ Create new profile
- ✅ Update existing profile
- ✅ License plate uniqueness validation
- ✅ Get profile by user ID
- ✅ Handle non-existent profile

#### BidManagementTest
- ✅ Create bid
- ✅ Prevent duplicate bids
- ✅ Validate bid amount
- ✅ Update bid status

#### DeliveryStatusTest
- ✅ Status progression: ASSIGNED → PICKED_UP
- ✅ Status progression: PICKED_UP → IN_TRANSIT
- ✅ Status progression: IN_TRANSIT → DELIVERED
- ✅ Prevent status going backwards
- ✅ Order status synchronization

### Farmer Module Tests

#### ProductModelTest
- ✅ Create new product
- ✅ Get products by farmer
- ✅ Find product by ID
- ✅ Find product by trace ID
- ✅ Update product
- ✅ Delete product (soft delete)
- ✅ Product ownership validation
- ✅ Product validation (required fields)

#### ProductControllerTest
- ✅ Get products for farmer
- ✅ Get single product for farmer
- ✅ Product ownership in controller
- ✅ Product deletion through controller

#### OrderModelTest
- ✅ Get orders for farmer
- ✅ Order status updates
- ✅ Payment status updates
- ✅ Order status progression
- ✅ Order ownership validation
- ✅ Order listing with buyer information

## Writing New Tests

1. Create test class extending `PHPUnit\Framework\TestCase`
2. Use `setUp()` to prepare test data
3. Use `tearDown()` to clean up test data
4. Follow naming convention: `testMethodName()`
5. Use assertions: `assertTrue()`, `assertEquals()`, `assertNotNull()`, etc.

**Example:**
```php
<?php
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    protected function setUp(): void
    {
        // Setup test data
    }
    
    public function testSomething(): void
    {
        // Test logic
        $this->assertTrue($result);
    }
}
```

## Continuous Integration

Add to `.github/workflows/tests.yml`:
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: php-actions/composer@v5
      - run: ./vendor/bin/phpunit tests/
```

---

**Last Updated:** 2025-01-27

