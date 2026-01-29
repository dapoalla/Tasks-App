<?php
require_once 'db.php';

// Calculate Totals
$totalFundsStmt = $pdo->query("SELECT SUM(amount) as total FROM funds");
$totalFunds = $totalFundsStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$totalExpensesStmt = $pdo->query("SELECT SUM(amount) as total FROM expenses");
$totalExpenses = $totalExpensesStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$balance = $totalFunds - $totalExpenses;

// NEW: Monthly Expenses
$monthExpStmt = $pdo->query("SELECT SUM(amount) as total FROM expenses WHERE MONTH(date_incurred) = MONTH(CURRENT_DATE()) AND YEAR(date_incurred) = YEAR(CURRENT_DATE())");
$monthlyExpenses = $monthExpStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// NEW: Transaction Count
$transCountStmt = $pdo->query("SELECT (SELECT COUNT(*) FROM funds) + (SELECT COUNT(*) FROM expenses) as total");
$transactionCount = $transCountStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// NEW: Top Category
$topCatStmt = $pdo->query("SELECT category, SUM(amount) as total FROM expenses GROUP BY category ORDER BY total DESC LIMIT 1");
$topCategory = $topCatStmt->fetch(PDO::FETCH_ASSOC);

// NEW: Category Breakdown
$catBreakdownStmt = $pdo->query("SELECT category, SUM(amount) as total FROM expenses GROUP BY category ORDER BY total DESC");
$categoryBreakdown = $catBreakdownStmt->fetchAll(PDO::FETCH_ASSOC);


// Funds Summary (Allocated vs Spent)
$fundsSummaryQuery = "
    SELECT 
        f.id, 
        f.title, 
        f.amount as allocated, 
        f.date_released,
        COALESCE(SUM(e.amount), 0) as spent
    FROM funds f
    LEFT JOIN expenses e ON f.id = e.fund_id
    WHERE f.status = 'Active'
    GROUP BY f.id
    ORDER BY f.date_released DESC
";
$fundsSummaryStmt = $pdo->query($fundsSummaryQuery);
$fundsSummary = $fundsSummaryStmt->fetchAll(PDO::FETCH_ASSOC);

// NEW: Task Summary
$outstandingTasksCount = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status != 'Completed'")->fetchColumn();
$highPriorityTasksCount = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status != 'Completed' AND priority = 'High'")->fetchColumn();
$overdueTasksCount = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status != 'Completed' AND due_date < CURRENT_DATE() AND due_date IS NOT NULL")->fetchColumn();

include 'header.php';
?>

<?php
// Default to showing all tasks unless explicitly set to '0'
$showAll = !isset($_GET['show_all']) || $_GET['show_all'] == '1';
?>

