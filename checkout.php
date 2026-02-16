<?php
require_once 'header.php';
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Initialize cart if not exists
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

// Get cart items
$cart_items = [];
$total = 0;
$prescription_required = false;
$out_of_stock = [];

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $result = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $row['cart_quantity'] = $_SESSION['cart'][$row['id']];
        
        // Check if enough stock
        if ($row['cart_quantity'] > $row['stock']) {
            $out_of_stock[] = $row['name'];
        }
        
        $row['subtotal'] = $row['price'] * $row['cart_quantity'];
        $total += $row['subtotal'];
        $cart_items[] = $row;
        
        if ($row['prescription_required'] == 1) {
            $prescription_required = true;
        }
    }
}

// Redirect if any items are out of stock
if (!empty($out_of_stock)) {
    $_SESSION['checkout_error'] = "Some items in your cart don't have enough stock: " . implode(', ', $out_of_stock);
    header('Location: cart.php');
    exit;
}

$error = '';
$success = '';
$order_placed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    $card_number = str_replace(' ', '', $_POST['card_number'] ?? '');
    $card_name = trim($_POST['card_name'] ?? '');
    $card_expiry = $_POST['card_expiry'] ?? '';
    $card_cvv = $_POST['card_cvv'] ?? '';
    $upi_id = trim($_POST['upi_id'] ?? '');
    
    // Validate based on payment method
    if (empty($shipping_address)) {
        $error = 'Shipping address is required';
    } elseif (empty($payment_method)) {
        $error = 'Please select a payment method';
    } elseif ($payment_method === 'card') {
        if (empty($card_number) || strlen($card_number) < 16) {
            $error = 'Valid card number is required';
        } elseif (empty($card_name)) {
            $error = 'Cardholder name is required';
        } elseif (empty($card_expiry) || !preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) {
            $error = 'Valid expiry date (MM/YY) is required';
        } elseif (empty($card_cvv) || strlen($card_cvv) < 3) {
            $error = 'Valid CVV is required';
        }
    } elseif ($payment_method === 'upi') {
        if (empty($upi_id) || !preg_match('/^[\w\.\-]+@[\w\.\-]+$/', $upi_id)) {
            $error = 'Valid UPI ID is required (e.g., name@bank)';
        }
    } elseif ($payment_method === 'cod') {
        // Cash on delivery - no additional validation needed
    }
    
    if (empty($error)) {
        // Begin transaction
        $conn->exec('BEGIN TRANSACTION');
        
        try {
            // Calculate final total with tax
            $tax = $total * 0.1;
            $cod_fee = ($payment_method === 'cod') ? 2 : 0;
            $final_total = $total + $tax + $cod_fee;
            
            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method) VALUES (:user_id, :total, 'pending', :address, :payment_method)");
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(':total', $final_total, SQLITE3_FLOAT);
            $stmt->bindValue(':address', $shipping_address, SQLITE3_TEXT);
            $stmt->bindValue(':payment_method', $payment_method, SQLITE3_TEXT);
            $stmt->execute();
            
            $order_id = $conn->lastInsertRowID();
            
            // Add order items and update stock
            foreach ($cart_items as $item) {
                // Add order item
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)");
                $stmt->bindValue(':order_id', $order_id, SQLITE3_INTEGER);
                $stmt->bindValue(':product_id', $item['id'], SQLITE3_INTEGER);
                $stmt->bindValue(':quantity', $item['cart_quantity'], SQLITE3_INTEGER);
                $stmt->bindValue(':price', $item['price'], SQLITE3_FLOAT);
                $stmt->execute();
                
                // Update stock
                $new_stock = $item['stock'] - $item['cart_quantity'];
                $stmt = $conn->prepare("UPDATE products SET stock = :stock WHERE id = :id");
                $stmt->bindValue(':stock', $new_stock, SQLITE3_INTEGER);
                $stmt->bindValue(':id', $item['id'], SQLITE3_INTEGER);
                $stmt->execute();
            }
            
            // Handle prescription uploads if any
            if ($prescription_required && isset($_FILES['prescriptions'])) {
                $upload_dir = 'prescriptions/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if (isset($_FILES['prescriptions']['tmp_name']) && is_array($_FILES['prescriptions']['tmp_name'])) {
                    foreach ($_FILES['prescriptions']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['prescriptions']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_name = time() . '_' . $_FILES['prescriptions']['name'][$key];
                            $file_path = $upload_dir . $file_name;
                            
                            if (move_uploaded_file($tmp_name, $file_path)) {
                                $product_id = $_POST['prescription_product'][$key] ?? 0;
                                $doctor_name = trim($_POST['doctor_name'][$key] ?? '');
                                $prescription_date = $_POST['prescription_date'][$key] ?? date('Y-m-d');
                                
                                $stmt = $conn->prepare("INSERT INTO prescriptions (user_id, product_id, prescription_file, doctor_name, prescription_date, status) VALUES (:user_id, :product_id, :file, :doctor, :date, 'pending')");
                                $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
                                $stmt->bindValue(':product_id', $product_id, SQLITE3_INTEGER);
                                $stmt->bindValue(':file', $file_path, SQLITE3_TEXT);
                                $stmt->bindValue(':doctor', $doctor_name, SQLITE3_TEXT);
                                $stmt->bindValue(':date', $prescription_date, SQLITE3_TEXT);
                                $stmt->execute();
                            }
                        }
                    }
                }
            }
            
            $conn->exec('COMMIT');
            
            // Clear the cart
            unset($_SESSION['cart']);
            
            // Set success message and order placed flag
            $order_placed = true;
            $success = 'Your order has been placed successfully!';
            
            ?>
            <!-- Show success message and redirect after 3 seconds -->
            <div class="card" style="max-width: 600px; margin: 2rem auto; text-align: center;">
                <div style="font-size: 5rem; color: #48bb78; margin-bottom: 1rem;">✓</div>
                <h1 style="color: #333; margin-bottom: 1rem;">Order Successful!</h1>
                <p style="color: #666; font-size: 1.1rem; margin-bottom: 2rem;">
                    <?php echo htmlspecialchars($success); ?><br>
                    Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?>
                </p>
                <p style="color: #666; margin-bottom: 2rem;">Redirecting to products page in <span id="countdown">3</span> seconds...</p>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <a href="product.php" class="btn btn-primary">Go to Products Now</a>
                    <a href="orders.php" class="btn btn-secondary">View My Orders</a>
                </div>
            </div>
            
            <script>
            // Countdown and redirect
            let seconds = 3;
            const countdownEl = document.getElementById('countdown');
            
            const interval = setInterval(function() {
                seconds--;
                if (countdownEl) {
                    countdownEl.textContent = seconds;
                }
                
                if (seconds <= 0) {
                    clearInterval(interval);
                    window.location.href = 'categories.php';
                }
            }, 1000);
            </script>
            
            <?php
            require_once 'footer.php';
            exit;
            
        } catch (Exception $e) {
            $conn->exec('ROLLBACK');
            $error = 'An error occurred while processing your order. Please try again.';
        }
    }
}

