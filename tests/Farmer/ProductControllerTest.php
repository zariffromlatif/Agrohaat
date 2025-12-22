<?php
/**
 * Unit Tests for ProductController
 * Tests product controller business logic
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/controllers/ProductController.php';
require_once BASE_PATH . '/models/Product.php';

class ProductControllerTest extends TestCase
{
    private $pdo;
    private $controller;
    private $testFarmerId = 999998;
    private $testProductId = null;

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
            
            $this->controller = new ProductController($this->pdo);
            
            // Create test farmer
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO users (user_id, full_name, email, phone_number, password_hash, role, is_verified, is_deleted)
                VALUES (?, 'Test Farmer', 'test@farmer.com', '+8801234567890', 'test_hash', 'FARMER', 1, 0)
            ");
            $stmt->execute([$this->testFarmerId]);
            
        } catch (PDOException $e) {
            $this->markTestSkipped('Test database not available: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if ($this->pdo) {
            $this->pdo->prepare("DELETE FROM products WHERE farmer_id = ?")->execute([$this->testFarmerId]);
        }
    }

    /**
     * Test getting products for farmer
     */
    public function testGetProductsForFarmer(): void
    {
        // Create test product first
        $this->createTestProduct();
        
        $products = $this->controller->getProductsForFarmer($this->testFarmerId);
        
        $this->assertIsArray($products, 'Should return an array');
        $this->assertGreaterThan(0, count($products), 'Should have products');
        
        // Verify all products belong to test farmer
        foreach ($products as $product) {
            $this->assertEquals($this->testFarmerId, $product['farmer_id']);
        }
    }

    /**
     * Test getting single product for farmer
     */
    public function testGetProductForFarmer(): void
    {
        // Create test product first
        $this->createTestProduct();
        
        $product = $this->controller->getProductForFarmer($this->testFarmerId, $this->testProductId);
        
        $this->assertNotNull($product, 'Product should be found');
        $this->assertEquals($this->testProductId, $product['product_id']);
        $this->assertEquals($this->testFarmerId, $product['farmer_id']);
    }

    /**
     * Test that farmer cannot access other farmer's products
     */
    public function testProductOwnershipInController(): void
    {
        // Create test product
        $this->createTestProduct();
        
        // Try to access with different farmer ID
        $otherFarmerId = $this->testFarmerId + 1;
        $product = $this->controller->getProductForFarmer($otherFarmerId, $this->testProductId);
        
        $this->assertFalse($product, 'Should not find product for different farmer');
    }

    /**
     * Test product deletion through controller
     */
    public function testHandleDelete(): void
    {
        // Create test product
        $this->createTestProduct();
        
        // Note: handleDelete redirects, so we test the model method it calls
        $productModel = new Product($this->pdo);
        $result = $productModel->delete($this->testProductId, $this->testFarmerId);
        
        $this->assertTrue($result, 'Product deletion should succeed');
        
        // Verify product is soft deleted
        $product = $productModel->find($this->testProductId, $this->testFarmerId);
        $this->assertFalse($product, 'Product should not be found after deletion');
    }

    /**
     * Helper method to create test product
     */
    private function createTestProduct(): void
    {
        $trace_id = uniqid("TRC");
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=test';
        
        $productModel = new Product($this->pdo);
        $data = [
            ':farmer_id' => $this->testFarmerId,
            ':category_id' => 1,
            ':title' => 'Controller Test Product',
            ':description' => 'Test description',
            ':image_url' => null,
            ':quantity_available' => 100.00,
            ':unit' => 'kg',
            ':price_per_unit' => 50.00,
            ':quality_grade' => 'A',
            ':harvest_date' => '2025-01-15',
            ':batch_number' => 'BATCH-001',
            ':trace_id' => $trace_id,
            ':qr_code_url' => $qrUrl
        ];
        
        $productModel->create($data);
        
        $product = $productModel->findByTraceId($trace_id);
        $this->testProductId = $product['product_id'];
    }
}

