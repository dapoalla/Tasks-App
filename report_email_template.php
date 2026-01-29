<?php
// report_email_template.php
// This file is included in report.php to generate the HTML body for emailed reports.
// It assumes $fund, $total_expenses, $balance, $fund_items, $expenses, $all_funds_summary, and $selected_fund_id are in scope.

global $settings;
$church = htmlspecialchars($settings['church_name'] ?? 'Church Funds Manager');
$dept = htmlspecialchars($settings['dept_name'] ?? 'ICT Department');
$date = date('F j, Y');

?>
<div style="font-family: sans-serif; color: #333; max-width: 650px; margin: auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
    <div style="background: #1e293b; color: #fff; padding: 30px; text-align: center;">
        <h1 style="margin: 0; font-size: 22px; color: #38bdf8;"><?php echo htmlspecialchars($fund['title']); ?></h1>
        <p style="margin: 5px 0 0; font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;"><?php echo $church; ?> - <?php echo $dept; ?></p>
    </div>
    
    <div style="padding: 30px;">
        <div style="margin-bottom: 25px; background: #f8fafc; padding: 20px; border-radius: 6px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding-bottom: 10px; font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase;">Reporting Date</td>
                    <td style="padding-bottom: 10px; text-align: right; font-weight: bold;"><?php echo $date; ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-top: 1px solid #e2e8f0; font-size: 14px;">Initial Allocated Amount</td>
                    <td style="padding: 10px 0; border-top: 1px solid #e2e8f0; text-align: right; font-size: 18px; font-weight: bold; color: #10b981;">₦<?php echo number_format($fund['amount'], 2); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; font-size: 14px;">Total Actual Expenses</td>
                    <td style="padding: 10px 0; text-align: right; font-size: 18px; font-weight: bold; color: #ef4444;">₦<?php echo number_format($total_expenses, 2); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-top: 2px solid #e2e8f0; font-size: 16px; font-weight: bold;">Remaining Balance</td>
                    <td style="padding: 10px 0; border-top: 2px solid #e2e8f0; text-align: right; font-size: 20px; font-weight: bold; color: #0ea5e9;">₦<?php echo number_format($balance, 2); ?></td>
                </tr>
            </table>
        </div>

        <?php if ($selected_fund_id === 'all'): ?>
            <h3 style="font-size: 13px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 15px;">Consolidated Funds Summary</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead style="background: #f1f5f9;">
                    <tr>
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid #e2e8f0;">Fund</th>
                        <th style="padding: 10px; text-align: right; border-bottom: 2px solid #e2e8f0;">Allocated</th>
                        <th style="padding: 10px; text-align: right; border-bottom: 2px solid #e2e8f0;">Spent</th>
                        <th style="padding: 10px; text-align: right; border-bottom: 2px solid #e2e8f0;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_funds_summary as $item): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px; font-weight: bold;"><?php echo htmlspecialchars($item['title']); ?></td>
                            <td style="padding: 10px; text-align: right;">₦<?php echo number_format($item['amount'], 2); ?></td>
                            <td style="padding: 10px; text-align: right; color: #ef4444;">₦<?php echo number_format($item['spent'], 2); ?></td>
                            <td style="padding: 10px; text-align: right; font-weight: bold;">₦<?php echo number_format($item['balance'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <?php if (count($fund_items) > 0): ?>
                <h3 style="font-size: 13px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 15px;">Intended Budget</h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 25px;">
                    <?php foreach ($fund_items as $item): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 8px 0;"><?php echo htmlspecialchars($item['description']); ?></td>
                            <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #10b981;">₦<?php echo number_format($item['amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>

            <?php if (count($expenses) > 0): ?>
                <h3 style="font-size: 13px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 15px;">Expense Details</h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead style="background: #f1f5f9;">
                        <tr>
                            <th style="padding: 8px; text-align: left; border-bottom: 1px solid #e2e8f0;">Date</th>
                            <th style="padding: 8px; text-align: left; border-bottom: 1px solid #e2e8f0;">Item</th>
                            <th style="padding: 8px; text-align: right; border-bottom: 1px solid #e2e8f0;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $exp): ?>
                            <tr style="border-bottom: 1px solid #f8fafc;">
                                <td style="padding: 8px; color: #64748b;"><?php echo date('M d', strtotime($exp['date_incurred'])); ?></td>
                                <td style="padding: 8px; font-weight: bold;"><?php echo htmlspecialchars($exp['item_name']); ?></td>
                                <td style="padding: 8px; text-align: right; font-weight: bold; color: #ef4444;">₦<?php echo number_format($exp['amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #f1f5f9; text-align: center;">
            <p style="font-size: 12px; color: #94a3b8; margin: 0;">Sent automatically from Church Funds Manager v2.0</p>
        </div>
    </div>
</div>
