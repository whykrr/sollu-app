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
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Sheet1');

        $worksheet = $spreadsheet->setActiveSheetIndex(0);

        $cellVal = 'A';

        // loop $format to set header
        foreach ($format as $ifc) {
            $worksheet->setCellValue($cellVal . '1', $ifc['label']);
            $worksheet->getStyle($cellVal . '1')->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $worksheet->getStyle($cellVal . '1')->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $worksheet->getStyle($cellVal . '1')->getBorders()->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $worksheet->getStyle($cellVal . '1')->getBorders()->getRight()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $worksheet->getStyle($cellVal . '1')->getFont()->setBold(true);
            $cellVal++;
        }


        // $row_start = 2;
        foreach ($data as $key => $value) {
            $cellValData = 'A';
            foreach ($format as $ifc) {
                if ($ifc['data'] == 'increament') {
                    $worksheet->setCellValue($cellValData . ($key + 2), $key + 1);
                } else {
                    $worksheet->setCellValue($cellValData . ($key + 2), $value[$ifc['data']]);
                }
                $worksheet->getStyle($cellValData . ($key + 2))->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $worksheet->getStyle($cellValData . ($key + 2))->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $worksheet->getStyle($cellValData . ($key + 2))->getBorders()->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $worksheet->getStyle($cellValData . ($key + 2))->getBorders()->getRight()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $cellValData++;
            }
        }

        // resize column
        $cellValResize = 'A';
        foreach ($format as $ifc) {
            $spreadsheet->getActiveSheet()->getColumnDimension($cellValResize)
                ->setAutoSize(true);
            $cellValResize++;
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save("$filename.xlsx");

        return redirect()->to("$filename.xlsx");
    }
}
