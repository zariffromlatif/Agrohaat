<?php
/**
 * Product Image Model
 * Handles multiple images per product
 */
class ProductImage {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all images for a product
     */
    public function getByProductId($product_id) {
        try {
            $sql = "SELECT * FROM product_images WHERE product_id = :pid ORDER BY display_order ASC, is_primary DESC, created_at ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':pid' => $product_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If table doesn't exist, return empty array
            return [];
        }
    }

    /**
     * Get primary image for a product
     */
    public function getPrimaryImage($product_id) {
        try {
            $sql = "SELECT * FROM product_images WHERE product_id = :pid AND is_primary = 1 LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':pid' => $product_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If no primary image, get first image
            if (!$result) {
                $sql = "SELECT * FROM product_images WHERE product_id = :pid ORDER BY display_order ASC, created_at ASC LIMIT 1";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':pid' => $product_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return $result;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Add image to product
     */
    public function addImage($product_id, $image_url, $is_primary = false, $display_order = 0) {
        try {
            // If this is primary, unset other primary images
            if ($is_primary) {
                $stmt = $this->pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = :pid");
                $stmt->execute([':pid' => $product_id]);
            }
            
            $sql = "INSERT INTO product_images (product_id, image_url, is_primary, display_order) 
                    VALUES (:product_id, :image_url, :is_primary, :display_order)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':product_id' => $product_id,
                ':image_url' => $image_url,
                ':is_primary' => $is_primary ? 1 : 0,
                ':display_order' => $display_order
            ]);
        } catch (PDOException $e) {
            error_log("Error adding product image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete image
     */
    public function deleteImage($image_id, $product_id) {
        try {
            $sql = "DELETE FROM product_images WHERE image_id = :image_id AND product_id = :product_id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':image_id' => $image_id,
                ':product_id' => $product_id
            ]);
        } catch (PDOException $e) {
            error_log("Error deleting product image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Set primary image
     */
    public function setPrimary($image_id, $product_id) {
        try {
            $this->pdo->beginTransaction();
            
            // Unset all primary images for this product
            $stmt = $this->pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = :pid");
            $stmt->execute([':pid' => $product_id]);
            
            // Set this image as primary
            $stmt = $this->pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE image_id = :img_id AND product_id = :pid");
            $stmt->execute([
                ':img_id' => $image_id,
                ':pid' => $product_id
            ]);
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error setting primary image: " . $e->getMessage());
            return false;
        }
    }
}

