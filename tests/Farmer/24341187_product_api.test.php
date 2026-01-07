<?php
/**
 * Assignment 2: Backend Unit Testing Integration
 * Student ID: 24341187
 * Feature: Product Management API
 * 
 * Tests all CRUD operations for Farmer Product API endpoints:
 * - Create Product (POST)
 * - Read Products (GET)
 * - Update Product (PUT)
 * - Delete Product (DELETE)
 * - Validation errors
 * - Unauthorized access
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/models/Product.php';

// Enable test mode
if (!defined('UNIT_TESTING')) {
    define('UNIT_TESTING', true);
}
$GLOBALS['__API_TEST_MODE'] = true;

// Set up paths for products.php relative includes
// products.php uses '../../../config/config.php' so we need to simulate being in that directory
$oldCwd = getcwd();
chdir(BASE_PATH . '/public/api/farmer');

// Load API file to get helper function
require_once BASE_PATH . '/public/api/farmer/products.php';

// Restore directory
chdir($oldCwd);

/**
 * Test class for Farmer Product API
 * Filename: 24341187_product_api.test.php (as per assignment requirements)
 */
class ProductApiTest24341187 extends TestCase
{
    private static $dbPath;
    private $pdo;
    private $farmerId = 1;

    public static function setUpBeforeClass(): void
    {
        self::$dbPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'agrohaat_farmer_api_test_' . time() . '.sqlite';
    }

