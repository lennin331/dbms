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
$error = '';
$success = '';

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($email)) {
        $error = 'Email is required';
    } else {
        // Check if email exists for another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        if ($result->fetchArray()) {
            $error = 'Email already exists';
        } else {
            // Update email
            $stmt = $conn->prepare("UPDATE users SET email = :email WHERE id = :id");
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
            $stmt->execute();
            
            // Update password if provided
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $error = 'Current password is required to change password';
                } elseif (!password_verify($current_password, $user['password'])) {
                    $error = 'Current password is incorrect';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match';
                } elseif (strlen($new_password) < 6) {
                    $error = 'New password must be at least 6 characters';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
                    $stmt->bindValue(':password', $hashed_password, SQLITE3_TEXT);
                    $stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
                    $stmt->execute();
                    $success = 'Profile updated successfully';
                }
            } else {
                $success = 'Profile updated successfully';
            }
        }
    }
}
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h1 class="card-title">My Profile</h1>
    
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
            <small style="color: #666;">Username cannot be changed</small>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
        </div>
        
        <div class="form-group">
            <label for="current_password">Current Password (required to change password):</label>
            <input type="password" id="current_password" name="current_password">
        </div>
        
        <div class="form-group">
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password">
            <small style="color: #666;">Leave blank to keep current password</small>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password">
        </div>
        
        <div class="form-group">
            <label>Account Created:</label>
            <input type="text" value="<?php echo date('F d, Y', strtotime($user['created_at'])); ?>" disabled>
        </div>
        
        <div class="form-group">
            <label>Role:</label>
            <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">Update Profile</button>
    </form>
</div>

<?php require_once 'footer.php'; ?>