<?php
session_start();
require '../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit;
}

// Fetch all reports with related information
$query = "
    SELECT 
        r.report_id,
        r.post_id,
        r.report_reason,
        r.post_content,
        r.bubble_name,
        r.report_status,
        r.report_date,
        reporter.username as reporter_username,
        post_owner.username as post_owner_username
    FROM reports r
    JOIN users reporter ON r.reporter_id = reporter.id
    JOIN users post_owner ON r.post_owner_id = post_owner.id
    ORDER BY r.report_date DESC
";

$result = $conn->query($query);
$reports = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timeline Reports - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }
        .status-reviewed {
            background-color: #DBEAFE;
            color: #1E40AF;
        }
        .status-resolved {
            background-color: #D1FAE5;
            color: #065F46;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-blue-900 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.html" class="flex items-center">
                        <img src="../ps.png" alt="PeerSync Logo" class="h-8 w-auto mr-2">
                        <span class="text-xl font-bold">PeerSync Admin</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.html" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-home mr-1"></i> Dashboard
                    </a>
                    <a href="../logout.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-800">Timeline Reports</h2>
            </div>

            <!-- Filters -->
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <div class="flex flex-wrap gap-4">
                    <div>
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700 mb-1 block">Filter by Status</span>
                            <select id="statusFilter" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="reviewed">Reviewed</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </label>
                    </div>
                    <div>
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700 mb-1 block">Search Reports</span>
                            <input type="text" id="searchInput" placeholder="Search reports..." 
                                   class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                    </div>
                </div>
            </div>

            <!-- Reports Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Report ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Post Content</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bubble</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reporter</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Post Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($reports as $report): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                #<?= htmlspecialchars($report['report_id']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="max-w-xs truncate">
                                    <?= htmlspecialchars($report['post_content']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($report['bubble_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($report['reporter_username']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($report['post_owner_username']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($report['report_reason']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="status-badge <?= 'status-' . $report['report_status'] ?>">
                                    <?= ucfirst(htmlspecialchars($report['report_status'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= date('M d, Y H:i', strtotime($report['report_date'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button onclick="updateStatus(<?= $report['report_id'] ?>, 'reviewed')" 
                                        class="text-blue-600 hover:text-blue-900" title="Mark as Reviewed">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button onclick="updateStatus(<?= $report['report_id'] ?>, 'resolved')" 
                                        class="text-green-600 hover:text-green-900" title="Mark as Resolved">
                                    <i class="fas fa-check-double"></i>
                                </button>
                                <button onclick="deleteReport(<?= $report['report_id'] ?>, <?= $report['post_id'] ?>)" 
                                        class="text-red-600 hover:text-red-900" title="Delete Report">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Filter functionality
        const statusFilter = document.getElementById('statusFilter');
        const searchInput = document.getElementById('searchInput');
        const tableRows = document.querySelectorAll('tbody tr');

        function filterTable() {
            const statusValue = statusFilter.value.toLowerCase();
            const searchValue = searchInput.value.toLowerCase();

            tableRows.forEach(row => {
                const status = row.querySelector('.status-badge').textContent.toLowerCase();
                const content = row.textContent.toLowerCase();
                const matchesStatus = !statusValue || status.includes(statusValue);
                const matchesSearch = !searchValue || content.includes(searchValue);
                row.style.display = matchesStatus && matchesSearch ? '' : 'none';
            });
        }

        statusFilter.addEventListener('change', filterTable);
        searchInput.addEventListener('input', filterTable);

        // Update report status
        function updateStatus(reportId, status) {
            if (!confirm(`Are you sure you want to mark this report as ${status}?`)) return;

            fetch('update_report_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    report_id: reportId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating report status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating report status');
            });
        }

        // Delete report and post
        function deleteReport(reportId, postId) {
            if (!confirm('Are you sure you want to delete this report and its associated post? This action cannot be undone.')) return;

            fetch('delete_reported_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    report_id: reportId,
                    post_id: postId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error deleting report');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting report');
            });
        }
    </script>
</body>
</html>
