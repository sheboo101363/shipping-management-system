<?php
if (!isset($conn)) {
    include 'db_connect.php';
}

// جلب بيانات الوكلاء
$agents_result = $conn->query("SELECT id, company_name, coop_type, commission_type, commission_value FROM agents WHERE status=1");
$agents = [];
while ($row = $agents_result->fetch_assoc()) {
    $agents[] = $row;
}

// جلب بيانات المحافظات
$governorates_result = $conn->query("SELECT * FROM governorates ORDER BY name ASC");
$governorates = [];
while ($g = $governorates_result->fetch_assoc()) {
    $governorates[] = $g;
}

// جلب بيانات المناديب
$couriers_result = $conn->query("SELECT id, name FROM couriers WHERE status=1 ORDER BY name ASC");
$couriers = [];
while ($c = $couriers_result->fetch_assoc()) {
    $couriers[] = $c;
}

// جلب بيانات الفروع
$branches_result = $conn->query("SELECT id, branch_code FROM branches ORDER BY branch_code ASC");
$branches = [];
while ($b = $branches_result->fetch_assoc()) {
    $branches[] = $b;
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة شحنة جديدة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb !important; font-family: 'Tajawal', Arial, sans-serif; direction: rtl; }
        .page-title { font-weight: bold; font-size: 2rem; color: #1976d2; margin-bottom: 18px; text-align: right; letter-spacing: 1px; }
        .modern-btn { border-radius: 2rem !important; font-weight: 600; font-size: 1.07rem; box-shadow: 0 2px 10px #38f9d733; padding: 0.7rem 2rem; transition: all 0.2s; }
        .modern-btn:hover { opacity: 0.95; }
        .box-section { background: #fff; border-radius: 18px; box-shadow: 0 4px 18px #ddeafc33; padding: 28px 22px 18px 22px; margin-bottom: 24px; position: relative; text-align: right; }
        .box-title { font-size: 1.25em; color: #1565c0; font-weight: bold; margin-bottom: 16px; display: flex; align-items: center; gap: 9px; justify-content: flex-end; }
        .form-label { font-weight: 500; color: #1976d2; }
        .form-control, .form-select { border-radius: 1.2rem !important; text-align: right; }
        .box-icon { font-size: 1.33em; color: #00bcd4; margin-left: 7px; margin-right: 0; }
        .agent-btn-float { position: absolute; top: -40px; left: 0; z-index: 9; }
        @media (max-width: 992px) { .agent-btn-float { position: static; margin-bottom: 20px; } }
        .cost-summary-card { background: #fff; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.09); padding: 32px 24px; margin: 32px auto 0 auto; max-width: 470px; text-align: right; font-size: 1.15em; color: #1a237e; direction: rtl; }
        .cost-summary-card .cost-label { font-weight: bold; font-size: 1.15em; color: #1976d2; margin-bottom: 9px; }
        .cost-summary-card .cost-value { font-weight: bold; font-size: 1.10em; color: #388e3c; margin-bottom: 13px; background: #f6f7fa; border-radius: 8px; box-shadow: 0 1.5px 6px #bbdefb22; padding: 7px 0; }
        .cost-summary-card .total-value { background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%); color: #222; font-size: 1.28em; font-weight: bold; padding: 14px 0; border-radius: 10px; margin-bottom: 0; box-shadow: 0 2px 8px #38f9d766; }
        .cost-summary-card .icon { font-size: 2em; color: #00bcd4; margin-bottom: 5px; }
        .autocomplete-suggestions { position: absolute; background: #fff; border: 1px solid #ddd; border-radius: 8px; max-height: 220px; overflow-y: auto; z-index: 99; width: 100%; box-shadow: 0 5px 15px #eee; }
        .autocomplete-suggestion { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee; text-align: right; }
        .autocomplete-suggestion:last-child { border-bottom: none; }
        .autocomplete-suggestion:hover { background: #e3f2fd; }
        #reset_selected_customer { margin: 6px 0 0 0; font-size: 0.95em; }
        .modal-content { border-radius: 1.5rem !important; background: #fff; box-shadow: 0 4px 18px #ddeafc33; }
        .modal-header { border-radius: 1.5rem 1.5rem 0 0 !important; background: linear-gradient(90deg, #17a2b8 0, #77eaff 100%) !important; text-align: right; }
        .modal-title { color: #fff; font-weight: bold; font-size: 1.2em; }
        .modal-footer { border-radius: 0 0 1.5rem 1.5rem !important; background: #f8fafd; }
        .list-group-item { cursor: pointer; text-align: right; }
        .bg-gradient-info { background: linear-gradient(90deg, #17a2b8 0, #77eaff 100%) !important; }
        .form-check-label, .form-label, .form-control, .form-select { text-align: right; }
        @media (max-width: 600px) { .page-title { font-size: 1.3rem; } }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10 position-relative">
            <div class="page-title"><i class="fa-solid fa-truck-fast"></i> إضافة شحنة جديدة</div>
            <div class="agent-btn-float">
                <button type="button" class="btn btn-outline-info modern-btn" data-bs-toggle="modal" data-bs-target="#agentModal">
                    <i class="fas fa-user-tie"></i> ربط وكيل للشحنة
                </button>
            </div>
            <form action="" id="manage-parcel" autocomplete="off">
                <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
                <input type="hidden" name="agent_id" id="agent_id" />
                <input type="hidden" name="agent_direction" id="agent_direction_val" />
                <div id="msg"></div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="box-section">
                            <div class="box-title"><span class="box-icon"><i class="fa-solid fa-paper-plane"></i></span> بيانات الراسل</div>
                            <div class="form-group" style="position:relative;">
                                <label class="form-label"><i class="fa-regular fa-address-card"></i> اسم الراسل</label>
                                <input type="text" name="sender_name" id="sender_name_autocomplete" class="form-control" required autocomplete="off" value="">
                                <div id="customer_suggestions" class="autocomplete-suggestions" style="display:none;"></div>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-mobile-screen"></i> رقم جوال الراسل</label>
                                <input type="text" name="sender_phone" class="form-control sender_input" required value="">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-envelope"></i> البريد الإلكتروني للراسل</label>
                                <input type="email" name="sender_email" class="form-control sender_input" value="">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-location-dot"></i> عنوان الراسل</label>
                                <input type="text" name="sender_address" class="form-control sender_input" value="">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-map"></i> المحافظة</label>
                                <select name="sender_governorate_id" id="sender-governorate" class="form-control sender_input" required>
                                    <option value="">اختر المحافظة</option>
                                    <?php foreach ($governorates as $g): ?>
                                        <option value="<?php echo $g['id'] ?>"><?php echo $g['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-location-crosshairs"></i> المنطقة</label>
                                <select name="sender_area_id" id="sender-area" class="form-control sender_input" required>
                                    <option value="">اختر المنطقة</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-user-check"></i> الحالة</label>
                                <select name="sender_status" class="form-control sender_input">
                                    <option value="1">نشط</option>
                                    <option value="0">موقوف</option>
                                </select>
                            </div>
                            <button type="button" id="reset_selected_customer" class="btn btn-warning btn-sm d-none">تغيير العميل</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="box-section">
                            <div class="box-title"><span class="box-icon"><i class="fa-solid fa-user-plus"></i></span> بيانات المستلم</div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-regular fa-address-card"></i> اسم المستلم</label>
                                <input type="text" name="recipient_name" class="form-control" required value="">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-mobile-screen"></i> رقم جوال المستلم</label>
                                <input type="text" name="recipient_phone" class="form-control" required value="">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-envelope"></i> البريد الإلكتروني للمستلم</label>
                                <input type="email" name="recipient_email" class="form-control" value="">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-location-dot"></i> عنوان المستلم</label>
                                <input type="text" name="recipient_address" class="form-control" value="">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-map"></i> المحافظة</label>
                                <select name="recipient_governorate_id" id="recipient-governorate" class="form-control" required>
                                    <option value="">اختر المحافظة</option>
                                    <?php foreach ($governorates as $g): ?>
                                        <option value="<?php echo $g['id'] ?>"><?php echo $g['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-location-crosshairs"></i> المنطقة</label>
                                <select name="recipient_area_id" id="recipient-area" class="form-control" required>
                                    <option value="">اختر المنطقة</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fa-solid fa-user-check"></i> الحالة</label>
                                <select name="recipient_status" class="form-control">
                                    <option value="1">نشط</option>
                                    <option value="0">موقوف</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="box-section">
                            <div class="box-title"><span class="box-icon"><i class="fa-solid fa-box"></i></span> تفاصيل الشحنة</div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group courier-group">
                                        <label class="form-label"><i class="fa-solid fa-person-biking"></i> المندوب</label>
                                        <select name="courier_id" id="courier_id" class="form-control" required>
                                            <option value="">اختر المندوب</option>
                                            <?php foreach ($couriers as $c): ?>
                                                <option value="<?php echo $c['id'] ?>"><?php echo $c['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label"><i class="fa-solid fa-building"></i> من الفرع</label>
                                        <select name="from_branch_id" class="form-control" required>
                                            <option value="">اختر الفرع</option>
                                            <?php foreach ($branches as $b): ?>
                                                <option value="<?php echo $b['id'] ?>"><?php echo $b['branch_code'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label"><i class="fa-solid fa-building-circle-arrow-right"></i> إلى الفرع</label>
                                        <select name="to_branch_id" class="form-control" required>
                                            <option value="">اختر الفرع</option>
                                            <?php foreach ($branches as $b): ?>
                                                <option value="<?php echo $b['id'] ?>"><?php echo $b['branch_code'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label"><i class="fa-solid fa-file"></i> نوع الطرد</label>
                                        <select name="type" class="form-control" required>
                                            <option value="1">مستندات</option>
                                            <option value="2">طرد</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="form-label"><i class="fa-solid fa-weight-hanging"></i> الوزن</label>
                                    <input type="text" name="weight" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><i class="fa-solid fa-ruler-vertical"></i> الطول</label>
                                    <input type="text" name="length" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><i class="fa-solid fa-ruler-horizontal"></i> العرض</label>
                                    <input type="text" name="width" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><i class="fa-solid fa-ruler-combined"></i> الارتفاع</label>
                                    <input type="text" name="height" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fa-solid fa-money-bill-wave"></i> سعر المنتج</label>
                                    <input type="text" name="price" id="product-price-input" class="form-control" required value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="agent_summary" style="display:none;" class="mb-3">
                    <div class="box-section" style="background:#fff;">
                        <div class="box-title"><span class="box-icon"><i class="fas fa-user-tie"></i></span> بيانات وكيل الشحنة</div>
                        <div><b>الوكيل:</b> <span id="agent_summary_name"></span></div>
                        <div><b>نوع التعاون:</b> <span id="agent_summary_coop"></span></div>
                        <div><b>العمولة:</b> <span id="agent_summary_comm"></span></div>
                        <div><b>اتجاه الشحنة:</b> <span id="agent_summary_dir"></span></div>
                    </div>
                </div>

                <div id="cost-summary" class="cost-summary-card" style="display:none;">
                    <div class="icon text-center">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div class="cost-label">تكلفة المنتج</div>
                    <div class="cost-value text-center" id="product-cost-value">-- جنيه</div>
                    <div class="cost-label">تكلفة الشحن</div>
                    <div class="cost-value text-center" id="shipping-cost-value">-- جنيه</div>
                    <div class="cost-label">إجمالي التكلفة</div>
                    <div class="total-value text-center" id="total-cost-value">-- جنيه</div>
                </div>
            
                <div class="row mt-4">
                    <div class="col-md-12 text-center">
                        <button type="submit" class="btn btn-primary modern-btn"><i class="fas fa-save"></i> حفظ الشحنة</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="agentModal" tabindex="-1" aria-labelledby="agentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agentModalLabel"><i class="fas fa-user-tie"></i> ربط الشحنة بوكيل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="checkbox" id="is_agent_parcel" class="form-check-input" />
                    <label for="is_agent_parcel" class="form-check-label fw-bold">هذه الشحنة تابعة لوكيل</label>
                </div>
                <div id="agent_section" style="display:none;">
                    <div class="box-section">
                        <div class="box-title"><span class="box-icon"><i class="fas fa-user-tie"></i></span> بيانات الوكيل</div>
                        <div class="row g-3 align-items-center mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">حدد الوكيل</label>
                                <input type="text" id="agent_search" class="form-control rounded-pill" placeholder="اكتب اسم الوكيل..." autocomplete="off" />
                                <div id="agent_list" class="list-group position-absolute w-100" style="z-index:999"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">نوع التعامل</label>
                                <select id="agent_direction" class="form-select rounded-pill">
                                    <option value="received">تم استلامها من الوكيل</option>
                                    <option value="delivered">سيتم تسليمها للوكيل</option>
                                </select>
                            </div>
                        </div>
                        <div id="agent_details" class="bg-light rounded-3 p-3 mb-3" style="display:none;">
                            <div><b>نوع التعاون:</b> <span id="agent_coop_type"></span></div>
                            <div><b>العمولة:</b> <span id="agent_commission"></span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary modern-btn" id="agent_apply" data-bs-dismiss="modal">
                    <i class="fas fa-link"></i> ربط الوكيل بالشحنة
                </button>
                <button type="button" class="btn btn-secondary modern-btn" data-bs-dismiss="modal">إلغاء</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
    // وكلاء
    const agents = <?php echo json_encode($agents); ?>;
    const agentSearch = $('#agent_search');
    const agentList = $('#agent_list');
    const agentDetails = $('#agent_details');
    const agentCoopType = $('#agent_coop_type');
    const agentCommission = $('#agent_commission');
    const agentIdInput = $('#agent_id');
    const agentApply = $('#agent_apply');
    const agentDirectionSel = $('#agent_direction');
    const agentDirectionVal = $('#agent_direction_val');
    const agentSummary = $('#agent_summary');
    const agentSummaryName = $('#agent_summary_name');
    const agentSummaryCoop = $('#agent_summary_coop');
    const agentSummaryComm = $('#agent_summary_comm');
    const agentSummaryDir = $('#agent_summary_dir');
    const courierInput = $('#courier_id');

    // منطق إظهار/إخفاء المندوب حسب اتجاه الوكيل
    function toggleCourierFieldByAgent() {
        let agent_id = agentIdInput.val();
        let agent_direction = agentDirectionVal.val();
        // إذا تم اختيار وكيل والاتجاه "سيتم تسليمها للوكيل" → أخفِ المندوب
        if (agent_id && agent_id !== "0" && agent_direction === "delivered") {
            courierInput.val('').prop('disabled', true).closest('.courier-group').hide();
        } else {
            courierInput.prop('disabled', false).closest('.courier-group').show();
        }
    }

    $('#is_agent_parcel').on('change', function() {
        $('#agent_section').toggle(this.checked);
        if(!this.checked) {
            agentIdInput.val('');
            agentDirectionVal.val('');
            agentDetails.hide();
            agentSearch.val('');
            agentSummary.hide();
            courierInput.prop('disabled', false).closest('.courier-group').show();
        }
        toggleCourierFieldByAgent();
    });

    agentSearch.on('input', function() {
        const q = $(this).val().trim().toLowerCase();
        agentList.html("");
        if (q.length < 2) {
            agentList.hide();
            return;
        }
        let found = 0;
        agents.forEach(a => {
            if(a.company_name.toLowerCase().includes(q)) {
                found++;
                let item = $('<div></div>').addClass("list-group-item").text(a.company_name);
                item.on('click', function() {
                    agentSearch.val(a.company_name);
                    agentIdInput.val(a.id);
                    agentCoopType.text((a.coop_type === 'exchange') ? "تبادل شحنات" : (a.coop_type === 'to_them' ? "نرسل لهم الطلبيات" : "يطلبون منا الشحن"));
                    agentCommission.text((a.commission_type === 'percent') ? `${a.commission_value} % لكل شحنة` : `${a.commission_value} جنيه لكل شحنة`);
                    agentDetails.show();
                    agentList.hide();
                    toggleCourierFieldByAgent();
                });
                agentList.append(item);
            }
        });
        agentList.toggle(found > 0);
    });

    agentDirectionSel.on('change', function() {
        agentDirectionVal.val($(this).val());
        toggleCourierFieldByAgent();
    });

    agentApply.on('click', function() {
        if(agentIdInput.val()) {
            agentSummary.show();
            agentSummaryName.text(agentSearch.val());
            agentSummaryCoop.text(agentCoopType.text());
            agentSummaryComm.text(agentCommission.text());
            agentSummaryDir.text(agentDirectionSel.find('option:selected').text());
            agentDirectionVal.val(agentDirectionSel.val());
        } else {
            agentSummary.hide();
        }
        toggleCourierFieldByAgent();
    });

    toggleCourierFieldByAgent();

    // تحميل المناطق عند اختيار المحافظة (للراسل)
    $('#sender-governorate').on('change', function(){
        var gov_id = $(this).val();
        $('#sender-area').html('<option value="">اختر المنطقة</option>');
        if(gov_id){
            $.post('ajax.php?action=get_areas_by_gov', {gov_id: gov_id}, function(resp){
                $('#sender-area').html(resp);
            });
        }
    });

    // تحميل المناطق عند اختيار المحافظة (للمستلم)
    $('#recipient-governorate').on('change', function(){
        var gov_id = $(this).val();
        $('#recipient-area').html('<option value="">اختر المنطقة</option>');
        if(gov_id){
            $.post('ajax.php?action=get_areas_by_gov', {gov_id: gov_id}, function(resp){
                $('#recipient-area').html(resp);
            });
        }
        calculateCosts();
    });

    $('#product-price-input').on('input', calculateCosts);
    $('#recipient-area').on('change', calculateCosts);

    function calculateCosts() {
        let recipient_area_id = $('#recipient-area').val();
        let recipient_governorate_id = $('#recipient-governorate').val();
        let product_price = parseFloat($('#product-price-input').val()) || 0;
        $('#cost-summary').hide();

        if (recipient_area_id) {
            $.post('ajax.php?action=get_shipping_cost', {area_id: recipient_area_id}, function(resp){
                let shipping_cost = parseFloat(resp) || 0;
                updateCostSummary(product_price, shipping_cost);
            });
        } else if (recipient_governorate_id) {
            $.post('ajax.php?action=get_shipping_cost', {gov_id: recipient_governorate_id}, function(resp){
                let shipping_cost = parseFloat(resp) || 0;
                updateCostSummary(product_price, shipping_cost);
            });
        }
    }

    function updateCostSummary(product_price, shipping_cost) {
        let total_cost = product_price + shipping_cost;
        $('#product-cost-value').text(`${product_price.toFixed(2)} جنيه`);
        $('#shipping-cost-value').text(`${shipping_cost.toFixed(2)} جنيه`);
        $('#total-cost-value').text(`${total_cost.toFixed(2)} جنيه`);
        $('#cost-summary').show();
    }

    // إكمال تلقائي للعميل
    $('#sender_name_autocomplete').on('input', function(){
        let name = $(this).val();
        if(name.length < 2) {
            $('#customer_suggestions').hide();
            return;
        }
        $.post('ajax.php?action=search_customers', {query: name}, function(resp){
            let data = [];
            try { data = JSON.parse(resp);} catch(e){}
            let suggestions = '';
            if(data.length > 0){
                suggestions = '<div>';
                data.forEach(function(customer){
                    suggestions += `<div class="autocomplete-suggestion" data-id="${customer.id}" data-name="${customer.name}" data-phone="${customer.phone}" data-email="${customer.email}" data-address="${customer.address}" data-governorate_id="${customer.governorate_id}" data-area_id="${customer.area_id}" data-status="${customer.status}"><b>${customer.name}</b> - ${customer.phone}</div>`;
                });
                suggestions += '</div>';
                $('#customer_suggestions').html(suggestions).show();
            } else {
                $('#customer_suggestions').hide();
            }
        });
    });

    $('#customer_suggestions').on('click', '.autocomplete-suggestion', function(){
        var selected = $(this).data();
        $('[name="sender_name"]').val(selected.name).prop('readonly', true);
        $('[name="sender_phone"]').val(selected.phone).prop('readonly', true);
        $('[name="sender_email"]').val(selected.email).prop('readonly', true);
        $('[name="sender_address"]').val(selected.address).prop('readonly', true);
        $('[name="sender_governorate_id"]').val(selected.governorate_id).prop('disabled', true).trigger('change');
        setTimeout(function(){
            $('[name="sender_area_id"]').val(selected.area_id).prop('disabled', true);
        },300);
        $('[name="sender_status"]').val(selected.status).prop('disabled', true);

        $('.sender_input').addClass('readonly-field');
        $('#customer_suggestions').hide();
        if($('#reset_selected_customer').length == 0){
            $('#sender_name_autocomplete').after('<button type="button" id="reset_selected_customer" class="btn btn-warning btn-sm">تغيير العميل</button>');
        }
    });

    $(document).on('click', '#reset_selected_customer', function(){
        $('[name="sender_name"]').prop('readonly', false).val('');
        $('[name="sender_phone"]').prop('readonly', false).val('');
        $('[name="sender_email"]').prop('readonly', false).val('');
        $('[name="sender_address"]').prop('readonly', false).val('');
        $('[name="sender_governorate_id"]').prop('disabled', false).val('');
        $('[name="sender_area_id"]').prop('disabled', false).val('');
        $('[name="sender_status"]').prop('disabled', false).val('');
        $('.sender_input').removeClass('readonly-field');
        $(this).remove();
    });

    // حفظ الشحنة مع الحساب المالي
    $('#manage-parcel').submit(function(e){
        e.preventDefault();
        $('#msg').html('');
        var formData = $(this).serialize();

        // حساب العمولات
        var price = parseFloat($('#product-price-input').val()) || 0;
        var recipient_area_id = $('#recipient-area').val();
        var agent_id = $('#agent_id').val();
        var courier_id = $('#courier_id').val();

        // حساب عمولة المندوب
        var courier_commission = 0;
        if(recipient_area_id){
            // مثال: دمياط = 25ج، غير ذلك = 30ج
            var areaText = $('#recipient-area option:selected').text();
            if(areaText.includes('دمياط')) {
                courier_commission = 25;
            } else {
                courier_commission = 30;
            }
        }

        // حساب عمولة الوكيل
        var agent_commission = 0;
        if(agent_id){
            var agent = agents.find(a => a.id == agent_id);
            if(agent){
                agent_commission = parseFloat(agent.commission_value) || 0;
            }
        }
        var total_commission = agent_commission + courier_commission;
        var project_profit = price - total_commission;

        // إرسال القيم مع النموذج
        formData +=
            "&courier_commission=" + encodeURIComponent(courier_commission) +
            "&agent_commission=" + encodeURIComponent(agent_commission) +
            "&project_profit=" + encodeURIComponent(project_profit);

        $.ajax({
            url:'ajax.php?action=save_parcel',
            method:'POST',
            data: formData,
            success:function(resp){
                var response = {};
                try { response = JSON.parse(resp); } catch (e) {
                    $('#msg').html('<div class="alert alert-danger">حدث خطأ: ' + resp.trim() + '</div>');
                    return;
                }
                if(response.status == "1"){
                    $('#msg').html("<div class='alert alert-success'>تم حفظ الشحنة بنجاح</div>");
                    setTimeout(function(){
                        location.reload();
                    },1000);
                } else {
                    $('#msg').html("<div class='alert alert-danger'>حدث خطأ: " + response.message + "</div>");
                }
            },
            error: function(xhr, status, error) {
                $('#msg').html("<div class='alert alert-danger'>حدث خطأ في الاتصال بالخادم: " + xhr.status + " " + error + "</div>");
            }
        });
    });
});
</script>
</body>
</html>