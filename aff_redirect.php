<?php
include_once 'config.php';

if (isset($_GET['code'])) {
    $affiliate_code = $_GET['code'];
    
    // Fetch affiliate link and product
    $stmt = $conn->prepare('SELECT al.user_id, ap.original_url 
                            FROM affiliate_links al 
                            JOIN affiliate_products ap ON al.product_id = ap.id 
                            WHERE al.affiliate_code = ?');
    $stmt->bind_param('s', $affiliate_code);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result) {
        // Log click (for future analytics)
        // Redirect to original product URL
        header('Location: ' . $result['original_url']);
        exit;
    }
}

// Fallback if invalid code
header('Location: index.php');
exit;
?>