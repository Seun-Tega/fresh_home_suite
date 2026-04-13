<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get report parameters
$report_type = $_GET['type'] ?? 'revenue';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Generate reports based on type
$report_data = [];
$chart_data = [];

if ($report_type == 'revenue') {
    // Revenue Report
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as bookings,
            SUM(total_amount) as revenue,
            'room' as type
        FROM room_bookings 
        WHERE payment_status = 'verified' 
        AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        UNION ALL
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as bookings,
            SUM(total_amount) as revenue,
            'hall' as type
        FROM hall_bookings 
        WHERE payment_status = 'verified' 
        AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute([$date_from, $date_to, $date_from, $date_to]);
    $report_data = $stmt->fetchAll();
    
    // Calculate totals
    $total_revenue = 0;
    $total_bookings = 0;
    foreach($report_data as $row) {
        $total_revenue += $row['revenue'];
        $total_bookings += $row['bookings'];
    }
    
    // Prepare chart data
    $dates = array_unique(array_column($report_data, 'date'));
    sort($dates);
    
    foreach($dates as $date) {
        $chart_data['labels'][] = $date;
        $room_rev = 0;
        $hall_rev = 0;
        
        foreach($report_data as $row) {
            if ($row['date'] == $date) {
                if ($row['type'] == 'room') {
                    $room_rev = $row['revenue'];
                } else {
                    $hall_rev = $row['revenue'];
                }
            }
        }
        
        $chart_data['room'][] = $room_rev;
        $chart_data['hall'][] = $hall_rev;
    }
}

