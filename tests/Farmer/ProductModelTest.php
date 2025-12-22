<?php
/**
 * Unit Tests for Product Model
 * Tests product CRUD operations
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/models/Product.php';

class ProductModelTest extends TestCase
{
    private $pdo;
    private $productModel;
    private $testFarmerId = 999998; // Test farmer ID
    private $testProductId = null;

    protected function setUp(): void
    {
        // Use test database or skip if not available
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
            
            $this->productModel = new Product($this->pdo);
            
            // Create test farmer if doesn't exist
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
        // Clean up test data
        if ($this->pdo) {
            $stmt = $this->pdo->prepare("DELETE FROM products WHERE farmer_id = ?");
            $stmt->execute([$this->testFarmerId]);
        }
    }

    /**
     * Test creating a new product
     */
    public function testCreateProduct(): void
    {
        $trace_id = uniqid("TRC");
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=test';
        
        $data = [
            ':farmer_id' => $this->testFarmerId,
            ':category_id' => 1,
            ':title' => 'Test Product',
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
        
        $result = $this->productModel->create($data);
        
        $this->assertTrue($result, 'Product creation should succeed');
        
        // Verify product was created
        $product = $this->productModel->findByTraceId($trace_id);
        $this->assertNotNull($product, 'Product should exist after creation');
        $this->assertEquals('Test Product', $product['title']);
        $this->assertEquals($trace_id, $product['trace_id']);
        $this->assertEquals('A', $product['quality_grade']);
        
        $this->testProductId = $product['product_id'];
    }

    /**
     * Test getting products by farmer
     */
    public function testGetByFarmer(): void
    {
        // Create test product first
        $this->testCreateProduct();
        
        $products = $this->productModel->getByFarmer($this->testFarmerId);
        
        $this->assertIsArray($products, 'Should return an array');
        $this->assertGreaterThan(0, count($products), 'Should have at least one product');
        
        // Verify all products belong to test farmer
        foreach ($products as $product) {
            $this->assertEquals($this->testFarmerId, $product['farmer_id']);
            $this->assertEquals(0, $product['is_deleted'], 'Should not be deleted');
        }
    }

    /**
     * Test finding product by ID
     */
    public function testFindProduct(): void
    {
        // Create test product first
        $this->testCreateProduct();
        
        $product = $this->productModel->find($this->testProductId, $this->testFarmerId);
        
        $this->assertNotNull($product, 'Product should be found');
        $this->assertEquals($this->testProductId, $product['product_id']);
        $this->assertEquals($this->testFarmerId, $product['farmer_id']);
    }

    /**
     * Test finding product by trace ID
     */
    public function testFindByTraceId(): void
    {
        // Create test product first
        $trace_id = uniqid("TRC");
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=test';
        
        $data = [
            ':farmer_id' => $this->testFarmerId,
            ':category_id' => 1,
            ':title' => 'Traceable Product',
            ':description' => 'Test',
            ':image_url' => null,
            ':quantity_available' => 50.00,
            ':unit' => 'kg',
            ':price_per_unit' => 30.00,
            ':quality_grade' => 'B',
            ':harvest_date' => '2025-01-20',
            ':batch_number' => 'BATCH-002',
            ':trace_id' => $trace_id,
            ':qr_code_url' => $qrUrl
        ];
        
        $this->productModel->create($data);
        
        $product = $this->productModel->findByTraceId($trace_id);
        
        $this->assertNotNull($product, 'Product should be found by trace ID');
        $this->assertEquals($trace_id, $product['trace_id']);
    }

    /**
     * Test updating a product
     */
    public function testUpdateProduct(): void
    {
        // Create test product first
        $this->testCreateProduct();
        
        $updateData = [
            ':category_id' => 2,
            ':title' => 'Updated Product',
            ':description' => 'Updated description',
            ':quantity_available' => 150.00,
            ':unit' => 'ton',
            ':price_per_unit' => 60.00,
            ':quality_grade' => 'B',
            ':harvest_date' => '2025-01-25',
            ':batch_number' => 'BATCH-002'
        ];
        
        $result = $this->productModel->update($this->testProductId, $this->testFarmerId, $updateData);
        
        $this->assertTrue($result, 'Product update should succeed');
        
        // Verify update
        $product = $this->productModel->find($this->testProductId, $this->testFarmerId);
        $this->assertEquals('Updated Product', $product['title']);
        $this->assertEquals(150.00, $product['quantity_available']);
        $this->assertEquals('B', $product['quality_grade']);
    }

    /**
     * Test deleting a product (soft delete)
     */
    public function testDeleteProduct(): void
    {
        // Create test product first
        $this->testCreateProduct();
        
        $result = $this->productModel->delete($this->testProductId, $this->testFarmerId);
        
        $this->assertTrue($result, 'Product deletion should succeed');
        
        // Verify soft delete
        $product = $this->productModel->find($this->testProductId, $this->testFarmerId);
        $this->assertFalse($product, 'Product should not be found after deletion');
        
        // Verify product still exists in database but is marked as deleted
        $stmt = $this->pdo->prepare("SELECT is_deleted FROM products WHERE product_id = ?");
        $stmt->execute([$this->testProductId]);
        $result = $stmt->fetch();
        $this->assertEquals(1, $result['is_deleted'], 'Product should be marked as deleted');
    }

    /**
     * Test that farmer cannot access other farmer's products
     */
    public function testProductOwnership(): void
    {
        // Create test product
        $this->testCreateProduct();
        
        // Try to access with different farmer ID
        $otherFarmerId = $this->testFarmerId + 1;
        $product = $this->productModel->find($this->testProductId, $otherFarmerId);
        
        $this->assertFalse($product, 'Should not find product for different farmer');
    }

    /**
     * Test product validation - required fields
     */
    public function testProductValidation(): void
    {
        // Try to create product with missing required fields
        $this->expectException(PDOException::class);
        
        $data = [
            ':farmer_id' => $this->testFarmerId,
            // Missing required fields
        ];
        
        $this->productModel->create($data);
    }
}

