<?php
/**
 * Unit Tests for TransporterProfile Model
 * Tests profile management functionality
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/models/TransporterProfile.php';

class TransporterProfileTest extends TestCase
{
    private $pdo;
    private $profileModel;
    private $testUserId = 999999; // Test user ID

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
            
            $this->profileModel = new TransporterProfile($this->pdo);
            
            // Create test user if doesn't exist
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO users (user_id, full_name, email, phone_number, password_hash, role, is_verified, is_deleted)
                VALUES (?, 'Test Transporter', 'test@transporter.com', '+8801234567890', 'test_hash', 'TRANSPORTER', 1, 0)
            ");
            $stmt->execute([$this->testUserId]);
            
        } catch (PDOException $e) {
            $this->markTestSkipped('Test database not available: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        // Clean up test data
        if ($this->pdo) {
            $stmt = $this->pdo->prepare("DELETE FROM transporter_profiles WHERE user_id = ?");
            $stmt->execute([$this->testUserId]);
        }
    }

    /**
     * Test creating a new transporter profile
     */
    public function testCreateProfile(): void
    {
        $result = $this->profileModel->save(
            $this->testUserId,
            'TRUCK',
            'DHK-T-1234',
            5000,
            'Dhaka, Tangail'
        );
        
        $this->assertTrue($result, 'Profile creation should succeed');
        
        $profile = $this->profileModel->getByUserId($this->testUserId);
        $this->assertNotNull($profile, 'Profile should exist after creation');
        $this->assertEquals('TRUCK', $profile['vehicle_type']);
        $this->assertEquals('DHK-T-1234', $profile['license_plate']);
        $this->assertEquals(5000, $profile['max_capacity_kg']);
    }

    /**
     * Test updating an existing profile
     */
    public function testUpdateProfile(): void
    {
        // Create profile first
        $this->profileModel->save(
            $this->testUserId,
            'TRUCK',
            'DHK-T-1234',
            5000,
            'Dhaka'
        );
        
        // Update profile
        $result = $this->profileModel->save(
            $this->testUserId,
            'VAN',
            'DHK-T-1234',
            3000,
            'Dhaka, Gazipur'
        );
        
        $this->assertTrue($result, 'Profile update should succeed');
        
        $profile = $this->profileModel->getByUserId($this->testUserId);
        $this->assertEquals('VAN', $profile['vehicle_type']);
        $this->assertEquals(3000, $profile['max_capacity_kg']);
        $this->assertEquals('Dhaka, Gazipur', $profile['service_area_districts']);
    }

    /**
     * Test license plate uniqueness
     */
    public function testLicensePlateUniqueness(): void
    {
        // Create first profile
        $this->profileModel->save(
            $this->testUserId,
            'TRUCK',
            'DHK-T-1234',
            5000,
            'Dhaka'
        );
        
        // Try to create another profile with same license plate
        $this->expectException(PDOException::class);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO transporter_profiles (user_id, vehicle_type, license_plate, max_capacity_kg)
            VALUES (?, 'VAN', 'DHK-T-1234', 3000)
        ");
        $stmt->execute([$this->testUserId + 1]);
    }

    /**
     * Test getting profile by user ID
     */
    public function testGetByUserId(): void
    {
        // Create profile
        $this->profileModel->save(
            $this->testUserId,
            'PICKUP',
            'DHK-T-5678',
            2000,
            'Tangail'
        );
        
        $profile = $this->profileModel->getByUserId($this->testUserId);
        $this->assertNotNull($profile);
        $this->assertEquals($this->testUserId, $profile['user_id']);
        $this->assertEquals('PICKUP', $profile['vehicle_type']);
    }

    /**
     * Test getting non-existent profile
     */
    public function testGetNonExistentProfile(): void
    {
        $profile = $this->profileModel->getByUserId(9999999);
        $this->assertFalse($profile, 'Non-existent profile should return false');
    }
}

