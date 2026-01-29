<?php
require_once 'db.php';

$selected_fund_id = isset($_GET['fund_id']) ? $_GET['fund_id'] : '0';
$fund = null;
$fund_items = [];
$expenses = [];
$total_expenses = 0;
$balance = 0;
$all_funds_summary = [];

$show_retired = isset($_GET['show_retired']) && $_GET['show_retired'] == '1';
$status_filter = $show_retired ? 'Retired' : 'Active';

// Handle Retire/Revive Action
if (isset($_POST['update_fund_status'])) {
    $fid = intval($_POST['fund_id']);
    $new_status = $_POST['new_status'];
    $stmtS = $pdo->prepare("UPDATE funds SET status = ? WHERE id = ?");
    $stmtS->execute([$new_status, $fid]);
    $success_msg = "Fund status updated to $new_status!";
}

// Fetch all funds for selector
$funds_stmt = $pdo->prepare("SELECT id, title, amount, date_released, status FROM funds WHERE status = ? ORDER BY date_released DESC");
$funds_stmt->execute([$status_filter]);
$all_funds = $funds_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($selected_fund_id === 'all') {
    // Consolidated Report Logic
    $total_allocated = 0;
    foreach ($all_funds as $f) {
        $total_allocated += $f['amount'];
        $stmtExp = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE fund_id = ?");
        $stmtExp->execute([$f['id']]);
        $f_spent = $stmtExp->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Fetch items for breakdown
        $stmtI = $pdo->prepare("SELECT description, amount FROM fund_items WHERE fund_id = ?");
        $stmtI->execute([$f['id']]);
        $items = $stmtI->fetchAll(PDO::FETCH_ASSOC);

        $all_funds_summary[] = [
            'id' => $f['id'],
            'title' => $f['title'],
            'amount' => $f['amount'],
            'spent' => $f_spent,
            'balance' => $f['amount'] - $f_spent,
            'date' => $f['date_released'],
            'items' => $items
        ];
        $total_expenses += $f_spent;
    }
    $fund = ['title' => 'Consolidated Funds Report (' . $status_filter . ')', 'amount' => $total_allocated, 'received_by' => 'Multiple Recipients'];
    $balance = $total_allocated - $total_expenses;
} elseif (intval($selected_fund_id) > 0) {
    $selected_fund_id = intval($selected_fund_id);
    // Get Fund Details
    $stmt = $pdo->prepare("SELECT * FROM funds WHERE id = ?");
    $stmt->execute([$selected_fund_id]);
    $fund = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($fund) {
        // Get Fund Intended Items
        $stmtItems = $pdo->prepare("SELECT * FROM fund_items WHERE fund_id = ?");
        $stmtItems->execute([$selected_fund_id]);
        $fund_items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Get Linked Expenses
        $stmtExp = $pdo->prepare("SELECT * FROM expenses WHERE fund_id = ? ORDER BY date_incurred DESC");
        $stmtExp->execute([$selected_fund_id]);
        $expenses = $stmtExp->fetchAll(PDO::FETCH_ASSOC);

        // Calculate Totals
        foreach ($expenses as $exp) {
            $total_expenses += $exp['amount'];
        }
        $balance = $fund['amount'] - $total_expenses;
    }
}

// Handle Email Report Action
if (isset($_POST['email_report'])) {
    require_once 'mail_helper.php';
    $recipient = $_POST['recipient_email'] ?? $settings['report_recipient_email'] ?? '';
    if (empty($recipient)) {
        $error_msg = "Please specify a recipient email.";
    } else {
        // Capture HTML Buffer
        ob_start();
        include 'report_email_template.php'; // We'll create this to generate the email body
        $html_content = ob_get_clean();
        
        $subject = "Fund Report: " . $fund['title'];
        if (send_mail($recipient, $subject, $html_content)) {
            $success_msg = "Report emailed successfully to $recipient!";
        } else {
            $error_msg = "Failed to email report. Check SMTP settings.";
        }
    }
}

include 'header.php';
?>

