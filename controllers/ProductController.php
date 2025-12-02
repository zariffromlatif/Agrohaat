<?php
require_once __DIR__ . '/../models/Product.php';

class ProductController {

    private $model;

    public function __construct($pdo) {
        $this->model = new Product($pdo);
    }

    /**
     * Create a new product for a farmer, including QR trace URL.
     */
    public function handleCreate($farmer_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // IMAGE UPLOAD (stored under public/uploads)
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . '/../public/uploads/product_images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $fullPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $fullPath)) {
                // Path relative to /public for use with $BASE_URL
                $imagePath = 'uploads/product_images/' . $fileName;
            }
        }

        // TRACE ID + QR URL (on-the-fly QR image via external service)
        $trace_id = uniqid("TRC");
        // BASE_URL is defined in config.php and loaded in the page
        global $BASE_URL;
        $traceUrl = $BASE_URL . 'trace.php?tid=' . urlencode($trace_id);
        $qrUrl    = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($traceUrl);

        $this->model->create([
            ':farmer_id'          => $farmer_id,
            ':category_id'        => $_POST['category_id'],
            ':title'              => $_POST['title'],
            ':description'        => $_POST['description'],
            ':image_url'          => $imagePath,
            ':quantity_available' => $_POST['quantity_available'],
            ':unit'               => $_POST['unit'],
            ':price_per_unit'     => $_POST['price_per_unit'],
            ':quality_grade'      => $_POST['quality_grade'],
            ':harvest_date'       => $_POST['harvest_date'],
            ':batch_number'       => $_POST['batch_number'],
            ':trace_id'           => $trace_id,
            ':qr_code_url'        => $qrUrl,
        ]);

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