// Only show the checkout form if order hasn't been placed
if (!$order_placed):
?>

<div class="card">
    <h1 class="card-title">Checkout</h1>
    
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- Progress Steps -->
    <div style="display: flex; justify-content: space-between; margin-bottom: 2rem; padding: 1rem; background: #f7fafc; border-radius: 10px;">
        <div style="text-align: center; flex: 1;">
            <div style="width: 30px; height: 30px; background: #667eea; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem;">1</div>
            <div style="font-weight: bold; color: #667eea;">Cart</div>
        </div>
        <div style="text-align: center; flex: 1;">
            <div style="width: 30px; height: 30px; background: #667eea; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem;">2</div>
            <div style="font-weight: bold; color: #667eea;">Checkout</div>
        </div>
        <div style="text-align: center; flex: 1;">
            <div style="width: 30px; height: 30px; background: #48bb78; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem;">✓</div>
            <div style="color: #48bb78; font-weight: bold;">Success</div>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- Checkout Form -->
        <div>
            <form method="POST" action="" enctype="multipart/form-data" id="checkout-form">
                <!-- Shipping Information -->
                <div style="background: #f7fafc; padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem;">
                    <h2 style="color: #333; margin-bottom: 1rem;">Shipping Information</h2>
                    
                    <div class="form-group">
                        <label for="shipping_address">Shipping Address:</label>
                        <textarea id="shipping_address" name="shipping_address" rows="4" required placeholder="Enter your complete shipping address including street, city, state, and pincode"><?php echo htmlspecialchars($_POST['shipping_address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div style="margin-top: 1rem; padding: 0.75rem; background: #e6f7ff; border-radius: 5px; font-size: 0.9rem;">
                        <strong style="color: #0066cc;">✓</strong> We deliver to all locations. Estimated delivery: 3-5 business days.
                    </div>
                </div>
                
                <!-- Prescription Upload (if needed) -->
                <?php if($prescription_required): ?>
                    <div style="background: #f7fafc; padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem;">
                        <h2 style="color: #333; margin-bottom: 1rem;">Upload Prescriptions</h2>
                        <div class="alert alert-info" style="margin-bottom: 1rem;">
                            <strong>Prescription Required:</strong> The following items require a valid prescription. Please upload clear images of your prescriptions. Our pharmacists will verify them before processing your order.
                        </div>
                        
                        <?php foreach($cart_items as $index => $item): ?>
                            <?php if($item['prescription_required']): ?>
                                <div style="border: 2px solid #ecc94b; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; background: white;">
                                    <h3 style="color: #333; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p style="color: #666; margin-bottom: 0.5rem;">Quantity: <?php echo $item['cart_quantity']; ?></p>
                                    
                                    <input type="hidden" name="prescription_product[]" value="<?php echo $item['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label>Prescription Image/PDF:</label>
                                        <input type="file" name="prescriptions[]" accept=".jpg,.jpeg,.png,.pdf" required style="padding: 0.5rem; border: 1px dashed #667eea; width: 100%;">
                                        <small style="color: #666;">Accepted formats: JPG, PNG, PDF (Max size: 5MB)</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Doctor's Name:</label>
                                        <input type="text" name="doctor_name[]" placeholder="Dr. John Smith" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Prescription Date:</label>
                                        <input type="date" name="prescription_date[]" required max="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <div style="margin-top: 1rem; padding: 0.75rem; background: #fff3cd; border-radius: 5px; font-size: 0.9rem; color: #856404;">
                            <strong>Note:</strong> Your order will be held until prescriptions are verified. You'll receive an email once verified.
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Payment Methods -->
                <div style="background: #f7fafc; padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem;">
                    <h2 style="color: #333; margin-bottom: 1rem;">Payment Method</h2>
                    
                    <!-- Card Payment -->
                    <div style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; padding: 1rem; background: <?php echo ($_POST['payment_method'] ?? '') === 'card' ? '#e6e6ff' : 'white'; ?>; border-radius: 10px; border: 1px solid #ddd; cursor: pointer;">
                            <input type="radio" name="payment_method" value="card" <?php echo ($_POST['payment_method'] ?? '') === 'card' ? 'checked' : ''; ?> onclick="togglePaymentFields()">
                            <span style="font-weight: 500; flex: 1;">Credit / Debit Card</span>
                            <span style="display: flex; gap: 0.25rem;">
                                <span style="font-size: 1.5rem;">💳</span>
                                <span style="font-size: 1.5rem;">💳</span>
                            </span>
                        </label>
                        
                        <div id="card-fields" style="display: <?php echo ($_POST['payment_method'] ?? '') === 'card' ? 'block' : 'none'; ?>; margin-left: 1.5rem; padding: 1.5rem; background: white; border-radius: 10px; margin-top: 0.5rem;">
                            <div class="form-group">
                                <label for="card_number">Card Number:</label>
                                <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>" maxlength="19">
                            </div>
                            
                            <div class="form-group">
                                <label for="card_name">Cardholder Name:</label>
                                <input type="text" id="card_name" name="card_name" placeholder="John Doe" value="<?php echo htmlspecialchars($_POST['card_name'] ?? ''); ?>">
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label for="card_expiry">Expiry Date:</label>
                                    <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY" value="<?php echo htmlspecialchars($_POST['card_expiry'] ?? ''); ?>" maxlength="5">
                                </div>
                                
                                <div class="form-group">
                                    <label for="card_cvv">CVV:</label>
                                    <input type="password" id="card_cvv" name="card_cvv" placeholder="123" value="<?php echo htmlspecialchars($_POST['card_cvv'] ?? ''); ?>" maxlength="3">
                                </div>
                            </div>
                            
                            <div style="margin-top: 1rem; padding: 1rem; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; gap: 0.5rem;">
                                <span style="font-size: 1.2rem;">🔒</span>
                                <small style="color: #0066cc;">Your card information is secure and encrypted</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- UPI Payment -->
                    <div style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; padding: 1rem; background: <?php echo ($_POST['payment_method'] ?? '') === 'upi' ? '#e6e6ff' : 'white'; ?>; border-radius: 10px; border: 1px solid #ddd; cursor: pointer;">
                            <input type="radio" name="payment_method" value="upi" <?php echo ($_POST['payment_method'] ?? '') === 'upi' ? 'checked' : ''; ?> onclick="togglePaymentFields()">
                            <span style="font-weight: 500; flex: 1;">UPI</span>
                            <span style="display: flex; gap: 0.25rem;">
                                <span style="font-size: 1.5rem;">📱</span>
                                <span style="font-size: 1.5rem;">📲</span>
                            </span>
                        </label>
                        
                        <div id="upi-fields" style="display: <?php echo ($_POST['payment_method'] ?? '') === 'upi' ? 'block' : 'none'; ?>; margin-left: 1.5rem; padding: 1.5rem; background: white; border-radius: 10px; margin-top: 0.5rem;">
                            <div class="form-group">
                                <label for="upi_id">UPI ID:</label>
                                <input type="text" id="upi_id" name="upi_id" placeholder="name@bank" value="<?php echo htmlspecialchars($_POST['upi_id'] ?? ''); ?>">
                                <small style="color: #666;">Enter your UPI ID (e.g., name@okhdfcbank, name@paytm, name@ybl)</small>
                            </div>
                            
                            <div style="margin-top: 1rem; padding: 1rem; background: #f0f0f0; border-radius: 5px;">
                                <small><strong>Supported UPI Apps:</strong> Google Pay, PhonePe, Paytm, BHIM, Amazon Pay</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cash on Delivery -->
                    <div style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 1rem; background: <?php echo ($_POST['payment_method'] ?? '') === 'cod' ? '#e6e6ff' : 'white'; ?>; border-radius: 10px; border: 1px solid #ddd; cursor: pointer;">
                            <input type="radio" name="payment_method" value="cod" <?php echo ($_POST['payment_method'] ?? '') === 'cod' ? 'checked' : ''; ?> onclick="togglePaymentFields(); updateTotalDisplay();">
                            <span style="font-weight: 500; flex: 1;">Cash on Delivery</span>
                            <span style="font-size: 1.5rem;">💵</span>
                        </label>
                        <p style="color: #666; font-size: 0.9rem; margin-left: 2rem; margin-top: 0.25rem;">Pay with cash when your order arrives. Additional $2 COD fee may apply.</p>
                    </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9f9f9; border-radius: 5px;">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="terms" name="terms" required>
                        <span style="color: #666;">I agree to the <a href="terms.php" target="_blank" style="color: #667eea;">Terms and Conditions</a> and <a href="privacy.php" target="_blank" style="color: #667eea;">Privacy Policy</a></span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">Place Order</button>
            </form>
        </div>
        
        <!-- Order Summary -->
        <div>
            <div style="background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 3px 10px rgba(0,0,0,0.1); position: sticky; top: 1rem;">
                <h2 style="color: #333; margin-bottom: 1rem; border-bottom: 2px solid #667eea; padding-bottom: 0.5rem;">Order Summary</h2>
                
                <div id="order-items">
                    <?php foreach($cart_items as $item): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #eee;">
                            <div style="flex: 2;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($item['name']); ?></div>
                                <small style="color: #666;">Qty: <?php echo $item['cart_quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></small>
                                <?php if($item['prescription_required']): ?>
                                    <div style="color: #f56565; font-size: 0.75rem; margin-top: 0.25rem;">⚠️ Prescription Required</div>
                                <?php endif; ?>
                            </div>
                            <div style="font-weight: 500;">$<?php echo number_format($item['subtotal'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 1rem;" id="total-summary">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Shipping:</span>
                        <span style="color: #48bb78;">Free</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Tax (10%):</span>
                        <span>$<?php echo number_format($total * 0.1, 2); ?></span>
                    </div>
                    <div id="cod-fee-row" style="display: none; justify-content: space-between; margin-bottom: 0.5rem; color: #f56565;">
                        <span>COD Fee:</span>
                        <span>$2.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold; margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #667eea;">
                        <span>Total:</span>
                        <span id="total-amount" style="color: #667eea;">$<?php echo number_format($total * 1.1, 2); ?></span>
                    </div>
                </div>
                
                <div style="margin-top: 1.5rem; padding: 1rem; background: #f7fafc; border-radius: 5px;">
                    <h3 style="color: #333; margin-bottom: 0.5rem;">Delivery Information</h3>
                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">✓ Free shipping on all orders</p>
                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">✓ Estimated delivery: 3-5 business days</p>
                    <p style="color: #666; font-size: 0.9rem;">✓ Easy returns within 7 days</p>
                </div>
                
                <div style="margin-top: 1rem; text-align: center;">
                    <a href="cart.php" style="color: #667eea; text-decoration: none;">← Edit Cart</a>
                </div>
                
                <!-- Secure Checkout Badge -->
                <div style="margin-top: 1rem; text-align: center; padding: 0.5rem; background: #e6f7ff; border-radius: 5px;">
                    <span style="color: #0066cc;">🔒 Secure Checkout</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Store the base total for calculations
const baseTotal = <?php echo $total; ?>;
const baseTotalWithTax = <?php echo $total * 1.1; ?>;

function togglePaymentFields() {
    const cardFields = document.getElementById('card-fields');
    const upiFields = document.getElementById('upi-fields');
    const cardRadio = document.querySelector('input[value="card"]');
    const upiRadio = document.querySelector('input[value="upi"]');
    
    if (cardRadio && cardRadio.checked) {
        cardFields.style.display = 'block';
        if (upiFields) upiFields.style.display = 'none';
    } else if (upiRadio && upiRadio.checked) {
        if (cardFields) cardFields.style.display = 'none';
        upiFields.style.display = 'block';
    } else {
        if (cardFields) cardFields.style.display = 'none';
        if (upiFields) upiFields.style.display = 'none';
    }
}

function updateTotalDisplay() {
    const codRadio = document.querySelector('input[value="cod"]');
    const codFeeRow = document.getElementById('cod-fee-row');
    const totalAmount = document.getElementById('total-amount');
    
    if (codRadio && codRadio.checked) {
        // Show COD fee and add to total
        codFeeRow.style.display = 'flex';
        totalAmount.textContent = '$' + (baseTotalWithTax + 2).toFixed(2);
    } else {
        // Hide COD fee and use base total
        codFeeRow.style.display = 'none';
        totalAmount.textContent = '$' + baseTotalWithTax.toFixed(2);
    }
}

// Format card number with spaces
document.getElementById('card_number')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
    if (value.length > 0) {
        // Add space every 4 digits
        value = value.match(new RegExp('.{1,4}', 'g'))?.join(' ') || value;
    }
    e.target.value = value;
});

// Format expiry date
document.getElementById('card_expiry')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    e.target.value = value;
});

// Validate CVV (only numbers)
document.getElementById('card_cvv')?.addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g, '');
});

