<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
}
include('include/header.php');
include('include/navbar.php');
include('include/config.php');

// Function to execute SQL query and return the result
function getCashboxSummary($sql) {
    global $conn;
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row[array_key_first($row)]; // Return the first value in the row
    } else {
        return 0; // Return 0 if no data found
    }
}

// Get total income
$total_income = getCashboxSummary("SELECT SUM(amount) AS total_income FROM cashbox_transactions WHERE amount > 0");

// Get total expenses
$total_expenses = getCashboxSummary("SELECT SUM(amount) AS total_expenses FROM cashbox_transactions WHERE amount < 0");

// Get current balance
$current_balance = getCashboxSummary("SELECT SUM(amount) AS current_balance FROM cashbox_transactions");

// Get net profit (excluding salary payments)
$net_profit = getCashboxSummary("SELECT SUM(amount) AS net_profit FROM cashbox_transactions WHERE transaction_type != 'Salary Payment'");

?>

<div class="container">

    <!-- Financial Summaries -->
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">إجمالي الإيرادات</h5>
                    <p class="card-text"><?php echo number_format($total_income, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">إجمالي المدفوعات</h5>
                    <p class="card-text"><?php echo number_format($total_expenses, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">رصيد الخزنة الحالي</h5>
                    <p class="card-text"><?php echo number_format($current_balance, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">الربح الصافي</h5>
                    <p class="card-text"><?php echo number_format($net_profit, 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <hr>

    <!-- Cashbox Transactions Table -->
    <h2>حركات الخزنة</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>نوع الحركة</th>
                <th>المبلغ</th>
                <th>الجهة</th>
                <th>طريقة الدفع</th>
                <th>الوصف</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch cashbox transactions from the database
            $sql = "SELECT * FROM cashbox_transactions ORDER BY transaction_date DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['transaction_date'] . "</td>";
                    echo "<td>" . $row['transaction_type'] . "</td>";
                    echo "<td>" . $row['amount'] . "</td>";
                    echo "<td>" . $row['source_type'] . " - " . $row['source_id'] . "</td>"; // You might need to fetch the actual name from the corresponding table
                    echo "<td>" . $row['payment_method'] . "</td>";
                    echo "<td>" . $row['description'] . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>لا توجد حركات في الخزنة.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <hr>

    <!-- Add New Transaction Button -->
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addTransactionModal">
        إضافة حركة جديدة
    </button>

    <!-- Add Transaction Modal -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1" role="dialog" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTransactionModalLabel">إضافة حركة جديدة</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Add Transaction Form -->
                    <form action="add_transaction.php" method="POST">
                        <div class="form-group">
                            <label for="transaction_type">نوع الحركة</label>
                            <select class="form-control" id="transaction_type" name="transaction_type" required>
                                <option value="استلام من عميل">استلام من عميل</option>
                                <option value="دفع لمندوب">دفع لمندوب</option>
                                <option value="تحويل لوكيل">تحويل لوكيل</option>
                                <!-- Add more transaction types as needed -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="amount">المبلغ</label>
                            <input type="number" class="form-control" id="amount" name="amount" required>
                        </div>
                        <div class="form-group">
                            <label for="source_type">الجهة</label>
                            <select class="form-control" id="source_type" name="source_type">
                                <option value="client">عميل</option>
                                <option value="delivery_agent">مندوب</option>
                                <option value="partner">وكيل</option>
                                <option value="system">نظام</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="source_id">رقم الجهة</label>
                            <input type="number" class="form-control" id="source_id" name="source_id">
                        </div>
                        <div class="form-group">
                            <label for="payment_method">طريقة الدفع</label>
                            <select class="form-control" id="payment_method" name="payment_method" required>
                                <option value="كاش">كاش</option>
                                <option value="تحويل بنكي">تحويل بنكي</option>
                                <!-- Add more payment methods as needed -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="description">الوصف</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
include('include/scripts.php');
include('include/footer.php');
?>
