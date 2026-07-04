<?php
session_start();
include 'connect.php';
require_once('vendor/tecnickcom/tcpdf/tcpdf.php');

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    die("Access denied");
}

$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Admin Panel');
$pdf->SetTitle('Users Export');
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

$html = '<h2>Users List</h2>';
$html .= '<table border="1" cellpadding="4">
<tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Mobile</th><th>Department</th><th>Last Login</th><th>Last Seen</th></tr>';

$res = $conn->query("SELECT id, firstName, lastName, email, mobile, department, last_login, last_seen FROM users ORDER BY id DESC");
while ($row = $res->fetch_assoc()) {
    $html .= '<tr>
        <td>'.$row['id'].'</td>
        <td>'.$row['firstName'].'</td>
        <td>'.$row['lastName'].'</td>
        <td>'.$row['email'].'</td>
        <td>'.$row['mobile'].'</td>
        <td>'.$row['department'].'</td>
        <td>'.$row['last_login'].'</td>
        <td>'.$row['last_seen'].'</td>
    </tr>';
}
$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('users_export.pdf', 'D');
exit;