// Auto-hide payment fields on page load
document.addEventListener('DOMContentLoaded', function() {
    togglePaymentFields();
    updateTotalDisplay();
    
    // Set minimum date for prescription date
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    dateInputs.forEach(input => {
        input.max = today;
    });
    
    // Add event listeners to all payment method radios
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            updateTotalDisplay();
        });
    });
});

// Form validation before submit
document.getElementById('checkout-form')?.addEventListener('submit', function(e) {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    const terms = document.getElementById('terms');
    
    if (!terms.checked) {
        e.preventDefault();
        alert('Please agree to the Terms and Conditions');
        return false;
    }
    
    if (!paymentMethod) {
        e.preventDefault();
        alert('Please select a payment method');
        return false;
    }
    
    if (paymentMethod.value === 'card') {
        const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
        const cardExpiry = document.getElementById('card_expiry').value;
        const cardCvv = document.getElementById('card_cvv').value;
        
        if (cardNumber.length !== 16) {
            e.preventDefault();
            alert('Please enter a valid 16-digit card number');
            return false;
        }
        
        if (!cardExpiry.match(/^\d{2}\/\d{2}$/)) {
            e.preventDefault();
            alert('Please enter a valid expiry date (MM/YY)');
            return false;
        }
        
        // Check if card is not expired
        const [month, year] = cardExpiry.split('/');
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear() % 100;
        const currentMonth = currentDate.getMonth() + 1;
        
        if (parseInt(year) < currentYear || (parseInt(year) === currentYear && parseInt(month) < currentMonth)) {
            e.preventDefault();
            alert('Card has expired');
            return false;
        }
        
        if (cardCvv.length !== 3) {
            e.preventDefault();
            alert('Please enter a valid 3-digit CVV');
            return false;
        }
    }
    
    if (paymentMethod.value === 'upi') {
        const upiId = document.getElementById('upi_id').value;
        if (!upiId.match(/^[\w\.\-]+@[\w\.\-]+$/)) {
            e.preventDefault();
            alert('Please enter a valid UPI ID (e.g., name@bank)');
            return false;
        }
    }
    
    return true;
});
</script>

<style>
/* Additional styles for checkout page */
#card-fields, #upi-fields {
    transition: all 0.3s ease;
}

input[type="radio"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.form-group small {
    display: block;
    margin-top: 0.25rem;
}

/* Payment method hover effect */
label:hover {
    background: #f0f0f0 !important;
    transition: background 0.3s ease;
}

/* File input styling */
input[type="file"] {
    padding: 0.5rem;
    border: 1px dashed #667eea;
    border-radius: 5px;
    width: 100%;
    cursor: pointer;
}

input[type="file"]:hover {
    background: #f7fafc;
}

/* Sticky order summary */
@media (max-width: 768px) {
    div[style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    
    .sticky {
        position: relative !important;
        top: 0 !important;
    }
    
    /* Progress steps for mobile */
    div[style*="justify-content: space-between"] {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
}
</style>

<?php 
endif; // End of checkout form
require_once 'footer.php'; 
?>
