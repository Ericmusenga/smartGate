<?php
// api/reports.php - Add this visitor report handling section

// Add this case in your existing switch statement for report generation
case 'visitors':
    // Get parameters
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $department = $_POST['department'] ?? '';
    $status = $_POST['status'] ?? '';
    $person_to_visit = $_POST['person_to_visit'] ?? '';
    $format = $_POST['format'] ?? 'pdf';
    
    // Build query with filters
    $query = "SELECT 
                v.id,
                v.visitor_name,
                v.id_number,
                v.email,
                v.telephone,
                v.department,
                v.person_to_visit,
                v.purpose,
                v.equipment_brought,
                v.other_equipment_details,
                v.registration_date,
                v.status,
                DATE_FORMAT(v.registration_date, '%Y-%m-%d %H:%i:%s') as formatted_registration_date,
                DATE_FORMAT(v.created_at, '%Y-%m-%d %H:%i:%s') as formatted_created_at
              FROM vistor v 
              WHERE 1=1";
    
    $params = [];
    
    // Add date range filter
    if (!empty($start_date) && !empty($end_date)) {
        $query .= " AND DATE(v.registration_date) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
    }
    
    // Add department filter
    if (!empty($department)) {
        $query .= " AND v.department = ?";
        $params[] = $department;
    }
    
    // Add status filter
    if (!empty($status)) {
        $query .= " AND v.status = ?";
        $params[] = $status;
    }
    
    // Add person to visit filter
    if (!empty($person_to_visit)) {
        $query .= " AND v.person_to_visit LIKE ?";
        $params[] = '%' . $person_to_visit . '%';
    }
    
    $query .= " ORDER BY v.registration_date DESC";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($format === 'pdf') {
            generateVisitorPDF($visitors, $start_date, $end_date);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error generating visitor report: ' . $e->getMessage()]);
    }
    break;

