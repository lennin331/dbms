<?php
require_once 'header.php';

$message_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // In a real application, you would send an email here
    // For this example, we'll just show a success message
    $message_sent = true;
}
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <h1 class="card-title">Contact Us</h1>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div>
            <h2 style="color: #333; margin-bottom: 1rem;">Get in Touch</h2>
            <p style="color: #666; line-height: 1.6; margin-bottom: 1rem;">
                Have questions about our products or services? Need help with your order? 
                Our team is here to assist you. Fill out the form and we'll get back to you as soon as possible.
            </p>
            
            <div style="margin-top: 2rem;">
                <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                    <span style="font-size: 1.5rem; color: #667eea; margin-right: 1rem;">📍</span>
                    <div>
                        <h3 style="color: #333;">Address</h3>
                        <p style="color: #666;">123 Healthcare Avenue, Medical District, City, ST 12345</p>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                    <span style="font-size: 1.5rem; color: #667eea; margin-right: 1rem;">📞</span>
                    <div>
                        <h3 style="color: #333;">Phone</h3>
                        <p style="color: #666;">+1 (555) 123-4567</p>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                    <span style="font-size: 1.5rem; color: #667eea; margin-right: 1rem;">✉️</span>
                    <div>
                        <h3 style="color: #333;">Email</h3>
                        <p style="color: #666;">support@pharmacare.com</p>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center;">
                    <span style="font-size: 1.5rem; color: #667eea; margin-right: 1rem;">🕒</span>
                    <div>
                        <h3 style="color: #333;">Business Hours</h3>
                        <p style="color: #666;">Monday - Friday: 9:00 AM - 6:00 PM</p>
                        <p style="color: #666;">Saturday: 10:00 AM - 4:00 PM</p>
                        <p style="color: #666;">Sunday: Closed</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div>
            <?php if($message_sent): ?>
                <div class="alert alert-success">
                    Thank you for contacting us! We'll get back to you within 24 hours.
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Your Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Your Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Send Message</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>