    protected function setUp(): void
    {
        // Clean up old test database
        if (file_exists(self::$dbPath)) {
            unlink(self::$dbPath);
        }

        // Set environment for SQLite test database
        putenv('DB_DRIVER=sqlite');
        putenv('DB_SQLITE_PATH=' . self::$dbPath);

        // Create SQLite database connection
        $this->pdo = new PDO(
            'sqlite:' . self::$dbPath,
            null,
            null,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $this->createSchema();
        $this->seedFarmer();
    }

    protected function tearDown(): void
    {
        // Clean up session
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * Test Case A1: Create Resource (Happy Path)
     * Expected: Status 201 Created, returns created object with ID
     */
    public function testCreateProductSucceeds()
    {
        $payload = $this->validPayload();
        $response = $this->invokeApi('POST', [], $payload);

        $this->assertSame(201, $response['status'], 'Should return 201 Created status');
        $this->assertTrue($response['body']['success'], 'Response should indicate success');
        $this->assertArrayHasKey('data', $response['body'], 'Response should contain data');
        $this->assertArrayHasKey('product_id', $response['body']['data'], 'Product should have an ID');
        $this->assertSame($payload['title'], $response['body']['data']['title'], 'Title should match');
    }

    /**
     * Test Case A2: Retrieve Resource (Happy Path)
     * Expected: Status 200 OK, returns matching data
     */
    public function testGetProductByIdAfterCreation()
    {
        $productId = $this->createProductAndReturnId();

        $response = $this->invokeApi('GET', ['id' => $productId]);

        $this->assertSame(200, $response['status'], 'Should return 200 OK status');
        $this->assertTrue($response['body']['success'], 'Response should indicate success');
        $this->assertSame($productId, $response['body']['data']['product_id'], 'Should return correct product ID');
        $this->assertArrayHasKey('title', $response['body']['data'], 'Product should have title');
    }

    /**
     * Test Case B1: Validation Error (Negative Flow)
     * Expected: Status 400 Bad Request, returns specific validation error
     */
    public function testMissingRequiredFieldReturnsValidationError()
    {
        $payload = $this->validPayload();
        unset($payload['title']); // Remove required field

        $response = $this->invokeApi('POST', [], $payload);

        $this->assertSame(400, $response['status'], 'Should return 400 Bad Request');
        $this->assertFalse($response['body']['success'], 'Response should indicate failure');
        $this->assertStringContainsString('Missing required fields', $response['body']['error'], 'Should mention missing fields');
    }

    /**
     * Test Case B2: Resource Not Found (Negative Flow)
     * Expected: Status 404 Not Found
     */
    public function testUpdateNonExistentProductReturnsNotFound()
    {
        $response = $this->invokeApi('PUT', [], [
            'id' => 999999, // Non-existent ID
            'title' => 'Updated Title'
        ]);

        $this->assertSame(404, $response['status'], 'Should return 404 Not Found');
        $this->assertFalse($response['body']['success'], 'Response should indicate failure');
        $this->assertStringContainsString('not found', strtolower($response['body']['error']), 'Should mention not found');
    }

    /**
     * Test Case C1: Unauthorized Access (Security)
     * Expected: Status 401 Unauthorized or 403 Forbidden
     */
    public function testUnauthorizedRequestIsRejected()
    {
        $response = $this->invokeApi('GET', [], [], false); // No authentication

        $this->assertSame(401, $response['status'], 'Should return 401 Unauthorized');
        $this->assertFalse($response['body']['success'], 'Response should indicate failure');
        $this->assertSame('Unauthorized', $response['body']['error'], 'Should indicate unauthorized');
    }

    /**
     * Test Case: Update Product (Happy Path)
     */
    public function testUpdateProductPrice()
    {
        $productId = $this->createProductAndReturnId();

        $response = $this->invokeApi('PUT', [], [
            'id' => $productId,
            'price_per_unit' => 75.5
        ]);

        $this->assertSame(200, $response['status'], 'Should return 200 OK');
        $this->assertTrue($response['body']['success'], 'Response should indicate success');
        $this->assertSame(75.5, (float)$response['body']['data']['price_per_unit'], 'Price should be updated');
    }

    /**
     * Test Case: Delete Product (Happy Path)
     */
    public function testDeleteProductThenNotFound()
    {
        $productId = $this->createProductAndReturnId();

        $deleteResponse = $this->invokeApi('DELETE', [], ['id' => $productId]);
        $this->assertSame(200, $deleteResponse['status'], 'Delete should return 200 OK');
        $this->assertTrue($deleteResponse['body']['success'], 'Delete should succeed');

        // Verify product is deleted (soft delete)
        $fetchResponse = $this->invokeApi('GET', ['id' => $productId]);
        $this->assertSame(404, $fetchResponse['status'], 'Deleted product should return 404');
        $this->assertFalse($fetchResponse['body']['success'], 'Should indicate product not found');
    }

    /**
     * Invoke the Farmer Products API programmatically
     * This simulates HTTP requests without actual HTTP calls
     */
    private function invokeApi($method, $query = [], $body = [], $withAuth = true)
    {
        // Set up request environment
        $_SERVER['REQUEST_METHOD'] = $method;
        $_GET = $query;
        $_POST = [];
        $_FILES = [];

        // Set up authentication
        if ($withAuth) {
            $_SESSION['user_id'] = $this->farmerId;
            $_SESSION['role'] = 'FARMER';
        } else {
            unset($_SESSION['user_id'], $_SESSION['role']);
        }

        // Set JSON input for POST/PUT/DELETE
        $GLOBALS['__API_RAW_INPUT'] = !empty($body) ? json_encode($body) : '';
        $GLOBALS['__API_TEST_MODE'] = true;

        // Capture output
        ob_start();
        $result = runFarmerProductsApi($this->pdo);
        $output = ob_get_clean();

        // If function returned array (test mode), use it
        if (is_array($result)) {
            return $result;
        }

        // Otherwise parse JSON output
        $decoded = json_decode($output, true);
        return [
            'status' => http_response_code() ?: 200,
            'body' => $decoded ?: []
        ];
    }

    private function createSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE users (
                user_id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT,
                phone_number TEXT,
                email TEXT,
                password TEXT,
                role TEXT,
                district TEXT,
                upazila TEXT
            );
        ");

        $this->pdo->exec("
            CREATE TABLE products (
                product_id INTEGER PRIMARY KEY AUTOINCREMENT,
                farmer_id INTEGER NOT NULL,
                category_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                image_url TEXT,
                quantity_available REAL,
                unit TEXT,
                price_per_unit REAL,
                quality_grade TEXT,
                harvest_date TEXT,
                batch_number TEXT,
                trace_id TEXT,
                qr_code_url TEXT,
                status TEXT DEFAULT 'PENDING',
                is_deleted INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }

    private function seedFarmer(): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (full_name, phone_number, email, password, role, district, upazila)
            VALUES (:name, :phone, :email, :password, :role, :district, :upazila)
        ");
        $stmt->execute([
            ':name' => 'Test Farmer',
            ':phone' => '0123456789',
            ':email' => 'farmer@example.com',
            ':password' => password_hash('secret', PASSWORD_DEFAULT),
            ':role' => 'FARMER',
            ':district' => 'Dhaka',
            ':upazila' => 'Tejgaon'
        ]);

        $this->farmerId = (int)$this->pdo->lastInsertId();
    }

    /**
     * Get valid product payload for testing
     */
    private function validPayload()
    {
        return [
            'category_id' => 1,
            'title' => 'Organic Rice',
            'description' => 'Fresh organic rice from local farm',
            'quantity_available' => 100.5,
            'unit' => 'kg',
            'price_per_unit' => 50.0,
            'quality_grade' => 'A',
            'harvest_date' => '2025-01-15',
            'batch_number' => 'BATCH-001'
        ];
    }

    /**
     * Helper: Create a product and return its ID
     */
    private function createProductAndReturnId()
    {
        $response = $this->invokeApi('POST', [], $this->validPayload());
        $this->assertSame(201, $response['status'], 'Product creation should succeed');
        return (int)$response['body']['data']['product_id'];
    }
}

