<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','super_admin'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';
$today = date('Y-m-d');

/* ===== KPI DATA ===== */
$kpiData = [];
$kpiData['overdue'] = $conn->query("SELECT COUNT(*) overdue FROM tasks WHERE deadline<'$today' AND status!='Completed'")->fetch_assoc()['overdue'];
$kpiData['completed'] = $conn->query("SELECT COUNT(*) completed FROM tasks WHERE status='Completed'")->fetch_assoc()['completed'];
$kpiData['pending'] = $conn->query("SELECT COUNT(*) pending FROM tasks WHERE status IN ('Not Started','In Progress','Pending')")->fetch_assoc()['pending'];
$qTop = $conn->query("SELECT u.fullname, AVG(e.rating) avg_rating
                      FROM evaluations e 
                      JOIN users u ON e.employee_id=u.id
                      GROUP BY e.employee_id
                      ORDER BY avg_rating DESC LIMIT 1");
$kpiData['top_performer'] = $qTop->num_rows ? $qTop->fetch_assoc()['fullname'] : 'N/A';

/* ===== CHART DATA ===== */
$pieLabels = ['Overdue','Completed','Pending'];
$pieData = [$kpiData['overdue'],$kpiData['completed'],$kpiData['pending']];
$pieColors = ['#dc3545','#28a745','#ffc107']; // red, green, yellow

$empNames=[]; $empRatings=[];
$qEmp = $conn->query("SELECT u.fullname, AVG(e.rating) avg_rating
                      FROM evaluations e 
                      JOIN users u ON e.employee_id=u.id
                      GROUP BY e.employee_id");
while($r=$qEmp->fetch_assoc()){
    $empNames[] = $r['fullname'];
    $empRatings[] = round($r['avg_rating'],2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports - ETMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* ===== KPI Cards ===== */
.kpi-card{
    cursor:pointer;
    border-radius:8px;
    text-align:center;
    transition:0.3s;
    padding: 1.5rem;
    color:#fff;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
}
.kpi-card:hover{
    transform:translateY(-3px);
    box-shadow:0 6px 18px rgba(0,0,0,0.15);
}
.kpi-card h6{margin-bottom:10px; font-weight:600;}
.kpi-card p{font-size:28px; font-weight:bold; margin:0;}

/* ===== Chart Containers ===== */
.chart-container{
    background:#fff;
    padding:20px;
    border-radius:8px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
    margin-bottom:20px;
}
.chart-container canvas{max-height:300px;}

/* ===== Tables ===== */
.styled-table{
    border-collapse:collapse;
    width:100%;
    background:#fff;
    border-radius:6px;
    overflow:hidden;
    box-shadow:0 2px 6px rgba(0,0,0,.05);
    margin-bottom:20px;
}
.styled-table th,.styled-table td{
    border:1px solid #ddd;
    padding:8px;
    text-align:center;
}
.styled-table th{background:#007bff;color:#fff;}
.styled-table tr:hover{background:#f1f1f1;}

/* ===== Status Column Colors ===== */
.status-not-started { background-color: #007bff; color: #fff; padding: 5px 10px; border-radius: 5px; display:inline-block; }
.status-in-progress { background-color: #17a2b8; color:#fff; padding: 5px 10px; border-radius: 5px; display:inline-block; }
.status-pending { background-color: #ffc107; color:#000; padding: 5px 10px; border-radius: 5px; display:inline-block; }
.status-completed { background-color: #28a745; color:#fff; padding: 5px 10px; border-radius: 5px; display:inline-block; }
.status-overdue { background-color: #dc3545; color:#fff; padding: 5px 10px; border-radius: 5px; display:inline-block; }

/* ===== Modals ===== */
.modal{
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.5);
    justify-content:center; align-items:center;
    z-index:1000;
}
.modal-content{
    background:#fff;
    padding:20px;
    border-radius:8px;
    width:90%;
    max-width:700px;
    max-height:80%;
    overflow-y:auto;
    position:relative;
}
.modal-close{
    position:absolute;
    top:10px; right:15px;
    cursor:pointer;
    font-size:20px;
    color:#333;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo">ETMS</div>
    <ul class="sidebar-menu">
        <li><a href="admin-dashboard.php">Dashboard</a></li>
        <li><a href="manage-users.php">Manage Users</a></li>
        <li><a href="create-task.php">Create Task</a></li>
        <li><a href="manage-tasks.php">Manage Tasks</a></li>
        <li><a href="assign-task.php">Assign Tasks</a></li>
        <li><a href="monitor-progress.php">Monitor Progress</a></li>
        <li><a href="evaluate-employees.php">Evaluate Employees</a></li>
        <li><a href="reports.php" class="active">Reports</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>

<div class="main-content">
<header><h2>Reports Dashboard</h2></header>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-card" style="background:#dc3545;" onclick="openModal('overdueModal')">
            <h6>Overdue Tasks</h6>
            <p><?= $kpiData['overdue'] ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card" style="background:#28a745;" onclick="openModal('completedModal')">
            <h6>Completed Tasks</h6>
            <p><?= $kpiData['completed'] ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card" style="background:#ffc107; color:#212529;" onclick="openModal('pendingModal')">
            <h6>Pending Tasks</h6>
            <p><?= $kpiData['pending'] ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card" style="background:#007bff;">
            <h6>Top Performer</h6>
            <p><?= $kpiData['top_performer'] ?></p>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row">
    <div class="col-md-6 chart-container"><canvas id="statusChart"></canvas></div>
    <div class="col-md-6 chart-container"><canvas id="employeeChart"></canvas></div>
</div>

<!-- Tasks Table -->
<h3 class="mt-4">All Tasks</h3>
<?php
$r=$conn->query("SELECT t.title, u.fullname AS assigned_to, t.status, t.deadline
FROM tasks t LEFT JOIN users u ON t.assigned_to=u.id ORDER BY t.deadline");
if($r->num_rows){
    echo "<table class='styled-table'><tr><th>Task</th><th>Assigned To</th><th>Status</th><th>Deadline</th></tr>";
    while($row=$r->fetch_assoc()){
        $isOver=($row['deadline']<$today && $row['status']!='Completed');
        $assigned=$row['assigned_to'] ?? 'Unassigned';

        // Status class only for status column
        if($isOver) $statusClass='status-overdue';
        else{
            switch($row['status']){
                case 'Not Started': $statusClass='status-not-started'; break;
                case 'In Progress': $statusClass='status-in-progress'; break;
                case 'Pending': $statusClass='status-pending'; break;
                case 'Completed': $statusClass='status-completed'; break;
                default: $statusClass='';
            }
        }

        echo "<tr>
                <td>{$row['title']}</td>
                <td>$assigned</td>
                <td><span class='$statusClass'>{$row['status']}</span></td>
                <td>{$row['deadline']}</td>
              </tr>";
    }
    echo "</table>";
}else echo "<p>No tasks found.</p>";
?>

<!-- Modals (optional: keep same status badges inside) -->
<?php
function generateModal($id,$title,$query,$today){
    global $conn;
    echo "<div id='$id' class='modal'><div class='modal-content'><span class='modal-close' onclick=\"closeModal('$id')\">&times;</span><h5>$title</h5>";
    $r=$conn->query($query);
    if($r->num_rows){
        echo "<table class='styled-table'><tr><th>Task</th><th>Assigned To</th><th>Status</th><th>Deadline</th></tr>";
        while($row=$r->fetch_assoc()){
            $assigned=$row['assigned_to'] ?? 'Unassigned';
            $isOver=($row['deadline']<$today && $row['status']!='Completed');
            if($isOver) $statusClass='status-overdue';
            else{
                switch($row['status']){
                    case 'Not Started': $statusClass='status-not-started'; break;
                    case 'In Progress': $statusClass='status-in-progress'; break;
                    case 'Pending': $statusClass='status-pending'; break;
                    case 'Completed': $statusClass='status-completed'; break;
                    default: $statusClass='';
                }
            }
            echo "<tr>
                    <td>{$row['title']}</td>
                    <td>$assigned</td>
                    <td><span class='$statusClass'>{$row['status']}</span></td>
                    <td>{$row['deadline']}</td>
                  </tr>";
        }
        echo "</table>";
    } else { echo "<p>No tasks.</p>"; }
    echo "</div></div>";
}
generateModal('overdueModal','Overdue Tasks',"SELECT t.title, u.fullname AS assigned_to, t.status, t.deadline FROM tasks t LEFT JOIN users u ON t.assigned_to=u.id WHERE t.deadline<'$today' AND t.status!='Completed'",$today);
generateModal('completedModal','Completed Tasks',"SELECT t.title, u.fullname AS assigned_to, t.status, t.deadline FROM tasks t LEFT JOIN users u ON t.assigned_to=u.id WHERE t.status='Completed'",$today);
generateModal('pendingModal','Pending Tasks',"SELECT t.title, u.fullname AS assigned_to, t.status, t.deadline FROM tasks t LEFT JOIN users u ON t.assigned_to=u.id WHERE t.status IN ('Not Started','In Progress','Pending') ORDER BY t.deadline ASC",$today);
?>

<script>
// Pie Chart (3 slices: Overdue, Completed, Pending)
new Chart(document.getElementById('statusChart'), {
    type:'pie',
    data:{
        labels: <?= json_encode($pieLabels) ?>,
        datasets:[{
            data: <?= json_encode($pieData) ?>,
            backgroundColor: <?= json_encode($pieColors) ?>
        }]
    },
    options:{
        responsive:true,
        plugins:{
            legend:{ display:true, position:'right' },
            tooltip:{ callbacks:{ label:function(context){ return context.label + ': ' + context.parsed; } } }
        }
    }
});

// Employee Bar Chart
new Chart(document.getElementById('employeeChart'), {
    type:'bar',
    data:{ labels: <?= json_encode($empNames) ?>, datasets:[{ label:'Avg Rating', data: <?= json_encode($empRatings) ?>, backgroundColor:'#007bff' }] },
    options:{ responsive:true, plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true} } }
});

// Modals
function openModal(id){document.getElementById(id).style.display='flex';}
function closeModal(id){document.getElementById(id).style.display='none';}
window.onclick = function(e){ document.querySelectorAll('.modal').forEach(m=>{if(e.target==m)m.style.display='none';}); }
</script>
</body>
</html>