// Add this function to generate visitor PDF
function generateVisitorPDF($visitors, $start_date, $end_date) {
    require_once '../vendor/autoload.php'; // Assuming you're using TCPDF or similar
    
    // If using TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Visitor Management System');
    $pdf->SetAuthor('System Administrator');
    $pdf->SetTitle('Visitor Report');
    $pdf->SetSubject('Visitor Report');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, 'Visitor Report', 'Generated on ' . date('Y-m-d H:i:s') . "\nPeriod: " . $start_date . ' to ' . $end_date);
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Summary section
    $summary = '
    <h2>Visitor Report Summary</h2>
    <table border="1" cellpadding="5">
        <tr>
            <td><strong>Total Visitors:</strong></td>
            <td>' . count($visitors) . '</td>
        </tr>
        <tr>
            <td><strong>Report Period:</strong></td>
            <td>' . $start_date . ' to ' . $end_date . '</td>
        </tr>
        <tr>
            <td><strong>Generated:</strong></td>
            <td>' . date('Y-m-d H:i:s') . '</td>
        </tr>
    </table>
    <br><br>
    ';
    
    $pdf->writeHTML($summary, true, false, true, false, '');
    
    // Visitor details table
    $html = '
    <h3>Visitor Details</h3>
    <table border="1" cellpadding="3" cellspacing="0">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th width="8%"><strong>ID</strong></th>
                <th width="12%"><strong>Name</strong></th>
                <th width="10%"><strong>ID Number</strong></th>
                <th width="12%"><strong>Email</strong></th>
                <th width="10%"><strong>Phone</strong></th>
                <th width="12%"><strong>Department</strong></th>
                <th width="12%"><strong>Person to Visit</strong></th>
                <th width="12%"><strong>Registration Date</strong></th>
                <th width="12%"><strong>Status</strong></th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($visitors as $visitor) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($visitor['id']) . '</td>
                <td>' . htmlspecialchars($visitor['visitor_name']) . '</td>
                <td>' . htmlspecialchars($visitor['id_number']) . '</td>
                <td>' . htmlspecialchars($visitor['email'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($visitor['telephone']) . '</td>
                <td>' . htmlspecialchars($visitor['department']) . '</td>
                <td>' . htmlspecialchars($visitor['person_to_visit'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($visitor['formatted_registration_date']) . '</td>
                <td>' . htmlspecialchars($visitor['status'] ?? 'Active') . '</td>
            </tr>';
    }
    
    $html .= '</tbody></table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Add detailed visitor information on separate pages if needed
    if (!empty($visitors)) {
        $pdf->AddPage();
        $pdf->writeHTML('<h3>Detailed Visitor Information</h3>', true, false, true, false, '');
        
        foreach ($visitors as $visitor) {
            $detailHtml = '
            <table border="1" cellpadding="5" style="margin-bottom: 20px;">
                <tr>
                    <td colspan="2" style="background-color: #f0f0f0;"><strong>Visitor ID: ' . $visitor['id'] . '</strong></td>
                </tr>
                <tr>
                    <td width="30%"><strong>Name:</strong></td>
                    <td width="70%">' . htmlspecialchars($visitor['visitor_name']) . '</td>
                </tr>
                <tr>
                    <td><strong>ID Number:</strong></td>
                    <td>' . htmlspecialchars($visitor['id_number']) . '</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>' . htmlspecialchars($visitor['email'] ?? 'N/A') . '</td>
                </tr>
                <tr>
                    <td><strong>Telephone:</strong></td>
                    <td>' . htmlspecialchars($visitor['telephone']) . '</td>
                </tr>
                <tr>
                    <td><strong>Department:</strong></td>
                    <td>' . htmlspecialchars($visitor['department']) . '</td>
                </tr>
                <tr>
                    <td><strong>Person to Visit:</strong></td>
                    <td>' . htmlspecialchars($visitor['person_to_visit'] ?? 'N/A') . '</td>
                </tr>
                <tr>
                    <td><strong>Purpose:</strong></td>
                    <td>' . htmlspecialchars($visitor['purpose']) . '</td>
                </tr>
                <tr>
                    <td><strong>Equipment Brought:</strong></td>
                    <td>' . htmlspecialchars($visitor['equipment_brought']) . '</td>
                </tr>
                <tr>
                    <td><strong>Other Equipment Details:</strong></td>
                    <td>' . htmlspecialchars($visitor['other_equipment_details'] ?? 'N/A') . '</td>
                </tr>
                <tr>
                    <td><strong>Registration Date:</strong></td>
                    <td>' . htmlspecialchars($visitor['formatted_registration_date']) . '</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>' . htmlspecialchars($visitor['status'] ?? 'Active') . '</td>
                </tr>
            </table>
            <br>';
            
            $pdf->writeHTML($detailHtml, true, false, true, false, '');
        }
    }
    
    // Output PDF
    $filename = 'visitor_report_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output($filename, 'D'); // 'D' for download
    exit;
}

