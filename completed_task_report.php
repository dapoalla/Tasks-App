<?php
require_once 'db.php';

// Fetch all completed tasks
$stmt = $pdo->query("SELECT * FROM tasks WHERE status = 'Completed' ORDER BY updated_at DESC");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<div class="max-w-4xl mx-auto mb-6 no-print">
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-gray-800 p-4 rounded-xl border border-gray-700 shadow-lg">
        <div>
            <h2 class="text-xl font-bold text-white">Completed Tasks Report</h2>
            <a href="task_report.php" class="text-xs text-accent-400 hover:underline">&larr; Switch to Outstanding Tasks</a>
        </div>
        <div class="flex gap-2">
            <button onclick="exportToJPG()" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                JPG
            </button>
            <button onclick="exportToPDF()" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                PDF
            </button>
            <button onclick="exportToWhatsApp()" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg flex items-center transition-colors shadow-lg text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-4 h-4 mr-2">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                </svg>
                WhatsApp
            </button>
        </div>
    </div>
</div>

<div id="report-content" class="max-w-4xl mx-auto bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-8 shadow-xl rounded-xl border border-gray-700 print:shadow-none print:border-0 print:p-0">
    <!-- Report Header -->
    <div class="border-b border-gray-200 dark:border-gray-700 pb-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold uppercase tracking-wide text-emerald-600">Completed Tasks Report</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?php echo htmlspecialchars($settings['church_name']); ?> - <?php echo htmlspecialchars($settings['dept_name']); ?></p>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500 dark:text-gray-400">Date Generated</div>
                <div class="font-medium"><?php echo date('F j, Y'); ?></div>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 print:grid-cols-2">
        <div class="p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-500/20 text-center">
            <span class="block text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase">Tasks Completed</span>
            <span class="text-2xl font-bold text-emerald-700 dark:text-emerald-300"><?php echo count($tasks); ?></span>
        </div>
        <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-600 text-center">
            <span class="block text-xs font-bold text-gray-500 uppercase">Status</span>
            <span class="text-2xl font-bold text-gray-700 dark:text-gray-300">Archive</span>
        </div>
    </div>

    <!-- Grouped Tasks Table -->
    <?php 
    $groupedTasks = [];
    foreach ($tasks as $task) {
        $loc = $task['location_dept'] ?: 'Other / Unassigned';
        $groupedTasks[$loc][] = $task;
    }
    ksort($groupedTasks);
    ?>

    <?php if (count($tasks) > 0): ?>
        <?php foreach ($groupedTasks as $location => $locTasks): ?>
            <div class="mt-8 mb-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-emerald-600 bg-emerald-500/5 px-3 py-1 rounded inline-block border border-emerald-500/20 mb-4"><?php echo htmlspecialchars($location); ?></h3>
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-700">
                            <th class="py-2 text-[10px] font-bold text-gray-400 uppercase tracking-tighter w-1/2">Completed Item</th>
                            <th class="py-2 text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Outcome/Details</th>
                            <th class="py-2 text-[10px] font-bold text-gray-400 uppercase tracking-tighter text-center text-emerald-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <?php foreach ($locTasks as $task): ?>
                            <tr>
                                <td class="py-3 pr-4">
                                    <div class="font-bold text-gray-800 dark:text-white text-sm"><?php echo htmlspecialchars($task['task_name']); ?></div>
                                    <div class="text-[10px] text-gray-500 mt-0.5 whitespace-pre-wrap break-words italic"><?php echo htmlspecialchars($task['description']); ?></div>
                                </td>
                                <td class="py-3 text-xs text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($task['status_details'] ?? 'No completion notes.'); ?>
                                </td>
                                <td class="py-3 text-center">
                                    <span class="status-badge text-[10px] px-1.5 py-0.5 rounded font-black uppercase text-emerald-500 transition-colors">
                                        <?php echo $task['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="py-12 text-center text-gray-500 italic">No completed tasks available to report.</div>
    <?php endif; ?>

    <div class="mt-12 text-center pt-8 border-t border-gray-100 dark:border-gray-700">
        <p class="text-[10px] text-gray-400 uppercase tracking-widest">© <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['app_name']); ?> System</p>
    </div>
</div>

<script>
async function exportToJPG() {
    await exportReport('jpg');
}

async function exportToPDF() {
    await exportReport('pdf');
}

