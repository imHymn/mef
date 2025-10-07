<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\{
    Chart,
    DataSeries,
    DataSeriesValues,
    Legend,
    PlotArea,
    Title
};



class ExportModel
{
    private $db;

    // Accept DatabaseClass from outside
    public function __construct(DatabaseClass $db)
    {
        $this->db = $db;
    }
    public function exportMPEFF($section, $year, $month, $data)
    {
        ob_clean();
        ob_start();

        // ✅ Ensure correct template path
        $filePath = realpath(__DIR__ . '/../../components/template/MPEFF SAMPLE.xlsx');
        if (!$filePath || !file_exists($filePath)) {
            throw new Exception("❌ Excel template not found: $filePath");
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('MPEFF SAMPLE');
        $spreadsheet->setActiveSheetIndexByName('MPEFF SAMPLE');

        // ✅ Unmerge header cells if merged
        foreach ($sheet->getMergeCells() as $range) {
            if ($sheet->getCell('D6')->isInRange($range)) $sheet->unmergeCells($range);
            if ($sheet->getCell('D7')->isInRange($range)) $sheet->unmergeCells($range);
        }

        // ✅ Fill header values
        $monthName = date("F", mktime(0, 0, 0, (int)$month, 1));
        $sheet->setCellValueExplicit('D6', "$monthName ($year)", DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D7', $section, DataType::TYPE_STRING);

        // ✅ Write daily values
        foreach ($data as $date => $metrics) {
            $totals = $metrics['totals'] ?? $metrics; // support flat or nested format

            $day = (int)date('j', strtotime($date));
            $colIndex = 6 + $day; // Column G (7th column) + day offset
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);

            $qty = isset($totals['totalQty']) ? ceil($totals['totalQty']) : 0;
            $totalWT = isset($totals['totalWorkingTime']) ? ceil($totals['totalWorkingTime']) : 0;
            $totalCT = isset($totals['avgCycleTime']) ? round($totals['avgCycleTime'], 2) : 0;

            $sheet->setCellValueExplicit("{$colLetter}38", $day, DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("{$colLetter}39", $qty, DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("{$colLetter}40", $totalWT, DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("{$colLetter}41", $totalCT, DataType::TYPE_NUMERIC);
        }

        // ✅ Style for % rows
        $sheet->getStyle('G42:AK43')->getNumberFormat()->setFormatCode('0.00%');

        // ✅ Legend labels
        $sheet->setCellValue('F42', 'Actual MPEFF');
        $sheet->setCellValue('F43', 'Target MPEFF');

        // ✅ Create Chart
        $categoryRange      = "'MPEFF SAMPLE'!G38:AK38";
        $actualValuesRange  = "'MPEFF SAMPLE'!G42:AK42";
        $targetValuesRange  = "'MPEFF SAMPLE'!G43:AK43";

        $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $categoryRange, null, 31)];
        $actualSeries = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $actualValuesRange, null, 31);
        $targetSeries = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $targetValuesRange, null, 31);

        $seriesLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'MPEFF SAMPLE'!F42", null, 1),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'MPEFF SAMPLE'!F43", null, 1),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_LINECHART,
            null,
            [0, 1],
            $seriesLabels,
            $categories,
            [$actualSeries, $targetSeries]
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $title = new Title("MPEFF vs Target per Day");

        $chart = new Chart('EfficiencyChart', $title, $legend, $plotArea);
        $chart->setTopLeftPosition('G12');
        $chart->setBottomRightPosition('AK34');
        $sheet->addChart($chart);

        // ✅ Save to temp file
        $timestamp = date('Ymd_His');
        $finalName = "MPEFF_SAMPLE_UPDATED_{$timestamp}.xlsx";
        $outputPath = sys_get_temp_dir() . "/$finalName";

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->setIncludeCharts(true);
        $writer->save($outputPath);

