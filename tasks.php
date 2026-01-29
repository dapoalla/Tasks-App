<?php
require_once 'db.php';

$success_msg = '';
$error_msg = '';

// Handle Add Task
if (isset($_POST['add_task'])) {
    $task_name = trim($_POST['task_name']);
    $location_dept = trim($_POST['location_dept'] ?? '');
    $description = trim($_POST['description']);
    $assigned_to = trim($_POST['assigned_to']);
    $due_date = $_POST['due_date'] ?: null;
    $priority = $_POST['priority'] ?: 'Medium';
    $status_details = trim($_POST['status_details'] ?? '');

    try {
        if (empty($task_name)) {
            throw new Exception("Task name is required.");
        }
        
        // Double check if column exists to avoid silent failure
        $checkCol = $pdo->query("SHOW COLUMNS FROM tasks LIKE 'status_details'");
        if ($checkCol->rowCount() == 0) {
            throw new Exception("Database mismatch: 'status_details' column is missing. Please run <a href='update_db.php' class='underline'>update_db.php</a> first.");
        }

        // Handle File Upload
        $document_path = null;
        if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
            $new_name = uniqid('task_') . '.' . $file_ext;
            $dest_path = $upload_dir . $new_name;
            
            if (move_uploaded_file($_FILES['document']['tmp_name'], $dest_path)) {
                $document_path = $dest_path;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO tasks (task_name, location_dept, description, assigned_to, due_date, priority, status, status_details, document_path) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, ?)");
        if ($stmt->execute([$task_name, $location_dept, $description, $assigned_to, $due_date, $priority, $status_details, $document_path])) {
            $success_msg = "Task added successfully!";
        } else {
            throw new Exception("Failed to add task to database.");
        }
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
    }
}

// Handle Edit Task (Full Update)
if (isset($_POST['edit_task'])) {
    $task_id = $_POST['task_id'];
    $task_name = trim($_POST['task_name']);
    $location_dept = trim($_POST['location_dept'] ?? '');
    $description = trim($_POST['description']);
    $assigned_to = trim($_POST['assigned_to']);
    $due_date = $_POST['due_date'] ?: null;
    $priority = $_POST['priority'];
    $status_details = trim($_POST['status_details'] ?? '');
    
    // Handle File Upload (Update)
    $document_update_sql = "";
    $params = [$task_name, $location_dept, $description, $assigned_to, $due_date, $priority, $status_details];
    
    if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
        $new_name = uniqid('task_') . '.' . $file_ext;
        $dest_path = $upload_dir . $new_name;
        
        if (move_uploaded_file($_FILES['document']['tmp_name'], $dest_path)) {
            $document_update_sql = ", document_path = ?";
            $params[] = $dest_path;
        }
    }
    
    $params[] = $task_id;
    
    $stmt = $pdo->prepare("UPDATE tasks SET task_name = ?, location_dept = ?, description = ?, assigned_to = ?, due_date = ?, priority = ?, status_details = ?$document_update_sql WHERE id = ?");
    if ($stmt->execute($params)) {
        $success_msg = "Task updated successfully!";
    } else {
        $error_msg = "Failed to update task.";
    }
}

// Handle Status Update (Mark Completed / Change Status)
if (isset($_POST['update_status'])) {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $task_id])) {
        $success_msg = "Task status updated!";
    } else {
        $error_msg = "Failed to update status.";
    }
}

// Handle Delete Task
if (isset($_POST['delete_task'])) {
    $task_id = $_POST['task_id'];
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    if ($stmt->execute([$task_id])) {
        $success_msg = "Task deleted!";
    } else {
        $error_msg = "Failed to delete task.";
    }
}

