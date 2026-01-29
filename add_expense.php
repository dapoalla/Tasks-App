<?php
require_once 'db.php';

$success_msg = '';
$error_msg = '';

// Fetch funds for dropdown (Only Active funds)
$funds_stmt = $pdo->query("SELECT id, title, amount FROM funds WHERE status = 'Active' ORDER BY date_released DESC");
$funds = $funds_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fund_id = $_POST['fund_id'] ? $_POST['fund_id'] : null; // Can be null if general
    $item_name = trim($_POST['item_name']);
    $amount = floatval($_POST['amount']);
    $date_incurred = $_POST['date_incurred'];
    $category = trim($_POST['category']);
    $vendor = trim($_POST['vendor']);
    
    $receipt_path = null;

    if (empty($item_name) || $amount <= 0 || empty($date_incurred) || empty($category)) {
        $error_msg = 'Please fill in all required fields correctly.';
    } else {
        
        // Handle File Upload
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileTmpPath = $_FILES['receipt']['tmp_name'];
            $fileName = $_FILES['receipt']['name'];
            $fileSize = $_FILES['receipt']['size'];
            $fileType = $_FILES['receipt']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'pdf');
            if (in_array($fileExtension, $allowedfileExtensions)) {
                // Generate unique name
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path = $uploadDir . $newFileName;

                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $receipt_path = $dest_path;
                } else {
                    $error_msg = 'Error moving uploaded file.';
                }
            } else {
                $error_msg = 'Upload failed. Allowed file types: ' . implode(',', $allowedfileExtensions);
            }
        }

        if (empty($error_msg)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO expenses (fund_id, item_name, amount, date_incurred, category, vendor, receipt_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$fund_id, $item_name, $amount, $date_incurred, $category, $vendor, $receipt_path]);
                $success_msg = 'Expense logged successfully!';
            } catch (PDOException $e) {
                $error_msg = 'Database Error: ' . $e->getMessage();
            }
        }
    }
}

include 'header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-white">Log New Expense</h2>
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

    <form method="POST" action="" enctype="multipart/form-data" class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 p-6 space-y-6">
        
        <!-- Fund Source -->
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1">Fund Source <span class="text-rose-500">*</span></label>
            <select name="fund_id" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all">
                <option value="">-- Select Source Fund --</option>
                <?php foreach ($funds as $fund): ?>
                    <option value="<?php echo $fund['id']; ?>">
                        <?php echo htmlspecialchars($fund['title']); ?> (Initial: ₦<?php echo number_format($fund['amount'], 2); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-gray-500 mt-1">Which fund release is this expense deducted from?</p>
        </div>

        <!-- Item Name -->
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1">Item / Service Name <span class="text-rose-500">*</span></label>
            <input type="text" name="item_name" required placeholder="e.g. Cat6 Ethernet Cable Roll"
                class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all placeholder-gray-600">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
             <!-- Amount -->
             <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Amount (₦) <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" name="amount" required placeholder="0.00"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all placeholder-gray-600">
            </div>

             <!-- Date -->
             <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Date Incurred <span class="text-rose-500">*</span></label>
                <input type="date" name="date_incurred" required value="<?php echo date('Y-m-d'); ?>"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all [color-scheme:dark]">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Category -->
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Category <span class="text-rose-500">*</span></label>
                <select name="category" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all">
                    <option value="Hardware">Hardware</option>
                    <option value="Software">Software</option>
                    <option value="Transport">Transport</option>
                    <option value="Feeding">Feeding/Refreshments</option>
                    <option value="Labor">Labor/Installation</option>
                    <option value="Miscellaneous">Miscellaneous</option>
                </select>
            </div>

            <!-- Vendor -->
            <div>
                 <label class="block text-sm font-medium text-gray-400 mb-1">Vendor / Store</label>
                 <input type="text" name="vendor" placeholder="e.g. Alaba Market"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 focus:border-transparent outline-none transition-all placeholder-gray-600">
            </div>
        </div>

        <!-- Receipt Upload -->
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1">Upload Receipt (Image/PDF)</label>
            <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf"
                class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-700 file:text-white hover:file:bg-gray-600">
            <p class="text-xs text-gray-500 mt-1">Optional. Max size 5MB.</p>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-gray-700">
            <button type="submit" class="w-full bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 text-white font-bold py-3 rounded-lg shadow-lg flex justify-center items-center transition-all transform active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Record Expense
            </button>
        </div>

    </form>
</div>

<?php include 'footer.php'; ?>