        return $outputPath;
    }
    public function exportQCExcel(string $section, int $year, string $month, array $data): string
    {
        $filePath = realpath(__DIR__ . '/../../components/template/Direct OK Monitoring Sample.xlsx');

        if (!file_exists($filePath)) {
            throw new Exception('Excel template not found.');
        }

        $spreadsheet = IOFactory::load($filePath);

        $monthYear = DateTime::createFromFormat('!m', $month)
            ? DateTime::createFromFormat('!m', $month)->format('F') . " $year"
            : "$month/$year";

        // ---------- Direct OK Sheet ----------
        $spreadsheet->setActiveSheetIndexByName('Direct OK');
        $okSheet = $spreadsheet->getActiveSheet();
        $okSheet->setCellValue('A2', $monthYear);
        $okSheet->setCellValue('A3', $section);

        $dayToCol = fn(int $d) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($d + 1);
        $dailyTotals = [];

        foreach ($data as $dateStr => $materials) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) continue;
            $day = (int)date('j', strtotime($dateStr));
            foreach ($materials as $itm) {
                $dailyTotals[$day]['good']    = ($dailyTotals[$day]['good']    ?? 0) + ($itm['good'] ?? 0);
                $dailyTotals[$day]['no_good'] = ($dailyTotals[$day]['no_good'] ?? 0) + ($itm['no_good'] ?? 0);
            }
        }

        for ($day = 1; $day <= 31; $day++) {
            $col = $dayToCol($day);
            $good    = $dailyTotals[$day]['good'] ?? 0;
            $no_good = $dailyTotals[$day]['no_good'] ?? 0;
            $total   = $good + $no_good;

            $okSheet->setCellValue("{$col}18", $total);
            $okSheet->setCellValue("{$col}19", $no_good);
            $okSheet->setCellValue("{$col}17", $total > 0 ? round($good / $total, 2) : 0);
            $okSheet->setCellValue("{$col}1", $day);
        }

        // Hide row 1 day numbers
        $okSheet->getStyle('B1:AF1')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);

        // ---------- Chart ----------
        $categoryRange     = "'Direct OK'!B1:AF1";
        $actualValuesRange = "'Direct OK'!B17:AF17";
        $targetValuesArray = array_fill(0, 31, 1);

        $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $categoryRange, null, 31)];
        $actualSeries = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $actualValuesRange, null, 31);
        $targetSeries = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, null, null, 31, $targetValuesArray);

        $seriesLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Direct OK'!A17", null, 1),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, null, null, 1, ['Target 100%']),
        ];

        $barSeries = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            [0],
            [$seriesLabels[0]],
            $categories,
            [$actualSeries]
        );
        $barSeries->setPlotDirection(DataSeries::DIRECTION_COL);

        $lineSeries = new DataSeries(
            DataSeries::TYPE_LINECHART,
            null,
            [1],
            [$seriesLabels[1]],
            $categories,
            [$targetSeries]
        );

        $plotArea = new PlotArea(null, [$barSeries, $lineSeries]);
        $legend   = new Legend(Legend::POSITION_RIGHT, null, false);
        $title    = new Title("Direct OK % per Day (with 100% Target Line)");

        $chart = new Chart('DirectOKChart', $title, $legend, $plotArea);
        $chart->setTopLeftPosition('C1');
        $chart->setBottomRightPosition('AF14');
        $okSheet->addChart($chart);

        // ---------- SKU Summary ----------
        $skuSheet = $spreadsheet->getSheetByName('SKU Summary');
        if ($skuSheet) {
            $dates = array_keys($data);
            sort($dates);
            $rowStart = 1;

            foreach ($dates as $dateStr) {
                $rowIdx = $rowStart;
                $dateNice = date('F j, Y', strtotime($dateStr));
                $skuSheet->setCellValue("A{$rowIdx}", $dateNice);
                $skuSheet->setCellValue("A" . ($rowIdx + 1), $section ?? 'SECTION');

                $labels = ['SKU', 'Good', 'No Good', 'Total Inspected', 'Direct OK'];
                foreach ($labels as $i => $lbl) {
                    $skuSheet->setCellValue("A" . ($rowIdx + 2 + $i), $lbl);
                }

                $colIdx = 2;
                $colLet = fn($i) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);

                foreach ($data[$dateStr] as $sku => $itm) {
                    $good   = $itm['good'] ?? 0;
                    $noGood = $itm['no_good'] ?? 0;

                    $skuSheet->setCellValue($colLet($colIdx) . ($rowIdx + 2), $sku);
                    $skuSheet->setCellValue($colLet($colIdx) . ($rowIdx + 3), $good);
                    $skuSheet->setCellValue($colLet($colIdx) . ($rowIdx + 4), $noGood);

                    $totCell = $colLet($colIdx) . ($rowIdx + 5);
                    $okCell  = $colLet($colIdx) . ($rowIdx + 6);

                    $skuSheet->setCellValue($totCell, "=SUM({$colLet($colIdx)}" . ($rowIdx + 3) . ",{$colLet($colIdx)}" . ($rowIdx + 4) . ")");
                    $skuSheet->setCellValue($okCell, "=IF({$totCell}=0,0,ROUND({$colLet($colIdx)}" . ($rowIdx + 3) . "/{$totCell}*100,2))");
                    $skuSheet->getStyle($okCell)->getNumberFormat()->setFormatCode('0.00"%"');

                    $colIdx++;
                }

                $rowStart = $rowIdx + 8;
            }
        }

        // ---------- OUTPUT FILE ----------
        $timestamp  = date('Ymd_His');
        $finalName  = "Direct_OK_Template_$timestamp.xlsx";
        $outputPath = sys_get_temp_dir() . "/$finalName";

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->setIncludeCharts(true);
        $writer->save($outputPath);

        return $outputPath;
    }
}