<div class="max-w-4xl mx-auto mb-6 no-print">
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-gray-800 p-4 rounded-xl border border-gray-700 shadow-lg">
        <h2 class="text-xl font-bold text-white shrink-0">Fund Reports</h2>
        <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
            <a href="?fund_id=<?php echo $selected_fund_id; ?>&show_retired=<?php echo $show_retired ? '0' : '1'; ?>" 
               class="text-xs px-3 py-2 rounded-lg border <?php echo $show_retired ? 'bg-amber-500/10 border-amber-500/30 text-amber-500' : 'bg-gray-700 border-gray-600 text-gray-400 hover:text-white'; ?> transition-all">
                <?php echo $show_retired ? 'Showing Retired' : 'Show Retired'; ?>
            </a>
            <form method="GET" class="flex items-center gap-2 flex-grow md:flex-grow-0">
                <input type="hidden" name="show_retired" value="<?php echo $show_retired ? '1' : '0'; ?>">
                <select name="fund_id" onchange="this.form.submit()" class="bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-accent-500 outline-none w-full">
                    <option value="0">-- Select a Fund --</option>
                    <option value="all" <?php echo $selected_fund_id === 'all' ? 'selected' : ''; ?>>All Funds Summary</option>
                    <?php foreach ($all_funds as $f): ?>
                        <option value="<?php echo $f['id']; ?>" <?php echo (string)$selected_fund_id === (string)$f['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($f['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <?php if ($fund): ?>
            <div class="flex gap-2">
                <a href="edit_fund.php?id=<?php echo $fund['id']; ?>" class="bg-amber-600 hover:bg-amber-500 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    Edit
                </a>
                
                <?php if (isset($fund['id'])): ?>
                <form method="POST">
                    <input type="hidden" name="fund_id" value="<?php echo $fund['id']; ?>">
                    <input type="hidden" name="new_status" value="<?php echo $fund['status'] == 'Active' ? 'Retired' : 'Active'; ?>">
                    <button type="submit" name="update_fund_status" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors border border-gray-600 shadow-sm">
                         <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $fund['status'] == 'Active' ? 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z' : 'M9 12.75l3 3m0 0l3-3m-3 3v-7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z'; ?>" />
                        </svg>
                        <?php echo $fund['status'] == 'Active' ? 'Retire' : 'Activate'; ?>
                    </button>
                </form>
                <?php endif; ?>
                 <form method="POST" action="delete_item.php" onsubmit="return confirm('Are you sure you want to delete this fund? This will delete all intended items but preserve expenses (orphaned).');">
                    <input type="hidden" name="type" value="fund">
                    <input type="hidden" name="id" value="<?php echo $fund['id']; ?>">
                    <input type="hidden" name="redirect" value="report.php">
                    <button type="submit" class="bg-rose-700 hover:bg-rose-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        Delete
                    </button>
                </form>
                <button onclick="window.print()" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                    </svg>
                    Print
                </button>

                <button onclick="exportReport('pdf')" class="bg-rose-600 hover:bg-rose-500 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    PDF
                </button>

                <button onclick="exportReport('jpg')" class="bg-accent-600 hover:bg-accent-500 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                    JPG
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="max-w-4xl mx-auto mb-6 no-print">
    <?php if (isset($success_msg) && $success_msg): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-lg mb-4 shadow-sm">
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_msg) && $error_msg): ?>
        <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-4 rounded-lg mb-4 shadow-sm">
            <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <?php if ($fund): ?>
        <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-lg">
            <h3 class="text-sm font-bold text-gray-400 uppercase mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 text-accent-500">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
                Email this report
            </h3>
            <form method="POST" class="flex flex-col md:flex-row gap-4">
                <input type="email" name="recipient_email" placeholder="Recipient email address" required
                    value="<?php echo htmlspecialchars($settings['report_recipient_email'] ?? ''); ?>"
                    class="flex-1 bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none">
                <button type="submit" name="email_report" class="bg-accent-600 hover:bg-accent-700 text-white font-bold px-8 py-3 rounded-lg transition-colors flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 mr-2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    Send Email
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php if ($fund): ?>
<!-- Report Content -->
<div id="report-container" class="max-w-4xl mx-auto bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-8 shadow-xl rounded-xl border border-gray-700 print:shadow-none print:border-0 print:p-0 transition-colors duration-200">
        
        <!-- Report Header -->
        <div class="border-b border-gray-200 dark:border-gray-700 pb-6 mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold uppercase tracking-wide">Fund Usage Report</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Apostolic Faith WECA - ICT Department</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Date Generated</div>
                    <div class="font-medium"><?php echo date('F j, Y'); ?></div>
                </div>
            </div>
        </div>

        <!-- Fund Summary Grid -->
        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-6 mb-8 grid grid-cols-1 md:grid-cols-3 gap-6 print:bg-transparent print:p-0 print:gap-4 print:mb-4">
            <div>
                <span class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Fund Title</span>
                <span class="text-lg font-semibold"><?php echo htmlspecialchars($fund['title']); ?></span>
            </div>
            <div>
                <span class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Date Released</span>
                <span class="text-lg font-medium"><?php echo date('M d, Y', strtotime($fund['date_released'])); ?></span>
            </div>
             <div>
                <span class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Received By</span>
                <span class="text-lg font-medium"><?php echo htmlspecialchars($fund['received_by']); ?></span>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 print:grid-cols-3 print:gap-4">
             <div class="p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-500/20 print:border print:bg-white print:text-black">
                <span class="block text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase">Initial Amount</span>
                <span class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">₦<?php echo number_format($fund['amount'], 2); ?></span>
            </div>
            <div class="p-4 rounded-lg bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-500/20 print:border print:bg-white print:text-black">
                <span class="block text-xs font-bold text-rose-600 dark:text-rose-400 uppercase">Total Expenses</span>
                <span class="text-2xl font-bold text-rose-700 dark:text-rose-300">₦<?php echo number_format($total_expenses, 2); ?></span>
            </div>
            <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-500/20 print:border print:bg-white print:text-black">
                <span class="block text-xs font-bold text-blue-600 dark:text-blue-400 uppercase">Remaining Balance</span>
                <span class="text-2xl font-bold text-blue-700 dark:text-blue-300">₦<?php echo number_format($balance, 2); ?></span>
            </div>
        </div>

        <!-- NEW: Intended Budget Breakdown / OR All Funds Summary -->
        <?php if ($selected_fund_id === 'all'): ?>
            <h3 class="text-lg font-bold mb-4 print:mb-2 text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wider">Consolidated Funds Summary</h3>
             <div class="bg-gray-100 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-8 print:border print:bg-transparent">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-200 dark:bg-gray-700/50">
                        <tr>
                            <th class="py-2 px-4 text-xs font-semibold text-gray-600 dark:text-gray-300">Fund Description</th>
                            <th class="py-2 px-4 text-xs font-semibold text-right text-gray-600 dark:text-gray-300">Allocated</th>
                            <th class="py-2 px-4 text-xs font-semibold text-right text-gray-600 dark:text-gray-300">Spent</th>
                            <th class="py-2 px-4 text-xs font-semibold text-right text-gray-600 dark:text-gray-300">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($all_funds_summary as $item): ?>
                            <tr class="hover:bg-gray-200/50 dark:hover:bg-gray-700 transition-colors">
                                <td class="py-4 px-4 text-sm">
                                    <div class="font-bold text-base"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <div class="text-[10px] text-gray-500 uppercase mb-2"><?php echo date('M Y', strtotime($item['date'])); ?></div>
                                    
                                    <!-- Breakdown for each fund -->
                                    <div class="mt-2 pl-4 border-l-2 border-gray-200 dark:border-gray-700 space-y-1">
                                        <div class="text-[10px] uppercase font-bold text-gray-400">Budget Breakdown:</div>
                                        <?php if(count($item['items']) > 0): ?>
                                            <?php foreach($item['items'] as $bi): ?>
                                                <div class="flex justify-between text-[11px] text-gray-500">
                                                    <span>• <?php echo htmlspecialchars($bi['description']); ?></span>
                                                    <span>₦<?php echo number_format($bi['amount'], 2); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-[11px] italic text-gray-400">No specific line items.</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-sm text-right text-emerald-600 dark:text-emerald-400 align-top">₦<?php echo number_format($item['amount'], 2); ?></td>
                                <td class="py-4 px-4 text-sm text-right text-rose-500 align-top">₦<?php echo number_format($item['spent'], 2); ?></td>
                                <td class="py-4 px-4 text-sm text-right font-bold align-top">₦<?php echo number_format($item['balance'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
             </div>
        <?php else: ?>
             <h3 class="text-lg font-bold mb-4 print:mb-2 text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wider">Intended Budget Breakdown</h3>
             <div class="bg-gray-100 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-8 print:border print:bg-transparent">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-200 dark:bg-gray-700/50">
                        <tr>
                            <th class="py-2 px-4 text-xs font-semibold text-gray-600 dark:text-gray-300">Intended Item</th>
                            <th class="py-2 px-4 text-xs font-semibold text-right text-gray-600 dark:text-gray-300">Budgeted Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (count($fund_items) > 0): ?>
                            <?php foreach ($fund_items as $item): ?>
                                <tr>
                                    <td class="py-2 px-4 text-sm"><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td class="py-2 px-4 text-sm text-right font-medium text-emerald-600 dark:text-emerald-400">₦<?php echo number_format($item['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="py-4 text-center text-gray-500 text-sm italic">No specific line items recorded.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
             </div>
        <?php endif; ?>

        <?php if ($selected_fund_id !== 'all'): ?>
            <!-- Expenses Table -->
            <h3 class="text-lg font-bold mb-4 print:mb-2 text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wider">Actual Expense Details</h3>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-200 dark:border-gray-600">
                        <th class="py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Date</th>
                        <th class="py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Item / Service</th>
                        <th class="py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Vendor</th>
                        <th class="py-3 text-sm font-semibold text-gray-600 dark:text-gray-300">Category</th>
                        <th class="py-3 text-sm font-semibold text-right text-gray-600 dark:text-gray-300">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (count($expenses) > 0): ?>
                        <?php foreach ($expenses as $exp): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 print:hover:bg-transparent group">
                                <td class="py-3 text-sm"><?php echo date('M d, Y', strtotime($exp['date_incurred'])); ?></td>
                                 <td class="py-3 text-sm font-medium">
                                    <?php echo htmlspecialchars($exp['item_name']); ?>
                                    <?php if($exp['receipt_path']): ?>
                                        <a href="<?php echo htmlspecialchars($exp['receipt_path']); ?>" target="_blank" class="no-print ml-1 text-xs text-accent-500 hover:underline">[View Receipt]</a>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($exp['vendor']); ?></td>
                                <td class="py-3 text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($exp['category']); ?></td>
                                <td class="py-3 text-sm font-bold text-right text-rose-500 dark:text-rose-400 relative">
                                    <div class="flex items-center justify-end gap-2">
                                        <span>₦<?php echo number_format($exp['amount'], 2); ?></span>
                                        <form method="POST" action="delete_item.php" onsubmit="return confirm('Delete this expense?');" class="no-print opacity-0 group-hover:opacity-100 transition-opacity">
                                            <input type="hidden" name="type" value="expense">
                                            <input type="hidden" name="id" value="<?php echo $exp['id']; ?>">
                                            <input type="hidden" name="redirect" value="report.php?fund_id=<?php echo $selected_fund_id; ?>">
                                            <button type="submit" class="text-gray-400 hover:text-rose-500 transition-colors" title="Delete Expense">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                  <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                     </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500 italic">No expenses recorded for this fund yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

         <?php if ($fund['description']): ?>
            <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-4">
                <span class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-2">Notes</span>
                <p class="text-sm text-gray-600 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($fund['description'])); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="mt-12 text-center hidden print:block">
            <p class="text-xs text-gray-400">Printed from WECA ICT Fund Tracker</p>
        </div>

    </div>
<?php else: ?>
    <?php if ($selected_fund_id > 0): ?>
        <div class="text-center py-12">
            <p class="text-gray-400 text-xl">Fund not found.</p>
        </div>
    <?php else: ?>
        <div class="text-center py-12">
            <p class="text-gray-400 text-xl">Please select a fund release to view the report.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
async function exportReport(format) {
    const element = document.getElementById('report-container');
    const originalClasses = element.className;
    
    // Force light mode styles for capture
    element.classList.remove('dark:bg-gray-800', 'dark:text-gray-100', 'dark:border-gray-700');
    element.classList.add('bg-white', 'text-gray-900', 'border-gray-200');
    
    // Find all nested dark elements and temporarily adjust
    const darkElements = element.querySelectorAll('[class*="dark:"]');
    const savedStyles = [];
    
    darkElements.forEach(el => {
        savedStyles.push({
            el: el,
            className: el.className
        });
        // Remove common dark classes and add light equivalents
        el.classList.remove(
            'dark:bg-gray-700/30', 'dark:bg-emerald-900/10', 'dark:bg-rose-900/10', 
            'dark:bg-blue-900/10', 'dark:bg-gray-800/50', 'dark:text-gray-400', 
            'dark:text-gray-100', 'dark:text-gray-300', 'dark:border-gray-700',
            'dark:divide-gray-700'
        );
        
        // Add explicit light classes where needed
        if (el.tagName === 'TABLE' || el.classList.contains('border')) el.classList.add('border-gray-200');
        if (el.classList.contains('divide-y')) el.classList.add('divide-gray-200');
    });

    try {
        const canvas = await html2canvas(element, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff'
        });

        if (format === 'jpg') {
            const link = document.createElement('a');
            link.download = `Fund_Report_${new Date().toISOString().replace(/[:.]/g, '-')}.jpg`;
            link.href = canvas.toDataURL('image/jpeg', 0.9);
            link.click();
        } else {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            const imgWidth = 210;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, 0, imgWidth, imgHeight);
            pdf.save(`Fund_Report_${new Date().toISOString().replace(/[:.]/g, '-')}.pdf`);
        }
    } catch (error) {
        console.error('Export failed:', error);
        alert('Export failed. Please try again or use the Print button.');
    } finally {
        // Restore original classes
        element.className = originalClasses;
        savedStyles.forEach(item => {
            item.el.className = item.className;
        });
    }
}
</script>

<?php include 'footer.php'; ?>
