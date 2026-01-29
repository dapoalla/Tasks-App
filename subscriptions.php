<?php
require_once 'db.php';

$success_msg = '';
$error_msg = '';

// Handle Add/Edit Subscription
if (isset($_POST['save_subscription'])) {
    $id = $_POST['id'] ?? null;
    $location_dept = trim($_POST['location_dept']);
    $provider = trim($_POST['provider']);
    $plan_name = trim($_POST['plan_name']);
    $amount = $_POST['amount'] ?: 0;
    $expiry_date = $_POST['expiry_date'] ?: null;
    $renewal_status = $_POST['renewal_status'];
    $last_paid_date = $_POST['last_paid_date'] ?: null;
    $notes = trim($_POST['notes']);

    try {
        if (empty($location_dept)) throw new Exception("Location/Department is required.");

        // Handle File Upload
        $document_path = null;
        if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
            $new_name = uniqid('sub_') . '.' . $file_ext;
            $dest_path = $upload_dir . $new_name;
            
            if (move_uploaded_file($_FILES['document']['tmp_name'], $dest_path)) {
                $document_path = $dest_path;
            }
        }

        if ($id) {
            $doc_sql = $document_path ? ", document_path = ?" : "";
            $params = [$location_dept, $provider, $plan_name, $amount, $expiry_date, $renewal_status, $last_paid_date, $notes];
            if ($document_path) $params[] = $document_path;
            $params[] = $id;
            
            $stmt = $pdo->prepare("UPDATE internet_subscriptions SET location_dept = ?, provider = ?, plan_name = ?, amount = ?, expiry_date = ?, renewal_status = ?, last_paid_date = ?, notes = ?$doc_sql WHERE id = ?");
            $stmt->execute($params);
            $success_msg = "Subscription updated!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO internet_subscriptions (location_dept, provider, plan_name, amount, expiry_date, renewal_status, last_paid_date, notes, document_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$location_dept, $provider, $plan_name, $amount, $expiry_date, $renewal_status, $last_paid_date, $notes, $document_path]);
            $success_msg = "Subscription added!";
        }
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM internet_subscriptions WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: subscriptions.php?success=Deleted");
    exit;
}

