<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductImage.php';

class ProductController {

    private $model;
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->model = new Product($pdo);
    }

    /**
     * Create a new product for a farmer, including QR trace URL.
     */
    public function handleCreate($farmer_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // MULTIPLE IMAGE UPLOAD (stored under public/uploads)
        $imagePath = null; // For backward compatibility (primary image)
        $uploadedImages = [];
        
        // Handle multiple image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = __DIR__ . '/../public/uploads/product_images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB per image
            
            // Process each uploaded file
            $fileCount = count($_FILES['images']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    // Validate file type
                    $fileType = $_FILES['images']['type'][$i];
                    if (!in_array($fileType, $allowedTypes)) {
                        continue; // Skip invalid file types
                    }
                    
                    // Validate file size
                    if ($_FILES['images']['size'][$i] > $maxSize) {
                        continue; // Skip files that are too large
                    }
                    
                    $fileName = time() . '_' . $i . '_' . basename($_FILES['images']['name'][$i]);
                    $fullPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $fullPath)) {
                        $relativePath = 'uploads/product_images/' . $fileName;
                        $uploadedImages[] = $relativePath;
                        
                        // Set first image as primary (for backward compatibility)
                        if ($imagePath === null) {
                            $imagePath = $relativePath;
                        }
                    }
                }
            }
        }
        
        // Also handle single image upload (for backward compatibility)
        if ($imagePath === null && !empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . '/../public/uploads/product_images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $fullPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $fullPath)) {
                $imagePath = 'uploads/product_images/' . $fileName;
                $uploadedImages[] = $imagePath;
            }
        }

        // Generate trace_id
        $trace_id = 'TRACE_' . time() . '_' . uniqid();
        
        // Generate QR code URL using trace_id
        global $BASE_URL;
        $trace_url = $BASE_URL . 'trace.php?trace_id=' . urlencode($trace_id);
        $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($trace_url);

        $this->model->create([
            ':farmer_id'          => $farmer_id,
            ':category_id'        => $_POST['category_id'],
            ':title'              => $_POST['title'],
            ':description'        => $_POST['description'],
            ':image_url'          => $imagePath, // Primary image for backward compatibility
            ':quantity_available' => $_POST['quantity_available'],
            ':unit'               => $_POST['unit'],
            ':price_per_unit'     => $_POST['price_per_unit'],
            ':quality_grade'      => $_POST['quality_grade'],
            ':harvest_date'       => $_POST['harvest_date'],
            ':batch_number'       => $_POST['batch_number'],
            ':trace_id'           => $trace_id,
            ':qr_code_url'        => $qr_code_url,
        ]);

        // Get the created product ID
        $product_id = $this->pdo->lastInsertId();
        
        // Save multiple images to product_images table
        if (!empty($uploadedImages) && $product_id) {
            try {
                $imageModel = new ProductImage($this->pdo);
                foreach ($uploadedImages as $index => $imageUrl) {
                    $isPrimary = ($index === 0); // First image is primary
                    $result = $imageModel->addImage($product_id, $imageUrl, $isPrimary, $index);
                    if (!$result) {
                        error_log("Failed to save image to product_images: product_id=$product_id, image_url=$imageUrl");
                    }
                }
            } catch (Exception $e) {
                error_log("Error saving images to product_images table: " . $e->getMessage());
                // Don't fail product creation if image save fails
            }
        }

        header("Location: products.php?created=1");
        exit;
    }

    public function getProductsForFarmer($farmer_id) {
        return $this->model->getByFarmer($farmer_id);
    }

    public function getProductForFarmer($farmer_id, $product_id) {
        return $this->model->find($product_id, $farmer_id);
    }

    public function handleUpdate($farmer_id, $product_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // Optional: new image upload
        $data = [
            ':category_id'        => $_POST['category_id'],
            ':title'              => $_POST['title'],
            ':description'        => $_POST['description'],
            ':quantity_available' => $_POST['quantity_available'],
            ':unit'               => $_POST['unit'],
            ':price_per_unit'     => $_POST['price_per_unit'],
            ':quality_grade'      => $_POST['quality_grade'],
            ':harvest_date'       => $_POST['harvest_date'],
            ':batch_number'       => $_POST['batch_number'],
        ];

        $this->model->update($product_id, $farmer_id, $data);

        header("Location: products.php?updated=1");
        exit;
    }

    public function handleDelete($farmer_id, $product_id) {
        $this->model->delete($product_id, $farmer_id);
        header("Location: products.php?deleted=1");
        exit;
    }
}