// Add this function to get visitor filter data
function getVisitorFilterData($pdo) {
    $data = [];
    
    // Get unique departments
    $stmt = $pdo->query("SELECT DISTINCT department FROM vistor WHERE department != '' ORDER BY department");
    $data['departments'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get unique statuses
    $stmt = $pdo->query("SELECT DISTINCT status FROM vistor WHERE status IS NOT NULL AND status != '' ORDER BY status");
    $data['statuses'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get unique persons to visit
    $stmt = $pdo->query("SELECT DISTINCT person_to_visit FROM vistor WHERE person_to_visit IS NOT NULL AND person_to_visit != '' ORDER BY person_to_visit");
    $data['persons_to_visit'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    return $data;
}

// Add this function to generate student PDF
function generateStudentPDF($students) {
    require_once '../vendor/autoload.php'; // Assuming you're using TCPDF or similar
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Student Management System');
    $pdf->SetAuthor('System Administrator');
    $pdf->SetTitle('Student Report');
    $pdf->SetSubject('Student Report');
    $pdf->SetHeaderData('', 0, 'Student Report', 'Generated on ' . date('Y-m-d H:i:s'));
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // 1. Summary page
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 14);
    $summary = '<h1 style="text-align:center;">Student Report Summary</h1>';
    $summary .= '<table border="1" cellpadding="8" style="width:60%;margin:auto;">'
        .'<tr><td><strong>Total Students:</strong></td><td>' . count($students) . '</td></tr>'
        .'<tr><td><strong>Generated:</strong></td><td>' . date('Y-m-d H:i:s') . '</td></tr>'
        .'</table>';
    $pdf->writeHTML($summary, true, false, true, false, '');

    // 2. Table of all students (on a new page)
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 9);
    $html = '<h2 style="text-align:center;">All Students</h2>';
    $html .= '<style>th { background-color: #e0e0e0; font-weight: bold; } tr:nth-child(even) { background-color: #f9f9f9; } td, th { white-space: pre-line; }</style>';
    $html .= '<table border="1" cellpadding="3" cellspacing="0" width="50%">'
        .'<thead><tr>'
        .'<th width="4%">ID</th>'
        .'<th width="8%">Reg. Number</th>'
        .'<th width="8%">Name</th>'
        .'<th width="8%">Email</th>'
        .'<th width="8%">Phone</th>'
        .'<th width="8%">Department</th>'
        .'<th width="8%">Program</th>'
        .'<th width="5%">Year</th>'
        .'<th width="5%">Gender</th>'
        .'<th width="5%">Created At</th>'
        .'</tr></thead><tbody>';
    foreach ($students as $student) {
        $html .= '<tr>'
            .'<td>' . htmlspecialchars($student['id']) . '</td>'
            .'<td>' . htmlspecialchars($student['registration_number']) . '</td>'
            .'<td>' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</td>'
            .'<td>' . htmlspecialchars($student['email']) . '</td>'
            .'<td>' . htmlspecialchars($student['phone']) . '</td>'
            .'<td>' . htmlspecialchars($student['department']) . '</td>'
            .'<td>' . htmlspecialchars($student['program']) . '</td>'
            .'<td>' . htmlspecialchars($student['year_of_study']) . '</td>'
            .'<td>' . htmlspecialchars($student['gender']) . '</td>'
            .'<td>' . htmlspecialchars($student['created_at']) . '</td>'
            .'</tr>';
    }
    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, true, false, '');

    // 3. Each student details on a new page
    if (!empty($students)) {
        foreach ($students as $student) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 11);
            $profile = '<h4 style="text-align:center;">Student Profile</h4>';
            $profile .= '<table border="0" cellpadding="7" style="width:80%;margin:auto;">'
                .'<tr><td width="40%"><strong>ID:</strong></td><td width="40%">' . htmlspecialchars($student['id']) . '</td></tr>'
                .'<tr><td><strong>Registration Number:</strong></td><td>' . htmlspecialchars($student['registration_number']) . '</td></tr>'
                .'<tr><td><strong>Name:</strong></td><td>' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</td></tr>'
                .'<tr><td><strong>Email:</strong></td><td>' . htmlspecialchars($student['email']) . '</td></tr>'
                .'<tr><td><strong>Phone:</strong></td><td>' . htmlspecialchars($student['phone']) . '</td></tr>'
                .'<tr><td><strong>Department:</strong></td><td>' . htmlspecialchars($student['department']) . '</td></tr>'
                .'<tr><td><strong>Program:</strong></td><td>' . htmlspecialchars($student['program']) . '</td></tr>'
                .'<tr><td><strong>Year of Study:</strong></td><td>' . htmlspecialchars($student['year_of_study']) . '</td></tr>'
                .'<tr><td><strong>Gender:</strong></td><td>' . htmlspecialchars($student['gender']) . '</td></tr>'
                .'<tr><td><strong>Created At:</strong></td><td>' . htmlspecialchars($student['created_at']) . '</td></tr>'
                .'</table>';
            $pdf->writeHTML($profile, true, false, true, false, '');
        }
    }
    $filename = 'student_report_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output($filename, 'D');
    exit;
}