<?php
/**
 * Unit Tests for Order Model (Farmer Perspective)
 * Tests order listing and status updates
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/models/Order.php';

class OrderModelTest extends TestCase
{
    private $pdo;
    private $orderModel;
    private $testFarmerId = 999998;
    private $testBuyerId = 999997;
    private $testOrderId = null;

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
            
            $this->orderModel = new Order($this->pdo);
            
            // Create test farmer
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO users (user_id, full_name, email, phone_number, password_hash, role, is_verified, is_deleted)
                VALUES (?, 'Test Farmer', 'test@farmer.com', '+8801234567890', 'test_hash', 'FARMER', 1, 0)
            ");
            $stmt->execute([$this->testFarmerId]);
            
            // Create test buyer
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO users (user_id, full_name, email, phone_number, password_hash, role, is_verified, is_deleted)
                VALUES (?, 'Test Buyer', 'test@buyer.com', '+8801234567891', 'test_hash', 'BUYER', 1, 0)
            ");
            $stmt->execute([$this->testBuyerId]);
            
        } catch (PDOException $e) {
            $this->markTestSkipped('Test database not available: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if ($this->pdo) {
            $this->pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$this->testOrderId]);
            $this->pdo->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$this->testOrderId]);
        }
    }

    /**
     * Test getting orders for farmer
     */
    public function testGetForFarmer(): void
    {
        // Create test order
        $this->createTestOrder();
        
        $orders = $this->orderModel->getForFarmer($this->testFarmerId);
        
        $this->assertIsArray($orders, 'Should return an array');
        $this->assertGreaterThan(0, count($orders), 'Should have at least one order');
        
        // Verify all orders belong to test farmer
        foreach ($orders as $order) {
            $this->assertEquals($this->testFarmerId, $order['farmer_id']);
        }
    }

    /**
     * Test order status updates
     */
    public function testOrderStatusUpdate(): void
    {
        // Create test order
        $this->createTestOrder();
        
        // Update order status
        $stmt = $this->pdo->prepare("UPDATE orders SET status = 'CONFIRMED' WHERE order_id = ?");
        $result = $stmt->execute([$this->testOrderId]);
        
        $this->assertTrue($result, 'Order status update should succeed');
        
        // Verify status update
        $stmt = $this->pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
        $stmt->execute([$this->testOrderId]);
        $order = $stmt->fetch();
        
        $this->assertEquals('CONFIRMED', $order['status']);
    }

    /**
     * Test payment status updates
     */
    public function testPaymentStatusUpdate(): void
    {
        // Create test order
        $this->createTestOrder();
        
        // Update payment status
        $result = $this->orderModel->updatePaymentStatus($this->testOrderId, $this->testBuyerId, 'PAID');
        
        $this->assertTrue($result, 'Payment status update should succeed');
        
        // Verify payment status update
        $stmt = $this->pdo->prepare("SELECT payment_status FROM orders WHERE order_id = ?");
        $stmt->execute([$this->testOrderId]);
        $order = $stmt->fetch();
        
        $this->assertEquals('PAID', $order['payment_status']);
    }

    /**
     * Test order status progression
     */
    public function testOrderStatusProgression(): void
    {
        // Create test order
        $this->createTestOrder();
        
        $statuses = ['PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED', 'DELIVERED'];
        
        foreach ($statuses as $status) {
            $stmt = $this->pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $result = $stmt->execute([$status, $this->testOrderId]);
            
            $this->assertTrue($result, "Status update to $status should succeed");
            
            // Verify
            $stmt = $this->pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
            $stmt->execute([$this->testOrderId]);
            $order = $stmt->fetch();
            
            $this->assertEquals($status, $order['status']);
        }
    }

    /**
     * Test that farmer only sees their own orders
     */
    public function testOrderOwnership(): void
    {
        // Create test order
        $this->createTestOrder();
        
        // Create another order for different farmer
        $otherFarmerId = $this->testFarmerId + 1;
        $stmt = $this->pdo->prepare("
            INSERT INTO orders (buyer_id, farmer_id, total_amount, status, payment_status, shipping_address)
            VALUES (?, ?, 2000.00, 'PENDING', 'UNPAID', 'Test Address')
        ");
        $stmt->execute([$this->testBuyerId, $otherFarmerId]);
        
        // Get orders for test farmer
        $orders = $this->orderModel->getForFarmer($this->testFarmerId);
        
        // Verify only test farmer's orders are returned
        foreach ($orders as $order) {
            $this->assertEquals($this->testFarmerId, $order['farmer_id']);
        }
    }

    /**
     * Test order listing with buyer information
     */
    public function testOrderWithBuyerInfo(): void
    {
        // Create test order
        $this->createTestOrder();
        
        $orders = $this->orderModel->getForFarmer($this->testFarmerId);
        
        $this->assertGreaterThan(0, count($orders), 'Should have orders');
        
        // Verify buyer information is included
        $order = $orders[0];
        $this->assertArrayHasKey('buyer_name', $order, 'Should include buyer name');
        $this->assertNotNull($order['buyer_name'], 'Buyer name should not be null');
    }

    /**
     * Helper method to create test order
     */
    private function createTestOrder(): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO orders (buyer_id, farmer_id, total_amount, status, payment_status, shipping_address)
            VALUES (?, ?, 1000.00, 'PENDING', 'UNPAID', 'Test Shipping Address')
        ");
        $stmt->execute([$this->testBuyerId, $this->testFarmerId]);
        $this->testOrderId = $this->pdo->lastInsertId();
    }
}

