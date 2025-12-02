<?php
class TransporterProfile {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get transporter profile by user_id
     */
    public function getByUserId($user_id) {
        $sql = "SELECT * FROM transporter_profiles WHERE user_id = :uid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create or update transporter profile
     */
    public function save($user_id, $vehicle_type, $license_plate, $max_capacity_kg, $service_area_districts) {
        $existing = $this->getByUserId($user_id);

        if ($existing) {
            // Update
            $sql = "UPDATE transporter_profiles SET 
                    vehicle_type = :vehicle_type,
                    license_plate = :license_plate,
                    max_capacity_kg = :max_capacity_kg,
                    service_area_districts = :service_area_districts,
                    updated_at = NOW()
                    WHERE user_id = :uid";
        } else {
            // Insert
            $sql = "INSERT INTO transporter_profiles 
                    (user_id, vehicle_type, license_plate, max_capacity_kg, service_area_districts) 
                    VALUES 
                    (:uid, :vehicle_type, :license_plate, :max_capacity_kg, :service_area_districts)";
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':uid' => $user_id,
            ':vehicle_type' => $vehicle_type,
            ':license_plate' => strtoupper(trim($license_plate)),
            ':max_capacity_kg' => (int)$max_capacity_kg,
            ':service_area_districts' => $service_area_districts
        ]);
    }
}

