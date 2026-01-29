<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $redirect = $_POST['redirect'] ?? 'index.php';

    if ($id > 0) {
        try {
            if ($type === 'fund') {
                // Delete Fund (Cascades to fund_items due to FK, Expenses set to NULL)
                $stmt = $pdo->prepare("DELETE FROM funds WHERE id = ?");
                $stmt->execute([$id]);
                $msg = "Fund deleted successfully.";
            } elseif ($type === 'expense') {
                // Delete Expense
                // First get receipt path to delete file if exists
                $stmtGet = $pdo->prepare("SELECT receipt_path FROM expenses WHERE id = ?");
                $stmtGet->execute([$id]);
                $expense = $stmtGet->fetch(PDO::FETCH_ASSOC);

                if ($expense && $expense['receipt_path'] && file_exists($expense['receipt_path'])) {
                    unlink($expense['receipt_path']);
                }

                $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
                $stmt->execute([$id]);
                $msg = "Expense deleted successfully.";
            } elseif ($type === 'task') {
                $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
                $stmt->execute([$id]);
                $msg = "Task deleted successfully.";
            }
        } catch (PDOException $e) {
            // In a real app we might log this or show error
            // For now, simple redirect
        }
    }
}

// Redirect back
header("Location: " . $redirect);
exit;
