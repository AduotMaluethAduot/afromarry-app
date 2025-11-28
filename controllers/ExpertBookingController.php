<?php
require_once 'BaseController.php';

class ExpertBookingController extends BaseController {
    
    public function index() {
        $this->requireAuth();
        
        try {
            // Get user's expert bookings
            $query = "SELECT eb.*, e.name as expert_name, e.tribe, e.specialization 
                     FROM expert_bookings eb 
                     JOIN experts e ON eb.expert_id = e.id 
                     WHERE eb.user_id = :user_id 
                     ORDER BY eb.booking_date DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $this->user['id']]);
            $bookings = $stmt->fetchAll();
            
            $this->sendResponse(true, 'Bookings retrieved successfully', $bookings);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function store() {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $this->validateRequired($data, ['expert_id', 'booking_date', 'booking_time', 'duration_hours', 'consultation_type']);
            
            // Check if expert exists
            $query = "SELECT * FROM experts WHERE id = :expert_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':expert_id' => $data['expert_id']]);
            $expert = $stmt->fetch();
            
            if (!$expert) {
                $this->sendResponse(false, 'Expert not found', null, 404);
            }
            
            // Calculate total amount
            $total_amount = $expert['hourly_rate'] * $data['duration_hours'];
            
            // Create booking
            $query = "INSERT INTO expert_bookings (user_id, expert_id, booking_date, duration_hours, total_amount, status, notes) 
                     VALUES (:user_id, :expert_id, :booking_date, :duration_hours, :total_amount, :status, :notes)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $this->user['id'],
                ':expert_id' => $data['expert_id'],
                ':booking_date' => $data['booking_date'] . ' ' . $data['booking_time'],
                ':duration_hours' => $data['duration_hours'],
                ':total_amount' => $total_amount,
                ':status' => 'pending',
                ':notes' => $data['notes'] ?? ''
            ]);
            
            $booking_id = $this->db->lastInsertId();
            
            // Generate meeting link
            $meeting_link = $this->generateMeetingLink($booking_id);
            
            // Update booking with meeting link
            $query = "UPDATE expert_bookings SET meeting_link = :meeting_link WHERE id = :booking_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':meeting_link' => $meeting_link,
                ':booking_id' => $booking_id
            ]);
            
            $this->sendResponse(true, 'Booking created successfully', [
                'booking_id' => $booking_id,
                'meeting_link' => $meeting_link
            ]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function update($id) {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $status = $data['status'];
        
        try {
            $query = "UPDATE expert_bookings SET status = :status WHERE id = :booking_id AND user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':status' => $status,
                ':booking_id' => $id,
                ':user_id' => $this->user['id']
            ]);
            
            $this->sendResponse(true, 'Booking status updated');
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function destroy($id) {
        $this->requireAuth();
        
        try {
            $query = "UPDATE expert_bookings SET status = 'cancelled' WHERE id = :booking_id AND user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':booking_id' => $id,
                ':user_id' => $this->user['id']
            ]);
            
            $this->sendResponse(true, 'Booking cancelled');
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    private function generateMeetingLink($booking_id) {
        // Generate Zoom meeting via API (requires Zoom API credentials in config)
        try {
            $zoom_meeting = $this->createZoomMeeting($booking_id);
            
            // Save Zoom meeting details
            $query = "INSERT INTO zoom_meetings (booking_id, meeting_id, meeting_password, join_url, start_url, status, scheduled_at) 
                     VALUES (:booking_id, :meeting_id, :meeting_password, :join_url, :start_url, 'scheduled', :scheduled_at)
                     ON DUPLICATE KEY UPDATE 
                     meeting_id = :meeting_id, 
                     meeting_password = :meeting_password, 
                     join_url = :join_url, 
                     start_url = :start_url";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':booking_id' => $booking_id,
                ':meeting_id' => $zoom_meeting['id'] ?? null,
                ':meeting_password' => $zoom_meeting['password'] ?? null,
                ':join_url' => $zoom_meeting['join_url'] ?? null,
                ':start_url' => $zoom_meeting['start_url'] ?? null,
                ':scheduled_at' => date('Y-m-d H:i:s')
            ]);
            
            return $zoom_meeting['join_url'] ?? "https://zoom.us/j/" . ($zoom_meeting['id'] ?? $booking_id);
        } catch (Exception $e) {
            // Fallback to placeholder if Zoom API fails
            error_log('Zoom API error: ' . $e->getMessage());
            return "https://zoom.us/j/afromarry-" . $booking_id . " (Please contact support for meeting details)";
        }
    }
    
    private function createZoomMeeting($booking_id) {
        // Zoom API Integration
        // Requires: ZOOM_API_KEY and ZOOM_API_SECRET in config
        $zoom_api_key = getenv('ZOOM_API_KEY') ?? '';
        $zoom_api_secret = getenv('ZOOM_API_SECRET') ?? '';
        
        if (empty($zoom_api_key) || empty($zoom_api_secret)) {
            // Return placeholder if API keys not configured
            return [
                'id' => 'afromarry-' . $booking_id,
                'join_url' => 'https://zoom.us/j/afromarry-' . $booking_id,
                'password' => null,
                'start_url' => null
            ];
        }
        
        // Get booking details for meeting time
        $query = "SELECT booking_date, duration_hours FROM expert_bookings WHERE id = :booking_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':booking_id' => $booking_id]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            throw new Exception('Booking not found');
        }
        
        // Create JWT token for Zoom API
        $token = $this->generateZoomJWT($zoom_api_key, $zoom_api_secret);
        
        // Create Zoom meeting via API
        $meeting_data = [
            'topic' => 'AfroMarry Cultural Consultation',
            'type' => 2, // Scheduled meeting
            'start_time' => date('Y-m-d\TH:i:s', strtotime($booking['booking_date'])),
            'duration' => (int)($booking['duration_hours'] * 60), // Convert hours to minutes
            'timezone' => 'Africa/Lagos',
            'settings' => [
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => false,
                'waiting_room' => true
            ]
        ];
        
        $ch = curl_init('https://api.zoom.us/v2/users/me/meetings');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($meeting_data));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 201) {
            $meeting = json_decode($response, true);
            return [
                'id' => $meeting['id'],
                'join_url' => $meeting['join_url'],
                'password' => $meeting['password'],
                'start_url' => $meeting['start_url']
            ];
        } else {
            throw new Exception('Failed to create Zoom meeting: ' . $response);
        }
    }
    
    private function generateZoomJWT($api_key, $api_secret) {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => $api_key,
            'exp' => time() + 3600
        ];
        
        $header_encoded = $this->base64url_encode(json_encode($header));
        $payload_encoded = $this->base64url_encode(json_encode($payload));
        $signature = $this->base64url_encode(hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, $api_secret, true));
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature;
    }
    
    // Helper function for base64url encoding
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
?>
