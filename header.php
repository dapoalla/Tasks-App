<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: login.php");
    exit;
}
require_once 'db.php';
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fund & Expense Tracker - WECA ICT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            900: '#0f172a', // Slate 900
                            800: '#1e293b', // Slate 800
                            700: '#334155', // Slate 700
                        },
                        accent: {
                            500: '#0ea5e9', // Sky 500
                            600: '#0284c7', // Sky 600
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom scrollbar for dark mode feel */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #0f172a; 
        }
        ::-webkit-scrollbar-thumb {
            background: #334155; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #475569; 
        }
        @media print {
            .no-print { display: none !important; }
            html, body { 
                background: white !important; 
                color: black !important; 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important;
            }
            /* Universal override for dark mode backgrounds */
            [class*="bg-gray-"], [class*="bg-slate-"], [class*="dark:bg-"] {
                background-color: white !important;
                background-image: none !important;
                color: black !important;
            }
            /* Ensure text is black everywhere */
            [class*="text-gray-"], [class*="text-slate-"], [class*="dark:text-"], h1, h2, h3, p, span, td, th {
                color: black !important;
            }
            /* Specialty color boxes (Success/Error/Warning) - keep background but light */
            .bg-emerald-50, .bg-rose-50, .bg-blue-50 {
                background-color: #f8fafc !important; /* Very light slate */
                border: 1px solid #e2e8f0 !important;
            }
            
            nav, header, footer, .menu-toggle { display: none !important; }
            .container { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
            
            /* Tables */
            table { border: 1px solid #ddd !important; }
            th { background-color: #f1f5f9 !important; border-bottom: 2px solid #000 !important; }
            td { border-bottom: 1px solid #eee !important; }
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 font-sans antialiased min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-gray-800 border-b border-gray-700 shadow-md sticky top-0 z-50 no-print">
        <div class="container mx-auto px-4 py-3 flex flex-wrap justify-between items-center">
            <a href="index.php" class="flex items-center hover:opacity-80 transition-opacity">
                <!-- Logo / Branding -->
                <div class="mr-3 shrink-0">
                   <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-accent-500">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h1 class="text-lg md:text-xl font-bold tracking-tight text-white flex items-center">
                        <span class="truncate"><?php echo htmlspecialchars($settings['dept_name'] ?? 'Tasks Manager'); ?></span>
                        <span class="ml-2 shrink-0 px-1.5 py-0.5 rounded text-[10px] bg-accent-600/20 text-accent-400 border border-accent-600/30">v3.0</span>
                    </h1>
                    <p class="text-[10px] md:text-xs text-gray-400 uppercase tracking-wider truncate"><?php echo htmlspecialchars($settings['church_name'] ?? 'Church Funds'); ?></p>
                </div>
            </a>

            <!-- Mobile Menu Toggle -->
            <button id="menu-toggle" class="md:hidden text-gray-300 hover:text-white p-2 rounded-lg bg-gray-700/50">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                    <path id="menu-icon" stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <!-- Navigation -->
            <nav id="nav-menu" class="hidden w-full md:flex md:w-auto mt-4 md:mt-0 bg-gray-900 md:bg-transparent rounded-xl overflow-hidden md:overflow-visible transition-all duration-300 flex-col md:flex-row space-y-1 md:space-y-0 md:space-x-1 p-2 md:p-1 md:bg-gray-700/50 md:rounded-lg">
                <a href="index.php" class="px-4 py-2.5 md:py-2 rounded-md text-sm font-medium transition-colors hover:bg-gray-600 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-accent-600 text-white shadow' : 'text-gray-300'; ?>">
                    Dashboard
                </a>
                <a href="add_fund.php" class="px-4 py-2.5 md:py-2 rounded-md text-sm font-medium transition-colors hover:bg-gray-600 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'add_fund.php' ? 'bg-accent-600 text-white shadow' : 'text-gray-300'; ?>">
                    + Fund
                </a>
                <a href="add_expense.php" class="px-4 py-2.5 md:py-2 rounded-md text-sm font-medium transition-colors hover:bg-gray-600 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'add_expense.php' ? 'bg-accent-600 text-white shadow' : 'text-gray-300'; ?>">
                    - Expense
                </a>
                <a href="report.php" class="px-4 py-2.5 md:py-2 rounded-md text-sm font-medium transition-colors hover:bg-gray-600 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'report.php' ? 'bg-accent-600 text-white shadow' : 'text-gray-300'; ?>">
                    Reports
                </a>
                <a href="tasks.php" class="px-4 py-2.5 md:py-2 rounded-md text-sm font-medium transition-colors hover:bg-gray-600 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'bg-accent-600 text-white shadow' : 'text-gray-300'; ?>">
                    Tasks
                </a>
                <a href="subscriptions.php" class="px-4 py-2.5 md:py-2 rounded-md text-sm font-medium transition-colors hover:bg-gray-600 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'subscriptions.php' ? 'bg-accent-600 text-white shadow' : 'text-gray-300'; ?>">
                    Internet Subscriptions
                </a>
                <a href="documentation.php" class="px-4 py-2.5 md:py-2 rounded-md text-sm font-medium transition-colors hover:bg-gray-600 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'documentation.php' ? 'bg-accent-600 text-white shadow' : 'text-gray-300'; ?>">
                    Documentation
                </a>
                <a href="settings.php" class="px-4 py-2.5 md:py-2 rounded-md text-sm font-medium transition-colors hover:bg-gray-600 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-accent-600 text-white shadow' : 'text-gray-300'; ?>">
                    Settings
                </a>
                <a href="logout.php" class="px-4 py-2.5 md:py-2 rounded-md text-sm font-medium transition-colors text-rose-400 hover:bg-rose-900/30 hover:text-rose-300">
                    Logout
                </a>
            </nav>
        </div>
    </header>

    <script>
        document.getElementById('menu-toggle').addEventListener('click', function() {
            const menu = document.getElementById('nav-menu');
            const icon = document.getElementById('menu-icon');
            menu.classList.toggle('hidden');
            menu.classList.toggle('flex');
            
            if (menu.classList.contains('hidden')) {
                icon.setAttribute('d', 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5');
            } else {
                icon.setAttribute('d', 'M6 18L18 6M6 6l12 12');
            }
        });
    </script>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
