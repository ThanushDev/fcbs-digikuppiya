<?php
session_start();
include 'connect.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    die("Access denied");
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Users');

// Header row
$sheet->fromArray(['ID','First Name','Last Name','Email','Mobile','Department','Last Login','Last Seen'], NULL, 'A1');

// Data rows
$res = $conn->query("SELECT id, firstName, lastName, email, mobile, department, last_login, last_seen FROM users ORDER BY id DESC");
$rowNum = 2;
while ($row = $res->fetch_assoc()) {
    $sheet->fromArray([
        $row['id'],
        $row['firstName'],
        $row['lastName'],
        $row['email'],
        $row['mobile'],
        $row['department'],
        $row['last_login'],
        $row['last_seen']
    ], NULL, 'A'.$rowNum);
    $rowNum++;
}

// Output Excel file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="users_export.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