// Fetch Tasks
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'outstanding';
if ($filter === 'completed') {
    $stmt = $pdo->query("SELECT * FROM tasks WHERE status = 'Completed' ORDER BY updated_at DESC");
} else {
    $stmt = $pdo->query("SELECT * FROM tasks WHERE status != 'Completed' ORDER BY due_date ASC, priority DESC");
}
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mr-3 text-accent-500">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 2.25a2.25 2.25 0 0 1-2.25 2.25h-3a2.25 2.25 0 0 1-2.25-2.25m7.332 0c.855.108 1.666.239 2.433.392m-9.765 0a42.947 42.947 0 0 0-2.433.392m12.198-12.198c.328.328.328.86 0 1.188L15.666 4.704a.84.84 0 0 1-1.188 0l-1.188-1.188a.84.84 0 0 1 0-1.188l1.188-1.188a.84.84 0 0 1 1.188 0l1.188 1.188ZM6.75 6.75h.008v.008H6.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                Task Management
            </h2>
            <p class="text-gray-400">Track and manage department tasks and projects.</p>
        </div>
        <div class="flex gap-2">
            <a href="task_report.php" class="bg-gray-700 hover:bg-gray-600 text-white px-3 py-2 rounded-lg flex items-center transition-all border border-gray-600 shadow-sm text-xs">
                Outstanding Report
            </a>
            <a href="completed_task_report.php" class="bg-gray-700 hover:bg-emerald-600 text-white px-3 py-2 rounded-lg flex items-center transition-all border border-gray-600 shadow-sm text-xs text-nowrap">
                Completed Report
            </a>
            <button onclick="document.getElementById('add-task-modal').classList.remove('hidden')" class="bg-accent-600 hover:bg-accent-500 text-white font-bold px-4 py-2 rounded-lg transition-all shadow-lg flex items-center text-xs text-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 mr-1">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Task
            </button>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-lg mb-6 shadow-sm flex items-center">
             <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 mr-3">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
            </svg>
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-4 rounded-lg mb-6 shadow-sm flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 mr-3">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
            </svg>
            <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <!-- Tabs/Filters -->
    <div class="flex gap-4 mb-6 border-b border-gray-700">
        <a href="tasks.php?filter=outstanding" class="pb-3 px-4 text-sm font-semibold transition-all <?php echo $filter === 'outstanding' ? 'text-accent-500 border-b-2 border-accent-500' : 'text-gray-400 hover:text-white'; ?>">
            Outstanding Tasks
            <span class="ml-2 bg-gray-700 text-gray-300 px-2 py-0.5 rounded-full text-[10px]">
                <?php echo $pdo->query("SELECT COUNT(*) FROM tasks WHERE status != 'Completed'")->fetchColumn(); ?>
            </span>
        </a>
        <a href="tasks.php?filter=completed" class="pb-3 px-4 text-sm font-semibold transition-all <?php echo $filter === 'completed' ? 'text-accent-500 border-b-2 border-accent-500' : 'text-gray-400 hover:text-white'; ?>">
            Completed History
        </a>
    </div>

    <!-- Tasks Grid/List -->
    <div class="grid grid-cols-1 gap-4">
        <?php if (count($tasks) > 0): ?>
            <?php foreach ($tasks as $task): ?>
                <div class="bg-gray-800 border border-gray-700 rounded-xl p-5 shadow-lg group transition-all hover:border-gray-600">
                    <div class="flex flex-col md:flex-row justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="bg-gray-700 text-gray-300 text-[10px] px-2 py-0.5 rounded-full">#<?php echo $task['id']; ?></span>
                                <h3 class="text-lg font-bold text-white"><?php echo htmlspecialchars($task['task_name']); ?></h3>
                                <?php if ($task['document_path']): ?>
                                    <a href="<?php echo htmlspecialchars($task['document_path']); ?>" target="_blank" class="text-blue-400 hover:text-blue-300" title="View Attachment">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                          <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 0 0-4.242 0l-7 7a3 3 0 0 0 4.241 4.243h.001l.497-.5a.75.75 0 0 1 1.064 1.057l-.498.501-.002.002a4.5 4.5 0 0 1-6.364-6.364l7-7a4.5 4.5 0 0 1 6.368 6.36l-3.455 3.553A2.625 2.625 0 1 1 9.52 9.52l3.45-3.451a.75.75 0 1 1 1.061 1.06l-3.45 3.451a1.125 1.125 0 0 0 1.587 1.595l3.454-3.553a3 3 0 0 0 0-4.242Z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                                <?php if ($task['location_dept']): ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-gray-700 text-gray-400 border border-gray-600"><?php echo htmlspecialchars($task['location_dept']); ?></span>
                                <?php endif; ?>
                                <?php 
                                    $pColor = 'bg-gray-700 text-gray-300';
                                    if ($task['priority'] == 'High') $pColor = 'bg-rose-500/20 text-rose-400 border border-rose-500/30';
                                    if ($task['priority'] == 'Medium') $pColor = 'bg-amber-500/20 text-amber-400 border border-amber-500/30';
                                    if ($task['priority'] == 'Low') $pColor = 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
                                ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?php echo $pColor; ?>"><?php echo $task['priority']; ?></span>
                                
                                <?php 
                                    $sColor = 'bg-gray-700 text-gray-300';
                                    if ($task['status'] == 'Pending') $sColor = 'bg-blue-500/10 text-blue-400 border border-blue-500/20';
                                    if ($task['status'] == 'In Progress') $sColor = 'bg-accent-500/10 text-accent-400 border border-accent-500/20';
                                    if ($task['status'] == 'Completed') $sColor = 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
                                ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?php echo $sColor; ?>"><?php echo $task['status']; ?></span>
                            </div>
                            <p class="text-gray-400 text-sm mb-2 break-words"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                            
                            <?php if ($task['status_details']): ?>
                            <div class="mb-3 p-3 rounded-lg bg-gray-900/50 border border-gray-700/50">
                                <span class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Status Details:</span>
                                <p class="text-sm text-accent-400 italic">"<?php echo nl2br(htmlspecialchars($task['status_details'])); ?>"</p>
                            </div>
                            <?php endif; ?>
                            <div class="flex items-center text-xs text-gray-500 gap-4">
                                <?php if ($task['assigned_to']): ?>
                                <span class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 mr-1 text-accent-500">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                    Assignee: <span class="text-gray-300 ml-1"><?php echo htmlspecialchars($task['assigned_to']); ?></span>
                                </span>
                                <?php endif; ?>
                                <span class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 mr-1 text-gray-600">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                    </svg>
                                    Due: <?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'No date'; ?>
                                </span>
                                <span class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 mr-1 text-gray-600">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Created: <?php echo date('M d, Y', strtotime($task['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="editTask(<?php echo htmlspecialchars(json_encode($task)); ?>)" class="p-1.5 text-gray-500 hover:text-accent-500 hover:bg-accent-500/10 rounded transition-all" title="Edit Task">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </button>
                            <?php if ($task['status'] !== 'Completed'): ?>
                                    <?php if ($task['status'] === 'Pending'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                            <input type="hidden" name="status" value="In Progress">
                                            <button type="submit" name="update_status" value="1" class="bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 px-3 py-1.5 rounded-lg text-xs font-bold transition-all border border-blue-600/30">
                                                Start Working
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <input type="hidden" name="status" value="Completed">
                                        <button type="submit" name="update_status" value="1" class="bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 px-3 py-1.5 rounded-lg text-xs font-bold transition-all border border-emerald-600/30">
                                            Mark Done
                                        </button>
                                    </form>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <input type="hidden" name="status" value="In Progress">
                                    <button type="submit" name="update_status" value="1" class="bg-gray-700 hover:bg-gray-600 text-gray-300 px-3 py-1.5 rounded-lg text-xs font-bold transition-all border border-gray-600">
                                        Re-open
                                    </button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" onsubmit="return confirm('Delete this task forever?');">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" name="delete_task" class="p-1.5 text-gray-500 hover:text-rose-500 hover:bg-rose-500/10 rounded transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-20 bg-gray-800/50 rounded-2xl border-2 border-dashed border-gray-700">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-700/50 text-gray-600 mb-4">
                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 2.25a2.25 2.25 0 0 1-2.25 2.25h-3a2.25 2.25 0 0 1-2.25-2.25m7.332 0c.855.108 1.666.239 2.433.392m-9.765 0a42.947 42.947 0 0 0-2.433.392" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-400">All caught up!</h3>
                <p class="text-gray-500 mt-1">No tasks in this category.</p>
                <button onclick="document.getElementById('add-task-modal').classList.remove('hidden')" class="mt-6 bg-gray-700 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-all">
                    Create your first task
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Task Modal -->
<div id="add-task-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[100] flex items-center justify-center p-4 hidden">
    <div class="bg-gray-800 border border-gray-700 rounded-2xl w-full max-w-lg shadow-2xl animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-gray-700 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">Add New Task</h3>
            <button onclick="document.getElementById('add-task-modal').classList.add('hidden')" class="text-gray-500 hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Task Title</label>
                <input type="text" name="task_name" required placeholder="What needs to be done?"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Location / Department</label>
                <input type="text" name="location_dept" placeholder="e.g. Server Room, Finance, etc."
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Description (Optional)</label>
                <textarea name="description" rows="3" placeholder="Provide more context..."
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Assign To</label>
                <input type="text" name="assigned_to" placeholder="Department member name"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Attachment (Optional)</label>
                <input type="file" name="document" class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-700 file:text-white hover:file:bg-gray-600 transition-all"/>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Due Date</label>
                    <input type="date" name="due_date"
                        class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Priority</label>
                    <select name="priority" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Status Details (Initial Status)</label>
                <textarea name="status_details" rows="2" placeholder="e.g. Waiting for parts, In queue..."
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all"></textarea>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="document.getElementById('add-task-modal').classList.add('hidden')" 
                    class="flex-1 bg-gray-700 hover:bg-gray-600 text-white font-bold py-3 rounded-xl transition-all">
                    Cancel
                </button>
                <button type="submit" name="add_task" class="flex-[2] bg-accent-600 hover:bg-accent-500 text-white font-bold py-3 rounded-xl shadow-lg transition-all transform active:scale-95">
                    Create Task
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Task Modal -->
<div id="edit-task-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[100] flex items-center justify-center p-4 hidden">
    <div class="bg-gray-800 border border-gray-700 rounded-2xl w-full max-w-lg shadow-2xl animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-gray-700 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">Edit Task</h3>
            <button onclick="document.getElementById('edit-task-modal').classList.add('hidden')" class="text-gray-500 hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="task_id" id="edit-task-id">
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Task Title</label>
                <input type="text" name="task_name" id="edit-task-name" required
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Location / Department</label>
                <input type="text" name="location_dept" id="edit-location-dept"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Description</label>
                <textarea name="description" id="edit-description" rows="3"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Assign To</label>
                <input type="text" name="assigned_to" id="edit-assigned-to"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Attachment (Replace/Update)</label>
                <input type="file" name="document" class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-700 file:text-white hover:file:bg-gray-600 transition-all"/>
                <p class="text-xs text-gray-500 mt-1">Leave empty to keep existing file.</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Due Date</label>
                    <input type="date" name="due_date" id="edit-due-date"
                        class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Priority</label>
                    <select name="priority" id="edit-priority" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all">
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Status Details</label>
                <textarea name="status_details" id="edit-status-details" rows="2"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all"></textarea>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="document.getElementById('edit-task-modal').classList.add('hidden')" 
                    class="flex-1 bg-gray-700 hover:bg-gray-600 text-white font-bold py-3 rounded-xl transition-all">
                    Cancel
                </button>
                <button type="submit" name="edit_task" class="flex-[2] bg-accent-600 hover:bg-accent-500 text-white font-bold py-3 rounded-xl shadow-lg transition-all transform active:scale-95">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editTask(task) {
    document.getElementById('edit-task-id').value = task.id;
    document.getElementById('edit-task-name').value = task.task_name;
    document.getElementById('edit-location-dept').value = task.location_dept || '';
    document.getElementById('edit-description').value = task.description || '';
    document.getElementById('edit-assigned-to').value = task.assigned_to || '';
    document.getElementById('edit-due-date').value = task.due_date || '';
    document.getElementById('edit-priority').value = task.priority;
    document.getElementById('edit-status-details').value = task.status_details || '';
    document.getElementById('edit-task-modal').classList.remove('hidden');
}
</script>

<?php include 'footer.php'; ?>