// Fetch all subscriptions
$subscriptions = $pdo->query("SELECT * FROM internet_subscriptions ORDER BY expiry_date ASC")->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-black text-white tracking-tight flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-10 h-10 mr-3 text-blue-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205l3 1m1.5-1.5l-3-1m-3 1.345l3 1m-1.5-1.5l-3-1m-3 1.345l3 1m-1.5-1.5l-3-1" />
                </svg>
                Internet Subscriptions
            </h2>
            <p class="text-gray-400 mt-1">Track payments and renewal status per location.</p>
        </div>
        <div class="flex gap-2">
            <a href="subscription_report.php" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-3 rounded-xl transition-all border border-gray-600 shadow-sm font-bold flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                View Report
            </a>
            <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-6 py-3 rounded-xl transition-all shadow-lg flex items-center transform active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add New Subscription
            </button>
        </div>
    </div>

    <?php if ($success_msg || isset($_GET['success'])): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-xl mb-6 flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <?php echo $success_msg ?: $_GET['success']; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($subscriptions as $sub): ?>
            <div class="bg-gray-800 border border-gray-700 p-6 rounded-2xl shadow-xl hover:shadow-2xl hover:border-blue-500/30 transition-all group relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="editSub(<?php echo htmlspecialchars(json_encode($sub)); ?>)" class="text-gray-400 hover:text-blue-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <a href="?delete=<?php echo $sub['id']; ?>" onclick="return confirm('Delete this subscription?')" class="text-gray-400 hover:text-rose-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </a>
                </div>

                <div class="mb-4">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="text-xl font-bold text-white"><?php echo htmlspecialchars($sub['location_dept']); ?></h3>
                        <?php if ($sub['document_path']): ?>
                            <a href="<?php echo htmlspecialchars($sub['document_path']); ?>" target="_blank" class="text-blue-400 hover:text-blue-300" title="View Receipt/Doc">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                  <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 0 0-4.242 0l-7 7a3 3 0 0 0 4.241 4.243h.001l.497-.5a.75.75 0 0 1 1.064 1.057l-.498.501-.002.002a4.5 4.5 0 0 1-6.364-6.364l7-7a4.5 4.5 0 0 1 6.368 6.36l-3.455 3.553A2.625 2.625 0 1 1 9.52 9.52l3.45-3.451a.75.75 0 1 1 1.061 1.06l-3.45 3.451a1.125 1.125 0 0 0 1.587 1.595l3.454-3.553a3 3 0 0 0 0-4.242Z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                    <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($sub['provider']); ?> - <?php echo htmlspecialchars($sub['plan_name']); ?></p>
                </div>

                <div class="space-y-3 mb-6">
                    <div class="flex justify-between items-center bg-gray-900/50 p-3 rounded-xl border border-gray-700/50">
                        <span class="text-xs font-bold text-gray-500 uppercase">Expiry Date</span>
                        <span class="text-sm font-bold <?php echo strtotime($sub['expiry_date']) < time() ? 'text-rose-400' : 'text-emerald-400'; ?>">
                            <?php echo date('M d, Y', strtotime($sub['expiry_date'])); ?>
                        </span>
                    </div>

                    <div class="flex justify-between items-center bg-gray-900/50 p-3 rounded-xl border border-gray-700/50">
                        <span class="text-xs font-bold text-gray-500 uppercase">Status</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase shadow-sm <?php 
                            if ($sub['renewal_status'] == 'Done') echo 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
                            elseif ($sub['renewal_status'] == 'Waiting for Funds') echo 'bg-amber-500/20 text-amber-400 border border-amber-500/30';
                            else echo 'bg-rose-500/20 text-rose-400 border border-rose-500/30';
                        ?>">
                            <?php echo $sub['renewal_status']; ?>
                        </span>
                    </div>

                    <div class="flex justify-between items-center text-sm px-1">
                        <span class="text-gray-500">Amount</span>
                        <span class="text-white font-black"><?php echo number_format($sub['amount'], 2); ?></span>
                    </div>
                </div>

                <?php if ($sub['notes']): ?>
                    <div class="text-xs text-gray-500 italic bg-gray-900/30 p-2 rounded-lg border border-gray-700/30">
                        "<?php echo htmlspecialchars($sub['notes']); ?>"
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal -->
<div id="sub-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-gray-800 border border-gray-700 rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="flex justify-between items-center p-6 border-b border-gray-700">
            <h3 id="modal-title" class="text-2xl font-black text-white px-2">Add Subscription</h3>
            <button onclick="closeModal()" class="text-gray-500 hover:text-white p-2 hover:bg-gray-700 rounded-full transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-8">
            <input type="hidden" name="id" id="form-id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-black text-gray-400 mb-2 uppercase tracking-widest">Location / Dept</label>
                    <input type="text" name="location_dept" id="form-location" required class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all border-l-4 border-l-blue-600 shadow-inner">
                </div>
                <div>
                    <label class="block text-sm font-black text-gray-400 mb-2 uppercase tracking-widest">Renewal Status</label>
                    <select name="renewal_status" id="form-status" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                        <option value="Not Done">Not Done</option>
                        <option value="Waiting for Funds">Waiting for Funds</option>
                        <option value="Done">Done</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-black text-gray-400 mb-2 uppercase tracking-widest">Provider</label>
                    <input type="text" name="provider" id="form-provider" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-black text-gray-400 mb-2 uppercase tracking-widest">Plan Name</label>
                    <input type="text" name="plan_name" id="form-plan" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-black text-gray-400 mb-2 uppercase tracking-widest">Amount</label>
                    <input type="number" step="0.01" name="amount" id="form-amount" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-black text-gray-400 mb-2 uppercase tracking-widest">Expiry Date</label>
                    <input type="date" name="expiry_date" id="form-expiry" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-black text-gray-400 mb-2 uppercase tracking-widest">Receipt / Document</label>
                <input type="file" name="document" class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-700 file:text-white hover:file:bg-gray-600 transition-all"/>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-black text-gray-400 mb-2 uppercase tracking-widest">Notes / Remarks</label>
                <textarea name="notes" id="form-notes" rows="3" class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all"></textarea>
            </div>
            <button type="submit" name="save_subscription" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-2xl transition-all shadow-xl text-lg uppercase tracking-widest transform active:scale-95 shadow-blue-900/20">
                Save Subscription Details
            </button>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modal-title').innerText = "Add Subscription";
    document.getElementById('form-id').value = "";
    document.getElementById('sub-modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('sub-modal').classList.add('hidden');
}

function editSub(sub) {
    document.getElementById('modal-title').innerText = "Edit Subscription";
    document.getElementById('form-id').value = sub.id;
    document.getElementById('form-location').value = sub.location_dept;
    document.getElementById('form-provider').value = sub.provider;
    document.getElementById('form-plan').value = sub.plan_name;
    document.getElementById('form-amount').value = sub.amount;
    document.getElementById('form-expiry').value = sub.expiry_date;
    document.getElementById('form-status').value = sub.renewal_status;
    document.getElementById('form-notes').value = sub.notes;
    document.getElementById('sub-modal').classList.remove('hidden');
}
</script>

<?php include 'footer.php'; ?>
