<?php
require_once __DIR__ . '/../model/AnnouncementModel.php';

class AnnouncementController
{
    private $announcementModel;
    private $db;
    public function __construct()
    {
        $this->db = new DatabaseClass();
        $this->announcementModel = new AnnouncementModel($this->db);
    }
    public function getAnnouncement()
    {
        $results = $this->announcementModel->getAnnouncement();
        echo json_encode($results);
    }
    public function createAnnouncement()
    {
        global $input;
        $title = trim($input['title'] ?? '');
        $message = trim($input['message'] ?? '');
        $category = trim($input['category'] ?? '');
        $priority = trim($input['priority'] ?? '');
        $status = trim($input['status'] ?? 'active');
        $start_date = trim($input['start_date'] ?? '');
        $end_date = trim($input['end_date'] ?? '');
        $created_at = date('Y-m-d H:i:s');
        $updated_at = date('Y-m-d H:i:s');

        // Basic validation
        if (empty($title) || empty($message) || empty($category) || empty($priority) || empty($status) || empty($start_date) || empty($end_date)) {
            echo json_encode([
                'success' => false,
                'message' => 'Incomplete announcement data.'
            ]);
            return;
        }

        if (strtotime($end_date) < strtotime($start_date)) {
            echo json_encode([
                'success' => false,
                'message' => 'End date cannot be before start date.'
            ]);
            return;
        }

        try {
            // Call the model function (make sure it now accepts start_date and end_date)
            $this->announcementModel->createAnnouncement(
                $title,
                $message,
                $category,
                $priority,
                $status,
                $start_date,
                $end_date,
                $created_at,
                $updated_at
            );

            echo json_encode([
                'success' => true,
                'message' => 'Announcement created successfully.'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error creating announcement: ' . $e->getMessage()
            ]);
        }
    }
    public function updateAnnouncement()
    {
        global $input;
        $id = intval($input['id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $message = trim($input['message'] ?? '');
        $category = trim($input['category'] ?? '');
        $priority = trim($input['priority'] ?? '');
        $status = trim($input['status'] ?? '');
        $start_date = trim($input['start_date'] ?? '');
        $end_date = trim($input['end_date'] ?? '');
        $cancel_reason = trim($input['cancel_reason'] ?? '');
        $updated_at = date('Y-m-d H:i:s');

        // Basic validation
        if ($id <= 0 || empty($title) || empty($message) || empty($category) || empty($priority) || empty($status) || empty($start_date) || empty($end_date)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or incomplete announcement data.'
            ]);
            return;
        }

        if (strtotime($end_date) < strtotime($start_date)) {
            echo json_encode([
                'success' => false,
                'message' => 'End date cannot be before start date.'
            ]);
            return;
        }

        // Require cancel reason if status is Cancelled
        if ($status === 'Cancelled' && empty($cancel_reason)) {
            echo json_encode([
                'success' => false,
                'message' => 'Please provide a reason for cancellation.'
            ]);
            return;
        }

        try {
            // Call the model function to update the record
            $this->announcementModel->updateAnnouncement(
                $id,
                $title,
                $message,
                $category,
                $priority,
                $status,
                $start_date,
                $end_date,
                $updated_at,
                $cancel_reason
            );

            echo json_encode([
                'success' => true,
                'message' => 'Announcement updated successfully.'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating announcement: ' . $e->getMessage()
            ]);
        }
    }


    public function deleteAnnouncement()
    {
        global $input;
        $id = $input['id'];
        if (!$id) {
            return ["status" => "error", "message" => "Missing announcement ID."];
        }

        $announcement = $this->announcementModel->getAnnouncementById($id);
        if (!$announcement) {
            return ["status" => "error", "message" => "Announcement not found."];
        }

        $this->announcementModel->softDeleteAnnouncement($id);

        echo json_encode([
            'success' => true,
            'message' => 'Announcement deleted successfully.'
        ]);
    }
}