<!-- Row 1: HIGHEST PRIORITY - Task Summary List -->
<div class="mb-10">
    <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700 flex flex-wrap gap-4 justify-between items-center bg-gray-700/30">
            <h3 class="text-lg font-semibold text-white flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 text-accent-500">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 2.25a2.25 2.25 0 0 1-2.25 2.25h-3a2.25 2.25 0 0 1-2.25-2.25m7.332 0c.855.108 1.666.239 2.433.392m-9.765 0a42.947 42.947 0 0 0-2.433.392m12.198-12.198c.328.328.328.86 0 1.188L15.666 4.704a.84.84 0 0 1-1.188 0l-1.188-1.188a.84.84 0 0 1 0-1.188l1.188-1.188a.84.84 0 0 1 1.188 0l1.188 1.188ZM6.75 6.75h.008v.008H6.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                Task Summary List
            </h3>
            
            <div class="flex items-center gap-6">
                <!-- Status Toggle -->
                <div class="flex items-center gap-3 bg-gray-900/50 px-3 py-1.5 rounded-full border border-gray-700">
                    <span class="text-[10px] font-bold uppercase tracking-wider <?php echo !$showAll ? 'text-accent-400' : 'text-gray-500'; ?>">Outstanding</span>
                    <button onclick="window.location.href='?show_all=<?php echo $showAll ? '0' : '1'; ?>'" 
                        class="relative inline-flex h-5 w-10 items-center rounded-full transition-colors focus:outline-none <?php echo $showAll ? 'bg-accent-600' : 'bg-gray-700'; ?>">
                        <span class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform <?php echo $showAll ? 'translate-x-6' : 'translate-x-1'; ?>"></span>
                    </button>
                    <span class="text-[10px] font-bold uppercase tracking-wider <?php echo $showAll ? 'text-accent-400' : 'text-gray-500'; ?>">All Tasks</span>
                </div>
                
                <a href="tasks.php" class="text-xs text-accent-400 hover:text-accent-300 transition-colors uppercase font-bold tracking-wider">Manage &rarr;</a>
            </div>
        </div>
        <div class="p-0">
            <?php 
            $whereClause = $showAll ? "" : "WHERE status != 'Completed'";
            $recentTasksStmt = $pdo->query("SELECT * FROM tasks $whereClause ORDER BY priority DESC, due_date ASC LIMIT 8");
            $recentTasks = $recentTasksStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <table class="w-full text-left border-collapse">
                <tbody class="divide-y divide-gray-700">
                    <?php if (count($recentTasks) > 0): ?>
                        <?php foreach ($recentTasks as $task): ?>
                            <tr class="hover:bg-gray-700/20 transition-colors cursor-pointer" onclick="window.location.href='tasks.php'">
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-white font-bold"><?php echo htmlspecialchars($task['task_name']); ?></span>
                                        <?php if ($task['location_dept']): ?>
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-gray-700/50 text-gray-500 border border-gray-700"><?php echo htmlspecialchars($task['location_dept']); ?></span>
                                        <?php endif; ?>
                                        <?php 
                                            $pColor = 'bg-gray-700 text-gray-400';
                                            if ($task['priority'] == 'High') $pColor = 'bg-rose-500/20 text-rose-400';
                                            if ($task['priority'] == 'Medium') $pColor = 'bg-amber-500/20 text-amber-400';
                                        ?>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border border-current <?php echo $pColor; ?> opacity-80"><?php echo $task['priority']; ?></span>
                                        <?php if ($task['status'] == 'Completed'): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500 text-white shadow-sm border border-emerald-400">Completed</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-1 italic">"<?php echo htmlspecialchars($task['status_details'] ?: 'No status details yet...'); ?>"</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="text-xs text-gray-400"><?php echo $task['due_date'] ? date('M d', strtotime($task['due_date'])) : 'No Date'; ?></div>
                                    <div class="text-[10px] text-gray-600 uppercase font-bold"><?php echo $task['status']; ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td class="px-6 py-8 text-center text-gray-500">No active tasks. You're all caught up!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Row 2: SUBSCRIPTION SUMMARY -->
