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
        // Generate trace_id if not provided or is null
        if (empty($data[':trace_id']) || $data[':trace_id'] === null) {
            $data[':trace_id'] = 'TRACE_' . time() . '_' . uniqid();
        }

        $sql = "INSERT INTO products 
                (farmer_id, category_id, title, description, image_url, 
                 quantity_available, unit, price_per_unit, quality_grade, 
                 harvest_date, batch_number, trace_id, qr_code_url, status, is_deleted)
                VALUES
                (:farmer_id, :category_id, :title, :description, :image_url,
                 :quantity_available, :unit, :price_per_unit, :quality_grade,
                 :harvest_date, :batch_number, :trace_id, :qr_code_url, 'PENDING', 0)";

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

    // === BUYER METHODS ===

    /**
     * Get all active products for marketplace (buyer view)
     */
    public function getAllActive() {
        $sql = "SELECT p.*, u.full_name AS farmer_name, u.district, u.upazila
                FROM products p
                JOIN users u ON u.user_id = p.farmer_id
                WHERE p.is_deleted = 0 AND p.status = 'ACTIVE'
                ORDER BY p.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search products with filters
     */
    public function search($search_term = '', $category_id = null, $district = null, $min_price = null, $max_price = null, $quality_grade = null, $limit = 12, $offset = 0) {
        $sql = "SELECT p.*, u.full_name AS farmer_name, u.district, u.upazila
                FROM products p
                JOIN users u ON u.user_id = p.farmer_id
                WHERE p.is_deleted = 0 AND p.status = 'ACTIVE'";

        $params = [];

        if (!empty($search_term)) {
            $sql .= " AND (p.title LIKE :search OR p.description LIKE :search)";
            $params[':search'] = '%' . $search_term . '%';
        }

        if ($category_id) {
            $sql .= " AND p.category_id = :category_id";
            $params[':category_id'] = $category_id;
        }

        if ($district) {
            $sql .= " AND u.district = :district";
            $params[':district'] = $district;
        }

        if ($min_price !== null) {
            $sql .= " AND p.price_per_unit >= :min_price";
            $params[':min_price'] = $min_price;
        }

        if ($max_price !== null) {
            $sql .= " AND p.price_per_unit <= :max_price";
            $params[':max_price'] = $max_price;
        }

        if ($quality_grade) {
            $sql .= " AND p.quality_grade = :quality_grade";
            $params[':quality_grade'] = $quality_grade;
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get product by ID (for buyer view)
     */
    public function getById($product_id) {
        $sql = "SELECT p.*, u.full_name AS farmer_name, u.district, u.upazila, u.phone_number AS farmer_phone
                FROM products p
                JOIN users u ON u.user_id = p.farmer_id
                WHERE p.product_id = :pid AND p.is_deleted = 0 AND p.status = 'ACTIVE'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pid' => $product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get count of products matching search criteria
     */
    public function getSearchCount($search_term = '', $category_id = null, $district = null, $min_price = null, $max_price = null, $quality_grade = null) {
        $sql = "SELECT COUNT(*) as total
                FROM products p
                JOIN users u ON u.user_id = p.farmer_id
                WHERE p.is_deleted = 0 AND p.status = 'ACTIVE'";

        $params = [];

        if (!empty($search_term)) {
            $sql .= " AND (p.title LIKE :search OR p.description LIKE :search)";
            $params[':search'] = '%' . $search_term . '%';
        }

        if ($category_id) {
            $sql .= " AND p.category_id = :category_id";
            $params[':category_id'] = $category_id;
        }

        if ($district) {
            $sql .= " AND u.district = :district";
            $params[':district'] = $district;
        }

        if ($min_price !== null) {
            $sql .= " AND p.price_per_unit >= :min_price";
            $params[':min_price'] = $min_price;
        }

        if ($max_price !== null) {
            $sql .= " AND p.price_per_unit <= :max_price";
            $params[':max_price'] = $max_price;
        }

        if ($quality_grade) {
            $sql .= " AND p.quality_grade = :quality_grade";
            $params[':quality_grade'] = $quality_grade;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
}
