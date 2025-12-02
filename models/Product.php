<?php
class Product {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // FETCH ALL PRODUCTS FOR A FARMER
    public function getByFarmer($farmer_id) {
        $sql = "SELECT * FROM products WHERE farmer_id = :fid AND is_deleted = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':fid' => $farmer_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // CREATE PRODUCT
    public function create($data) {

        $sql = "INSERT INTO products 
                (farmer_id, category_id, title, description, image_url, 
                 quantity_available, unit, price_per_unit, quality_grade, 
                 harvest_date, batch_number, trace_id, qr_code_url, status, is_deleted)
                VALUES
                (:farmer_id, :category_id, :title, :description, :image_url,
                 :quantity_available, :unit, :price_per_unit, :quality_grade,
                 :harvest_date, :batch_number, :trace_id, :qr_code_url, 'ACTIVE', 0)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    public function find($product_id, $farmer_id) {
        $sql = "SELECT * FROM products WHERE product_id = :pid AND farmer_id = :fid AND is_deleted = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':pid' => $product_id,
            ':fid' => $farmer_id
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByTraceId($trace_id) {
        $sql = "SELECT * FROM products WHERE trace_id = :tid AND is_deleted = 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tid' => $trace_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function update($product_id, $farmer_id, $data) {

        $sql = "UPDATE products SET
                category_id = :category_id,
                title = :title,
                description = :description,
                quantity_available = :quantity_available,
                unit = :unit,
                price_per_unit = :price_per_unit,
                quality_grade = :quality_grade,
                harvest_date = :harvest_date,
                batch_number = :batch_number
                WHERE product_id = :pid AND farmer_id = :fid";

        $stmt = $this->pdo->prepare($sql);

        $data[':pid'] = $product_id;
        $data[':fid'] = $farmer_id;

        return $stmt->execute($data);
    }

    // DELETE (SOFT DELETE)
    public function delete($product_id, $farmer_id) {
        $sql = "UPDATE products SET is_deleted = 1 WHERE product_id = :pid AND farmer_id = :fid";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':pid' => $product_id,
            ':fid' => $farmer_id
        ]);
    }
}
