<?php
require_once __DIR__ . '/../model/ExportModel.php';

class ExportController
{
    private $exportModel;

    public function __construct()
    {
        $db = new DatabaseClass();
        $this->exportModel = new ExportModel($db);
    }
    public function exportMPEFF()
    {
        $rawInput = file_get_contents("php://input");
        $input = json_decode($rawInput, true);

        $section = $input['section'] ?? 'NO SECTION';
        $year    = (int)($input['year'] ?? date('Y'));
        $month   = str_pad((int)($input['month'] ?? date('n')), 2, '0', STR_PAD_LEFT);
        $data    = $input['data'] ?? [];

        $filePath = $this->exportModel->exportMPEFF($section, $year, $month, $data);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache');
        header('Pragma: public');
        readfile($filePath);
        unlink($filePath);
        exit;
    }
    public function exportQCExcel()
    {
        // 🔹 Get JSON input from frontend
        $input = json_decode(file_get_contents("php://input"), true);

        $section = $input['section'] ?? 'NO SECTION';
        $year    = (int)($input['year'] ?? date('Y'));
        $month   = str_pad((int)($input['month'] ?? date('n')), 2, '0', STR_PAD_LEFT);
        $data    = $input['data'] ?? [];

        // 🔹 Generate the Excel file path using the model
        $filePath = $this->exportModel->exportQCExcel($section, $year, $month, $data);

        // 🔹 Output the file to browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache');
        header('Pragma: public');
        readfile($filePath);

        // 🔹 Clean up
        unlink($filePath);
        exit;
    }
}
