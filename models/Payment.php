<?php
/**
 * Payment Model
 * Handles all payment-related database operations
 */
class Payment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all active payment methods
     */
    public function getPaymentMethods($type = null) {
        $sql = "SELECT * FROM payment_methods WHERE is_active = 1";
        $params = [];
        
        if ($type) {
            $sql .= " AND type = :type";
            $params[':type'] = $type;
        }
        
        $sql .= " ORDER BY type, name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get payment method by ID
     */
    public function getPaymentMethodById($method_id) {
        $sql = "SELECT * FROM payment_methods WHERE method_id = :method_id AND is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':method_id' => $method_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all bank accounts
     */
    public function getBankAccounts() {
        $sql = "SELECT * FROM bank_accounts WHERE is_active = 1 ORDER BY bank_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Safely encode payment_details.
     * - If already a JSON string, keep it.
     * - If array/object, encode once.
     */
    private function normalizePaymentDetails($payment_details) {
        if ($payment_details === null || $payment_details === '') {
            return null;
        }

        if (is_string($payment_details)) {
            $trim = trim($payment_details);
            // If it looks like JSON, keep it.
            if (($trim !== '') && ($trim[0] === '{' || $trim[0] === '[')) {
                json_decode($trim);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $trim;
                }
            }
            // Otherwise store as a JSON string wrapper
            return json_encode(['raw' => $payment_details], JSON_UNESCAPED_UNICODE);
        }

        return json_encode($payment_details, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Create a new payment record
     */
    public function createPayment($order_id, $user_id, $method_id, $amount, $payment_details = null) {
        // Get payment method to calculate processing fee
        $method = $this->getPaymentMethodById($method_id);
        if (!$method) {
            throw new Exception('Invalid payment method');
        }

        $processing_fee = ($amount * $method['processing_fee_percentage']) / 100;
        $total_amount = $amount + $processing_fee;

        // Generate reference number
        $reference_number = 'PAY' . date('Ymd') . sprintf('%06d', rand(1, 999999));

        // Extract transaction_id from payment_details if provided
        $transaction_id = null;
        if (is_array($payment_details) && isset($payment_details['transaction_id'])) {
            $transaction_id = $payment_details['transaction_id'];
        }

        $sql = "INSERT INTO payments (order_id, user_id, method_id, amount, processing_fee, total_amount, 
                transaction_id, payment_details, reference_number, status, created_at) 
                VALUES (:order_id, :user_id, :method_id, :amount, :processing_fee, :total_amount, 
                :transaction_id, :payment_details, :reference_number, 'PENDING', NOW())";

        try {
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([
                ':order_id' => $order_id,
                ':user_id' => $user_id,
                ':method_id' => $method_id,
                ':amount' => $amount,
                ':processing_fee' => $processing_fee,
                ':total_amount' => $total_amount,
                ':transaction_id' => $transaction_id,
                ':payment_details' => $this->normalizePaymentDetails($payment_details),
                ':reference_number' => $reference_number
            ]);

            // Return payment_id instead of boolean for better tracking
            if ($success) {
                $payment_id = $this->pdo->lastInsertId();
                error_log("Payment created successfully: payment_id=$payment_id, order_id=$order_id, user_id=$user_id, method_id=$method_id");
                return $payment_id;
            } else {
                $error_info = $stmt->errorInfo();
                error_log("Payment creation failed - SQL Error: " . print_r($error_info, true));
                return false;
            }
        } catch (PDOException $e) {
            error_log("Payment creation PDO Exception: " . $e->getMessage() . " | Code: " . $e->getCode());
            throw $e;
        } catch (Exception $e) {
            error_log("Payment creation Exception: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update payment with transaction details
     */
    public function updatePaymentTransaction($payment_id, $transaction_id, $gateway_transaction_id = null, $gateway_response = null) {
        $sql = "UPDATE payments SET 
                transaction_id = :transaction_id,
                gateway_transaction_id = :gateway_transaction_id,
                gateway_response = :gateway_response,
                status = 'PROCESSING',
                updated_at = NOW()
                WHERE payment_id = :payment_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':payment_id' => $payment_id,
            ':transaction_id' => $transaction_id,
            ':gateway_transaction_id' => $gateway_transaction_id,
            ':gateway_response' => $gateway_response
        ]);
    }

    /**
     * Complete payment
     */
    public function completePayment($payment_id, $notes = null) {
        try {
            // Try COMPLETED first (newer schema)
            $sql = "UPDATE payments SET 
                    status = 'COMPLETED',
                    processed_at = NOW(),
                    notes = :notes,
                    updated_at = NOW()
                    WHERE payment_id = :payment_id";

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':payment_id' => $payment_id,
                ':notes' => $notes
            ]);
            
            // If that failed, try SUCCESS (older schema without processed_at)
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                if ($errorInfo[0] !== '00000') {
                    // Try with SUCCESS status
                    $sql = "UPDATE payments SET 
                            status = 'SUCCESS',
                            notes = :notes
                            WHERE payment_id = :payment_id";
                    
                    $stmt = $this->pdo->prepare($sql);
                    $result = $stmt->execute([
                        ':payment_id' => $payment_id,
                        ':notes' => $notes
                    ]);
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            // If COMPLETED failed, try SUCCESS
            error_log("Payment completion error (trying SUCCESS): " . $e->getMessage());
            try {
                $sql = "UPDATE payments SET 
                        status = 'SUCCESS',
                        notes = :notes
                        WHERE payment_id = :payment_id";
                
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    ':payment_id' => $payment_id,
                    ':notes' => $notes
                ]);
            } catch (PDOException $e2) {
                error_log("Payment completion failed: " . $e2->getMessage());
                return false;
            }
        }
    }

    /**
     * Fail payment
     */
    public function failPayment($payment_id, $notes = null) {
        $sql = "UPDATE payments SET 
                status = 'FAILED',
                notes = :notes,
                updated_at = NOW()
                WHERE payment_id = :payment_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':payment_id' => $payment_id,
            ':notes' => $notes
        ]);
    }

    /**
     * Get payment by ID
     */
    public function getPaymentById($payment_id) {
        $sql = "SELECT p.*, pm.name as method_name, pm.type as method_type, pm.provider
                FROM payments p
                JOIN payment_methods pm ON p.method_id = pm.method_id
                WHERE p.payment_id = :payment_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':payment_id' => $payment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get payments for an order
     */
    public function getOrderPayments($order_id) {
        $sql = "SELECT p.*, pm.name as method_name, pm.type as method_type, pm.provider
                FROM payments p
                JOIN payment_methods pm ON p.method_id = pm.method_id
                WHERE p.order_id = :order_id
                ORDER BY p.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':order_id' => $order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user payments
     */
    public function getUserPayments($user_id, $limit = 50) {
        $sql = "SELECT p.*, pm.name as method_name, pm.type as method_type, pm.provider, o.order_id
                FROM payments p
                JOIN payment_methods pm ON p.method_id = pm.method_id
                JOIN orders o ON p.order_id = o.order_id
                WHERE p.user_id = :user_id
                ORDER BY p.created_at DESC
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Process card payment (mock implementation)
     */
    public function processCardPayment($payment_id, $card_details) {
        // In a real implementation, you would integrate with a payment gateway
        // like SSL Commerz, Stripe, or local Bangladeshi gateways
        
        // Mock processing
        $success = rand(1, 10) > 2; // 80% success rate for demo
        
        if ($success) {
            $gateway_transaction_id = 'TXN' . time() . rand(1000, 9999);
            $this->updatePaymentTransaction($payment_id, $gateway_transaction_id, $gateway_transaction_id, 'Payment successful');
            $this->completePayment($payment_id, 'Card payment processed successfully');
            return ['success' => true, 'transaction_id' => $gateway_transaction_id];
        } else {
            $this->failPayment($payment_id, 'Card payment failed - insufficient funds or invalid card');
            return ['success' => false, 'message' => 'Card payment failed'];
        }
    }

    /**
     * Verify mobile banking payment
     */
    public function verifyMobileBankingPayment($payment_id, $transaction_id, $sender_number) {
        // In a real implementation, you would verify with the mobile banking API
        // For now, we'll accept any transaction ID that looks valid
        
        if (strlen($transaction_id) >= 8) {
            $this->updatePaymentTransaction($payment_id, $transaction_id);
            $this->completePayment($payment_id, "Mobile banking payment verified. Sender: $sender_number");
            return ['success' => true, 'message' => 'Payment verified successfully'];
        } else {
            $this->failPayment($payment_id, 'Invalid transaction ID');
            return ['success' => false, 'message' => 'Invalid transaction ID'];
        }
    }

    /**
     * Process bank transfer
     */
    public function processBankTransfer($payment_id, $bank_details) {
        // Bank transfers are usually manual verification
        // Set to processing and wait for admin verification
        $details = [
            'bank_name' => $bank_details['bank_name'],
            'account_number' => $bank_details['account_number'],
            'transaction_date' => $bank_details['transaction_date'],
            'reference' => $bank_details['reference']
        ];
        
        $this->updatePaymentTransaction($payment_id, $bank_details['reference'], null, json_encode($details));
        
        // Keep status as PROCESSING for manual verification
        $sql = "UPDATE payments SET status = 'PROCESSING', updated_at = NOW() WHERE payment_id = :payment_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':payment_id' => $payment_id]);
        
        return ['success' => true, 'message' => 'Bank transfer details submitted for verification'];
    }
}
