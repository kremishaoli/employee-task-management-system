<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit();
}

include 'db.php';

if (!isset($_GET['type'])) {
    die("Invalid request.");
}

$type = $_GET['type'];
$filename = "report_" . $type . "_" . date('Ymd') . ".csv";

// Set headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="' . $filename . '"');

$output = fopen('php://output', 'w');

switch ($type) {
    case 'performance':
        fputcsv($output, ['Employee Name', 'Average Rating']);
        $query = "SELECT u.fullname, AVG(e.rating) AS avg_rating
                  FROM evaluations e
                  JOIN users u ON e.employee_id = u.id
                  GROUP BY e.employee_id";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [$row['fullname'], number_format($row['avg_rating'], 2)]);
        }
        break;

    case 'task-progress':
        fputcsv($output, ['Task Title', 'Status', 'Deadline']);
        $query = "SELECT title, status, deadline FROM tasks ORDER BY deadline ASC";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [$row['title'], $row['status'], $row['deadline']]);
        }
        break;

    case 'overdue':
        fputcsv($output, ['Task Title', 'Deadline', 'Status']);
        $today = date('Y-m-d');
        $query = "SELECT title, deadline, status FROM tasks WHERE deadline < '$today' AND status != 'Completed'";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [$row['title'], $row['deadline'], $row['status']]);
        }
        break;

    case 'assignment':
        fputcsv($output, ['Task Title', 'Assigned To', 'Status']);
        $query = "SELECT t.title, u.fullname AS assigned_to, t.status
                  FROM tasks t
                  LEFT JOIN users u ON t.assigned_to = u.id";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $assignedTo = $row['assigned_to'] ? $row['assigned_to'] : 'Unassigned';
            fputcsv($output, [$row['title'], $assignedTo, $row['status']]);
        }
        break;

    default:
        echo "Invalid report type.";
        break;
}

fclose($output);
exit();
