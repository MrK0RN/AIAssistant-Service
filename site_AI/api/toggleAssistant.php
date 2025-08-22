<?php

// Include database functions

include_once "../system/pg.php";

// Get parameters
$hash = $_GET['hash'] ?? '';
$assistant_id = $_GET['aid'] ?? '';

// Validate parameters
if (empty($hash) || empty($assistant_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing hash or assistant ID']);
    exit;
}

// Validate hash and get user
$exists = pgQuery("SELECT * FROM accounts WHERE hash = '$hash'");
if (count($exists) === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid authentication']);
    exit;
}
$user_id = $exists[0]["id"];

// Validate assistant ID is numeric
if (!is_numeric($assistant_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid assistant ID']);
    exit;
}


try {
    // First, verify the assistant belongs to the authenticated user
    $assistant = pgQuery("SELECT * FROM assistant WHERE id = $assistant_id AND user_id = $user_id");

    if (empty($assistant)) {
        http_response_code(404);
        echo json_encode(['error' => 'Assistant not found']);
        exit;
    }

    $current_status = $assistant[0]['status'];

    // Toggle status
    $new_status = ($current_status === 'active') ? 'inactive' : 'active';

    // Update status in database
    $update_result = pgQuery("UPDATE assistant SET status = '$new_status' WHERE id = $assistant_id AND user_id = $user_id");

    if ($update_result) {
        error_log("Assistant status toggled: ID $assistant_id from '$current_status' to '$new_status' by user $user_id");

        echo json_encode([
            'success' => true,
            'message' => 'Assistant status updated successfully',
            'new_status' => $new_status,
            'assistant_id' => $assistant_id
        ]);
    } else {
        throw new Exception('Failed to update assistant status');
    }

} catch (Exception $e) {
    error_log("Error toggling assistant status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to toggle assistant status']);
}
?>