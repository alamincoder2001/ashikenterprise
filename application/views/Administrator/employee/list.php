	<div class="row">
		<div class="col-xs-12">
			<div class="clearfix">
				<div class="pull-right tableTools-container"></div>
			</div>
			
			<div class="table-header">
				Employee Information
			</div>
		</div>

		<div class="col-xs-12">
				<!-- div.table-responsive -->
				<!-- div.dataTables_borderWrap -->  
				<div class="table-responsive">
					<table id="dynamic-table" class="table table-striped table-bordered table-hover">
						<thead>
							<tr>

								<th>Photo</th>
								<th>Employee ID</th>
								<th>Employee Name</th>
								<th class="hidden-480">Designation</th>
								<th>Contact No</th>
								<th>Status</th>
								<!--<th class="hidden-480">Purchase Rate</th>
								<th class="hidden-480">Sell Rate</th>--->

								<th>Action</th>
							</tr>
						</thead>

						<tbody>
						<?php 
						if(isset($employes) && $employes){
						//echo "<pre>";print_r($row);exit;
						foreach($employes as $row){
						?>
							<tr>
								<td>
									<?php if($row->Employee_Pic_thum){ ?>
									<img src="<?php echo base_url().'uploads/employeePhoto_thum/'.$row->Employee_Pic_thum; ?>" alt="" style="width:45px;height:45px;"></td>
									<?php }else{ ?>
									<img src="<?php echo base_url().'uploads/no_image.jpg' ?>" alt="" style="width:45px;height:45px;"></td>
									<?php } ?>
								</td>
								<td><?php echo $row->Employee_ID; ?></td>
								<td class="hidden-480"><?php echo $row->Employee_Name; ?></td>
								<td><?php echo $row->Designation_Name; ?></td>

								<td class="hidden-480"><?php echo $row->Employee_ContactNo; ?></td>
								<td class="hidden-480"><?php if($row->status=='a'){ echo '<strong class="text-success">Active</strong>'; }else if($row->status=='p'){ echo '<strong class="text-danger">Deactive</strong>'; } ?></td>

								<td>
									<div class="hidden-sm hidden-xs action-buttons">

										<?php if($this->session->userdata('accountType') != 'u'){?>

										<a class="blue" href="<?php echo base_url(); ?>employeeEdit/<?php echo $row->Employee_SlNo; ?>" style="cursor:pointer;">
											<i class="ace-icon fa fa-pencil bigger-130"></i> <?php //echo $row->Status; ?>
										</a>

										<a class="blue" onclick="employeeView(<?php echo $row->Employee_SlNo; ?>)" href="javascript:void(0)" style="cursor:pointer;">
											<i class="fa fa-eye bigger-130"></i>
										</a>
										
										<span onclick="deleted(<?php echo $row->Employee_SlNo; ?>)" style="cursor:pointer;color:red;font-size:20px;margin-right:20px;">
											<i class="fa fa-trash-o"></i>
										</span>
										<?php }?>
										
									</div>
								</td>
							</tr>
							
						<?php
							} }
						?>
							</tbody>
						</table>
					</div>
	</div><!-- /.col -->
</div><!-- /.row -->


<!-- end modal -->

<div class="modal fade" id="workSchedule" role="dialog">
    <div class="modal-dialog">
    
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Employee Work Schedule</h4>
        </div>
        <div class="modal-body">

			<div class="form-group">
				
				<div class="table-responsive">
					<table class="table table-bordered">
						<tr class="text-center">
							<th>Sl</th>
							<th>Day</th>
							<th>Area</th>
						</tr>
						<tbody class="sheduleData">

						</tbody>
					</table>
				</div>
				
			</div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
        </div>
      </div>
      
    </div>
  </div>


<!-- modal -->

<script type="text/javascript">

	function employeeView(id)
	{
		$.ajax({
            type: "POST",
            url: '<?php echo base_url();?>employeeView',
            data: {id:id},
            success:function(res){
				$("#workSchedule .sheduleData").html(res);
				$("#workSchedule").modal('show');
            }
        });
	} 


    function deleted(id){
        var deletedd= id;
        var inputdata = 'deleted='+deletedd;
		if (confirm("Are you sure, You want to delete this?")) {
        var urldata = "<?php echo base_url();?>employeeDelete";
        $.ajax({
            type: "POST",
            url: urldata,
            data: inputdata,
            success:function(data){
                alert("Delete Success");
				location.reload();
            }
        });
		}
    }
</script>


<script type="text/javascript">
    function active(id){
        var deletedd= id;
        var inputdata = 'deleted='+deletedd;
        if (confirm("Are you sure, You want to active this?")) {
        var urldata = "<?php echo base_url();?>employeeActive";
        $.ajax({
            type: "POST",
            url: urldata,
            data: inputdata,
            success:function(data){
                //$("#saveResult").html(data);
                alert("Delete Success");
				location.reload();
            }
        });
		}
    }
</script>

