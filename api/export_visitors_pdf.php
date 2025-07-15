<?php
require_once __DIR__ . '/../fpdf186/fpdf.php';
require_once __DIR__ . '/../config/database.php';

// Connect to the database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed');
}

// Get date range from GET parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

$visitors = [];
$hasDataInRange = true;

if ($start_date && $end_date) {
    // First, check if there is at least one visitor in the selected date range
    $check_sql = "SELECT COUNT(*) as cnt FROM vistor WHERE created_at BETWEEN '" . $conn->real_escape_string($start_date) . " 00:00:00' AND '" . $conn->real_escape_string($end_date) . " 23:59:59'";
    $check_result = $conn->query($check_sql);
    $row = $check_result ? $check_result->fetch_assoc() : null;
    if (!$row || $row['cnt'] == 0) {
        $hasDataInRange = false;
    }
}

if ($hasDataInRange) {
    // Fetch all visitors (regardless of date)
    $sql = "SELECT id, visitor_name, id_number, email, telephone, department, person_to_visit, purpose, equipment_brought, other_equipment_details, registration_date, status FROM vistor";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $visitors[] = $row;
        }
    }
}
$conn->close();

class PDF extends FPDF {
    // Abbreviated headers and compact widths
    var $headerTitles = ['ID', 'Name', 'ID No.', 'Email', 'Phone', 'Dept.', 'To Visit', 'Purpose', 'Equip.', 'Other Equip.', 'Reg. Date', 'Status'];
    var $widths = [7, 22, 16, 28, 14, 18, 18, 18, 18, 18, 18, 12];

    function Header() {
        $this->SetFont('Arial', 'B', 7);
        $this->Cell(0, 8, 'Visitor Report', 0, 1, 'C');
        $this->Ln(1);
        $this->SetFont('Arial', 'B', 6);
        $this->SetFillColor(220, 220, 220);
        for ($i = 0; $i < count($this->headerTitles); $i++) {
            $this->Cell($this->widths[$i], 6, $this->headerTitles[$i], 1, 0, 'C', true);
        }
        $this->Ln();
    }

    function Row($data) {
        $this->SetFont('Arial', '', 6);
        $h = 4.5; // row height
        $x = $this->GetX();
        $y = $this->GetY();
        $maxHeight = $h;
        // Calculate max height for multi-line cells
        $nb = 1;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        $maxHeight = $h * $nb;
        // Draw the cells
        for ($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $this->Rect($x, $y, $w, $maxHeight);
            $this->MultiCell($w, $h, $data[$i], 0, 'L');
            $x += $w;
            $this->SetXY($x, $y);
        }
        $this->Ln($maxHeight);
    }

    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }
}

$pdf = new PDF('L', 'mm', 'A4');
$pdf->AddPage();

if (!$hasDataInRange) {
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 20, 'No visitor data recorded for the selected date range.', 0, 1, 'C');
} else {
    foreach ($visitors as $visitor) {
        $pdf->Row([
            $visitor['id'],
            $visitor['visitor_name'],
            $visitor['id_number'],
            $visitor['email'],
            $visitor['telephone'],
            $visitor['department'],
            $visitor['person_to_visit'],
            $visitor['purpose'],
            $visitor['equipment_brought'],
            $visitor['other_equipment_details'],
            $visitor['registration_date'],
            $visitor['status'],
        ]);
    }
}

$pdf->Output('D', 'visitor_report.pdf');
?>