<?php
session_start();
include("connection.php");

// Redirect to login if user is not authenticated as business owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    header("Location: index.php");
    exit();
}

$ownerId = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

// Handle different types of updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Update basic profile information
    if (isset($_POST['fullName']) && isset($_POST['phoneNumber']) && isset($_POST['companyName'])) {
        $ownerName = trim($_POST['fullName']);
        $phoneNumber = '0' . substr($_POST['phoneNumber'], 0, 2) . '-' . substr($_POST['phoneNumber'], 2);
        $companyName = trim($_POST['companyName']);
        
        // Validate inputs
        if (empty($ownerName) || empty($phoneNumber) || empty($companyName)) {
            $response['message'] = 'All fields are required';
        } else {
            // Update in database
            $updateQuery = "UPDATE business_owners SET owner_name = ?, phone_number = ?, company_name = ? WHERE owner_id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("sssi", $ownerName, $phoneNumber, $companyName, $ownerId);
            
            if ($stmt->execute()) {
                // Update session variables
                $_SESSION['user_name'] = $ownerName;
                $_SESSION['company_name'] = $companyName;
                
                $response['success'] = true;
                $response['message'] = 'Profile updated successfully';
            } else {
                $response['message'] = 'Error updating profile: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    // Update email
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_email') {
        $currentPassword = $_POST['current_password'];
        $newEmail = $_POST['new_email'];
        
        // Verify current password
        $verifyQuery = "SELECT password FROM business_owners WHERE owner_id = ?";
        $stmt = $conn->prepare($verifyQuery);
        $stmt->bind_param("i", $ownerId);
        $stmt->execute();
        $stmt->bind_result($dbPassword);
        $stmt->fetch();
        $stmt->close();
        
        // For plain text passwords (as in your current setup)
        if ($currentPassword === $dbPassword) {
            // Check if email already exists
            $checkEmailQuery = "SELECT owner_id FROM business_owners WHERE email = ? AND owner_id != ?";
            $stmt = $conn->prepare($checkEmailQuery);
            $stmt->bind_param("si", $newEmail, $ownerId);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $response['message'] = 'Email already exists';
            } else {
                // Update email
                $updateEmailQuery = "UPDATE business_owners SET email = ? WHERE owner_id = ?";
                $stmt2 = $conn->prepare($updateEmailQuery);
                $stmt2->bind_param("si", $newEmail, $ownerId);
                
                if ($stmt2->execute()) {
                    // Update session variable
                    $_SESSION['user_email'] = $newEmail;
                    $response['success'] = true;
                    $response['message'] = 'Email updated successfully';
                } else {
                    $response['message'] = 'Error updating email';
                }
                $stmt2->close();
            }
            $stmt->close();
        } else {
            $response['message'] = 'Current password is incorrect';
        }
    }
    
    // Update password
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        
        // Verify current password
        $verifyQuery = "SELECT password FROM business_owners WHERE owner_id = ?";
        $stmt = $conn->prepare($verifyQuery);
        $stmt->bind_param("i", $ownerId);
        $stmt->execute();
        $stmt->bind_result($dbPassword);
        $stmt->fetch();
        $stmt->close();
        
        // For plain text passwords (as in your current setup)
        if ($currentPassword === $dbPassword) {
            // Update password
            $updatePasswordQuery = "UPDATE business_owners SET password = ? WHERE owner_id = ?";
            $stmt = $conn->prepare($updatePasswordQuery);
            $stmt->bind_param("si", $newPassword, $ownerId);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Password updated successfully';
            } else {
                $response['message'] = 'Error updating password';
            }
            $stmt->close();
        } else {
            $response['message'] = 'Current password is incorrect';
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>