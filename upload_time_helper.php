<?php
function isUploadPeriodActive() {
    // For now, allow uploads anytime for testing purposes
    // You can uncomment the original logic when ready to restrict uploads
    return true;
    
    /*
    $current_date = new DateTime();
    $current_day = (int)$current_date->format('d');
    $days_in_month = (int)$current_date->format('t');
    
    // Check if we're in the last week (7 days) of the month
    $upload_start_day = $days_in_month - 6; // Last 7 days
    
    return $current_day >= $upload_start_day;
    */
}

function canClientUpload($client_id, $animal_id, $conn) {
    // Check if upload period is active
    if (!isUploadPeriodActive()) {
        return [
            'can_upload' => false,
            'reason' => 'Photo uploads are only available during the last week of each month.'
        ];
    }
    
    // Check client status - clients with 'Complied' status cannot upload
    // Temporarily disabled for testing
    /*
    $client_status_query = $conn->prepare("
        SELECT status FROM clients WHERE client_id = ?
    ");
    $client_status_query->bind_param("i", $client_id);
    $client_status_query->execute();
    $client_status_result = $client_status_query->get_result();
    
    if ($client_status_result->num_rows > 0) {
        $client_data = $client_status_result->fetch_assoc();
        if ($client_data['status'] === 'Complied') {
            return [
                'can_upload' => false,
                'reason' => 'Your account status is already complied. No additional uploads are required.'
            ];
        }
    }
    */

    // Rule (client-wide): If client's latest submission (any animal) is Pending, block uploads.
    // If it was Rejected, allow uploads again. If Approved, block.
    $latest_query = $conn->prepare("
        SELECT ap.status
        FROM animal_photos ap
        JOIN livestock_poultry lp ON ap.animal_id = lp.animal_id
        WHERE lp.client_id = ?
        ORDER BY ap.uploaded_at DESC
        LIMIT 1
    ");
    if ($latest_query) {
        $latest_query->bind_param("i", $client_id);
        $latest_query->execute();
        $latest_result = $latest_query->get_result();
        if ($latest_result && $latest_result->num_rows > 0) {
            $row = $latest_result->fetch_assoc();
            $latest_status = $row['status'];
            if ($latest_status === 'Pending') {
                return [
                    'can_upload' => false,
                    'reason' => 'You already submitted photos. Please wait for review.'
                ];
            }
            if ($latest_status === 'Approved') {
                return [
                    'can_upload' => false,
                    'reason' => 'Your latest submission was approved. No further uploads are needed.' 
                ];
            }
            // Rejected → allow
        }
    }

    return ['can_upload' => true, 'reason' => ''];
}
?>