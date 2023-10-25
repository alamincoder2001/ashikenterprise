<span id="Edit_emloyee_form">

	<div class="row">
		<!-- PAGE CONTENT BEGINS -->
		<div class="form-horizontal">
			<br />
			<div class="col-sm-6">
				<div class="col-sm-12 align-center"> <strong>Job Information</strong></div>
				<hr />
				<div class="form-group">
					<label class="col-sm-4 control-label" for="Product_id"> Employee ID </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-2">
						<input type="text" name="Employeer_id" id="Employeer_id" name="Employeer_id" value="<?php echo $selected->Employee_ID; ?>" class="form-control" readonly />
						<input name="Employeer_id" type="hidden" id="Employeer_id" class="required" value="<?php echo $selected->Employee_ID; ?>" />
						<input name="iidd" type="hidden" id="iidd" class="required" value="<?php echo $selected->Employee_SlNo; ?>" />
					</div>

					<label class="col-sm-2 control-label" for="bio_id"> Bio ID: </label>
					<div class="col-sm-2">
						<input type="text" name="bio_id" id="bio_id" value="<?php if(isset($selected->bio_id)){ echo $selected->bio_id; } ?>" class="form-control" />
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="em_name"> Employee Name </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<input type="text" id="em_name" name="em_name" value="<?php echo $selected->Employee_Name; ?>" placeholder="Employee Name .." class="form-control" />
						<div id="em_name_" class="col-sm-12"></div>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="em_Designation"> Designation </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<select class="chosen-select form-control" name="em_Designation" id="em_Designation" data-placeholder="Choose a Designation...">
							<option value=""> </option>
							<?php
							$query = $this->db->query("SELECT * FROM tbl_designation where Status='a' order by Designation_Name asc");
							$row = $query->result();
							foreach ($row as $row) { ?>
								<option value="<?php echo $row->Designation_SlNo; ?>" <?php if ($selected->Designation_ID == $row->Designation_SlNo) { ?> selected="selected" <?php } ?>><?php echo $row->Designation_Name; ?></option>
							<?php } ?>
						</select>
						<div id="em_Designation_" class="col-sm-12"></div>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="em_Depertment"> Depertment </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<select class="chosen-select form-control" name="em_Depertment" id="em_Depertment" data-placeholder="Choose a Depertment...">
							<option value=""> </option>
							<?php
							$dquery = $this->db->query("SELECT * FROM tbl_department order by Department_Name asc ");
							$drow = $dquery->result();
							foreach ($drow as $drow) { ?>
								<option value="<?php echo $drow->Department_SlNo; ?>" <?php if ($selected->Department_ID == $drow->Department_SlNo) { ?> selected="selected" <?php } ?>><?php echo $drow->Department_Name; ?></option>
							<?php } ?>
						</select>
						<div id="em_Depertment_" class="col-sm-12"></div>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="em_Joint_date">Joint Date</label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<input class="form-control date-picker" name="em_Joint_date" id="em_Joint_date" type="text" data-date-format="yyyy-mm-dd" style="border-radius: 5px !important;" value="<?php echo $selected->Employee_JoinDate; ?>" />
					</div>
				</div>


				<div class="form-group">
					<label class="col-sm-4 control-label" for="salary_range">Salary Range</label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<input type="text" id="salary_range" name="salary_range" value="<?php echo $selected->salary_range; ?>" placeholder="Salary Range .." class="form-control" />
						<div id="salary_range_" class="col-sm-12"></div>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="form-field-1"> Activation Status</label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<select class="chosen-select form-control" name="status" id="status" data-placeholder="Choose a status...">
							<option value=""> </option>
							<option value="a" <?php if ($selected->status == 'a') { ?> selected="selected" <?php } ?>> Active </option>
							<option value="p" <?php if ($selected->status == 'p') { ?> selected="selected" <?php } ?>> Deactive </option>
						</select>
					</div>
				</div>
			</div>


			<!-- contact information -->
			<div class="col-sm-6">
				<div class="col-sm-12 align-center"> <strong>Contact Information</strong></div>

				<hr />
				<div class="form-group">
					<label class="col-sm-4 control-label" for="em_Present_address"> Present Address </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<input type="text" id="em_Present_address" name="em_Present_address" value="<?php echo $selected->Employee_PrasentAddress; ?>" placeholder="Present Address" class="form-control" />
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="em_Permanent_address"> Permanent Address </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<input type="text" id="em_Permanent_address" name="em_Permanent_address" value="<?php echo $selected->Employee_PermanentAddress; ?>" placeholder="Present Address" class="form-control" />
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="em_contact"> Contact No </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<input type="text" id="em_contact" name="em_contact" value="<?php echo $selected->Employee_ContactNo; ?>" placeholder="Purchase Rate" class="form-control" />
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="ec_email"> E-mail </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<input type="text" id="ec_email" name="ec_email" value="<?php echo $selected->Employee_Email; ?>" placeholder="E-mail" class="form-control" />
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="em_photo"> Employee Image </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<input type="file" id="em_photo" name="em_photo" class="form-control" onchange="readURL(this)" style="height:35px" />
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="em_photo"> Reference </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<textarea id="em_reference" name="em_reference" placeholder="Reference" class="form-control">
							<?php if ($selected->Employee_Reference) {
								echo $selected->Employee_Reference; }  ?>
						</textarea>
					</div>
				</div>

				
			</div>
		</div>

	</div>

	<div class="row">
		<!-- personal info -->
		<div class="form-horizontal">
			<br />
			<div class="col-sm-6">
				<div class="col-sm-12 align-center"> <strong>Personal Information</strong></div>
				<hr />
				<div class="form-group">
					<label class="col-sm-4 control-label" for="form-field-1"> Father's Name </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<input type="text" id="em_father" name="em_father" value="<?php echo $selected->Employee_FatherName; ?>" placeholder="Father's Name" class="form-control" />
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="form-field-1"> Mother's Name </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<input type="text" id="mother_name" name="mother_name" value="<?php echo $selected->Employee_MotherName; ?>" placeholder="Mother's Name" class="form-control" />
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="form-field-1"> Gender </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<select class="chosen-select form-control" name="Gender" id="Gender" data-placeholder="Choose a Gender...">
							<option value=""> </option>
							<option value="Male" <?php if ($selected->Employee_Gender == 'Male') { ?> selected="selected" <?php } ?>>Male</option>
							<option value="Female" <?php if ($selected->Employee_Gender == 'Female') { ?> selected="selected" <?php } ?>>Female</option>
						</select>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="em_dob">Date of Birth</label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<input class="form-control date-picker" name="em_dob" id="em_dob" value="<?php echo $selected->Employee_BirthDate; ?>" type="text" data-date-format="yyyy-mm-dd" style="border-radius: 5px !important;" />
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="Marital"> Marital Status </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<select class="chosen-select form-control" name="Marital" id="Marital" data-placeholder="Choose a Marital Status...">
							<option value=""> </option>
							<option value="married" <?php if ($selected->Employee_MaritalStatus == 'married') { ?> selected="selected" <?php } ?>>Married</option>
							<option value="unmarried" <?php if ($selected->Employee_MaritalStatus == 'unmarried') { ?> selected="selected" <?php } ?>>Unmarried</option>
						</select>
					</div>
				</div>

			</div>

			<!-- preview employee image -->
			<div class="col-sm-1">
				<?php if ($selected->Employee_Pic_thum) { ?>
					<img id="preview" src="<?php echo base_url(); ?>uploads/employeePhoto_thum/<?php echo $selected->Employee_Pic_thum; ?>" style="width:120px;height:80px;margin-top:25px;">
				<?php } else { ?>
					<img id="preview" src="<?php echo base_url(); ?>uploads/no_image.jpg" alt="" style="width:120px;height:80px;margin-top: 25px;">
				<?php } ?>
			</div>

			<!-- work information -->
			<div class="col-sm-5 workInformation">
				<div class="col-sm-12 align-center"> <strong>Work Information</strong></div>
				<hr />
				<div class="form-group">
					<label class="col-sm-4 control-label" for="form-field-1"> Target </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<input type="number" id="em_target" name="target" value="<?php echo $selected->target ?>" placeholder="Target" class="form-control" />
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="form-field-1"> Day </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<select class="chosen-select form-control daySelect" name="day" id="day" data-placeholder="Choose a day...">
							<option value=""> </option>
							<option value="Saturday">Saturday</option>
							<option value="Sunday">Sunday</option>
							<option value="Monday">Monday</option>
							<option value="Tuesday">Tuesday</option>
							<option value="Wednesday">Wednesday</option>
							<option value="Thursday">Thursday</option>
							<option value="Friday">Friday</option>
						</select>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-4 control-label" for="form-field-1"> Area </label>
					<label class="col-sm-1 control-label">:</label>
					<div class="col-sm-6">
						<select class="chosen-select form-control areaSelect" name="area_id" id="area_id" data-placeholder="Choose a area...">
							<option value="0"> </option>
							<?php
							
							$dquery = $this->db->query("SELECT * FROM tbl_district order by District_Name asc ");
							$drow = $dquery->result();
							foreach ($drow as $drow) { ?>
								<option value="<?php echo $drow->District_SlNo; ?>" data-value="<?php echo $drow->District_Name; ?>">
									<?php echo $drow->District_Name; ?>
								</option>
							<?php } ?>
						</select>
					</div>

					<button type="button" class="btn btn-sm btn-success addToCard" style="float: right; height: 25px; width: 83px; margin-right: 53px; padding: 0;">
						Add to cart
					</button>
				
				</div>



				<!-- new work schedule -->
				<div class="form-group">
					<div class="col-sm-3"></div>
					<div class="col-sm-8 table-responsive" style="margin-top: 10px;">
						<table class="table table-bordered">
							<caption style="font-weight: bold;">New Work Schedule</caption>
							<tr class="text-center">
								<th>Sl</th>
								<th>Day</th>
								<th>Area</th>
								<th>Action</th>
							</tr>
							<tbody id="workInfo">

							</tbody>
						</table>
					</div>
					<div class="col-sm-1"></div>
				</div>

				<?php
					$id = $selected->Employee_SlNo;

					$query = $this->db->query("SELECT * FROM tbl_work_schedule where employee_id = '$id'");
					$workSchedule = $query->result();
				?>

				<!-- show existing work schedule -->
				<?php 
				if (isset($workSchedule) &&  count($workSchedule) > 0) { ?>
					<div class="form-group">
						<div class="col-sm-3"></div>
						<div class="col-sm-8 table-responsive" style="margin-top: 10px;">
							<table class="table table-bordered">
								<caption style="font-weight: bold;">Existing Work Schedule</caption>
								<tr class="text-center">
									<th>Sl</th>
									<th>Day</th>
									<th>Area</th>
									<th>Action</th>
								</tr>
								<tbody>

								<?php
									foreach ($workSchedule as $key => $schedule) { ?>
										<tr class="text-center scheduleList_<?php echo $schedule->id; ?>">
											<td><?php echo ++$key; ?></td>
											<td><?php echo $schedule->day; ?></td>

											<?php 
												$query = $this->db->query("SELECT * FROM tbl_district where District_SlNo = '$schedule->area_id'");
												$em_area = $query->result();
												foreach($em_area as $dataArea) {?>
													<td><?php echo $dataArea->District_Name; ?></td>
												<?php } ?>
											<td>
												<a onclick="workInfoDestroy(<?php echo $schedule->id; ?>)" class="text-danger">
													<i class="fa fa-trash"></i>
												</a>
											</td>
										</tr>
								<?php } ?>

								</tbody>
							</table>
						</div>
						<div class="col-sm-1"></div>
					</div>
				<?php } ?>
				

				<div class="form-group">
					<div class="col-sm-3"></div>
					<div class="col-sm-8">
						<button type="button" onclick="Employee_submit()" name="btnSubmit" title="Save" class="btn btn-sm btn-success pull-left" style="float: right !important;">
							Update
							<i class="ace-icon fa fa-arrow-right icon-on-right bigger-110"></i>
						</button>
					</div>
					<div class="col-sm-1"></div>
				</div>
			</div>
			
		</div>
	</div>
