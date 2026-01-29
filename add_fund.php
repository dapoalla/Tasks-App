<?php
require_once 'db.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $date_released = $_POST['date_released'];
    $received_by = trim($_POST['received_by']);
    $description = trim($_POST['description']);
    
    // Arrays for line items
    $item_descriptions = $_POST['item_desc'] ?? [];
    $item_amounts = $_POST['item_amount'] ?? [];

    // Calculate Total Amount from items
    $totalAmount = 0;
    foreach ($item_amounts as $amt) {
        $totalAmount += floatval($amt);
    }

    if (empty($title) || $totalAmount <= 0 || empty($date_released) || empty($received_by)) {
        $error_msg = 'Please fill in all required fields and ensure at least one line item with an amount is added.';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Insert Fund Record
            $stmt = $pdo->prepare("INSERT INTO funds (title, amount, date_released, received_by, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $totalAmount, $date_released, $received_by, $description]);
            $fund_id = $pdo->lastInsertId();

            // 2. Insert Line Items
            $stmtItem = $pdo->prepare("INSERT INTO fund_items (fund_id, description, amount) VALUES (?, ?, ?)");
            for ($i = 0; $i < count($item_descriptions); $i++) {
                $desc = trim($item_descriptions[$i]);
                $amt = floatval($item_amounts[$i]);
                
                if (!empty($desc) && $amt > 0) {
                    $stmtItem->execute([$fund_id, $desc, $amt]);
                }
            }

            $pdo->commit();
            $success_msg = 'Fund release and line items added successfully!';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_msg = 'Database Error: ' . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-white">Log Fund Release</h2>
        <a href="index.php" class="text-sm text-gray-400 hover:text-white transition-colors">&larr; Back to Dashboard</a>
    </div>

    <?php if ($success_msg): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-lg mb-6">
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-4 rounded-lg mb-6">
            <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-6">
        
        <!-- Main Fund Details -->
        <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 p-6 space-y-6">
            <h3 class="text-lg font-semibold text-gray-200 border-b border-gray-700 pb-2">General Information</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Fund Title / Project Name <span class="text-rose-500">*</span></label>
                <input type="text" name="title" required placeholder="e.g. Camp Meeting 2024 Network Upgrade"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all placeholder-gray-600">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Date Released <span class="text-rose-500">*</span></label>
                    <input type="date" name="date_released" required value="<?php echo date('Y-m-d'); ?>"
                        class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all [color-scheme:dark]">
                </div>

                <!-- Received By -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Received By <span class="text-rose-500">*</span></label>
                    <input type="text" name="received_by" required placeholder="Name of person who collected funds"
                        class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all placeholder-gray-600">
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Description / Notes</label>
                <textarea name="description" rows="2" placeholder="Additional details..."
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all placeholder-gray-600"></textarea>
            </div>
        </div>

        <!-- Line Items Section -->
        <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 p-6">
            <div class="flex justify-between items-center border-b border-gray-700 pb-2 mb-4">
                <h3 class="text-lg font-semibold text-gray-200">Fund Breakdown (Line Items)</h3>
                <div class="text-right">
                    <span class="text-sm text-gray-400 mr-2">Total Amount:</span>
                    <span id="displayTotal" class="text-xl font-bold text-emerald-400">₦0.00</span>
                </div>
            </div>
            
            <div id="lineItemsContainer" class="space-y-3">
                <!-- Initial Row -->
                <div class="line-item-row flex items-center gap-4">
                    <div class="flex-grow">
                        <input type="text" name="item_desc[]" required placeholder="Item Description (e.g. 500m Fiber Cable)"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-600 focus:ring-1 focus:ring-accent-500 outline-none">
                    </div>
                    <div class="w-40">
                        <input type="number" step="0.01" name="item_amount[]" required placeholder="Amount" oninput="calculateTotal()"
                            class="item-amount w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-600 focus:ring-1 focus:ring-accent-500 outline-none text-right">
                    </div>
                    <button type="button" onclick="removeRow(this)" class="text-gray-500 hover:text-rose-500 transition-colors p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="mt-4">
                <button type="button" onclick="addRow()" class="text-sm text-accent-400 hover:text-accent-300 font-medium flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Another Item
                </button>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-gray-700">
            <button type="submit" class="w-full bg-gradient-to-r from-accent-600 to-accent-500 hover:from-accent-500 hover:to-accent-400 text-white font-bold py-3 rounded-lg shadow-lg flex justify-center items-center transition-all transform active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Log Fund Entry
            </button>
        </div>

    </form>
</div>

<script>
    function addRow() {
        const container = document.getElementById('lineItemsContainer');
        const newRow = document.createElement('div');
        newRow.className = 'line-item-row flex items-center gap-4 animate-fade-in-down';
        newRow.innerHTML = `
            <div class="flex-grow">
                <input type="text" name="item_desc[]" required placeholder="Item Description"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-600 focus:ring-1 focus:ring-accent-500 outline-none">
            </div>
            <div class="w-40">
                <input type="number" step="0.01" name="item_amount[]" required placeholder="Amount" oninput="calculateTotal()"
                    class="item-amount w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-600 focus:ring-1 focus:ring-accent-500 outline-none text-right">
            </div>
            <button type="button" onclick="removeRow(this)" class="text-gray-500 hover:text-rose-500 transition-colors p-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        `;
        container.appendChild(newRow);
    }

    function removeRow(btn) {
        const container = document.getElementById('lineItemsContainer');
        if (container.children.length > 1) {
            btn.closest('.line-item-row').remove();
            calculateTotal();
        } else {
            alert("At least one item is required.");
        }
    }

    function calculateTotal() {
        const amounts = document.querySelectorAll('.item-amount');
        let total = 0;
        amounts.forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val)) {
                total += val;
            }
        });
        document.getElementById('displayTotal').innerText = '₦' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
</script>

<?php include 'footer.php'; ?>