<div class="mb-10">
    <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 overflow-hidden group hover:border-blue-500/30 transition-all">
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center bg-gray-700/30 cursor-pointer" onclick="window.location.href='subscriptions.php'">
            <h3 class="text-lg font-semibold text-white flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 text-blue-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205l3 1m1.5-1.5l-3-1m-3 1.345l3 1m-1.5-1.5l-3-1m-3 1.345l3 1m-1.5-1.5l-3-1" />
                </svg>
                Subscription Expiry Summary
            </h3>
            <span class="text-xs text-blue-400 hover:text-blue-300 font-bold uppercase tracking-wider">Manage Subscriptions &rarr;</span>
        </div>
        <div class="p-0">
            <?php 
            // Fetch expiring subscriptions (Expired or expiring in 30 days)
            $subStmt = $pdo->query("SELECT * FROM internet_subscriptions WHERE renewal_status != 'Done' AND expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) ORDER BY expiry_date ASC LIMIT 5");
            $expiringSubs = $subStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <table class="w-full text-left border-collapse">
                <tbody class="divide-y divide-gray-700">
                    <?php if (count($expiringSubs) > 0): ?>
                        <?php foreach ($expiringSubs as $sub): 
                            $daysLeft = (strtotime($sub['expiry_date']) - time()) / (60 * 60 * 24);
                            $statusColor = $daysLeft < 0 ? 'text-rose-500' : ($daysLeft <= 10 ? 'text-amber-500' : 'text-blue-400');
                            $statusText = $daysLeft < 0 ? 'Expired' : ceil($daysLeft) . ' days left';
                        ?>
                            <tr class="hover:bg-gray-700/20 transition-colors cursor-pointer" onclick="window.location.href='subscriptions.php'">
                                <td class="px-6 py-4">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <span class="text-white font-bold block"><?php echo htmlspecialchars($sub['location_dept']); ?></span>
                                            <span class="text-xs text-gray-500"><?php echo htmlspecialchars($sub['provider']); ?> - <?php echo htmlspecialchars($sub['plan_name']); ?></span>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-bold <?php echo $statusColor; ?>"><?php echo $statusText; ?></div>
                                            <div class="text-[10px] text-gray-500"><?php echo date('M d, Y', strtotime($sub['expiry_date'])); ?></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td class="px-6 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mb-2 text-emerald-500/50">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    All subscriptions are active and healthy!
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Row 2b: FUNDS ACTIVITY -->
<div class="mb-10">
    <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center bg-gray-700/30">
            <h3 class="text-lg font-semibold text-white">Funds Activity Summary</h3>
            <span class="text-xs text-gray-500"><?php echo count($fundsSummary); ?> active funds</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-700/50 text-gray-400 text-xs uppercase tracking-wider">
                        <th class="px-6 py-3 font-medium">Fund Title</th>
                        <th class="px-6 py-3 font-medium text-right">Allocated</th>
                        <th class="px-6 py-3 font-medium text-right">Spent</th>
                        <th class="px-6 py-3 font-medium text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700 text-sm">
                    <?php if (count($fundsSummary) > 0): ?>
                        <?php foreach ($fundsSummary as $item): 
                            $rowBalance = $item['allocated'] - $item['spent'];
                            $spentPercent = ($item['allocated'] > 0) ? ($item['spent'] / $item['allocated']) * 100 : 0;
                        ?>
                            <tr class="hover:bg-gray-700/30 transition-colors cursor-pointer" onclick="window.location='report.php?fund_id=<?php echo $item['id']; ?>'">
                                <td class="px-6 py-4">
                                    <div class="text-gray-200 font-bold"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <div class="text-xs text-gray-500 mt-1"><?php echo date('M d, Y', strtotime($item['date_released'])); ?></div>
                                </td>
                                <td class="px-6 py-4 text-right text-emerald-400 font-medium">
                                    ₦<?php echo number_format($item['allocated'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-right text-rose-400 font-medium">
                                    ₦<?php echo number_format($item['spent'], 2); ?>
                                    <div class="text-[10px] text-gray-500"><?php echo number_format($spentPercent, 1); ?>% used</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-bold <?php echo $rowBalance >= 0 ? 'text-accent-400' : 'text-rose-500'; ?>">
                                        ₦<?php echo number_format($rowBalance, 2); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                No funds found. Start by adding a fund!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Row 3: MID PRIORITY - Status Tiles & Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
     <!-- Open Tasks -->
     <a href="tasks.php?filter=outstanding" class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-lg hover:border-blue-500/50 transition-all group">
        <div class="flex justify-between items-start">
            <div>
                <span class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Open Tasks</span>
                <span class="text-3xl font-bold text-blue-400"><?php echo $outstandingTasksCount; ?></span>
            </div>
            <div class="p-2 rounded-lg bg-blue-500/10 text-blue-500 group-hover:rotate-12 transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        </div>
    </a>

    <!-- Closed Tasks -->
    <a href="tasks.php?filter=completed" class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-lg hover:border-emerald-500/50 transition-all group">
        <div class="flex justify-between items-start">
            <div>
                <span class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Closed Tasks</span>
                <span class="text-3xl font-bold text-emerald-400">
                    <?php echo $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'Completed'")->fetchColumn(); ?>
                </span>
            </div>
            <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-500 group-hover:scale-110 transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        </div>
    </a>

    <!-- Quick Actions -->
    <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-lg md:col-span-2">
        <span class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Quick Actions</span>
        <div class="flex gap-2">
            <a href="add_fund.php" class="flex-1 bg-gray-700 hover:bg-emerald-600 text-white text-center py-2 rounded-lg text-sm font-bold transition-all border border-gray-600 hover:border-emerald-500">Record Fund</a>
            <a href="add_expense.php" class="flex-1 bg-gray-700 hover:bg-rose-600 text-white text-center py-2 rounded-lg text-sm font-bold transition-all border border-gray-600 hover:border-rose-500">New Expense</a>
        </div>
    </div>
</div>

<!-- Row 4: LOW PRIORITY - Financial Summary -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Balance Card -->
    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl shadow-lg border border-gray-700 p-6 flex flex-col justify-between text-white md:col-span-2">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Current Available Balance</h2>
                <div class="mt-2 text-4xl font-bold text-white">
                    ₦<?php echo number_format($balance, 2); ?>
                </div>
            </div>
            <div class="hidden md:block">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-16 h-16 text-gray-700">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Monthly Expenses Tile -->
    <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 p-6">
        <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider">Expenses this Month</h2>
        <div class="mt-4 text-2xl font-bold text-rose-400">
            ₦<?php echo number_format($monthlyExpenses, 2); ?>
        </div>
        <div class="mt-2 text-[10px] text-gray-500 uppercase font-bold"><?php echo date('F Y'); ?></div>
    </div>
</div>

<?php include 'footer.php'; ?>