</span>


<script type="text/javascript">
	function readURL(input) {
		if (input.files && input.files[0]) {
			var reader = new FileReader();
			reader.onload = function(e) {
				document.getElementById('preview').src = e.target.result;
			}
			reader.readAsDataURL(input.files[0]);
			$("#hideid").hide();
			$("#preview").show();
		}
	}
</script>

<script type="text/javascript">

	var detailArr = [];

	function Employee_submit() {
		var Employeer_id = $("#Employeer_id").val();
		var em_name = $("#em_name").val();
		if (em_name == "") {
			$("#em_name").css('border-color', 'red');
			return false;
		}
		var em_Designation = $("#em_Designation").val();
		if (em_Designation == "") {
			$("#em_Designation").css('border-color', 'red');
			return false;
		}
		var em_Depertment = $("#em_Depertment").val();
		if (em_Depertment == "") {
			$("#em_Depertment").css('border-color', 'red');
			return false;
		}
		var em_Joint_date = $("#em_Joint_date").val();
		if (em_Joint_date == "") {
			$("#em_Joint_date").css('border-color', 'red');
			return false;
		}
		var Gender = $("#Gender").val();
		if (Gender == "") {
			$("#Gender").css('border-color', 'red');
			return false;
		}
		var em_dob = $("#em_dob").val();
		if (em_dob == "") {
			$("#em_dob").css('border-color', 'red');
			return false;
		}
		var Marital = $("#Marital").val();
		if (Marital == "") {
			$("#Marital").css('border-color', 'red');
			return false;
		}
		var em_contact = $("#em_contact").val();
		if (em_contact == "") {
			$("#em_contact").css('border-color', 'red');
			return false;
		}
		var em_Present_address = $("#em_Present_address").val();
		var em_reference = $("#em_reference").val();

		var em_father = $("#em_father").val();
		var mother_name = $("#mother_name").val();

		var em_Permanent_address = $("#em_Permanent_address").val();

		var ec_email = $("#ec_email").val();

		var fd = new FormData();
		fd.append("workscheduledata", JSON.stringify(detailArr));
		fd.append('target', $('#em_target').val());
		fd.append('em_photo', $('#em_photo')[0].files[0]);
		fd.append('Employeer_id', $('#Employeer_id').val());
		fd.append('em_name', $('#em_name').val());
		fd.append('em_Designation', $('#em_Designation').val());
		fd.append('em_Depertment', $('#em_Depertment').val());
		fd.append('em_Joint_date', $('#em_Joint_date').val());
		fd.append('em_father', $('#em_father').val());
		fd.append('mother_name', $('#mother_name').val());
		fd.append('em_Present_address', $('#em_Present_address').val());
		fd.append('em_reference', $('#em_reference').val());
		fd.append('em_Permanent_address', $('#em_Permanent_address').val());
		fd.append('em_dob', $('#em_dob').val());
		fd.append('em_contact', $('#em_contact').val());
		fd.append('Gender', $('#Gender').val());
		fd.append('ec_email', $('#ec_email').val());
		fd.append('Marital', $('#Marital').val());
		fd.append('iidd', $('#iidd').val());
		fd.append('salary_range', $('#salary_range').val());
		fd.append('status', $('#status').val());
		fd.append('bio_id', $('#bio_id').val());

		var eid = $('#iidd').val();
		var x = $.ajax({
			url: "<?php echo base_url(); ?>employeeUpdate",
			type: "POST",
			data: fd,
			enctype: 'multipart/form-data',
			processData: false,
			contentType: false,
			success: function(res) {
				let resp = $.parseJSON(res);
				alert(resp.message);
				if (resp.success) {
					window.location.href = '<?php echo base_url(); ?>employeeEdit/' + eid;
				}
			}
		});
	}


	function listArr() {
		$("#workInfo").html("");

		$.each(detailArr, (index, value) => {
			let workInfo = `<tr class="text-center preDetails_${index + 1}">
							<td>${index + 1}</td>
							<td>${value.day}</td>
							<td>${value.area}</td>
							<td>
								<a onclick="workInfoRemove(${index})" class="text-danger">
									<i class="fa fa-trash"></i>
								</a>
							</td>
							<input type="hidden" class="day" name="day[]" value='${value.day}'>
							<input type="hidden" class="area_id" name="area_id[]" value='${value.area_id}'>
						</tr>`;
			$("#workInfo").append(workInfo);
		})
	}

	// work information
	$(".workInformation .addToCard").on('click', function() {
		// let ws_id = $(".workInformation .ws_id").val();
		let day = $(".workInformation select[name=day] option:selected").val();
		let area = $(".workInformation select[name=area_id] option:selected").attr('data-value');
		let area_id = $(".workInformation select[name=area_id] option:selected").val();

		let detail = {
			'day': day,
			'area': area,
			'area_id': area_id
		};

		detailArr.push(detail);
		listArr();
		// make null these field
		$('.daySelect').val('').trigger('chosen:updated');
		$('.areaSelect').val('').trigger('chosen:updated');
	})

	// remove existing work schedule
	function workInfoRemove(id) {
		detailArr.splice(id, 1);
		listArr();
	}

	// remove existing work schedule
	function workInfoDestroy(id)
	{
		console.log(id);
		if (confirm("Are You Sure Want To Remove This Work Schedule?") == true) {
			
			$.ajax({
				url: "<?php echo base_url(); ?>workScheduleDelete",
				type: "POST",
				data: {id:id},
				success: function(res) {
					let resp = $.parseJSON(res);
					console.log();
					alert(resp.message);
					if (resp.success) {
						$(".scheduleList_"+id).closest("tr").remove();
						// location.reload();
					}
				}
			});
		} else {
			console.log('No');
			// location.reload();
		}
	}
</script>