<?php
@include 'config.php';
session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:login.php');
}

// Data for daily sales
$data1 = '';
$data2 = '';

$select_orders = $conn->prepare("SELECT `placed_on`, SUM(`total_price`) AS total_sales 
                                FROM `orders` 
                                WHERE payment_status = ? 
                                GROUP BY `placed_on`");
$select_orders->execute(['completed']);

if ($select_orders->rowCount() > 0) {
    $chart_data = [];
    while ($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)) {
        $data1 .= '"' . $fetch_orders['placed_on'] . '",';
        $data2 .= $fetch_orders['total_sales'] . ',';
        $chart_data[] = [
            'date' => $fetch_orders['placed_on'],
            'sales' => $fetch_orders['total_sales']
        ];
    }
    $date = trim($data1, ",");
    $price = trim($data2, ",");
}

// Data for payment methods
$payment_data = $conn->prepare("SELECT method, COUNT(*) as count, SUM(total_price) as total 
                               FROM orders 
                               WHERE payment_status = 'completed' 
                               GROUP BY method");
$payment_data->execute();
$payment_labels = '';
$payment_counts = '';
$payment_totals = '';

while ($row = $payment_data->fetch(PDO::FETCH_ASSOC)) {
    $payment_labels .= '"' . $row['method'] . '",';
    $payment_counts .= $row['count'] . ',';
    $payment_totals .= $row['total'] . ',';
}

// Data for monthly trends
$monthly_trends = $conn->prepare("
    SELECT 
        DATE_FORMAT(STR_TO_DATE(placed_on, '%d-%m-%Y'), '%Y-%m') as month,
        COUNT(*) as order_count,
        SUM(total_price) as monthly_sales,
        AVG(total_price) as avg_order_value
    FROM orders 
    WHERE payment_status = 'completed'
    GROUP BY DATE_FORMAT(STR_TO_DATE(placed_on, '%d-%m-%Y'), '%Y-%m')
    ORDER BY month ASC
");
$monthly_trends->execute();

$months = '';
$monthly_sales = '';
$monthly_orders = '';
$avg_order_values = '';

while ($row = $monthly_trends->fetch(PDO::FETCH_ASSOC)) {
    $months .= '"' . $row['month'] . '",';
    $monthly_sales .= $row['monthly_sales'] . ',';
    $monthly_orders .= $row['order_count'] . ',';
    $avg_order_values .= $row['avg_order_value'] . ',';
}

// Data for product analysis
$product_data = $conn->prepare("
    SELECT 
        total_products,
        COUNT(*) as order_count,
        SUM(total_price) as total_revenue
    FROM orders
    WHERE payment_status = 'completed'
    GROUP BY total_products
    ORDER BY order_count DESC
    LIMIT 1000
");
$product_data->execute();

$product_labels = '';
$product_quantities = '';
$product_revenue = '';

while ($row = $product_data->fetch(PDO::FETCH_ASSOC)) {
    $product_labels .= '"' . $row['total_products'] . '",';
    $product_quantities .= $row['order_count'] . ',';
    $product_revenue .= $row['total_revenue'] . ',';
}

// Data for rider performance
$rider_data = $conn->prepare("
    SELECT 
        o.delivery_rider,
        u.name AS rider_name, -- Fetch rider's name
        COUNT(*) AS total_deliveries,
        SUM(o.total_price) AS total_revenue
    FROM orders o
    LEFT JOIN users u ON o.delivery_rider = u.id -- Join with users table to get rider name
    WHERE o.payment_status = 'completed'
    GROUP BY o.delivery_rider, u.name
    ORDER BY total_deliveries DESC
");



$rider_data->execute();

$rider_labels = '';
$rider_deliveries = '';
$rider_revenue = '';

while ($row = $rider_data->fetch(PDO::FETCH_ASSOC)) {
    $rider_labels .= '"' . $row['rider_name'] . '",';
    $rider_deliveries .= $row['total_deliveries'] . ',';
    $rider_revenue .= $row['total_revenue'] . ',';
}

// Export functionality
if (isset($_POST['export_data'])) {
    $export_type = $_POST['export_type'];
    $filename = 'report_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    switch ($export_type) {
        case 'sales':
            fputcsv($output, array('Date', 'Total Sales (RM)'));
            foreach ($chart_data as $row) {
                fputcsv($output, array($row['date'], number_format($row['sales'], 2)));
            }
            break;
            
        case 'products':
            fputcsv($output, array('Products', 'Order Count', 'Total Revenue (RM)'));
            $product_data->execute();
            while ($row = $product_data->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, array(
                    $row['total_products'],
                    $row['order_count'],
                    number_format($row['total_revenue'], 2)
                ));
            }
            break;
            
        case 'riders':
    fputcsv($output, array('Rider Name', 'Total Deliveries', 'Total Revenue (RM)'));  // Change 'Rider ID' to 'Rider Name'
    $rider_data->execute();
    while ($row = $rider_data->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, array(
            $row['rider_name'],    // Output rider's name instead of rider ID
            $row['total_deliveries'],
            number_format($row['total_revenue'], 2)
        ));
    }
    break;

    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Analysis Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .sales-dashboard {
            padding: 2rem;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .dashboard-title {
            font-size: 1.8rem;
            color: #2c3e50;
            margin: 0;
        }

        .dashboard-actions {
            display: flex;
            gap: 0.5rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }

        .chart-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
            height: 400px;
            display: flex;
            flex-direction: column;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .chart-header h2 {
            font-size: 1.2rem;
            color: #2c3e50;
            margin: 0;
        }

        .chart-body {
            flex: 1;
            position: relative;
            width: 100%;
            height: 100px;
        }

        .export-btn {
            padding: 0.75rem 1.5rem;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .export-btn:hover {
            background: #45a049;
        }

        .print-btn {
            background: #2196F3;
        }

        .print-btn:hover {
            background: #1976D2;
        }

        .product-analysis-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .product-analysis-container {
                grid-template-columns: 1fr;
            }

            .chart-container {
                height: 350px;
            }
        }

        @media (max-width: 768px) {
            .sales-dashboard {
                padding: 1rem;
            }

            .dashboard-header {
                flex-direction: column;
                gap: 1rem;
            }

            .dashboard-actions {
                width: 100%;
                flex-wrap: wrap;
            }

            .export-btn {
                width: 100%;
                justify-content: center;
            }

            .chart-container {
                height: 300px;
            }
        }

        @media print {
            .dashboard-actions {
                display: none;
            }
            
            .chart-container {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            
            .sales-dashboard {
                background: white;
                padding: 0;
            }
        }
    </style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<section class="sales-dashboard">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Sales Analysis Dashboard</h1>
        <div class="dashboard-actions">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="export_type" value="sales">
                <button type="submit" name="export_data" class="export-btn">
                    <i class="fas fa-download"></i> Export Sales Data
                </button>
            </form>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="export_type" value="products">
                <button type="submit" name="export_data" class="export-btn">
                    <i class="fas fa-box"></i> Export Product Data
                </button>
            </form>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="export_type" value="riders">
                <button type="submit" name="export_data" class="export-btn">
                    <i class="fas fa-motorcycle"></i> Export Rider Data
                </button>
            </form>
            <button onclick="window.print()" class="export-btn print-btn">
                <i class="fas fa-print"></i> Print Dashboard
            </button>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Daily Sales Chart -->
        <div class="chart-container">
            <div class="chart-header">
                <h2><i class="fas fa-chart-line"></i> Daily Sales Overview</h2>
            </div>
            <div class="chart-body">
                <canvas id="dailySalesChart"></canvas>
            </div>
        </div>

        <!-- Payment Methods Chart -->
        <div class="chart-container">
            <div class="chart-header">
                <h2><i class="fas fa-wallet"></i> Payment Methods Distribution</h2>
            </div>
            <div class="chart-body">
                <canvas id="paymentMethodsChart"></canvas>
            </div>
        </div>

        <!-- Product Analysis Charts -->
        <div class="chart-container">
            <div class="chart-header">
                <h2><i class="fas fa-box"></i> Order Distribution</h2>
            </div>
            <div class="chart-body">
                <canvas id="productAnalysisChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <div class="chart-header">
                <h2><i class="fas fa-money-bill"></i> Revenue Distribution</h2>
            </div>
            <div class="chart-body">
                <canvas id="productRevenueChart"></canvas>
            </div>
        </div>

        <!-- Monthly Trends Chart -->
        <div class="chart-container">
            <div class="chart-header">
                <h2><i class="fas fa-chart-bar"></i> Monthly Performance</h2>
            </div>
            <div class="chart-body">
                <canvas id="monthlyTrendsChart"></canvas>
            </div>
        </div>

        <!-- Rider Performance Chart -->
        <div class="chart-container">
            <div class="chart-header">
                <h2><i class="fas fa-motorcycle"></i> Rider Performance</h2>
            </div>
            <div class="chart-body">
                <canvas id="riderPerformanceChart"></canvas>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Your existing Chart.js configurations with updated options
const chartOptions = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                padding: 20,
                boxWidth: 12
            }
        }
    }
};

// Daily Sales Chart
const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
new Chart(dailySalesCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo $date ?>],
        datasets: [{
            label: 'Daily Sales (RM)',
            data: [<?php echo $price ?>],
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        ...chartOptions,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'RM' + value;
                    }
                }
            }
        }
    }
});

