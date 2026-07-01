<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

// Only admins or super_admins
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','super_admin'])) {
    echo json_encode(["success"=>false,"message"=>"Unauthorized"]);
    exit();
}

// Get parameters
$employeeId = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : null;
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$tasksPerPage = 5;
$offset = ($page - 1) * $tasksPerPage;

$response = ["success"=>false,"data"=>[],"totalTasks"=>0,"totalPages"=>0,"currentPage"=>$page,"message"=>""];

try {
    if ($employeeId) {
        // Fetch tasks for a specific employee (Evaluate Employees)
        $stmt = $conn->prepare("
            SELECT t.id, t.title, t.description, t.deadline, t.priority, t.status, t.progress,
                   u.id AS assigned_employee_id,
                   u.fullname AS assigned_employee
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.assigned_to = ?
            ORDER BY t.deadline ASC
        ");
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tasks = [];

        while ($row = $result->fetch_assoc()) {
            $row['progress'] = intval($row['progress']);
            $row['status'] = $row['status'] ?? 'Not Started';
            $row['priority'] = $row['priority'] ?? 'Medium';
            $tasks[] = $row;
        }
        $stmt->close();

        $response["success"] = true;
        $response["data"] = $tasks;
        $response["message"] = count($tasks) ? "Tasks found" : "No tasks assigned";

    } else {
        // Fetch all tasks with search & pagination (Manage Tasks)
        $like = "%$search%";

        // Count total tasks
        $stmtCount = $conn->prepare("
            SELECT COUNT(*) 
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.title LIKE ? OR t.description LIKE ? OR u.fullname LIKE ?
        ");
        $stmtCount->bind_param("sss", $like, $like, $like);
        $stmtCount->execute();
        $stmtCount->bind_result($totalTasks);
        $stmtCount->fetch();
        $stmtCount->close();

        // Fetch tasks
        $stmtFetch = $conn->prepare("
            SELECT t.id, t.title, t.description, t.deadline, t.priority, t.status, t.progress,
                   u.id AS assigned_employee_id,
                   u.fullname AS assigned_employee
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.title LIKE ? OR t.description LIKE ? OR u.fullname LIKE ?
            ORDER BY t.created_at DESC
            LIMIT ?, ?
        ");
        $stmtFetch->bind_param("sssii", $like, $like, $like, $offset, $tasksPerPage);
        $stmtFetch->execute();
        $result = $stmtFetch->get_result();
        $tasks = [];
        while($row = $result->fetch_assoc()){
            $row['progress'] = intval($row['progress']);
            $row['status'] = $row['status'] ?? 'Not Started';
            $row['priority'] = $row['priority'] ?? 'Medium';
            $tasks[] = $row;
        }
        $stmtFetch->close();

        $response["success"] = true;
        $response["data"] = $tasks;
        $response["totalTasks"] = $totalTasks;
        $response["totalPages"] = ceil($totalTasks/$tasksPerPage);
        $response["message"] = count($tasks) ? "Tasks found" : "No tasks found";
    }

} catch(Exception $e){
    $response["message"] = "Error: ".$e->getMessage();
}

echo json_encode($response);
