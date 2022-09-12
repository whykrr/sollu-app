<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Export extends BaseController
{
    /**
     * Export Data to Excel format.xlsx
     * 
     * @param array $format
     * @param array $data
     * @param string $filename 
     * 
     * @return redirect
     */
    public static function do($format = [], $data = [], $filename = 'export-data')
    {
        $mode = 'normal';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Sheet1');

        // check if format is have key parent
        if (array_key_exists('parent', $format)) {
            $parent = $format['parent'];
            $child = $format['child'];
            $mode = 'group';
        } else {
            $child = $format;
        }

        $total_col = count($child);
        //change total col to alphabet
        $last_col = self::numberToLetter($total_col);

        $worksheet = $spreadsheet->setActiveSheetIndex(0);

        $row = 1;
        // create header by filename merge
        $worksheet->mergeCells('A' . $row . ':' . $last_col . $row)->setCellValue('A1', $filename);
        // set bold and center
        $worksheet->getStyle('A' . $row . ':' . $last_col . $row)->getFont()->setBold(true);
        $worksheet->getStyle('A' . $row . ':' . $last_col . $row)->getAlignment()->setHorizontal('center');

        $row = $row + 2;

        $col_start = 'A';
        // loop $format to set header
        foreach ($child as $ifc) {
            $worksheet->setCellValue($col_start . $row, $ifc['label']);
            $worksheet->getStyle($col_start . $row)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $worksheet->getStyle($col_start . $row)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $worksheet->getStyle($col_start . $row)->getBorders()->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $worksheet->getStyle($col_start . $row)->getBorders()->getRight()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $worksheet->getStyle($col_start . $row)->getFont()->setBold(true);
            $col_start++;
        }

        $row = $row + 1;
        $marker_data = '';

        // $row_start = 2;
        foreach ($data as $key => $value) {
            $marker = false;
            // chwck if mode is group
            if ($mode == 'group') {
                // check if marker is not same
                if ($marker_data != $value[$parent['marker']]) {
                    $marker = true;
                    $marker_data = $value[$parent['marker']];
                }

                // if marker is true
                if ($marker) {
                    // replace {field} with value
                    $value_parent = $parent['format'];
                    foreach ($value as $key => $val) {
                        $value_parent = str_replace('{' . $key . '}', $val, $value_parent);
                    }

                    $worksheet->mergeCells('A' . $row . ':' . $last_col . $row)->setCellValue('A' . $row, $value_parent);
                    $worksheet->getStyle('A' . $row . ':' . $last_col . $row)->getFont()->setBold(true);
                    // $worksheet->getStyle('A' . $row . ':' . $last_col . $row)->getAlignment()->setHorizontal('center');

                    // add border all
                    $worksheet->getStyle('A' . $row . ':' . $last_col . $row)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $worksheet->getStyle('A' . $row . ':' . $last_col . $row)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $worksheet->getStyle('A' . $row . ':' . $last_col . $row)->getBorders()->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $worksheet->getStyle('A' . $row . ':' . $last_col . $row)->getBorders()->getRight()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                    // 2 line height
                    $spreadsheet->getActiveSheet()->getStyle('A' . $row)->getAlignment()->setWrapText(true);
                    $worksheet->getRowDimension($row)->setRowHeight(
                        (14.5 * (substr_count($parent['format'], "\n") + 1))
                    );

                    $row = $row + 1;
                }
            }
            $col_start_data = 'A';
            foreach ($child as $ifc) {
                if ($ifc['data'] == 'increament') {
                    $worksheet->setCellValue($col_start_data . $row, $key + 1);
                } else {
                    $worksheet->setCellValue($col_start_data . $row, $value[$ifc['data']]);
                }
                $worksheet->getStyle($col_start_data . $row)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $worksheet->getStyle($col_start_data . $row)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $worksheet->getStyle($col_start_data . $row)->getBorders()->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $worksheet->getStyle($col_start_data . $row)->getBorders()->getRight()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $col_start_data++;
            }
            $row++;
        }

        // resize column
        $col_start_size = 'A';
        foreach ($child as $ifc) {
            $spreadsheet->getActiveSheet()->getColumnDimension($col_start_size)
                ->setAutoSize(true);
            $col_start_size++;
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save("$filename.xlsx");

        // download file and delete
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');

        $writer->save('php://output');
        unlink("$filename.xlsx");

        // return redirect()->to("$filename.xlsx");
    }

    /**
     * Convert number to alphabet
     * 
     * @param int $number
     * 
     * @return string
     */
    public static function numberToLetter($number)
    {
        $alphabet = range('A', 'Z');
        $letter = '';
        while ($number > 0) {
            $number--;
            $letter = $alphabet[$number % 26] . $letter;
            $number = intval($number / 26);
        }
        return $letter;
    }
}
