<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$selfId = (int)$_SESSION['user_id'];
$selfRole = $_SESSION['role'];
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$usersPerPage = 10;
$offset = ($page - 1) * $usersPerPage;

// Count total users
function countUsers($conn, $selfId, $selfRole, $search = '') {
    if ($selfRole === 'admin') {
        $query = "SELECT COUNT(*) FROM users WHERE id != ? AND role='employee'";
    } else {
        $query = "SELECT COUNT(*) FROM users WHERE id != ?";
    }

    if ($search !== '') {
        $query .= " AND (fullname LIKE ? OR username LIKE ? OR email LIKE ?)";
    }

    $stmt = $conn->prepare($query);
    if ($search !== '') {
        $like = "%$search%";
        $stmt->bind_param("isss", $selfId, $like, $like, $like);
    } else {
        $stmt->bind_param("i", $selfId);
    }

    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    return $total;
}

// Fetch users
function fetchUserList($conn, $selfId, $selfRole, $search = '', $limit = 10, $offset = 0) {
    $limit = (int)$limit;
    $offset = (int)$offset;

    if ($selfRole === 'admin') {
        $query = "SELECT id, fullname, username, email, role FROM users WHERE id != ? AND role='employee'";
    } else {
        $query = "SELECT id, fullname, username, email, role FROM users WHERE id != ?";
    }

    if ($search !== '') {
        $query .= " AND (fullname LIKE ? OR username LIKE ? OR email LIKE ?)";
    }

    // Safe LIMIT by direct interpolation
    $query .= " ORDER BY fullname ASC LIMIT $offset, $limit";

    $stmt = $conn->prepare($query);
    if ($search !== '') {
        $like = "%$search%";
        $stmt->bind_param("isss", $selfId, $like, $like, $like);
    } else {
        $stmt->bind_param("i", $selfId);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $isSelf = ($row['id'] == $selfId);
        $row['canPromote'] = !$isSelf && $selfRole === 'super_admin' && $row['role'] === 'employee';
        $row['canDemote'] = !$isSelf && $selfRole === 'super_admin' && $row['role'] === 'admin';
        $row['canDelete'] = !$isSelf && ($selfRole === 'super_admin' || ($selfRole === 'admin' && $row['role'] === 'employee'));
        $users[] = $row;
    }
    $stmt->close();
    return $users;
}

$totalUsers = countUsers($conn, $selfId, $selfRole, $search);
$users = fetchUserList($conn, $selfId, $selfRole, $search, $usersPerPage, $offset);

echo json_encode([
    'users' => $users,
    'totalUsers' => $totalUsers,
    'totalPages' => ceil($totalUsers / $usersPerPage),
    'currentPage' => $page
]);
