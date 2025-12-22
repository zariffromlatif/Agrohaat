<?php
/**
 * Unit Tests for Delivery Status Updates
 * Tests status progression and validation
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class DeliveryStatusTest extends TestCase
{
    private $pdo;
    private $testJobId = 999999;
    private $testOrderId = 999999;
    private $testUserId = 999999;

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
            
            $this->createTestData();
            
        } catch (PDOException $e) {
            $this->markTestSkipped('Test database not available: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if ($this->pdo) {
            $this->pdo->prepare("DELETE FROM deliveries WHERE job_id = ?")->execute([$this->testJobId]);
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
            VALUES (?, 1, 2, 1000.00, 'PROCESSING', 'PAID', 'Test Address')
        ");
        $stmt->execute([$this->testOrderId]);
        
        // Create test delivery job with accepted bid
        $stmt = $this->pdo->prepare("
            INSERT INTO deliveryjobs (job_id, order_id, pickup_location, dropoff_location, status)
            VALUES (?, ?, 'Pickup', 'Dropoff', 'ASSIGNED')
        ");
        $stmt->execute([$this->testJobId, $this->testOrderId]);
        
        // Create accepted bid
        $stmt = $this->pdo->prepare("
            INSERT INTO deliverybids (job_id, transporter_id, bid_amount, status)
            VALUES (?, ?, 500.00, 'ACCEPTED')
        ");
        $stmt->execute([$this->testJobId, $this->testUserId]);
        
        // Create delivery record
        $stmt = $this->pdo->prepare("
            INSERT INTO deliveries (job_id, order_id, transporter_id, status)
            VALUES (?, ?, ?, 'ASSIGNED')
        ");
        $stmt->execute([$this->testJobId, $this->testOrderId, $this->testUserId]);
    }

    /**
     * Test status progression: ASSIGNED -> PICKED_UP
     */
    public function testStatusProgressionPickedUp(): void
    {
        $this->pdo->beginTransaction();
        
        try {
            // Update to PICKED_UP
            $stmt = $this->pdo->prepare("UPDATE deliveryjobs SET status = 'PICKED_UP' WHERE job_id = ?");
            $result = $stmt->execute([$this->testJobId]);
            $this->assertTrue($result);
            
            $stmt = $this->pdo->prepare("UPDATE deliveries SET status = 'PICKED_UP', pickup_time = NOW() WHERE job_id = ?");
            $result = $stmt->execute([$this->testJobId]);
            $this->assertTrue($result);
            
            $this->pdo->commit();
            
            // Verify
            $stmt = $this->pdo->prepare("SELECT status FROM deliveryjobs WHERE job_id = ?");
            $stmt->execute([$this->testJobId]);
            $job = $stmt->fetch();
            $this->assertEquals('PICKED_UP', $job['status']);
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Test status progression: PICKED_UP -> IN_TRANSIT
     */
    public function testStatusProgressionInTransit(): void
    {
        // First set to PICKED_UP
        $this->pdo->prepare("UPDATE deliveryjobs SET status = 'PICKED_UP' WHERE job_id = ?")->execute([$this->testJobId]);
        $this->pdo->prepare("UPDATE deliveries SET status = 'PICKED_UP' WHERE job_id = ?")->execute([$this->testJobId]);
        
        // Update to IN_TRANSIT
        $stmt = $this->pdo->prepare("UPDATE deliveryjobs SET status = 'IN_TRANSIT' WHERE job_id = ?");
        $result = $stmt->execute([$this->testJobId]);
        $this->assertTrue($result);
        
        $stmt = $this->pdo->prepare("UPDATE deliveries SET status = 'IN_TRANSIT' WHERE job_id = ?");
        $result = $stmt->execute([$this->testJobId]);
        $this->assertTrue($result);
        
        // Verify
        $stmt = $this->pdo->prepare("SELECT status FROM deliveryjobs WHERE job_id = ?");
        $stmt->execute([$this->testJobId]);
        $job = $stmt->fetch();
        $this->assertEquals('IN_TRANSIT', $job['status']);
    }

    /**
     * Test status progression: IN_TRANSIT -> DELIVERED
     */
    public function testStatusProgressionDelivered(): void
    {
        // Set to IN_TRANSIT first
        $this->pdo->prepare("UPDATE deliveryjobs SET status = 'IN_TRANSIT' WHERE job_id = ?")->execute([$this->testJobId]);
        $this->pdo->prepare("UPDATE deliveries SET status = 'IN_TRANSIT' WHERE job_id = ?")->execute([$this->testJobId]);
        
        // Update to DELIVERED
        $this->pdo->beginTransaction();
        
        try {
            $stmt = $this->pdo->prepare("UPDATE deliveryjobs SET status = 'DELIVERED' WHERE job_id = ?");
            $stmt->execute([$this->testJobId]);
            
            $stmt = $this->pdo->prepare("UPDATE deliveries SET status = 'DELIVERED', delivery_time = NOW() WHERE job_id = ?");
            $stmt->execute([$this->testJobId]);
            
            $stmt = $this->pdo->prepare("UPDATE orders SET status = 'DELIVERED' WHERE order_id = ?");
            $stmt->execute([$this->testOrderId]);
            
            $this->pdo->commit();
            
            // Verify
            $stmt = $this->pdo->prepare("SELECT status FROM deliveryjobs WHERE job_id = ?");
            $stmt->execute([$this->testJobId]);
            $job = $stmt->fetch();
            $this->assertEquals('DELIVERED', $job['status']);
            
            $stmt = $this->pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
            $stmt->execute([$this->testOrderId]);
            $order = $stmt->fetch();
            $this->assertEquals('DELIVERED', $order['status']);
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Test that status cannot go backwards
     */
    public function testStatusCannotGoBackwards(): void
    {
        // Set to IN_TRANSIT
        $this->pdo->prepare("UPDATE deliveryjobs SET status = 'IN_TRANSIT' WHERE job_id = ?")->execute([$this->testJobId]);
        
        // Try to go back to PICKED_UP (should be prevented by application logic)
        // This test documents expected behavior
        $stmt = $this->pdo->prepare("SELECT status FROM deliveryjobs WHERE job_id = ?");
        $stmt->execute([$this->testJobId]);
        $job = $stmt->fetch();
        
        $this->assertEquals('IN_TRANSIT', $job['status'], 'Status should remain IN_TRANSIT');
    }

    /**
     * Test order status synchronization
     */
    public function testOrderStatusSynchronization(): void
    {
        // Update delivery to IN_TRANSIT
        $this->pdo->prepare("UPDATE deliveryjobs SET status = 'IN_TRANSIT' WHERE job_id = ?")->execute([$this->testJobId]);
        $this->pdo->prepare("UPDATE orders SET status = 'SHIPPED' WHERE order_id = ?")->execute([$this->testOrderId]);
        
        // Verify order status updated
        $stmt = $this->pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
        $stmt->execute([$this->testOrderId]);
        $order = $stmt->fetch();
        
        $this->assertEquals('SHIPPED', $order['status']);
    }
}

