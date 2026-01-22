<?php
@include 'config.php';
session_start();

// FIX: Sync Timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

$delivery_id = $_SESSION['delivery_id'];
if(!isset($delivery_id)){ header('location:login.php'); exit(); }

// --- 1. ACTIVE ORDERS ---
$stmt_active = $conn->prepare("SELECT COUNT(*) FROM `orders` WHERE (delivery_status = 'Preparing Order' OR delivery_status LIKE 'On Route%') AND delivery_rider = ?");
$stmt_active->execute([$delivery_id]);
$total_active = $stmt_active->fetchColumn();

// --- 2. COMPLETED ORDERS ---
$stmt_done = $conn->prepare("SELECT COUNT(*) FROM `orders` WHERE delivery_status LIKE 'Delivered%' AND delivery_rider = ?");
$stmt_done->execute([$delivery_id]);
$total_done = $stmt_done->fetchColumn();

// --- 3. REAL TOTAL EARNINGS (From Database) ---
// Summing the 'delivery_fee' column for all delivered orders by this rider
$stmt_earn = $conn->prepare("SELECT SUM(delivery_fee) FROM `orders` WHERE delivery_status LIKE 'Delivered%' AND delivery_rider = ?");
$stmt_earn->execute([$delivery_id]);
$raw_earnings = $stmt_earn->fetchColumn();
$total_earnings = $raw_earnings ? $raw_earnings : 0.00;

