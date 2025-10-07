<?php
require_once __DIR__ . '/../model/ReusableModel.php';

class ReusableController
{
    private $reusableModel;

    public function __construct()
    {
        $db = new DatabaseClass();
        $this->reusableModel = new ReusableModel($db);
    }
    public function getCustomerandModel()
    {
        $customers = $this->reusableModel->getCustomerAndModel();
        echo json_encode(['data' => $customers]);
    }
    public function updateAccountMinimal()
    {
        global $input;


        error_log("INPUT DATA: " . json_encode($input));

        if (!$input || !isset($input['id'], $input['section'], $input['specific_section'])) {
            echo json_encode([
                'success' => false,
                'message' => 'No input received or invalid JSON.'
            ]);
            return;
        }

        try {
            $id = (int) $input['id'];
            $section = json_encode((array) $input['section']);
            $specific_section = json_encode((array) $input['specific_section']);

            $updated = $this->reusableModel->updateAccountMinimal($id, $section, $specific_section);

            echo json_encode([
                'success' => (bool) $updated,
                'message' => $updated
                    ? 'Production information updated successfully.'
                    : 'No changes made or update failed.'
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    public function reset_timein()
    {
        global $input;
        $id = (int)($input['id'] ?? 0);

        $location             = strtolower(trim($input['location'] ?? ''));
        $role             = strtolower(trim($input['role'] ?? ''));
        $section          = strtolower(trim($input['section'] ?? ''));
        $specific_section = strtolower(trim($input['specific_section'] ?? ''));
        $assemblySection  = strtolower(trim($input['assembly_section'] ?? ''));

        $supervisorId     = trim($input['supervisor_id'] ?? '');
        $supervisorName   = trim($input['supervisor_name'] ?? '');

        try {
            $authPassed = false;

            // ✅ 1. Check supervisor exists
            $user = $this->reusableModel->getUserByIdAndName($supervisorId, $supervisorName);
            if (!$user) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'User not recognized.'
                ]);
                exit;
            }
            $userSections = json_decode($user['section'] ?? '[]', true);
            $userSpecificSections = json_decode($user['specific_section'] ?? '[]', true);


            $userSections = array_map('strtolower', (array)$userSections);
            $userSpecificSections = array_map('strtolower', (array)$userSpecificSections);

            $dbRole = strtolower(trim($user['role'] ?? ''));

            if ($dbRole === 'administrator') {
                $authPassed = true;
            } elseif (in_array($dbRole, ['supervisor', 'line leader'], true)) {

                if (in_array($section, $userSections, true)) {
                    $authPassed = true;
                }
            }

            if (!$authPassed) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized reset attempt — role or section mismatch.'
                ]);
                exit;
            }

            if ($location === 'assembly') {
                $success = $this->reusableModel->resetAssembly($id);
            } else if ($location === 'stamping') {
                $success = $this->reusableModel->resetStamping($id);
            } else if ($location === 'finishing') {
                $success = $this->reusableModel->resetFinishing($id);
            } else if ($location === 'qc') {
                $success = $this->reusableModel->resetQC($id);
            } else if ($location === 'reworkqc') {
                $success = $this->reusableModel->resetReworkQC($id);
            }


            echo json_encode([
                'success' => $success,
                'message' => $success
                    ? "Inventory reset for ID $id"
                    : 'Reset failed.'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'DB Error: ' . $e->getMessage()
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
