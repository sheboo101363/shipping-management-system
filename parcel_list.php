<?php 
include 'db_connect.php';

// حذف كل الشحنات
if (isset($_POST['delete_all_parcels'])) {
	mysqli_query($conn, "DELETE FROM parcels");
	echo '<div class="alert alert-success text-end">تم حذف جميع الشحنات بنجاح.</div>';
}

// حذف جميع الأرصدة (تصفير is_paid)
if (isset($_POST['reset_all_paid'])) {
	mysqli_query($conn, "UPDATE parcels SET is_paid=0, paid_at=NULL");
	echo '<div class="alert alert-success text-end">تم تصفير جميع الأرصدة بنجاح. كل الشحنات أصبحت غير مدفوعة.</div>';
}
?>
<div class="col-lg-12">
	<div class="card card-outline card-primary">
		<div class="card-header d-flex justify-content-between align-items-center">
			<div>
				<a class="btn btn-sm btn-success" href="./index.php?page=new_parcel"><i class="fa fa-plus"></i> إضافة شحنة جديدة</a>
			</div>
			<form method="post" class="d-inline-block m-0">
				<button name="delete_all_parcels" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف جميع الشحنات؟ هذا الإجراء لا يمكن التراجع عنه!')">
					<i class="fa fa-trash"></i> حذف جميع الشحنات
				</button>
			</form>
			<form method="post" class="d-inline-block m-0">
				<button name="reset_all_paid" class="btn btn-warning btn-sm" onclick="return confirm('هل أنت متأكد من تصفير جميع الأرصدة؟ سيظهر كل الشحنات كأنها غير مدفوعة!')">
					<i class="fa fa-redo"></i> تصفير كل الأرصدة
				</button>
			</form>
		</div>
		<div class="card-body">
			<table class="table tabe-hover table-bordered" id="list">
				<thead>
					<tr>
						<th class="text-center">#</th>
						<th>رقم الشحنة</th>
						<th>اسم المرسل</th>
						<th>اسم المستلم</th>
						<th>المندوب</th>
						<th>منطقة المستلم</th>
						<th>الحالة</th>
						<th>مدفوع؟</th>
						<th>أدوات</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$i = 1;
					$where = "";
					if(isset($_GET['s'])){
						$where = " where p.status = ".intval($_GET['s'])." ";
					}
					if($_SESSION['login_type'] != 1 ){
						if(empty($where))
							$where = " where ";
						else
							$where .= " and ";
						$where .= " (p.from_branch_id = {$_SESSION['login_branch_id']} or p.to_branch_id = {$_SESSION['login_branch_id']}) ";
					}
					$qry = $conn->query("SELECT p.*, c.name as courier_name, ar.name as area_name, g.name as gov_name 
						FROM parcels p 
						LEFT JOIN couriers c ON p.courier_id = c.id 
						LEFT JOIN areas ar ON p.recipient_area_id = ar.id
						LEFT JOIN governorates g ON p.recipient_governorate_id = g.id
						$where
						ORDER BY unix_timestamp(p.date_created) DESC");
					$status_arr = array(
						0 => "تم قبول الطرد من المندوب",
						1 => "تم استلام الطرد",
						2 => "تم الشحن",
						3 => "قيد النقل",
						4 => "وصل للوجهة",
						5 => "خرج للتسليم",
						6 => "جاهز للاستلام",
						7 => "تم التسليم",
						8 => "تم الاستلام",
						9 => "محاولة تسليم غير ناجحة"
					);
					while($row= $qry->fetch_assoc()):
					?>
					<tr>
						<td class="text-center"><?php echo $i++; ?></td>
						<td><b><?php echo htmlspecialchars($row['reference_number']); ?></b></td>
						<td><b><?php echo htmlspecialchars($row['sender_name']); ?></b></td>
						<td><b><?php echo htmlspecialchars($row['recipient_name']); ?></b></td>
						<td><b><?php echo $row['courier_name'] ? htmlspecialchars($row['courier_name']) : '<span class="text-danger">غير معين</span>'; ?></b></td>
						<td>
							<?php 
								echo htmlspecialchars($row['area_name']);
								if($row['gov_name']) echo ' <span class="text-muted">(' . htmlspecialchars($row['gov_name']) . ')</span>';
							?>
						</td>
						<td class="text-center">
							<?php 
							$status_val = isset($row['status']) ? intval($row['status']) : 0;
							switch ($status_val) {
								case 1:
									echo "<span class='badge badge-pill badge-info'>".$status_arr[$status_val]."</span>";
									break;
								case 2:
									echo "<span class='badge badge-pill badge-info'>".$status_arr[$status_val]."</span>";
									break;
								case 3:
								case 4:
								case 5:
								case 6:
									echo "<span class='badge badge-pill badge-primary'>".$status_arr[$status_val]."</span>";
									break;
								case 7:
								case 8:
									echo "<span class='badge badge-pill badge-success'>".$status_arr[$status_val]."</span>";
									break;
								case 9:
									echo "<span class='badge badge-pill badge-danger'>".$status_arr[$status_val]."</span>";
									break;
								default:
									echo "<span class='badge badge-pill badge-info'>".$status_arr[0]."</span>";
									break;
							}
							?>
						</td>
						<td class="text-center">
							<?php
								if ($row['is_paid'] == 1) {
									echo '<span class="badge badge-success">نعم</span>';
								} else {
									echo '<span class="badge badge-danger">لا</span>';
								}
							?>
						</td>
						<td class="text-center">
		                    <div class="btn-group">
		                    	<button type="button" class="btn btn-info btn-flat view_parcel" data-id="<?php echo $row['id'] ?>">
		                          <i class="fas fa-eye"></i>
		                        </button>
		                        <a href="index.php?page=edit_parcel&id=<?php echo $row['id'] ?>" class="btn btn-primary btn-flat ">
		                          <i class="fas fa-edit"></i>
		                        </a>
		                        <button type="button" class="btn btn-danger btn-flat delete_parcel" data-id="<?php echo $row['id'] ?>">
		                          <i class="fas fa-trash"></i>
		                        </button>
	                      </div>
						</td>
					</tr>	
				<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<style>
	table td{
		vertical-align: middle !important;
	}
</style>
<script>
	$(document).ready(function(){
		$('#list').dataTable();
		$('.view_parcel').click(function(){
			uni_modal("تفاصيل الشحنة","view_parcel.php?id="+$(this).attr('data-id'),"large");
		});
		$('.delete_parcel').click(function(){
			_conf("هل أنت متأكد من حذف هذه الشحنة؟","delete_parcel",[$(this).attr('data-id')]);
		});
	});
	function delete_parcel($id){
		start_load()
		$.ajax({
			url:'ajax.php?action=delete_parcel',
			method:'POST',
			data:{id:$id},
			success:function(resp){
				if(resp==1){
					alert_toast("تم حذف الشحنة بنجاح",'success')
					setTimeout(function(){
						location.reload()
					},1500)
				}
			}
		})
	}
</script>