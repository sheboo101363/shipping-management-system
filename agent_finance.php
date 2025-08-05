<?php
// تفعيل عرض الأخطاء للمساعدة في تصحيح أي مشاكل
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تضمين ملف الاتصال بقاعدة البيانات
include 'db_connect.php';

// التحقق من ID الوكيل
$agent_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($agent_id == 0) {
    echo '<div class="container mt-5"><div class="alert alert-danger text-center">الوكيل غير موجود.</div></div>';
    exit;
}

// جلب بيانات الوكيل للتأكد من وجوده
$agent_qry = $conn->query("SELECT * FROM agents WHERE id = $agent_id");
if ($agent_qry->num_rows > 0) {
    $agent_data = $agent_qry->fetch_assoc();
} else {
    echo '<div class="container mt-5"><div class="alert alert-danger text-center">الوكيل غير موجود.</div></div>';
    exit;
}

// جلب ملخص الحسابات من جدول agent_transactions الموحد
$financial_summary_q = $conn->query("
    SELECT
        SUM(CASE WHEN transaction_type = 'commission' THEN amount ELSE 0 END) AS total_commissions,
        SUM(CASE WHEN transaction_type = 'payout' THEN amount ELSE 0 END) AS total_payouts,
        SUM(CASE WHEN transaction_type = 'commission' THEN amount ELSE amount END) AS current_balance
    FROM
        agent_transactions
    WHERE agent_id = $agent_id
");
$financial_summary = $financial_summary_q->fetch_assoc();
$total_commissions = $financial_summary['total_commissions'] ?? 0;
$total_payouts = abs($financial_summary['total_payouts'] ?? 0); // المدفوعات تُسجل بالسالب
$current_balance = $financial_summary['current_balance'] ?? 0;

// جلب سجل جميع المعاملات
$transactions_q = $conn->query("SELECT * FROM agent_transactions WHERE agent_id = $agent_id ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشوفات حساب الوكيل</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap">
    <style>
        /* تنسيق عام للصفحة */
        body { font-family: 'Tajawal', Arial, sans-serif; direction: rtl; background-color: #f4f7f6; }
        .card { border-radius: 1rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: none; }
        .card-header { background-color: #17a2b8; color: white; border-radius: 1rem 1rem 0 0; padding: 1.5rem; text-align: center; }
        .stat-card-finance { padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 20px; }
        .stat-card-finance h5 { font-weight: bold; margin-bottom: 10px; font-size: 1.1rem; }
        .stat-card-finance h4 { font-weight: bold; font-size: 1.8rem; }
        .table thead th { background-color: #17a2b8; color: #fff; border-color: #17a2b8; }
        .table tbody tr:hover { background-color: #e9ecef; }
        /* تنسيق الأرصدة */
        .balance-positive { color: #28a745; font-weight: bold; }
        .balance-negative { color: #dc3545; font-weight: bold; }
        .balance-neutral { color: #6c757d; font-weight: bold; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0 text-center"><i class="fas fa-file-invoice-dollar me-2"></i> كشوفات حساب الوكيل: <?php echo htmlspecialchars($agent_data['company_name']); ?></h4>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card-finance bg-light text-primary" style="border: 1px solid #cce5ff;">
                        <h5>إجمالي العمولات</h5>
                        <h4><?php echo number_format($total_commissions, 2); ?> جنيه</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card-finance bg-light text-success" style="border: 1px solid #c3e6cb;">
                        <h5>إجمالي المدفوعات</h5>
                        <h4><?php echo number_format($total_payouts, 2); ?> جنيه</h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card-finance bg-light text-warning" style="border: 1px solid #ffeeba;">
                        <h5>الرصيد الحالي</h5>
                        <h4 class="<?php echo ($current_balance > 0) ? 'balance-positive' : (($current_balance < 0) ? 'balance-negative' : 'balance-neutral'); ?>">
                            <?php echo number_format($current_balance, 2); ?> جنيه
                        </h4>
                    </div>
                </div>
            </div>

            <h5 class="mt-4"><i class="fas fa-history me-2"></i> سجل المعاملات المالية</h5>
            <hr>
            <div class="text-start mb-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPayoutModal">
                    <i class="fas fa-plus"></i> تسجيل دفعة جديدة
                </button>
            </div>
            
            <?php if($transactions_q->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>النوع</th>
                            <th>الوصف</th>
                            <th>المبلغ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while($row = $transactions_q->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            <td>
                                <?php if ($row['transaction_type'] == 'commission'): ?>
                                    <span class="badge bg-success">عمولة</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">دفعة</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo number_format(abs($row['amount']), 2); ?> جنيه</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info text-center">لم يتم تسجيل أي معاملات مالية لهذا الوكيل.</div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="index.php?page=agent_profile&id=<?php echo $agent_id; ?>" class="btn btn-outline-secondary mx-1"><i class="fas fa-arrow-right"></i> العودة لملف الوكيل</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addPayoutModal" tabindex="-1" aria-labelledby="addPayoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPayoutModalLabel">تسجيل دفعة جديدة للوكيل: <span id="payoutAgentName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="payoutForm">
                    <input type="hidden" name="agent_id" value="<?php echo $agent_id; ?>">
                    <div class="mb-3">
                        <label for="payout_amount" class="form-label">مبلغ الدفعة</label>
                        <input type="number" step="0.01" class="form-control" id="payout_amount" name="amount" required>
                        <small class="form-text text-muted" id="payoutBalanceInfo">الرصيد المتاح: <?php echo number_format($current_balance, 2); ?> جنيه</small>
                    </div>
                    <div class="mb-3">
                        <label for="payout_description" class="form-label">وصف الدفعة</label>
                        <textarea class="form-control" id="payout_description" name="description" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="fas fa-paper-plane"></i> تأكيد السداد</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// عند فتح نافذة الدفعة، يتم تحديث اسم الوكيل ورصيده
$('#addPayoutModal').on('show.bs.modal', function() {
    var agentName = "<?php echo htmlspecialchars($agent_data['company_name']); ?>";
    var currentBalance = "<?php echo $current_balance; ?>";
    $('#payoutAgentName').text(agentName);
    $('#payoutBalanceInfo').text('الرصيد المتاح: ' + parseFloat(currentBalance).toFixed(2) + ' جنيه');
});

// عند إرسال نموذج الدفعة
$('#payoutForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    var payoutAmount = parseFloat($('#payout_amount').val());
    var currentBalance = parseFloat("<?php echo $current_balance; ?>");

    // تحقق من أن المبلغ لا يتجاوز الرصيد
    if (payoutAmount <= 0 || payoutAmount > currentBalance) {
        alert("مبلغ الدفعة غير صالح. يجب أن يكون أكبر من صفر ولا يتجاوز الرصيد المتاح.");
        return;
    }
    
    if (confirm("هل أنت متأكد من سداد هذه الدفعة؟")) {
        $.ajax({
            url: 'process_payout.php', // ملف معالجة الدفع
            type: 'POST',
            data: formData,
            success: function(response) {
                alert(response);
                location.reload(); // إعادة تحميل الصفحة لرؤية التغييرات
            },
            error: function(xhr, status, error) {
                alert("حدث خطأ أثناء إتمام الدفعة: " + error);
            }
        });
    }
});
</script>

</body>
</html>