// --- 4. MONTHLY EARNINGS DATA FOR GRAPH ---
// Grouping by Month/Year to get data points
$stmt_graph = $conn->prepare("
    SELECT 
        DATE_FORMAT(placed_on, '%M') as month_name, 
        SUM(delivery_fee) as total_earned 
    FROM `orders` 
    WHERE delivery_rider = ? AND delivery_status LIKE 'Delivered%'
    GROUP BY YEAR(placed_on), MONTH(placed_on) 
    ORDER BY placed_on ASC 
    LIMIT 6
");
$stmt_graph->execute([$delivery_id]);

$months = [];
$earnings_data = [];

while($row = $stmt_graph->fetch(PDO::FETCH_ASSOC)){
    $months[] = $row['month_name'];
    $earnings_data[] = $row['total_earned'];
}

// Convert PHP arrays to JSON for JavaScript
$json_months = json_encode($months);
$json_earnings = json_encode($earnings_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Dashboard | Rider Panel</title>

   <script src="https://cdn.tailwindcss.com"></script>
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

   <style>
      /* === NUCLEAR DESIGN SYSTEM === */
      html, body {
         margin: 0; padding: 0;
         height: 100vh; 
         overflow: hidden !important;
         font-family: 'Inter', sans-serif;
         background-color: #ffffff !important;
         color: #000;
      }

      /* LAYOUT */
      .master-scroll-wrapper {
         height: 100vh; width: 100%; display: flex; justify-content: center; background-color: #fff;
      }
      .content-container {
         width: 100%; max-width: 1400px; height: 100%; display: grid;
         grid-template-columns: 280px minmax(0, 1fr); gap: 60px; padding: 0 30px;
      }
      .sidebar-scroll { height: 100%; overflow-y: auto; padding-top: 40px; scrollbar-width: none; }
      .content-scroll { height: 100%; overflow-y: auto; padding-top: 40px; padding-bottom: 100px; scrollbar-width: none; }
      
      /* STAT CARDS */
      .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-bottom: 40px; }
      .stat-card {
         background: #fff; border-radius: 24px; padding: 30px;
         border: 1px solid #f3f4f6; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
         transition: transform 0.3s ease, box-shadow 0.3s ease;
         display: flex; flex-direction: column; justify-content: space-between;
      }
      .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1); border-color: #000; }
      
      .stat-icon {
         width: 45px; height: 45px; background: #f8fafc; border-radius: 12px;
         display: flex; align-items: center; justify-content: center;
         font-size: 1.2rem; margin-bottom: 20px; color: #1e293b;
      }
      .stat-value { font-size: 2.5rem; font-weight: 900; color: #000; line-height: 1; margin-bottom: 5px; letter-spacing: -2px; }
      .stat-label { font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }

      /* CHART CARD */
      .chart-card {
         background: #fff; border-radius: 24px; padding: 35px;
         border: 1px solid #f3f4f6; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
         position: relative;
      }
      .chart-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
      .chart-title { font-size: 1.2rem; font-weight: 800; color: #000; }
      .chart-sub { font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; }

      /* BADGES */
      .alert-badge {
         display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 8px;
         font-size: 0.65rem; font-weight: 800; text-transform: uppercase; margin-top: 15px; width: fit-content;
      }
      .badge-blue { background: #eff6ff; color: #2563eb; }
      .badge-green { background: #f0fdf4; color: #16a34a; }
      .badge-purple { background: #faf5ff; color: #9333ea; }

      @media (max-width: 1024px) {
         .content-container { grid-template-columns: 1fr; }
         .sidebar-scroll { display: none; }
      }
   </style>
</head>
<body>

<div class="master-scroll-wrapper">
   <div class="content-container">
      
      <div class="sidebar-scroll">
         <?php include 'du_header.php'; ?>
      </div>

      <div class="content-scroll">
         
         <div class="mb-10">
            <h1 class="text-4xl font-black text-black uppercase tracking-tighter">Overview</h1>
            <p class="text-sm font-bold text-gray-400 tracking-wide uppercase mt-1">Rider Performance Metrics</p>
         </div>

         <div class="stat-grid">
            <div class="stat-card">
               <div class="stat-icon"><i class="fas fa-motorcycle"></i></div>
               <div>
                  <div class="stat-value"><?= number_format($total_active) ?></div>
                  <div class="stat-label">Active Jobs</div>
                  <div class="alert-badge badge-blue">In Progress</div>
               </div>
               <a href="du_your_order.php" class="mt-4 text-[10px] font-bold uppercase tracking-widest hover:underline text-blue-600">View Active &rarr;</a>
            </div>

            <div class="stat-card">
               <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
               <div>
                  <div class="stat-value"><?= number_format($total_done) ?></div>
                  <div class="stat-label">Total Delivered</div>
                  <div class="alert-badge badge-green">Lifetime</div>
               </div>
               <a href="du_completed.php" class="mt-4 text-[10px] font-bold uppercase tracking-widest hover:underline text-green-600">View History &rarr;</a>
            </div>

            <div class="stat-card">
               <div class="stat-icon"><i class="fas fa-wallet"></i></div>
               <div>
                  <div class="stat-value">RM<?= number_format($total_earnings, 2) ?></div>
                  <div class="stat-label">Total Earnings</div>
                  <div class="alert-badge badge-purple">Confirmed Income</div>
               </div>
            </div>
         </div>

         <div class="chart-card">
             <div class="chart-header">
                 <div>
                     <div class="chart-sub">Performance</div>
                     <div class="chart-title">Monthly Earnings Overview</div>
                 </div>
                 <div class="text-xs font-bold text-gray-400">LAST 6 MONTHS</div>
             </div>
             
             <div style="height: 300px; width: 100%;">
                 <canvas id="earningChart"></canvas>
             </div>
         </div>

      </div>

   </div>
</div>

<script>
    // --- CHART.JS CONFIGURATION ---
    const ctx = document.getElementById('earningChart').getContext('2d');
    
    // Data from PHP
    const labels = <?= $json_months ?>;
    const dataPoints = <?= $json_earnings ?>;

    // Gradient Fill
    let gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(147, 51, 234, 0.2)'); // Purple tint
    gradient.addColorStop(1, 'rgba(147, 51, 234, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length > 0 ? labels : ['No Data'],
            datasets: [{
                label: 'Earnings (RM)',
                data: dataPoints.length > 0 ? dataPoints : [0],
                borderColor: '#9333ea', // Purple line
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#9333ea',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                fill: true,
                tension: 0.4 // Smooth curve
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#000',
                    titleFont: { family: 'Inter', size: 13 },
                    bodyFont: { family: 'Inter', size: 13, weight: 'bold' },
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return ' RM ' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f3f4f6', borderDash: [5, 5] },
                    ticks: {
                        font: { family: 'Inter', size: 10, weight: 'bold' },
                        color: '#94a3b8',
                        callback: function(value) { return 'RM' + value; }
                    },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { family: 'Inter', size: 11, weight: 'bold' },
                        color: '#64748b'
                    },
                    border: { display: false }
                }
            }
        }
    });
</script>

<script src="js/script.js"></script>

</body>
</html>