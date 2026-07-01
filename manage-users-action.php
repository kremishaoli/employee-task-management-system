<?php
session_start();
include 'db.php';

// Only admins and super_admin can access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header("Location: login.php");
    exit();
}

$selfId = (int)$_SESSION['user_id'];
$selfRole = $_SESSION['role'];

function redirect_back() {
    header("Location: manage-users.php");
    exit();
}

function executeQuery($conn, $query, $param) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $param);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function getUserRole($conn, $userId) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($role);
    $stmt->fetch();
    $stmt->close();
    return $role;
}

// --- PROMOTE ---
if (!empty($_POST['promote_id'])) {
    $id = intval($_POST['promote_id']);
    if ($selfRole !== 'super_admin') {
        $_SESSION['error'] = "Only super admins can promote users.";
    } elseif ($id === $selfId) {
        $_SESSION['error'] = "You cannot promote yourself.";
    } elseif (executeQuery($conn, "UPDATE users SET role='admin' WHERE id=?", $id)) {
        $_SESSION['success'] = "User promoted to admin.";
    } else {
        $_SESSION['error'] = "Failed to promote user.";
    }
    redirect_back();
}

// --- DEMOTE ---
if (!empty($_POST['demote_id'])) {
    $id = intval($_POST['demote_id']);
    if ($selfRole !== 'super_admin') {
        $_SESSION['error'] = "Only super admins can demote admins.";
    } elseif ($id === $selfId) {
        $_SESSION['error'] = "You cannot demote yourself.";
    } elseif (executeQuery($conn, "UPDATE users SET role='employee' WHERE id=?", $id)) {
        $_SESSION['success'] = "User demoted to employee.";
    } else {
        $_SESSION['error'] = "Failed to demote user.";
    }
    redirect_back();
}


// --- DELETE ---
if (!empty($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    if ($id === $selfId) {
        $_SESSION['error'] = "You cannot delete yourself.";
    } else {
        $targetRole = getUserRole($conn, $id);
        if ($selfRole === 'admin' && $targetRole !== 'employee') {
            $_SESSION['error'] = "Admins can only delete employees.";
        } else {
            // 1️⃣ Free tasks assigned to this user
            $stmt = $conn->prepare("UPDATE tasks SET assigned_to = NULL WHERE assigned_to = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // 2️⃣ Delete the user
            if (executeQuery($conn, "DELETE FROM users WHERE id=?", $id)) {
                $_SESSION['success'] = "User deleted successfully and tasks are now unassigned.";
            } else {
                $_SESSION['error'] = "Failed to delete user.";
            }
        }
    }
    redirect_back();
}


redirect_back();