elseif ($report_type == 'occupancy') {
    // Occupancy Report
    $stmt = $pdo->prepare("
        SELECT 
            r.room_type,
            COUNT(DISTINCT r.id) as total_rooms,
            COUNT(DISTINCT rb.id) as booked_days,
            SUM(DATEDIFF(rb.check_out, rb.check_in)) as nights_booked,
            AVG(rb.total_amount) as avg_rate
        FROM rooms r
        LEFT JOIN room_bookings rb ON r.id = rb.room_id 
            AND rb.booking_status IN ('confirmed', 'checked_in')
            AND rb.check_in >= ? 
            AND rb.check_out <= ?
        GROUP BY r.room_type
    ");
    $stmt->execute([$date_from, $date_to]);
    $report_data = $stmt->fetchAll();
    
    // Calculate total nights in period
    $total_nights = (strtotime($date_to) - strtotime($date_from)) / (60 * 60 * 24);
    
    foreach($report_data as &$row) {
        $row['occupancy_rate'] = $total_nights > 0 ? 
            round(($row['nights_booked'] / ($row['total_rooms'] * $total_nights)) * 100, 2) : 0;
    }
}

elseif ($report_type == 'hall_utilization') {
    // Hall Utilization Report
    $stmt = $pdo->prepare("
        SELECT 
            DATE(booking_date) as date,
            COUNT(*) as bookings,
            SUM(TIMESTAMPDIFF(HOUR, start_time, end_time)) as hours_booked,
            SUM(total_amount) as revenue
        FROM hall_bookings 
        WHERE booking_status = 'confirmed'
        AND booking_date BETWEEN ? AND ?
        GROUP BY DATE(booking_date)
        ORDER BY date
    ");
    $stmt->execute([$date_from, $date_to]);
    $report_data = $stmt->fetchAll();
    
    // Calculate totals
    $total_hours = 0;
    $total_revenue = 0;
    foreach($report_data as $row) {
        $total_hours += $row['hours_booked'];
        $total_revenue += $row['revenue'];
    }
}

elseif ($report_type == 'menu_popularity') {
    // Menu Popularity Report
    $stmt = $pdo->prepare("
        SELECT 
            fi.name,
            fc.name as category,
            COUNT(*) as order_count,
            SUM(fi.price) as revenue
        FROM food_items fi
        JOIN food_categories fc ON fi.category_id = fc.id
        LEFT JOIN order_items oi ON fi.id = oi.food_item_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at BETWEEN ? AND ?
        GROUP BY fi.id
        ORDER BY order_count DESC
        LIMIT 20
    ");
    $stmt->execute([$date_from, $date_to]);
    $report_data = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Fresh Home & Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #0F0F0F;
            min-height: 100vh;
        }
        .sidebar {
            background: rgba(15, 15, 15, 0.95);
            border-right: 1px solid rgba(201, 164, 90, 0.2);
        }
        .card-gradient {
            background: linear-gradient(145deg, rgba(201, 164, 90, 0.1) 0%, rgba(15, 15, 15, 0.95) 100%);
            border: 1px solid rgba(201, 164, 90, 0.2);
        }
        .heading-gold {
            color: #C9A45A;
        }
        .text-gold {
            color: #C9A45A;
        }
        .text-gold-hover:hover {
            color: #A8843F;
        }
        .border-gold {
            border-color: #C9A45A;
        }
        .bg-gold {
            background-color: #C9A45A;
        }
        .bg-gold-hover:hover {
            background-color: #A8843F;
        }
        .table-row-hover:hover {
            background: rgba(201, 164, 90, 0.05);
        }
        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(201, 164, 90, 0.2);
            color: #F5F5F5;
        }
        .input-field:focus {
            border-color: #C9A45A;
            outline: none;
            ring: 2px solid #C9A45A;
        }
        .input-field option {
            background: #0F0F0F;
            color: #F5F5F5;
        }
        .stat-card {
            background: rgba(201, 164, 90, 0.05);
            border: 1px solid rgba(201, 164, 90, 0.2);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            border-color: #C9A45A;
            transform: translateY(-2px);
        }
        .progress-bar {
            background: rgba(201, 164, 90, 0.2);
        }
        .progress-fill {
            background: #C9A45A;
        }
    </style>
</head>
<body class="text-[#F5F5F5]">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar w-64 p-6 overflow-y-auto">
            <div class="text-center mb-8">
                <img src="../assets/images/logo.png" alt="Logo" class="h-16 mx-auto mb-4">
                <h2 class="text-xl font-bold heading-gold">Admin Panel</h2>
                <p class="text-sm text-[#F5F5F5]/60">Welcome, <?php echo $_SESSION['admin_name']; ?></p>
            </div>
            
            <nav class="space-y-2">
                <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-dashboard text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Dashboard</span>
                </a>
                <a href="bookings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-calendar-check text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Bookings</span>
                </a>
                <a href="receipts.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-receipt text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Receipts</span>
                </a>
                <a href="rooms.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-bed text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Rooms</span>
                </a>
                <a href="hall.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-building text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Event Hall</span>
                </a>
                <a href="menu.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-utensils text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Restaurant Menu</span>
                </a>
                <a href="media.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-images text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Media Library</span>
                </a>
                <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-[#C9A45A]/20 border-l-4 border-[#C9A45A]">
                    <i class="fas fa-chart-bar text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Reports</span>
                </a>
                <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-users text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Users</span>
                </a>
                <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-cog text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Settings</span>
                </a>
                <hr class="border-[#C9A45A]/20 my-4">
                <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-sign-out-alt text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Logout</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <h1 class="text-3xl font-bold heading-gold mb-8">Reports & Analytics</h1>
            
            <!-- Report Filters -->
            <div class="card-gradient rounded-2xl p-6 mb-8">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-[#C9A45A] mb-2 font-medium">Report Type</label>
                        <select name="type" class="w-full px-4 py-2 rounded-lg input-field">
                            <option value="revenue" <?php echo $report_type == 'revenue' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Revenue Report</option>
                            <option value="occupancy" <?php echo $report_type == 'occupancy' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Room Occupancy</option>
                            <option value="hall_utilization" <?php echo $report_type == 'hall_utilization' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Hall Utilization</option>
                            <option value="menu_popularity" <?php echo $report_type == 'menu_popularity' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Menu Popularity</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-[#C9A45A] mb-2 font-medium">From Date</label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>" 
                               class="w-full px-4 py-2 rounded-lg input-field">
                    </div>
                    
                    <div>
                        <label class="block text-[#C9A45A] mb-2 font-medium">To Date</label>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>" 
                               class="w-full px-4 py-2 rounded-lg input-field">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" 
                                class="bg-[#C9A45A] text-[#0F0F0F] px-6 py-2 rounded-lg hover:bg-[#A8843F] transition w-full font-semibold">
                            <i class="fas fa-search mr-2"></i> Generate
                        </button>
                    </div>
                    
                    <div class="flex items-end">
                        <a href="export-report.php?type=<?php echo $report_type; ?>&from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>" 
                           class="bg-[#C9A45A]/20 text-[#C9A45A] border border-[#C9A45A] px-6 py-2 rounded-lg hover:bg-[#C9A45A] hover:text-[#0F0F0F] transition w-full text-center font-semibold">
                            <i class="fas fa-download mr-2"></i> Export
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Report Display -->
            <div class="card-gradient rounded-2xl p-8">
                <?php if($report_type == 'revenue'): ?>
                    <!-- Revenue Report -->
                    <h2 class="text-2xl font-bold heading-gold mb-6">Revenue Report</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="stat-card rounded-xl p-6 text-center">
                            <p class="text-[#F5F5F5]/60 mb-2">Total Revenue</p>
                            <p class="text-3xl font-bold heading-gold"><?php echo formatCurrency($total_revenue ?? 0); ?></p>
                        </div>
                        
                        <div class="stat-card rounded-xl p-6 text-center">
                            <p class="text-[#F5F5F5]/60 mb-2">Total Bookings</p>
                            <p class="text-3xl font-bold text-[#F5F5F5]"><?php echo $total_bookings ?? 0; ?></p>
                        </div>
                        
                        <div class="stat-card rounded-xl p-6 text-center">
                            <p class="text-[#F5F5F5]/60 mb-2">Average per Booking</p>
                            <p class="text-3xl font-bold heading-gold">
                                <?php echo ($total_bookings ?? 0) > 0 ? formatCurrency(($total_revenue ?? 0) / $total_bookings) : formatCurrency(0); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Revenue Chart -->
                    <div class="h-96 mb-8 bg-[#0F0F0F]/50 rounded-xl p-4">
                        <canvas id="revenueChart"></canvas>
                    </div>
                    
                    <!-- Revenue Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-[#C9A45A]/20">
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Date</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Room Revenue</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Hall Revenue</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Total Revenue</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Bookings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $grouped = [];
                                foreach($report_data as $row) {
                                    $grouped[$row['date']][$row['type']] = $row;
                                }
                                
                                foreach($grouped as $date => $types):
                                ?>
                                <tr class="border-b border-[#C9A45A]/10 table-row-hover transition-colors">
                                    <td class="py-3 text-[#F5F5F5]"><?php echo $date; ?></td>
                                    <td class="py-3 text-[#F5F5F5]"><?php echo formatCurrency($types['room']['revenue'] ?? 0); ?></td>
                                    <td class="py-3 text-[#F5F5F5]"><?php echo formatCurrency($types['hall']['revenue'] ?? 0); ?></td>
                                    <td class="py-3 heading-gold font-bold"><?php echo formatCurrency(($types['room']['revenue'] ?? 0) + ($types['hall']['revenue'] ?? 0)); ?></td>
                                    <td class="py-3 text-[#F5F5F5]"><?php echo ($types['room']['bookings'] ?? 0) + ($types['hall']['bookings'] ?? 0); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <script>
                    const ctx = document.getElementById('revenueChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($chart_data['labels'] ?? []); ?>,
                            datasets: [
                                {
                                    label: 'Room Revenue',
                                    data: <?php echo json_encode($chart_data['room'] ?? []); ?>,
                                    borderColor: '#C9A45A',
                                    backgroundColor: 'rgba(201, 164, 90, 0.2)',
                                    tension: 0.4
                                },
                                {
                                    label: 'Hall Revenue',
                                    data: <?php echo json_encode($chart_data['hall'] ?? []); ?>,
                                    borderColor: '#A8843F',
                                    backgroundColor: 'rgba(168, 132, 63, 0.2)',
                                    tension: 0.4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(201, 164, 90, 0.1)'
                                    },
                                    ticks: {
                                        color: '#F5F5F5',
                                        callback: function(value) {
                                            return '₦' + value.toLocaleString();
                                        }
                                    }
                                },
                                x: {
                                    grid: {
                                        color: 'rgba(201, 164, 90, 0.1)'
                                    },
                                    ticks: {
                                        color: '#F5F5F5'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    labels: {
                                        color: '#F5F5F5'
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            label += '₦' + context.raw.toLocaleString();
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                    </script>
                    
                <?php elseif($report_type == 'occupancy'): ?>
                    <!-- Occupancy Report -->
                    <h2 class="text-2xl font-bold heading-gold mb-6">Room Occupancy Report</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="stat-card rounded-xl p-6 text-center">
                            <p class="text-[#F5F5F5]/60 mb-2">Average Occupancy Rate</p>
                            <p class="text-3xl font-bold heading-gold">
                                <?php 
                                $avg_occupancy = 0;
                                $count = count($report_data);
                                if($count > 0) {
                                    foreach($report_data as $row) {
                                        $avg_occupancy += $row['occupancy_rate'];
                                    }
                                    echo round($avg_occupancy / $count, 2) . '%';
                                } else {
                                    echo '0%';
                                }
                                ?>
                            </p>
                        </div>
                        
                        <div class="stat-card rounded-xl p-6 text-center">
                            <p class="text-[#F5F5F5]/60 mb-2">Total Nights Booked</p>
                            <p class="text-3xl font-bold text-[#F5F5F5]">
                                <?php 
                                $total_nights = array_sum(array_column($report_data, 'nights_booked'));
                                echo $total_nights;
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-[#C9A45A]/20">
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Room Type</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Total Rooms</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Nights Booked</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Occupancy Rate</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Average Rate</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($report_data as $row): ?>
                                <tr class="border-b border-[#C9A45A]/10 table-row-hover transition-colors">
                                    <td class="py-3 text-[#F5F5F5] font-semibold"><?php echo $row['room_type']; ?></td>
                                    <td class="py-3 text-[#F5F5F5]"><?php echo $row['total_rooms']; ?></td>
                                    <td class="py-3 text-[#F5F5F5]"><?php echo $row['nights_booked']; ?></td>
                                    <td class="py-3">
                                        <div class="flex items-center">
                                            <span class="mr-2 text-[#F5F5F5]"><?php echo $row['occupancy_rate']; ?>%</span>
                                            <div class="w-24 progress-bar rounded-full h-2">
                                                <div class="progress-fill h-2 rounded-full" style="width: <?php echo $row['occupancy_rate']; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 heading-gold"><?php echo formatCurrency($row['avg_rate']); ?></td>
                                    <td class="py-3 heading-gold"><?php echo formatCurrency($row['nights_booked'] * $row['avg_rate']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php elseif($report_type == 'hall_utilization'): ?>
                    <!-- Hall Utilization Report -->
                    <h2 class="text-2xl font-bold heading-gold mb-6">Hall Utilization Report</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="stat-card rounded-xl p-6 text-center">
                            <p class="text-[#F5F5F5]/60 mb-2">Total Bookings</p>
                            <p class="text-3xl font-bold text-[#F5F5F5]"><?php echo count($report_data); ?></p>
                        </div>
                        
                        <div class="stat-card rounded-xl p-6 text-center">
                            <p class="text-[#F5F5F5]/60 mb-2">Total Hours Booked</p>
                            <p class="text-3xl font-bold heading-gold"><?php echo $total_hours ?? 0; ?></p>
                        </div>
                        
                        <div class="stat-card rounded-xl p-6 text-center">
                            <p class="text-[#F5F5F5]/60 mb-2">Total Revenue</p>
                            <p class="text-3xl font-bold heading-gold"><?php echo formatCurrency($total_revenue ?? 0); ?></p>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-[#C9A45A]/20">
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Date</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Bookings</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Hours Booked</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Revenue</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Avg per Booking</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($report_data as $row): ?>
                                <tr class="border-b border-[#C9A45A]/10 table-row-hover transition-colors">
                                    <td class="py-3 text-[#F5F5F5]"><?php echo $row['date']; ?></td>
                                    <td class="py-3 text-[#F5F5F5]"><?php echo $row['bookings']; ?></td>
                                    <td class="py-3 text-[#F5F5F5]"><?php echo $row['hours_booked']; ?></td>
                                    <td class="py-3 heading-gold"><?php echo formatCurrency($row['revenue']); ?></td>
                                    <td class="py-3 heading-gold"><?php echo formatCurrency($row['revenue'] / $row['bookings']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php elseif($report_type == 'menu_popularity'): ?>
                    <!-- Menu Popularity Report -->
                    <h2 class="text-2xl font-bold heading-gold mb-6">Menu Popularity Report</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="stat-card rounded-xl p-6 text-center">
                            <p class="text-[#F5F5F5]/60 mb-2">Total Items Ordered</p>
                            <p class="text-3xl font-bold text-[#F5F5F5]"><?php echo array_sum(array_column($report_data, 'order_count')); ?></p>
                        </div>
                        
                        <div class="stat-card rounded-xl p-6 text-center">
                            <p class="text-[#F5F5F5]/60 mb-2">Total Food Revenue</p>
                            <p class="text-3xl font-bold heading-gold"><?php echo formatCurrency(array_sum(array_column($report_data, 'revenue'))); ?></p>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-[#C9A45A]/20">
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Item Name</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Category</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Times Ordered</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Revenue</th>
                                    <th class="text-left py-3 text-[#C9A45A] font-semibold">Popularity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $max_orders = !empty($report_data) ? max(array_column($report_data, 'order_count')) : 1;
                                foreach($report_data as $row): 
                                $popularity = ($row['order_count'] / $max_orders) * 100;
                                ?>
                                <tr class="border-b border-[#C9A45A]/10 table-row-hover transition-colors">
                                    <td class="py-3 font-semibold heading-gold"><?php echo $row['name']; ?></td>
                                    <td class="py-3 text-[#F5F5F5]"><?php echo $row['category']; ?></td>
                                    <td class="py-3 text-[#F5F5F5]"><?php echo $row['order_count']; ?></td>
                                    <td class="py-3 heading-gold"><?php echo formatCurrency($row['revenue']); ?></td>
                                    <td class="py-3">
                                        <div class="flex items-center">
                                            <span class="mr-2 text-[#F5F5F5]"><?php echo round($popularity); ?>%</span>
                                            <div class="w-24 progress-bar rounded-full h-2">
                                                <div class="progress-fill h-2 rounded-full" style="width: <?php echo $popularity; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
</html>