<?php
/**
 * Employee Profile System with Excel Import
 * Features: Employee ID, Clock In/Out, Total Hours calculation
 */

// Database configuration (modify as needed)
$db_host = 'localhost';
$db_name = 'employee_system';
$db_user = 'root';
$db_pass = '';

// Create database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle Excel file upload and import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    require_once 'vendor/autoload.php'; // PhpSpreadsheet library needed
    
    $file = $_FILES['excel_file']['tmp_name'];
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
    $worksheet = $spreadsheet->getActiveSheet();
    
    $importedCount = 0;
    foreach ($worksheet->getRowIterator(2) as $row) { // Start from row 2 (skip header)
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        
        $data = [];
        foreach ($cellIterator as $cell) {
            $data[] = $cell->getValue();
        }
        
        // Expecting columns: Employee ID, Employee Name, Clock In, Clock Out, Date
        if (!empty($data[0])) {
            $employee_id = $data[0];
            $employee_name = $data[1] ?? '';
            $clock_in = $data[2] ?? '';
            $clock_out = $data[3] ?? '';
            $date = $data[4] ?? date('Y-m-d');
            
            // Calculate total hours
            $total_hours = 0;
            if ($clock_in && $clock_out) {
                $in = new DateTime($clock_in);
                $out = new DateTime($clock_out);
                $interval = $in->diff($out);
                $total_hours = $interval->h + ($interval->i / 60);
            }
            
            // Insert into database
            $stmt = $pdo->prepare("INSERT INTO employee_attendance 
                (employee_id, employee_name, clock_in, clock_out, total_hours, date) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$employee_id, $employee_name, $clock_in, $clock_out, $total_hours, $date]);
            $importedCount++;
        }
    }
    
    $success_message = "$importedCount records imported successfully!";
}

// Fetch all employee records
$stmt = $pdo->query("SELECT * FROM employee_attendance ORDER BY date DESC, employee_id");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Search functionality
$search_results = [];
if (isset($_GET['search']) && !empty($_GET['employee_id'])) {
    $employee_id = $_GET['employee_id'];
    $stmt = $pdo->prepare("SELECT * FROM employee_attendance WHERE employee_id = ? ORDER BY date DESC");
    $stmt->execute([$employee_id]);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 30px;
        }
        
        .upload-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 2px dashed #667eea;
        }
        
        .upload-section h2 {
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            margin-right: 15px;
        }
        
        .file-input-wrapper input[type="file"] {
            display: none;
        }
        
        .file-input-label {
            background: #667eea;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .file-input-label:hover {
            background: #5568d3;
        }
        
        .upload-btn {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .upload-btn:hover {
            background: #218838;
        }
        
        .search-section {
            background: #e9ecef;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .search-section h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
        }
        
        .search-input {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .search-btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .search-btn:hover {
            background: #5568d3;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }
        
        table tr:hover {
            background: #f8f9fa;
        }
        
        .profile-card {
            background: white;
            border: 2px solid #667eea;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .employee-id {
            font-size: 1.3em;
            color: #667eea;
            font-weight: bold;
        }
        
        .employee-name {
            color: #333;
            font-size: 1.1em;
        }
        
        .time-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }
        
        .time-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
        
        .time-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        
        .time-value {
            font-size: 1.2em;
            color: #333;
            font-weight: bold;
        }
        
        .total-hours {
            background: #28a745;
            color: white;
        }
        
        .instructions {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .instructions h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .instructions ul {
            margin-left: 20px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Employee Profile System</h1>
            <p>Clock In/Out Tracking & Management</p>
        </div>
        
        <div class="content">
            <?php if (isset($success_message)): ?>
                <div class="success-message">
                    ✓ <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="instructions">
                <h4>📄 Excel File Format:</h4>
                <ul>
                    <li>Column A: Employee ID</li>
                    <li>Column B: Employee Name</li>
                    <li>Column C: Clock In (e.g., 09:00:00 or 2024-01-01 09:00:00)</li>
                    <li>Column D: Clock Out (e.g., 17:30:00 or 2024-01-01 17:30:00)</li>
                    <li>Column E: Date (e.g., 2024-01-01)</li>
                </ul>
            </div>
            
            <div class="upload-section">
                <h2>📤 Import Excel File</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="file-input-wrapper">
                        <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" required>
                        <label for="excel_file" class="file-input-label">Choose Excel File</label>
                    </div>
                    <button type="submit" class="upload-btn">Upload & Import</button>
                </form>
            </div>
            
            <div class="search-section">
                <h3>🔍 Search Employee</h3>
                <form method="GET" class="search-form">
                    <input type="text" name="employee_id" class="search-input" 
                           placeholder="Enter Employee ID" 
                           value="<?php echo isset($_GET['employee_id']) ? htmlspecialchars($_GET['employee_id']) : ''; ?>">
                    <button type="submit" name="search" class="search-btn">Search</button>
                </form>
            </div>
            
            <?php if (!empty($search_results)): ?>
                <h2>Search Results for Employee ID: <?php echo htmlspecialchars($_GET['employee_id']); ?></h2>
                <?php foreach ($search_results as $employee): ?>
                    <div class="profile-card">
                        <div class="profile-header">
                            <div>
                                <div class="employee-id">ID: <?php echo htmlspecialchars($employee['employee_id']); ?></div>
                                <div class="employee-name"><?php echo htmlspecialchars($employee['employee_name']); ?></div>
                            </div>
                            <div style="color: #666;">📅 <?php echo $employee['date']; ?></div>
                        </div>
                        <div class="time-info">
                            <div class="time-box">
                                <div class="time-label">Clock In</div>
                                <div class="time-value">🕐 <?php echo $employee['clock_in']; ?></div>
                            </div>
                            <div class="time-box">
                                <div class="time-label">Clock Out</div>
                                <div class="time-value">🕐 <?php echo $employee['clock_out']; ?></div>
                            </div>
                            <div class="time-box total-hours">
                                <div class="time-label">Total Hours</div>
                                <div class="time-value">⏱️ <?php echo number_format($employee['total_hours'], 2); ?> hrs</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <h2>All Employee Records</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Employee Name</th>
                            <th>Date</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Total Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #999;">
                                    No records found. Please upload an Excel file to import data.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($employee['employee_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($employee['employee_name']); ?></td>
                                    <td><?php echo $employee['date']; ?></td>
                                    <td><?php echo $employee['clock_in']; ?></td>
                                    <td><?php echo $employee['clock_out']; ?></td>
                                    <td><strong><?php echo number_format($employee['total_hours'], 2); ?> hrs</strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