// Payment Methods Chart
const paymentCtx = document.getElementById('paymentMethodsChart').getContext('2d');
new Chart(paymentCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php echo trim($payment_labels, ',') ?>],
        datasets: [{
            data: [<?php echo trim($payment_counts, ',') ?>],
            backgroundColor: [
                'rgba(54, 162, 235, 0.6)',
                'rgba(255, 99, 132, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(255, 206, 86, 0.6)'
            ],
            borderWidth: 1
        }]
    },
    options: chartOptions
});

// Monthly Trends Chart
const monthlyCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: [<?php echo trim($months, ',') ?>],
        datasets: [{
            label: 'Monthly Sales (RM)',
            data: [<?php echo trim($monthly_sales, ',') ?>],
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            fill: true,
            tension: 0.4
        }, {
            label: 'Order Count',
            data: [<?php echo trim($monthly_orders, ',') ?>],
            borderColor: 'rgba(255, 99, 132, 1)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            fill: true,
            tension: 0.4,
            yAxisID: 'y1'
        }]
    },
    options: {
        ...chartOptions,
        scales: {
            y: {
                beginAtZero: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Sales (RM)'
                }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});

// Product Analysis Chart
const productCtx = document.getElementById('productAnalysisChart').getContext('2d');
new Chart(productCtx, {
    type: 'pie',
    data: {
        labels: [<?php echo trim($product_labels, ',') ?>],
        datasets: [{
            data: [<?php echo trim($product_quantities, ',') ?>],
            backgroundColor: [
                'rgba(54, 162, 235, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(255, 159, 64, 0.6)',
                'rgba(153, 102, 255, 0.6)',
                'rgba(255, 99, 132, 0.6)'
            ],
            borderWidth: 1
        }]
    },
    options: chartOptions
});