async function exportReport(format) {
    const element = document.getElementById('report-content');
    const originalClasses = element.className;
    
    element.classList.remove('dark:bg-gray-800', 'dark:text-gray-100', 'dark:border-gray-700');
    element.classList.add('bg-white', 'text-gray-900', 'border-gray-200');
    
    const darkElements = element.querySelectorAll('[class*="dark:"]');
    const savedStyles = [];
    
    darkElements.forEach(el => {
        savedStyles.push({ el: el, className: el.className });
        el.classList.remove('dark:bg-gray-800', 'dark:bg-gray-700/30', 'dark:bg-rose-900/10', 'dark:bg-blue-900/10', 'dark:bg-emerald-900/10', 'dark:text-gray-300', 'dark:text-gray-400', 'dark:text-white', 'dark:text-emerald-300', 'dark:border-gray-600', 'dark:divide-gray-700', 'bg-gray-100', 'dark:bg-gray-700');
        
        if (el.classList.contains('bg-gray-50') || el.classList.contains('dark:bg-gray-700/30')) el.classList.add('bg-gray-50');
        if (el.classList.contains('bg-emerald-50') || el.classList.contains('dark:bg-emerald-900/10')) el.classList.add('bg-emerald-50');
        if (el.classList.contains('text-gray-800') || el.classList.contains('dark:text-white')) el.classList.add('text-gray-900');
        if (el.classList.contains('text-gray-500') || el.classList.contains('dark:text-gray-400')) el.classList.add('text-gray-600');
        
        if (el.classList.contains('status-badge')) {
            el.style.backgroundColor = 'transparent';
            el.style.backgroundImage = 'none';
        }
    });

    try {
        const canvas = await html2canvas(element, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });

        if (format === 'jpg') {
            const link = document.createElement('a');
            link.download = `Completed_Tasks_Report_${new Date().toISOString().split('T')[0]}.jpg`;
            link.href = canvas.toDataURL('image/jpeg', 0.9);
            link.click();
        } else {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            const imgWidth = 210;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            pdf.addImage(canvas.toDataURL('image/jpeg', 1.0), 'JPEG', 0, 0, imgWidth, imgHeight);
            pdf.save(`Completed_Tasks_Report_${new Date().toISOString().split('T')[0]}.pdf`);
        }
    } catch (error) {
        console.error('Export failed:', error);
        alert('Export failed. Please try again.');
    } finally {
        element.className = originalClasses;
        savedStyles.forEach(item => { item.el.className = item.className; });
    }
}

function exportToWhatsApp() {
    let text = "*✅ COMPLETED TASKS REPORT*\n";
    text += "Date: " + new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) + "\n";
    text += "--------------------------------\n\n";

    const tasks = <?php echo json_encode($tasks); ?>;
    
    // Group by location for WhatsApp
    const grouped = {};
    tasks.forEach(t => {
        const loc = t.location_dept || 'Unassigned';
        if (!grouped[loc]) grouped[loc] = [];
        grouped[loc].push(t);
    });

    for (const [location, locTasks] of Object.entries(grouped)) {
        text += `📍 *${location.toUpperCase()}*\n`;
        locTasks.forEach((task, index) => {
            text += `  ✅ *${task.task_name}*\n`;
            if (task.status_details) {
                text += `     _${task.status_details}_\n`;
            }
        });
        text += "\n";
    }

    text += "--------------------------------\n";
    text += "🔗 *Full History:* https://wcs.afmweca.com/tasks/tasks.php";

    document.getElementById('whatsapp-text').value = text;
    document.getElementById('whatsapp-modal').classList.remove('hidden');
}

function copyWhatsAppText() {
    const textArea = document.getElementById('whatsapp-text');
    textArea.select();
    document.execCommand('copy');
    alert('Report text copied!');
}
</script>

<div id="whatsapp-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[100] flex items-center justify-center p-4 hidden">
    <div class="bg-gray-800 border border-gray-700 rounded-2xl w-full max-w-lg shadow-2xl">
        <div class="p-6 border-b border-gray-700 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-6 h-6 mr-2 text-emerald-500">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                </svg>
                WhatsApp Export
            </h3>
            <button onclick="document.getElementById('whatsapp-modal').classList.add('hidden')" class="text-gray-500 hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-6">
            <p class="text-gray-400 text-sm mb-4">Formatted for sharing your achievements.</p>
            <textarea id="whatsapp-text" rows="12" readonly
                class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-emerald-400 font-mono text-sm focus:ring-1 focus:ring-emerald-500 outline-none transition-all resize-none"></textarea>
            <div class="mt-6 flex gap-3">
                <button onclick="document.getElementById('whatsapp-modal').classList.add('hidden')" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white font-bold py-3 rounded-xl transition-all">Close</button>
                <button onclick="copyWhatsAppText()" class="flex-[2] bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl shadow-lg transition-all flex items-center justify-center">
                    Copy to Clipboard
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
