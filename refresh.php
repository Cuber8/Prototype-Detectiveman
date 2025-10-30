<?php
header('Content-Type: application/json');

// Simple endpoint to confirm refresh action
echo json_encode([
    'success' => true,
    'message' => 'Display cleared successfully',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>