// Product Revenue Chart
const revenueCtx = document.getElementById('productRevenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php echo trim($product_labels, ',') ?>],
        datasets: [{
            data: [<?php echo trim($product_revenue, ',') ?>],
            backgroundColor: [
                'rgba(255, 99, 132, 0.6)',
                'rgba(255, 206, 86, 0.6)',
                'rgba(54, 162, 235, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(153, 102, 255, 0.6)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        ...chartOptions,
        plugins: {
            ...chartOptions.plugins,
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return `RM${context.raw.toFixed(2)}`;
                    }
                }
            }
        }
    }
});

// Rider Performance Chart
const riderCtx = document.getElementById('riderPerformanceChart').getContext('2d');
new Chart(riderCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo trim($rider_labels, ',') ?>],
        datasets: [{
            label: 'Total Deliveries',
            data: [<?php echo trim($rider_deliveries, ',') ?>],
            backgroundColor: 'rgba(75, 192, 192, 0.6)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1,
            yAxisID: 'y'
        }, {
            label: 'Revenue (RM)',
            data: [<?php echo trim($rider_revenue, ',') ?>],
            backgroundColor: 'rgba(255, 206, 86, 0.6)',
            borderColor: 'rgba(255, 206, 86, 1)',
            borderWidth: 1,
            yAxisID: 'y1'
        }]
    },
    options: {
        ...chartOptions,
        scales: {
            y: {
                beginAtZero: true,
                position: 'left'
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false
                },
                ticks: {
                    callback: function(value) {
                        return 'RM' + value;
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>