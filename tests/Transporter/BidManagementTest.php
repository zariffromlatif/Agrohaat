<?php
/**
 * Unit Tests for Bid Management
 * Tests bid creation, validation, and status updates
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class BidManagementTest extends TestCase
{
    private $pdo;
    private $testUserId = 999999;
    private $testJobId = 999999;
    private $testOrderId = 999999;

    protected function setUp(): void
    {
        try {
            $this->pdo = new PDO(
                "mysql:host=localhost;dbname=" . TEST_DB_NAME . ";charset=utf8mb4",
                "root",
                "",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            // Create test data
            $this->createTestData();
            
        } catch (PDOException $e) {
            $this->markTestSkipped('Test database not available: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if ($this->pdo) {
            $this->pdo->prepare("DELETE FROM deliverybids WHERE job_id = ?")->execute([$this->testJobId]);
            $this->pdo->prepare("DELETE FROM deliveryjobs WHERE job_id = ?")->execute([$this->testJobId]);
            $this->pdo->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$this->testOrderId]);
        }
    }

    private function createTestData(): void
    {
        // Create test order
        $stmt = $this->pdo->prepare("
            INSERT INTO orders (order_id, buyer_id, farmer_id, total_amount, status, payment_status, shipping_address)
            VALUES (?, 1, 2, 1000.00, 'CONFIRMED', 'PAID', 'Test Address')
        ");
        $stmt->execute([$this->testOrderId]);
        
        // Create test delivery job
        $stmt = $this->pdo->prepare("
            INSERT INTO deliveryjobs (job_id, order_id, pickup_location, dropoff_location, status)
            VALUES (?, ?, 'Pickup Location', 'Dropoff Location', 'OPEN')
        ");
        $stmt->execute([$this->testJobId, $this->testOrderId]);
        
        // Create test transporter profile
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO transporter_profiles (user_id, vehicle_type, license_plate, max_capacity_kg)
            VALUES (?, 'TRUCK', 'TEST-1234', 5000)
        ");
        $stmt->execute([$this->testUserId]);
    }

    /**
     * Test creating a bid
     */
    public function testCreateBid(): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO deliverybids (job_id, transporter_id, bid_amount, message, status)
            VALUES (?, ?, ?, ?, 'PENDING')
        ");
        $result = $stmt->execute([
            $this->testJobId,
            $this->testUserId,
            500.00,
            'Test bid message'
        ]);
        
        $this->assertTrue($result, 'Bid creation should succeed');
        
        $stmt = $this->pdo->prepare("SELECT * FROM deliverybids WHERE job_id = ? AND transporter_id = ?");
        $stmt->execute([$this->testJobId, $this->testUserId]);
        $bid = $stmt->fetch();
        
        $this->assertNotNull($bid, 'Bid should exist');
        $this->assertEquals(500.00, $bid['bid_amount']);
        $this->assertEquals('PENDING', $bid['status']);
    }

    /**
     * Test duplicate bid prevention
     */
    public function testDuplicateBidPrevention(): void
    {
        // Create first bid
        $stmt = $this->pdo->prepare("
            INSERT INTO deliverybids (job_id, transporter_id, bid_amount, status)
            VALUES (?, ?, 500.00, 'PENDING')
        ");
        $stmt->execute([$this->testJobId, $this->testUserId]);
        
        // Try to create duplicate bid
        $this->expectException(PDOException::class);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO deliverybids (job_id, transporter_id, bid_amount, status)
            VALUES (?, ?, 600.00, 'PENDING')
        ");
        $stmt->execute([$this->testJobId, $this->testUserId]);
    }

    /**
     * Test bid amount validation
     */
    public function testBidAmountValidation(): void
    {
        // Test zero amount
        $this->expectException(PDOException::class);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO deliverybids (job_id, transporter_id, bid_amount, status)
            VALUES (?, ?, 0, 'PENDING')
        ");
        $stmt->execute([$this->testJobId, $this->testUserId]);
    }

    /**
     * Test bid status update
     */
    public function testBidStatusUpdate(): void
    {
        // Create bid
        $stmt = $this->pdo->prepare("
            INSERT INTO deliverybids (job_id, transporter_id, bid_amount, status)
            VALUES (?, ?, 500.00, 'PENDING')
        ");
        $stmt->execute([$this->testJobId, $this->testUserId]);
        
        $bidId = $this->pdo->lastInsertId();
        
        // Update status to ACCEPTED
        $stmt = $this->pdo->prepare("UPDATE deliverybids SET status = 'ACCEPTED' WHERE bid_id = ?");
        $result = $stmt->execute([$bidId]);
        
        $this->assertTrue($result, 'Status update should succeed');
        
        $stmt = $this->pdo->prepare("SELECT status FROM deliverybids WHERE bid_id = ?");
        $stmt->execute([$bidId]);
        $bid = $stmt->fetch();
        
        $this->assertEquals('ACCEPTED', $bid['status']);
    }
}

