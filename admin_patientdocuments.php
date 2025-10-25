<?php
// admin_patientdocuments.php
include 'database.php';

// Check if the user is logged in and is an admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirect if not logged in
    exit();
}

// Handle document status update
if (isset($_POST['update_document'])) {
    $document_id = $_POST['document_id'];
    $status = $_POST['status'];
    $comments = $_POST['comments'] ?? '';
    
    $stmt = $conn->prepare("UPDATE patient_documents SET approval_status = ?, approval_comments = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $comments, $document_id);
    
    if ($stmt->execute()) {
        $success_message = "Document status updated successfully!";
    } else {
        $error_message = "Error updating document status: " . $stmt->error;
    }
    $stmt->close();
}

// Handle document deletion
if (isset($_POST['delete_document'])) {
    $document_id = $_POST['document_id'];
    
    // Get file path before deletion
    $stmt = $conn->prepare("SELECT file_path FROM patient_documents WHERE id = ?");
    $stmt->bind_param("i", $document_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $document = $result->fetch_assoc();
    $stmt->close();
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM patient_documents WHERE id = ?");
    $stmt->bind_param("i", $document_id);
    
    if ($stmt->execute()) {
        // Delete physical file
        if ($document && file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }
        $success_message = "Document deleted successfully!";
    } else {
        $error_message = "Error deleting document: " . $stmt->error;
    }
    $stmt->close();
}

// Get all patient documents with patient details
$stmt = $conn->prepare("SELECT pd.*, r.Name as PatientName, r.Email as PatientEmail, r.Phone_no as PatientPhone
                       FROM patient_documents pd 
                       LEFT JOIN register r ON pd.Patient_id = r.Patient_id 
                       ORDER BY pd.upload_date DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $documents = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $documents = [];
    $error_message = "Patient documents table not found. Please create the patient_documents table first.";
}

// Get document statistics
$stmt_stats = $conn->prepare("SELECT 
    COUNT(*) as total_documents,
    COUNT(CASE WHEN approval_status = 'pending' THEN 1 END) as pending_documents,
    COUNT(CASE WHEN approval_status = 'approved' THEN 1 END) as approved_documents,
    COUNT(CASE WHEN approval_status = 'rejected' THEN 1 END) as rejected_documents
    FROM patient_documents");
if ($stmt_stats) {
    $stmt_stats->execute();
    $stats = $stmt_stats->get_result()->fetch_assoc();
    $stmt_stats->close();
} else {
    $stats = [
        'total_documents' => 0,
        'pending_documents' => 0,
        'approved_documents' => 0,
        'rejected_documents' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin - Patient Documents Management</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Yellow-Black Theme Styling */
        :root {
            --primary-color: #FFD700;
            /* Gold/Yellow */
            --secondary-color: #212121;
            /* Dark Gray/Black */
            --accent-color: #FFC107;
            /* Amber */
            --text-light: #FFFFFF;
            --text-dark: #212121;
            --danger-color: #FF3D00;
            --success-color: #00C853;
            --warning-color: #FF9800;
            --info-color: #2196F3;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            display: flex;
            color: var(--text-dark);
        }

        .container {
            display: flex;
            width: 100%;
            margin: 0;
            padding: 0;
            max-width: none;
        }

        .sidebar {
            background-color: var(--secondary-color);
            width: 250px;
            height: 100vh;
            padding: 20px;
            color: var(--text-light);
            position: fixed;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar .logo {
            border-bottom: 1px solid var(--primary-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .sidebar .logo h2 {
            font-size: 24px;
            text-align: center;
            color: var(--primary-color);
            font-weight: 700;
        }

        .nav {
            list-style-type: none;
            padding: 0;
        }

        .nav li {
            margin: 15px 0;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav li a {
            color: var(--text-light);
            text-decoration: none;
            font-size: 16px;
            display: block;
            padding: 10px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav li a:hover,
        .nav li a.active {
            background-color: var(--primary-color);
            color: var(--secondary-color);
            transform: translateX(5px);
        }

        .nav li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex-grow: 1;
            padding: 30px;
            margin-left: 250px;
            width: calc(100% - 250px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
        }

        .header .logo {
            font-size: 28px;
            font-weight: bold;
            color: var(--secondary-color);
        }

        .header .user-profile {
            display: flex;
            align-items: center;
        }

        .header .user-profile span {
            margin-right: 15px;
            font-weight: 500;
        }

        .header .user-profile a {
            color: var(--danger-color);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 5px;
            background-color: rgba(255, 61, 0, 0.1);
            transition: all 0.3s ease;
        }

        .header .user-profile a:hover {
            background-color: var(--danger-color);
            color: var(--text-light);
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 2.5rem;
            margin: 0;
            color: var(--primary-color);
        }

        .stat-card p {
            margin: 5px 0 0 0;
            color: var(--text-dark);
            font-weight: 500;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background-color: var(--secondary-color);
            color: var(--text-light);
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--secondary-color);
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--secondary-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
        }

        .btn-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
        }

        .table th {
            background-color: var(--secondary-color);
            color: var(--text-light);
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table tr:hover {
            background-color: #f1f1f1;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 30px;
            font-weight: 500;
            font-size: 12px;
        }

        .badge-pending {
            background-color: var(--warning-color);
            color: white;
        }

        .badge-approved {
            background-color: var(--success-color);
            color: white;
        }

        .badge-rejected {
            background-color: var(--danger-color);
            color: white;
        }

        .modal-content {
            border-radius: 10px;
            border: none;
        }

        .modal-header {
            background-color: var(--secondary-color);
            color: var(--text-light);
            border-radius: 10px 10px 0 0;
        }

        .modal-footer {
            border-top: none;
        }

        .document-preview {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .document-info {
            margin-bottom: 20px;
        }

        .document-info p {
            margin-bottom: 5px;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .file-icon {
            font-size: 2rem;
            margin-right: 10px;
        }

        .file-icon.pdf {
            color: #dc3545;
        }

        .file-icon.image {
            color: #28a745;
        }

        .file-icon.document {
            color: #007bff;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2>MEDI TRACK </h2>
            </div>
            <ul class="nav">
                <li>
                    <a href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="admin_patient.php">
                        <i class="fas fa-user"></i>
                        <span>Patients</span>
                    </a>
                </li>
                <li>
                    <a href="admin_doctors.php">
                        <i class="fas fa-user-md"></i>
                        <span>Doctors</span>
                    </a>
                </li>
                <li>
                    <a href="admin_bookings.php">
                        <i class="fas fa-calendar-check"></i>
                        <span>Bookings</span>
                    </a>
                </li>
                <li>
                    <a href="admin_patientdocuments.php" class="active">
                        <i class="fas fa-file-medical"></i>
                        <span>Patient Documents</span>
                    </a>
                </li>
                <li>
                    <a href="admin_prescriptions.php">
                        <i class="fas fa-prescription"></i>
                        <span>Prescriptions</span>
                    </a>
                </li>
                <li>
                    <a href="admin_billing.php">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Billing</span>
                    </a>
                </li>
                <li>
                    <a href="admin_logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="logo">Patient Documents Management</div>
                <div class="user-profile">
                    <span>Admin</span>
                    <a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if(isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <h3><?php echo $stats['total_documents']; ?></h3>
                    <p>Total Documents</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['pending_documents']; ?></h3>
                    <p>Pending Review</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['approved_documents']; ?></h3>
                    <p>Approved</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['rejected_documents']; ?></h3>
                    <p>Rejected</p>
                </div>
            </div>

            <!-- Documents Table -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-file-medical"></i> All Patient Documents</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Document Type</th>
                                    <th>File Name</th>
                                    <th>Upload Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($documents)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No documents found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($documents as $document): ?>
                                        <tr>
                                            <td><?php echo $document['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($document['PatientName']); ?></strong><br>
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($document['PatientEmail']); ?><br>
                                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($document['PatientPhone']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <i class="fas fa-file-alt file-icon"></i>
                                                <?php echo ucwords(str_replace('_', ' ', $document['document_type'])); ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($document['original_filename']); ?></strong><br>
                                                <small class="text-muted"><?php echo number_format($document['file_size'] / 1024, 2); ?> KB</small>
                                            </td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($document['upload_date'])); ?></td>
                                            <td>
                                                <?php if ($document['approval_status'] == 'pending'): ?>
                                                    <span class="badge badge-pending">Pending</span>
                                                <?php elseif ($document['approval_status'] == 'approved'): ?>
                                                    <span class="badge badge-approved">Approved</span>
                                                <?php elseif ($document['approval_status'] == 'rejected'): ?>
                                                    <span class="badge badge-rejected">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-info view-document" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#documentModal"
                                                            data-id="<?php echo $document['id']; ?>"
                                                            data-patient="<?php echo htmlspecialchars($document['PatientName']); ?>"
                                                            data-type="<?php echo $document['document_type']; ?>"
                                                            data-path="<?php echo $document['file_path']; ?>"
                                                            data-status="<?php echo $document['approval_status']; ?>"
                                                            data-comments="<?php echo htmlspecialchars($document['approval_comments']); ?>"
                                                            data-filename="<?php echo htmlspecialchars($document['original_filename']); ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this document?');">
                                                        <input type="hidden" name="document_id" value="<?php echo $document['id']; ?>">
                                                        <button type="submit" name="delete_document" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Details Modal -->
    <div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="documentModalLabel">Document Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="document-info">
                        <p><strong>Patient:</strong> <span id="modal-patient"></span></p>
                        <p><strong>Document Type:</strong> <span id="modal-type"></span></p>
                        <p><strong>File Name:</strong> <span id="modal-filename"></span></p>
                        <p><strong>Current Status:</strong> <span id="modal-status"></span></p>
                    </div>

                    <div class="document-preview-container">
                        <h6>Document Preview:</h6>
                        <div class="text-center">
                            <a href="#" id="document-link" target="_blank" class="btn btn-sm btn-primary mb-3">
                                <i class="fas fa-external-link-alt"></i> Open Document in New Tab
                            </a>
                        </div>
                        <div class="embed-responsive embed-responsive-16by9">
                            <iframe id="document-preview" class="embed-responsive-item"
                                style="width: 100%; height: 400px;" src="" allowfullscreen></iframe>
                        </div>
                    </div>

                    <form id="document-form" method="POST" action="">
                        <input type="hidden" name="document_id" id="document-id">
                        <div class="mb-3 mt-4">
                            <label for="status" class="form-label">Update Status:</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="comments" class="form-label">Comments:</label>
                            <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="update_document" class="btn btn-primary">Update Document
                                Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Handle document view button click
            $('.view-document').click(function () {
                var id = $(this).data('id');
                var patient = $(this).data('patient');
                var type = $(this).data('type');
                var path = $(this).data('path');
                var status = $(this).data('status');
                var comments = $(this).data('comments');
                var filename = $(this).data('filename');

                // Format the document type for display
                var formattedType = type.replace(/_/g, ' ').replace(/\b\w/g, function (l) { return l.toUpperCase(); });

                // Set modal values
                $('#document-id').val(id);
                $('#modal-patient').text(patient);
                $('#modal-type').text(formattedType);
                $('#modal-filename').text(filename);

                // Set status badge
                var statusHtml = '';
                if (status === 'pending') {
                    statusHtml = '<span class="badge badge-pending">Pending</span>';
                } else if (status === 'approved') {
                    statusHtml = '<span class="badge badge-approved">Approved</span>';
                } else if (status === 'rejected') {
                    statusHtml = '<span class="badge badge-rejected">Rejected</span>';
                }
                $('#modal-status').html(statusHtml);

                // Set document preview
                $('#document-preview').attr('src', path);
                $('#document-link').attr('href', path);

                // Set form values
                $('#status').val(status);
                $('#comments').val(comments || '');
            });
        });
    </script>
</body>

</html>