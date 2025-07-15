<?php
require_once __DIR__ . '/../fpdf186/fpdf.php';
require_once __DIR__ . '/../config/database.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed');
}

// Get date range from GET parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

$hasDataInRange = true;
if ($start_date && $end_date) {
    // Check entry logs
    $check_entry_sql = "SELECT COUNT(*) as cnt FROM entry_student WHERE created_at BETWEEN '" . $conn->real_escape_string($start_date) . " 00:00:00' AND '" . $conn->real_escape_string($end_date) . " 23:59:59'";
    $check_entry_result = $conn->query($check_entry_sql);
    $entry_row = $check_entry_result ? $check_entry_result->fetch_assoc() : null;
    $entry_count = $entry_row ? $entry_row['cnt'] : 0;
    // Check exit logs
    $check_exit_sql = "SELECT COUNT(*) as cnt FROM exit_student WHERE created_at BETWEEN '" . $conn->real_escape_string($start_date) . " 00:00:00' AND '" . $conn->real_escape_string($end_date) . " 23:59:59'";
    $check_exit_result = $conn->query($check_exit_sql);
    $exit_row = $check_exit_result ? $check_exit_result->fetch_assoc() : null;
    $exit_count = $exit_row ? $exit_row['cnt'] : 0;
    if (($entry_count + $exit_count) == 0) {
        $hasDataInRange = false;
    }
}

// Entry logs
$entry_sql = "SELECT e.id, s.registration_number, CONCAT(s.first_name, ' ', s.last_name) AS name, s.department, e.entry_time, e.entry_gate, e.notes, e.created_at FROM entry_student e LEFT JOIN students s ON e.student_id = s.id ORDER BY e.entry_time DESC";
$entry_result = $conn->query($entry_sql);
$entry_logs = [];
if ($entry_result && $entry_result->num_rows > 0) {
    while ($row = $entry_result->fetch_assoc()) {
        $entry_logs[] = $row;
    }
}

// Exit logs
$exit_sql = "SELECT x.id, s.registration_number, CONCAT(s.first_name, ' ', s.last_name) AS name, s.department, x.exit_time, x.exit_gate, x.notes, x.created_at FROM exit_student x LEFT JOIN students s ON x.student_id = s.id ORDER BY x.exit_time DESC";
$exit_result = $conn->query($exit_sql);
$exit_logs = [];
if ($exit_result && $exit_result->num_rows > 0) {
    while ($row = $exit_result->fetch_assoc()) {
        $exit_logs[] = $row;
    }
}
$conn->close();

class PDF extends FPDF {
    var $entryHeader = ['ID', 'Reg. No.', 'Name', 'Dept.', 'Entry Time', 'Entry Gate', 'Notes', 'Created'];
    var $entryWidths = [7, 18, 28, 18, 22, 18, 28, 18];
    var $exitHeader = ['ID', 'Reg. No.', 'Name', 'Dept.', 'Exit Time', 'Exit Gate', 'Notes', 'Created'];
    var $exitWidths = [7, 18, 28, 18, 22, 18, 28, 18];

    function sectionTitle($title) {
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 10, $title, 0, 1, 'C');
        $this->Ln(1);
    }
    function entryTableHeader() {
        $this->SetFont('Arial', 'B', 6);
        $this->SetFillColor(220, 220, 220);
        for ($i = 0; $i < count($this->entryHeader); $i++) {
            $this->Cell($this->entryWidths[$i], 6, $this->entryHeader[$i], 1, 0, 'C', true);
        }
        $this->Ln();
    }
    function exitTableHeader() {
        $this->SetFont('Arial', 'B', 6);
        $this->SetFillColor(220, 220, 220);
        for ($i = 0; $i < count($this->exitHeader); $i++) {
            $this->Cell($this->exitWidths[$i], 6, $this->exitHeader[$i], 1, 0, 'C', true);
        }
        $this->Ln();
    }
    function entryRow($data) {
        $this->SetFont('Arial', '', 6);
        $h = 4.5;
        $x = $this->GetX();
        $y = $this->GetY();
        $maxHeight = $h;
        $nb = 1;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->entryWidths[$i], $data[$i]));
        }
        $maxHeight = $h * $nb;
        for ($i = 0; $i < count($data); $i++) {
            $w = $this->entryWidths[$i];
            $this->Rect($x, $y, $w, $maxHeight);
            $this->MultiCell($w, $h, $data[$i], 0, 'L');
            $x += $w;
            $this->SetXY($x, $y);
        }
        $this->Ln($maxHeight);
    }
    function exitRow($data) {
        $this->SetFont('Arial', '', 6);
        $h = 4.5;
        $x = $this->GetX();
        $y = $this->GetY();
        $maxHeight = $h;
        $nb = 1;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->exitWidths[$i], $data[$i]));
        }
        $maxHeight = $h * $nb;
        for ($i = 0; $i < count($data); $i++) {
            $w = $this->exitWidths[$i];
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
    $pdf->Cell(0, 20, 'No entry/exit data recorded for the selected date range.', 0, 1, 'C');
    $pdf->Output('D', 'entry_exit_logs_report.pdf');
    exit;
}

// Entry Logs Section
$pdf->sectionTitle('Entry Logs');
$pdf->entryTableHeader();
foreach ($entry_logs as $row) {
    $pdf->entryRow([
        $row['id'],
        $row['registration_number'],
        $row['name'],
        $row['department'],
        $row['entry_time'],
        $row['entry_gate'],
        $row['notes'],
        $row['created_at'],
    ]);
}

// Exit Logs Section
$pdf->Ln(5);
$pdf->sectionTitle('Exit Logs');
$pdf->exitTableHeader();
foreach ($exit_logs as $row) {
    $pdf->exitRow([
        $row['id'],
        $row['registration_number'],
        $row['name'],
        $row['department'],
        $row['exit_time'],
        $row['exit_gate'],
        $row['notes'],
        $row['created_at'],
    ]);
}

$pdf->Output('D', 'entry_exit_logs_report.pdf');
?>