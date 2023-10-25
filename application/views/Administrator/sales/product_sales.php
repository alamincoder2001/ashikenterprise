<style>
	.v-select {
		margin-bottom: 5px;
	}

	.v-select .dropdown-toggle {
		padding: 0px;
	}

	.v-select input[type=search],
	.v-select input[type=search]:focus {
		margin: 0px;
	}

	.v-select .vs__selected-options {
		overflow: hidden;
		flex-wrap: nowrap;
	}

	.v-select .selected-tag {
		margin: 2px 0px;
		white-space: nowrap;
		position: absolute;
		left: 0px;
	}

	.v-select .vs__actions {
		margin-top: -5px;
	}

	.v-select .dropdown-menu {
		width: auto;
		overflow-y: auto;
	}

	.v-select .dropdown-toggle {
		background: #fff;
	}

	#branchDropdown .vs__actions button {
		display: none;
	}

	#branchDropdown .vs__actions .open-indicator {
		height: 15px;
		margin-top: 7px;
	}

	.btn-return {
		padding: 2px 7px;
		background: #B74635 !important;
		border: none !important;
		border-radius: 3px;
		float: right;
	}

	.advance_heading,
	.renter_heading {
		background: #DDDDDD;
		padding: 5px;
		font-size: 14px;
		color: #323A89;
	}

	.information {
		border: 1px solid #89AED8;
		/* background-color: #EEEEEE; */
		border-radius: 3px;
		margin: 7px 0px;
	}

	.return-form,
	.damage-form {
		/* border: 1px solid #89AED8; */
		padding: 5px;
		background-color: #EEEEEE;
	}
</style>

<div id="sales" class="row">
	<div class="col-xs-12 col-md-12 col-lg-12" style="border-bottom:1px #ccc solid;margin-bottom:5px;">
		<div class="row">
			<div class="form-group">
				<label class="col-md-1 col-xs-4 control-label no-padding-right"> Invoice no </label>
				<div class="col-md-2 col-xs-8">
					<input type="text" id="invoiceNo" class="form-control" v-model="sales.invoiceNo" readonly />
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-1 col-xs-4 control-label no-padding-right"> Sales By </label>
				<div class="col-md-2 col-xs-8">
					<v-select v-bind:options="employees" v-model="selectedEmployee" label="Employee_Name" @input="changeEmployee" placeholder="Select Employee"></v-select>
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-1 col-xs-4 control-label no-padding-right"> Sales From </label>
				<div class="col-md-2 col-xs-8">
					<v-select id="branchDropdown" v-bind:options="branches" label="Brunch_name" v-model="selectedBranch" disabled></v-select>
				</div>
			</div>

			<div class="form-group">
				<div class="col-md-3 col-xs-12">
					<input class="form-control" id="salesDate" type="date" v-model="sales.salesDate" v-bind:disabled="userType == 'u' ? true : false" />
				</div>
			</div>
		</div>
	</div>


	<div class="col-xs-12 col-md-9 col-lg-9">
		<div class="widget-box">
			<div class="widget-header">
				<h4 class="widget-title">Order Information</h4>
				<div class="widget-toolbar">
					<a href="#" data-action="collapse">
						<i class="ace-icon fa fa-chevron-up"></i>
					</a>

					<a href="#" data-action="close">
						<i class="ace-icon fa fa-times"></i>
					</a>
				</div>
			</div>

			<div class="widget-body">
				<div class="widget-main">

					<div class="row">
						<div class="col-md-5 col-xs-12">
							<div class="form-group clearfix" style="margin-bottom: 8px;">
								<label class="col-xs-4 control-label no-padding-right"> Order Type </label>
								<div class="col-xs-8">
									<input type="radio" name="salesType" value="retail" v-model="sales.salesType" v-on:change="onSalesTypeChange"> Retail &nbsp;
									<input type="radio" name="salesType" value="wholesale" v-model="sales.salesType" v-on:change="onSalesTypeChange"> Wholesale
								</div>
							</div>
							<div class="form-group">
								<label class="col-xs-4 control-label no-padding-right"> Area </label>
								<div class="col-xs-7">
									<v-select v-bind:options="areas" label="District_Name" v-model="selectedArea"></v-select>
								</div>
								<div class="col-xs-1" style="padding: 0;">
									<a href="<?= base_url('customer') ?>" class="btn btn-xs btn-danger" style="height: 25px; border: 0; width: 27px; margin-left: -10px;" target="_blank" title="Add New Customer"><i class="fa fa-plus" aria-hidden="true" style="margin-top: 5px;"></i></a>
								</div>
							</div>
							<div class="form-group">
								<label class="col-xs-4 control-label no-padding-right"> Customer </label>
								<div class="col-xs-7">
									<v-select v-bind:options="filterCustomers" label="display_name" v-model="selectedCustomer" v-on:input="customerOnChange"></v-select>
								</div>
								<div class="col-xs-1" style="padding: 0;">
									<a href="<?= base_url('customer') ?>" class="btn btn-xs btn-danger" style="height: 25px; border: 0; width: 27px; margin-left: -10px;" target="_blank" title="Add New Customer"><i class="fa fa-plus" aria-hidden="true" style="margin-top: 5px;"></i></a>
								</div>
							</div>

							<div class="form-group" style="display:none;" v-bind:style="{display: selectedCustomer.Customer_Type == 'G' || selectedCustomer.Customer_Type == 'N' ? '' : 'none'}">
								<label class="col-xs-4 control-label no-padding-right"> Name </label>
								<div class="col-xs-8">
									<input type="text" id="customerName" placeholder="Customer Name" class="form-control" v-model="selectedCustomer.Customer_Name" v-bind:disabled="selectedCustomer.Customer_Type == 'G' || selectedCustomer.Customer_Type == 'N' ? false : true" />
								</div>
							</div>

							<div class="form-group">
								<label class="col-xs-4 control-label no-padding-right"> Mobile No </label>
								<div class="col-xs-8">
									<input type="text" id="mobileNo" placeholder="Mobile No" class="form-control" v-model="selectedCustomer.Customer_Mobile" v-bind:disabled="selectedCustomer.Customer_Type == 'G' || selectedCustomer.Customer_Type == 'N' ? false : true" />
								</div>
							</div>

							<div class="form-group">
								<label class="col-xs-4 control-label no-padding-right"> Address </label>
								<div class="col-xs-8">
									<textarea id="address" placeholder="Address" class="form-control" v-model="selectedCustomer.Customer_Address" v-bind:disabled="selectedCustomer.Customer_Type == 'G' || selectedCustomer.Customer_Type == 'N' ? false : true"></textarea>
								</div>
							</div>
						</div>

						<div class="col-md-5 col-xs-12">
							<form v-on:submit.prevent="addToCart">
								<div class="form-group">
									<label class="col-xs-3 control-label no-padding-right"> Product </label>
									<div class="col-xs-8">
										<v-select v-bind:options="products" v-model="selectedProduct" label="display_text" v-on:input="productOnChange"></v-select>
									</div>
									<div class="col-xs-1" style="padding: 0;">
										<a href="<?= base_url('product') ?>" class="btn btn-xs btn-danger" style="height: 25px; border: 0; width: 27px; margin-left: -10px;" target="_blank" title="Add New Product"><i class="fa fa-plus" aria-hidden="true" style="margin-top: 5px;"></i></a>
									</div>
								</div>

								<div class="form-group" style="display: none;" :style="{ display: selectedProduct.imageUrl != '' ? '' : 'none' }">
									<label class="col-xs-3 control-label no-padding-right"> </label>
									<div class="col-xs-9">
										<img :src="selectedProduct.imageUrl != null ? selectedProduct.imageUrl : '/assets/no_image.gif'" style="height: 100px; width:150px; margin-bottom: 5px;" alt="">
									</div>
								</div>

								<div class="form-group" style="display: none;">
									<label class="col-xs-3 control-label no-padding-right"> Brand </label>
									<div class="col-xs-9">
										<input type="text" id="brand" placeholder="Group" class="form-control" />
									</div>
								</div>

								<div class="form-group">
									<label class="col-xs-3 control-label no-padding-right"> Sale Rate </label>
									<div class="col-xs-9">
										<input type="number" id="salesRate" placeholder="Rate" step="0.01" class="form-control" v-model="selectedProduct.Product_SellingPrice" v-on:input="productTotal" />
									</div>
								</div>

								<div class="form-group">
									<label class="col-xs-3 control-label no-padding-right"> Box Qty. </label>
									<div class="col-xs-4">
										<input type="number" step="0.01" id="quantity" min="0" placeholder="Qty" class="form-control" ref="quantity" v-model="selectedProduct.boxQty" v-on:input="productTotal" autocomplete="off" />
									</div>
									<div class="col-xs-1">Pcs</div>
									<div class="col-xs-4">
										<input type="number" class="form-control" min="0" v-model="selectedProduct.pcs" v-on:input="productTotal">
									</div>
								</div>

								<div class="form-group">
									<label class="col-xs-3 control-label no-padding-right"> Discount</label>
									<div class="col-xs-5" style="display: flex;">
										<input type="number" step="0.01" min="0" id="discount" placeholder="Discount" class="form-control" ref="discount" v-model="selectedProduct.discount" v-on:input="productTotal" autocomplete="off" style="width: 80%;" />
										<button type="button" style="width: 20%;padding: 2px 2px;height: 25px;border:0;">%</button>
									</div>
									<div class="col-xs-4 no-padding-left">
										<input type="text" id="discountAmount" v-model="selectedProduct.discountAmount" v-on:input="productTotal" placeholder="Amount" class="form-control" :readonly="selectedProduct.quantity == undefined || selectedProduct.quantity == 0 ? true : false"/>
									</div>
								</div>

								<div class="form-group" style="display:none;">
									<label class="col-xs-3 control-label no-padding-right"> Discount</label>
									<div class="col-xs-9">
										<span>(%)</span>
										<input type="text" id="productDiscount" placeholder="Discount" class="form-control" style="display: inline-block; width: 90%" />
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-3 control-label no-padding-right"> Amount </label>
									<div class="col-xs-9">
										<input type="text" id="productTotal" placeholder="Amount" class="form-control" v-model="selectedProduct.total" readonly />
									</div>
								</div>
								<div class="form-group">
									<div class="col-xs-3" style="text-align: end;padding-right:0;">
										<input type="checkbox" id="is_free" v-model="selectedProduct.is_free" true-value="true" false-value="false" style="width:15px;height:15px;" @change="freeProductChange" />
									</div>
									<label class="col-xs-9 control-label no-padding-right" for="is_free"> Free Product </label>
								</div>

								<div class="form-group">
									<label class="col-xs-3 control-label no-padding-right"> </label>
									<div class="col-xs-9">
										<button type="submit" class="btn btn-default pull-right">Add to Cart</button>
									</div>
								</div>
							</form>

						</div>
						<div class="col-md-2 col-xs-12">
							<div style="display:none;" v-bind:style="{display:sales.isService == 'true' ? 'none' : ''}">
								<div class="text-center" style="display:none;" v-bind:style="{color: productStock > 0 ? 'green' : 'red', display: selectedProduct.Product_SlNo == '' ? 'none' : ''}">{{ productStockText }}</div class="text-center">

								<input type="text" id="productStock" v-model="productStock" readonly style="border:none;font-size:20px;width:100%;text-align:center;color:green"><br>
								<input type="text" id="stockUnit" v-model="selectedProduct.Unit_Name" readonly style="border:none;font-size:12px;width:100%;text-align: center;"><br><br>
							</div>
							<input type="password" ref="productPurchaseRate" v-model="selectedProduct.Product_Purchase_Rate" v-on:mousedown="toggleProductPurchaseRate" v-on:mouseup="toggleProductPurchaseRate" readonly title="Purchase rate (click & hold)" style="font-size:12px;width:100%;text-align: center;">

						</div>
					</div>
				</div>
			</div>
		</div>


		<div class="col-xs-12 col-md-12 col-lg-12" style="padding-left: 0px;padding-right: 0px;">
			<div class="table-responsive">
				<table class="table table-bordered" style="color:#000;margin-bottom: 5px;">
					<thead>
						<tr>
							<th style="width:5%;color:#000;">Sl</th>
							<th style="width:15%;color:#000;">Product Code</th>
							<th style="width:20%;color:#000;">Product Name</th>
							<th style="width:8%;color:#000;">Box</th>
							<th style="width:8%;color:#000;">Pcs</th>
							<th style="width:9%;color:#000;">T.Qty</th>
							<th style="width:10%;color:#000;">Rate</th>
							<th style="width:7%;color:#000;">Discount(%)</th>
							<th style="width:15%;color:#000;">Total</th>
							<th style="width:10%;color:#000;">Action</th>
						</tr>
					</thead>
					<tbody style="display:none;" v-bind:style="{display: cart.length > 0 ? '' : 'none'}">
						<tr v-for="(product, sl) in cart"  :style="{background: product.is_free == 'true'?'#ffd17e':''}" :title="product.is_free == 'false'?'':'Free Product'">
							<td>{{ sl + 1 }}</td>
							<td>{{ product.productCode }}</td>
							<td>{{ product.name }}</td>
							<td>{{ product.boxQty ?? 0 }}</td>
							<td>{{ product.pcs ?? 0 }}</td>
							<td><input style="width: 60px; height:20px; text-align:center;" type="number" v-model="product.quantity" :readonly="sales.salesId != 0" v-on:input="calculateProductQty(sl)"></td>
							<td>{{ product.salesRate }}</td>
							<td>{{ product.discount }}</td>
							<td>{{ product.total }}</td>
							<td><a href="" v-on:click.prevent="removeFromCart(sl)"><i class="fa fa-trash"></i></a></td>
						</tr>

						<tr>
							<td colspan="10"></td>
						</tr>

						<tr style="font-weight: bold;">
							<td colspan="7">Note</td>
							<td colspan="3">Total</td>
						</tr>

						<tr>
							<td colspan="7"><textarea style="width: 100%;font-size:13px;" placeholder="Note" v-model="sales.note"></textarea></td>
							<td colspan="3" style="padding-top: 15px;font-size:18px;">{{ sales.total }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<div class="col-xs-12 col-md-12 col-lg-12">
			<div class="row">
				<div class="col-xs-12 no-padding">
					<div class="information">
						<div class="advance_heading">
							<strong>Damage Entry</strong>
						</div>
						<div class="damage-form">
							<form v-on:submit.prevent="addDamageCart">
								<div class="form-group row">
									<label class="col-xs-2" for="">Product</label>
									<div class="col-xs-5">
										<v-select v-bind:options="damageProducts" v-model="damageProduct" label="display_text"></v-select>
									</div>
									<label class="col-xs-2" for="">Quantity</label>
									<div class="col-xs-3 no-padding-left">
										<input type="number" step="0.00" v-model="damageProduct.quantity" class="form-control" v-on:input="productDamageTotal" required>
									</div>
								</div>
								<div class="form-group row">
									<label class="col-xs-2" for="">Total</label>
									<div class="col-xs-5">
										<input type="text" v-model="damageProduct.total" class="form-control" readonly>
									</div>
									<div class="col-xs-5">
										<button type="submit" class="btn btn-return">Add to cart</button>
									</div>
								</div>
							</form>
						</div>
						<div class="table-responsive" style="padding: 5px 3px 0px 3px;">
							<table class="table table-bordered">
								<thead>
									<tr>
										<th>Sl</th>
										<th>Product</th>
										<th>Quantity</th>
										<th>Total</th>
										<th>Actioin</th>
									</tr>
								</thead>
								<tbody>
									<tr v-for="(damage, sl) in damagecart">
										<td>{{ sl + 1 }}</td>
										<td>{{ damage.name }}</td>
										<td>{{ damage.quantity }}</td>
										<td>{{ damage.total }}</td>
										<td><a href="" v-on:click.prevent="removeFromDamageCart(sl)"><i class="fa fa-trash"></i></a></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>


	<div class="col-xs-12 col-md-3 col-lg-3">
		<div class="widget-box">
			<div class="widget-header">
				<h4 class="widget-title">Amount Details</h4>
				<div class="widget-toolbar">
					<a href="#" data-action="collapse">
						<i class="ace-icon fa fa-chevron-up"></i>
					</a>

					<a href="#" data-action="close">
						<i class="ace-icon fa fa-times"></i>
					</a>
				</div>
			</div>

			<div class="widget-body">
				<div class="widget-main">
					<div class="row">
						<div class="col-xs-12">
							<div class="table-responsive">
								<table style="color:#000;margin-bottom: 0px;border-collapse: collapse;">
									<tr>
										<td>
											<div class="form-group">
												<label class="col-xs-12 control-label no-padding-right">Sub Total</label>
												<div class="col-xs-12">
													<input type="number" id="subTotal" class="form-control" v-model="sales.subTotal" readonly v-on:input="calculateTotal" />
												</div>
											</div>
										</td>
									</tr>
									<!-- <tr :style="{display: sales.salesId == 0 ? 'none' : ''}">
										<td>
											<div class="form-group">
												<label class="col-xs-12 control-label no-padding-right">Return Total</label>
												<div class="col-xs-12">
													<input type="number" id="returnTotal" class="form-control" v-model="sales.returnTotal" readonly />
												</div>
											</div>
										</td>
									</tr> -->
									<tr>
										<td>
											<div class="form-group">
												<label class="col-xs-12 control-label no-padding-right">Damage Total</label>
												<div class="col-xs-12">
													<input type="number" id="damageTotal" class="form-control" v-model="sales.damageTotal" readonly />
												</div>
											</div>
										</td>
									</tr>

									<tr>
										<td>
											<div class="form-group">
												<label class="col-xs-12 control-label no-padding-right"> Vat </label>
												<div class="col-xs-12">
													<input type="number" id="vat" readonly="" class="form-control" v-model="sales.vat" />
												</div>
											</div>
										</td>
									</tr>

									<tr>
										<td>
											<div class="form-group">
												<label class="col-xs-12 control-label no-padding-right">Discount Persent</label>

												<div class="col-xs-4">
													<input type="number" id="discountPercent" class="form-control" v-model="discountPercent" v-on:input="calculateTotal" />
												</div>

												<label class="col-xs-1 control-label no-padding-right">%</label>

												<div class="col-xs-7">
													<input type="number" id="discount" class="form-control" v-model="sales.discount" v-on:input="calculateTotal" />
												</div>

											</div>
										</td>
									</tr>

									<tr>
										<td>
											<div class="form-group">
												<label class="col-xs-12 control-label no-padding-right">Transport Cost</label>
												<div class="col-xs-12">
													<input type="number" class="form-control" v-model="sales.transportCost" v-on:input="calculateTotal" />
												</div>
											</div>
										</td>
									</tr>

									<tr style="display:none;">
										<td>
											<div class="form-group">
												<label class="col-xs-12 control-label no-padding-right">Round Of</label>
												<div class="col-xs-12">
													<input type="number" id="roundOf" class="form-control" />
												</div>
											</div>
										</td>
									</tr>

									<tr>
										<td>
											<div class="form-group">
												<label class="col-xs-12 control-label no-padding-right">Total</label>
												<div class="col-xs-12">
													<input type="number" id="total" class="form-control" v-model="sales.total" readonly />
												</div>
											</div>
										</td>
									</tr>

									<tr>
										<td>
											<div class="form-group">
												<label class="col-xs-12 control-label no-padding-right">Payment Type</label>
												<div class="col-xs-12">
													<select style="padding: 0px 6px; border-radius: 3px;" id="payment-type" v-model="sales.paymentType" class="form-control" v-on:input="onChangePaymentType">
														<option value="Cash">Cash</option>
														<option value="Bank">Bank</option>
													</select>
												</div>
											</div>
										</td>
									</tr>

									<tr>
										<td>
											<div class="form-group" style="display:none;" v-bind:style="{display: sales.paymentType == 'Bank' ? '' : 'none'}">
												<label class="col-md-12 control-label">Bank Account</label>
												<div class="col-md-12">
													<v-select v-bind:options="filteredAccounts" v-model="selectedAccount" label="display_text" placeholder="Select account"></v-select>
												</div>
											</div>
										</td>
									</tr>

									<tr>
										<td>
											<div class="form-group">
												<label class="col-xs-12 control-label no-padding-right">Paid</label>
												<div class="col-xs-12">
													<input type="number" id="paid" class="form-control" v-model="sales.paid" v-on:input="calculateTotal" v-bind:disabled="selectedCustomer.Customer_Type == 'G' ? true : false" />
												</div>
											</div>
										</td>
									</tr>

									<tr>
										<td>
											<div class="form-group">
												<label class="col-xs-12 control-label">Due</label>
												<div class="col-xs-6">
													<input type="number" id="due" class="form-control" v-model="sales.due" readonly />
												</div>
												<div class="col-xs-6">
													<input type="number" id="previousDue" class="form-control" v-model="sales.previousDue" readonly style="color:red;" />
												</div>
											</div>
										</td>
									</tr>

									<tr>
										<td>
											<div class="form-group">
												<div class="col-xs-6">
													<input type="button" class="btn btn-default btn-sm" value="Sales" v-on:click="saveSales" v-bind:disabled="saleOnProgress ? true : false" style="color: black!important;margin-top: 0px;width:100%;padding:5px;font-weight:bold;">
												</div>
												<div class="col-xs-6">
													<a class="btn btn-info btn-sm" v-bind:href="`/sales/${sales.isService == 'true' ? 'service' : 'product'}`" style="color: black!important;margin-top: 0px;width:100%;padding:5px;font-weight:bold;">New Sales</a>
												</div>
											</div>
										</td>
									</tr>

								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script src="<?php echo base_url(); ?>assets/js/vue/vue.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/vue/axios.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/vue/vue-select.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/moment.min.js"></script>

<script>
	Vue.component('v-select', VueSelect.VueSelect);
	new Vue({
		el: '#sales',
		data() {
			return {
				sales: {
					salesId: parseInt('<?php echo $salesId; ?>'),
					invoiceNo: '<?php echo $invoice; ?>',
					salesBy: '<?php echo $this->session->userdata("FullName"); ?>',
					salesType: 'retail',
					paymentType: 'Cash',
					account_id: null,
					salesFrom: '',
					salesDate: '',
					customerId: '',
					employeeId: null,
					subTotal: 0.00,
					returnTotal: 0.00,
					damageTotal: 0.00,
					discount: 0.00,
					vat: 0.00,
					transportCost: 0.00,
					total: 0.00,
					paid: 0.00,
					previousDue: 0.00,
					due: 0.00,
					isService: '<?php echo $isService; ?>',
					note: ''
				},
				vatPercent: 0,
				discountPercent: 0,
				cart: [],
				returncart: [],
				damagecart: [],
				employees: [],
				selectedEmployee: null,
				branches: [],
				selectedBranch: {
					brunch_id: "<?php echo $this->session->userdata('BRANCHid'); ?>",
					Brunch_name: "<?php echo $this->session->userdata('Brunch_name'); ?>"
				},
				areas: [],
				selectedArea: {
					District_SlNo: null,
					District_Name: 'select area'
				},
				customers: [],
				filterCustomers: [],
				selectedCustomer: {
					Customer_SlNo: '',
					Customer_Code: '',
					Customer_Name: '',
					display_name: 'Select Customer',
					Customer_Mobile: '',
					Customer_Address: '',
					Customer_Type: ''
				},
				oldCustomerId: null,
				oldPreviousDue: 0,
				products: [],
				selectedProduct: {
					Product_SlNo: '',
					display_text: 'Select Product',
					Product_Name: '',
					imageUrl: '',
					Unit_Name: '',
					quantity: 0,
					boxQty: 0,
					pcs: 0,
					Product_Purchase_Rate: '',
					Product_SellingPrice: 0.00,
					vat: 0.00,
					total: 0.00,
					is_free: 'false'
				},
				returnProducts: [],
				damageProducts: [],
				returnProduct: {
					Product_SlNo: '',
					display_text: 'Select Product',
					Product_Name: '',
					Unit_Name: '',
					Product_Purchase_Rate: '',
					Product_SellingPrice: 0.00,
					quantity: 0,
					total: 0.00
				},
				damageProduct: {
					Product_SlNo: '',
					display_text: 'Select Product',
					Product_Name: '',
					Unit_Name: '',
					Product_Purchase_Rate: '',
					Product_SellingPrice: 0.00,
					quantity: 0,
					total: 0.00
				},
				productPurchaseRate: '',
				productStockText: '',
				productStock: '',
				saleOnProgress: false,
				userType: '<?php echo $this->session->userdata("accountType"); ?>',
				accounts: [],
				selectedAccount: null,
			}
		},

		computed: {
			filteredAccounts() {
				let accounts = this.accounts.filter(account => account.status == '1');
				return accounts.map(account => {
					account.display_text = `${account.account_name} - ${account.account_number} (${account.bank_name})`;
					return account;
				})
			},
		},

		watch: {
			selectedArea(area) {
				if (this.selectedCustomer.Customer_Type != 'N') {

					if (area == undefined) return;
					let customer = this.customers.filter(item => item.area_ID == area.District_SlNo)
					this.filterCustomers = customer;
				}
			},

			selectedAccount(selectedAccount) {
				if (selectedAccount == undefined) return;
				this.sales.account_id = selectedAccount.account_id;
			}
		},

		async created() {
			this.sales.salesDate = moment().format('YYYY-MM-DD');
			await this.getEmployees();
			await this.getBranches();
			await this.getAreas();
			await this.getCustomers();
			this.getProducts();
			this.getAccounts();

			if (this.sales.salesId != 0) {
				await this.getSales();
			}
		},
		methods: {
			onChangePaymentType() {
				if (this.sales.paymentType == 'Cash') {
					this.selectedAccount = null;
				}
			},

			getEmployees() {
				axios.get('/get_employees').then(res => {
					this.employees = res.data;
				})
			},

			getBranches() {
				axios.get('/get_branches').then(res => {
					this.branches = res.data;
				})
			},

			getAreas() {
				axios.get('/get_districts').then(res => {
					this.areas = res.data;
				})
			},

			async getCustomers() {
				await axios.post('/get_customers', {
					customerType: this.sales.salesType
				}).then(res => {
					this.customers = res.data;
					this.filterCustomers = res.data;
					this.filterCustomers.unshift({
						Customer_SlNo: 'C01',
						Customer_Code: '',
						Customer_Name: '',
						display_name: 'New Customer',
						Customer_Mobile: '',
						Customer_Address: '',
						Customer_Type: 'N'
					})
					this.filterCustomers.unshift({
						Customer_SlNo: 'C01',
						Customer_Code: '',
						Customer_Name: '',
						display_name: 'General Customer',
						Customer_Mobile: '',
						Customer_Address: '',
						Customer_Type: 'G'
					})
				})
			},

			getAccounts() {
				axios.get('/get_bank_accounts').then(res => {
					this.accounts = res.data;
				})
			},

			getProducts() {
				axios.post('/get_products', {
					isService: this.sales.isService
				}).then(res => {
					if (this.sales.salesType == 'wholesale') {
						this.products = res.data.filter((product) => product.Product_WholesaleRate > 0);
						this.products.map((product) => {
							return product.Product_SellingPrice = product.Product_WholesaleRate;
						})
					} else {
						this.products = res.data;
						this.damageProducts = res.data;

						// this.damageProducts.unshift({
						// 	Product_SlNo: '',
						// 	Product_Code: '',
						// 	display_text: 'New Product',
						// 	Product_Name: '',
						// 	ProductCategory_ID: '',
						// 	Unit_ID: '',
						// 	quantity: 0,
						// 	Product_Purchase_Rate: '',
						// 	Product_SellingPrice: 0.00,
						// 	vat: 0.00,
						// 	total: 0.00
						// })
					}
				})
			},
			productTotal() {
				let boxQty = this.selectedProduct.boxQty ? this.selectedProduct.per_unit_convert * this.selectedProduct.boxQty : 0;
				let pcsQty = this.selectedProduct.pcs ? this.selectedProduct.pcs : 0;
				this.selectedProduct.quantity = parseFloat(boxQty) + parseFloat(pcsQty);

				if (event.target.id == 'discount') {
					this.selectedProduct.discountAmount = parseFloat((parseFloat(this.selectedProduct.Product_SellingPrice) * parseFloat(this.selectedProduct.discount)) / 100).toFixed(2);
				}
				if (event.target.id == 'discountAmount') {
					this.selectedProduct.discount = parseFloat((parseFloat(this.selectedProduct.discountAmount)*100) / parseFloat(this.selectedProduct.Product_SellingPrice)).toFixed(2);
				}
				let total = parseFloat(this.selectedProduct.quantity) * parseFloat(this.selectedProduct.Product_SellingPrice);
				let discountTotal = this.selectedProduct.discountAmount == undefined ? 0 : parseFloat(this.selectedProduct.discountAmount) * this.selectedProduct.quantity;
				this.selectedProduct.total = parseFloat(total - discountTotal).toFixed(2);
			},

			productReturnTotal() {
				this.returnProduct.total = (parseFloat(this.returnProduct.quantity) * parseFloat(this.returnProduct.Product_SellingPrice)).toFixed(2);
			},

			productDamageTotal() {
				this.damageProduct.total = (parseFloat(this.damageProduct.quantity) * parseFloat(this.damageProduct.Product_SellingPrice)).toFixed(2);
			},

			onSalesTypeChange() {
				this.selectedCustomer = {
					Customer_SlNo: '',
					Customer_Code: '',
					Customer_Name: '',
					display_name: 'Select Customer',
					Customer_Mobile: '',
					Customer_Address: '',
					Customer_Type: ''
				}
				this.getCustomers();

				this.clearProduct();
				this.getProducts();
			},

			async calculateProductQty(ind) {
				if ((this.cart[ind].productId != '' || this.cart[ind].productId != 0)) {
					this.productStock = await axios.post('/get_product_stock', {
						productId: this.cart[ind].productId
					}).then(res => {
						return res.data;
					});

					if (this.productStock < this.cart[ind].quantity) {
						alert('Stock Unavailable');
						this.cart[ind].quantity = this.productStock;
						return false;
					}
				}

				var new_rate = this.cart[ind].salesRate - this.cart[ind].discountAmount;
				this.cart[ind].total = (this.cart[ind].quantity * new_rate).toFixed(2);
				this.calculateTotal()
			},

			async customerOnChange() {
				if (this.selectedCustomer.Customer_SlNo == '') {
					return;
				}
				if (event.type == 'readystatechange') {
					return;
				}

				if (this.sales.salesId != 0 && this.oldCustomerId != parseInt(this.selectedCustomer.Customer_SlNo)) {
					let changeConfirm = confirm('Changing customer will set previous due to current due amount. Do you really want to change customer?');
					if (changeConfirm == false) {
						return;
					}
				} else if (this.sales.salesId != 0 && this.oldCustomerId == parseInt(this.selectedCustomer.Customer_SlNo)) {
					this.sales.previousDue = this.oldPreviousDue;
					return;
				}

				if (this.selectedCustomer.Customer_SlNo != 'C01' && this.selectedCustomer.Customer_SlNo != '') {
					this.selectedEmployee = {
						Employee_SlNo: this.selectedCustomer.Employee_Id,
						Employee_Name: this.selectedCustomer.Employee_Name,
					}
				}

				await this.getCustomerDue();

				this.calculateTotal();
			},
			changeEmployee() {
				this.getCustomers();
				this.customers = this.customers.filter(cus => cus.Employee_Id == this.selectedEmployee.Employee_SlNo)
			},
			async getCustomerDue() {
				await axios.post('/get_customer_due', {
					customerId: this.selectedCustomer.Customer_SlNo
				}).then(res => {
					if (res.data.length > 0) {
						this.sales.previousDue = res.data[0].dueAmount;
					} else {
						this.sales.previousDue = 0;
					}
				})
			},
			async productOnChange() {
				if ((this.selectedProduct.Product_SlNo != '' || this.selectedProduct.Product_SlNo != 0) && this.sales.isService == 'false') {
					this.productStock = await axios.post('/get_product_stock', {
						productId: this.selectedProduct.Product_SlNo
					}).then(res => {
						return res.data;
					})

					this.productStockText = this.productStock > 0 ? "Available Stock" : "Stock Unavailable";
				}

				this.$refs.quantity.focus();
			},
			freeProductChange(){
				if (event.target.checked) {
					if (this.selectedProduct != null || this.selectedProduct.Product_SlNo != '') {
						localStorage.setItem('sale_rate', this.selectedProduct.Product_SellingPrice)
					}
					this.selectedProduct.Product_SellingPrice = 0.00;
					this.selectedProduct.total = 0.00;
					this.selectedProduct.discount = 0;
					this.selectedProduct.discountAmount = 0;
				}else{
					this.selectedProduct.boxQty = 0;
					this.selectedProduct.pcs = 0;
					this.selectedProduct.quantity = 0;
					this.selectedProduct.Product_SellingPrice = localStorage.getItem('sale_rate');
				}
			},
			toggleProductPurchaseRate() {
				//this.productPurchaseRate = this.productPurchaseRate == '' ? this.selectedProduct.Product_Purchase_Rate : '';
				this.$refs.productPurchaseRate.type = this.$refs.productPurchaseRate.type == 'text' ? 'password' : 'text';
			},
			addToCart() {
				let product = {
					productId: this.selectedProduct.Product_SlNo,
					productCode: this.selectedProduct.Product_Code,
					categoryName: this.selectedProduct.ProductCategory_Name,
					name: this.selectedProduct.Product_Name,
					salesRate: this.selectedProduct.Product_SellingPrice,
					vat: this.selectedProduct.vat,
					discount: this.selectedProduct.discount,
					discountAmount: this.selectedProduct.discountAmount,
					quantity: this.selectedProduct.quantity,
					boxQty: this.selectedProduct.boxQty,
					pcs: this.selectedProduct.pcs,
					total: this.selectedProduct.total,
					is_free: this.selectedProduct.is_free,
					purchaseRate: this.selectedProduct.Product_Purchase_Rate
				}

				if (product.productId == '') {
					alert('Select Product');
					return;
				}

				if (product.quantity == 0 || product.quantity == '') {
					alert('Enter quantity');
					return;
				}

				// if (product.salesRate == 0 || product.salesRate == '') {
				// 	alert('Enter sales rate');
				// 	return;
				// }

				if (product.quantity > this.productStock && this.sales.isService == 'false') {
					alert('Stock unavailable');
					return;
				}

				let cartInd = this.cart.findIndex(p => p.productId == product.productId);
				if (cartInd > -1) {
					if (product.is_free == 'false') {
						this.cart.splice(cartInd, 1);
					}
				}

				this.cart.unshift(product);
				this.clearProduct();
				this.calculateTotal();
			},

			removeFromCart(ind) {
				this.cart.splice(ind, 1);
				this.calculateTotal();
			},

			addReturnCart() {
				let product = {
					productId: this.returnProduct.Product_SlNo,
					name: this.returnProduct.Product_Name,
					salesRate: this.returnProduct.Product_SellingPrice,
					quantity: this.returnProduct.quantity,
					total: this.returnProduct.total,
				}

				if (product.productId == '') {
					alert('Select Product');
					return;
				}
				if (product.quantity == 0 || product.quantity == '') {
					alert('Enter quantity');
					return;
				}
				var exitsCheck = this.cart.find(p => p.productId == this.returnProduct.Product_SlNo);
				if (exitsCheck == undefined) {
					alert('Product not exits in cart')
					return;
				}
				if (+product.quantity > +exitsCheck.quantity) {
					alert('the quantity more then cart quantity')
					return;
				}

				let cartInd = this.returncart.findIndex(p => p.productId == product.productId);
				if (cartInd > -1) {
					this.returncart.splice(cartInd, 1);
				}

				this.returncart.unshift(product);
				this.clearReturnProduct();
				this.calculateTotal();
			},

			removeFromReturnCart(ind) {
				this.returncart.splice(ind, 1);
				this.clearReturnProduct();
				this.calculateTotal();
			},

			addDamageCart() {
				let product = {
					productId: this.damageProduct.Product_SlNo,
					name: this.damageProduct.Product_Name,
					salesRate: this.damageProduct.Product_SellingPrice,
					quantity: this.damageProduct.quantity,
					total: this.damageProduct.total,
				}

				if (product.productId == '') {
					alert('Select Product');
					return;
				}
				if (product.quantity == 0 || product.quantity == '') {
					alert('Enter quantity');
					return;
				}

				let cartInd = this.damagecart.findIndex(p => p.productId == product.productId);
				if (cartInd > -1) {
					this.damagecart.splice(cartInd, 1);
				}

				this.damagecart.unshift(product);
				this.clearDamageProduct();
				this.calculateTotal();
			},

			removeFromDamageCart(ind) {
				this.damagecart.splice(ind, 1);
				this.clearDamageProduct();
				this.calculateTotal();
			},

			clearProduct() {
				this.selectedProduct = {
					Product_SlNo: '',
					display_text: 'Select Product',
					Product_Name: '',
					Unit_Name: '',
					imageUrl: '',
					quantity: 0,
					boxQty: 0,
					pcs: 0,
					Product_Purchase_Rate: '',
					Product_SellingPrice: 0.00,
					vat: 0.00,
					total: 0.00
				}
				this.productStock = '';
				this.productStockText = '';
			},
			clearReturnProduct() {
				this.returnProduct = {
					Product_SlNo: '',
					display_text: 'Select Product',
					Product_Name: '',
					Unit_Name: '',
					quantity: 0,
					Product_SellingPrice: 0.00,
					total: 0.00
				}
			},
			clearDamageProduct() {
				this.damageProduct = {
					Product_SlNo: '',
					display_text: 'Select Product',
					Product_Name: '',
					Unit_Name: '',
					quantity: 0,
					Product_SellingPrice: 0.00,
					total: 0.00
				}
			},
			calculateTotal() {
				let totalAmount = this.cart.reduce((prev, curr) => {
					return prev + parseFloat(curr.total)
				}, 0).toFixed(2);
				let returnAmount = this.returncart.reduce((prev, curr) => {
					return prev + parseFloat(curr.total)
				}, 0).toFixed(2);
				let damagAmount = this.damagecart.reduce((prev, curr) => {
					return prev + parseFloat(curr.total)
				}, 0).toFixed(2);

				this.sales.subTotal = parseFloat(totalAmount - returnAmount - damagAmount).toFixed(2);
				this.sales.returnTotal = returnAmount;
				this.sales.damageTotal = damagAmount;

				this.sales.vat = this.cart.reduce((prev, curr) => {
					return +prev + +(curr.total * (curr.vat / 100))
				}, 0);
				if (event.target.id == 'discountPercent') {
					this.sales.discount = ((parseFloat(this.sales.subTotal) * parseFloat(this.discountPercent)) / 100).toFixed(2);
				} else {
					this.discountPercent = (parseFloat(this.sales.discount) / parseFloat(this.sales.subTotal) * 100).toFixed(2);
				}
				this.sales.total = ((parseFloat(this.sales.subTotal) + parseFloat(this.sales.vat) + parseFloat(this.sales.transportCost)) - parseFloat(this.sales.discount)).toFixed(2);
				if (this.selectedCustomer.Customer_Type == 'G') {
					this.sales.paid = this.sales.total;
					this.sales.due = 0;
				} else {
					if (event.target.id != 'paid') {
						this.sales.paid = 0;
					}
					this.sales.due = (parseFloat(this.sales.total) - parseFloat(this.sales.paid)).toFixed(2);
				}
			},

			async saveSales() {
				if (this.selectedCustomer.Customer_Type == 'N' && this.selectedArea.District_SlNo == null) {
					alert('Select Area');
					return;
				}
				if (this.selectedCustomer.Customer_SlNo == '') {
					alert('Select Customer');
					return;
				}
				if (this.cart.length == 0) {
					alert('Cart is empty');
					return;
				}

				if (this.selectedEmployee == null) {
					alert('Employee is empty');
					return;
				}

				if (this.sales.paymentType == 'Bank' && this.selectedAccount == null) {
					alert('Select Bank Account');
					return;
				}

				await this.getCustomerDue();

				let url = "/add_sales";
				if (this.sales.salesId != 0) {
					url = "/update_sales";
					// this.sales.previousDue = parseFloat((this.sales.previousDue - this.sales_due_on_update)).toFixed(2);
				}

				// if(parseFloat(this.selectedCustomer.Customer_Credit_Limit) < (parseFloat(this.sales.due) + parseFloat(this.sales.previousDue))){
				// 	alert(`Customer credit limit (${this.selectedCustomer.Customer_Credit_Limit}) exceeded`);
				// 	this.saleOnProgress = false;
				// 	return;
				// }

				if (this.selectedEmployee != null && this.selectedEmployee.Employee_SlNo != null) {
					this.sales.employeeId = this.selectedEmployee.Employee_SlNo;
				} else {
					this.sales.employeeId = null;
				}

				this.sales.customerId = this.selectedCustomer.Customer_SlNo;
				this.sales.salesFrom = this.selectedBranch.brunch_id;

				if (this.selectedCustomer.Customer_Type == 'N') {
					this.selectedCustomer.area_ID = this.selectedArea.District_SlNo;
				}

				if (this.sales.paymentType == 'Cash') {
					this.sales.account_id = null;
				}

				let data = {
					sales: this.sales,
					cart: this.cart,
					customer: this.selectedCustomer,
					damagecart: this.damagecart
				}

				this.saleOnProgress = true;

				axios.post(url, data).then(async res => {
					let r = res.data;
					if (r.success) {
						let conf = confirm('Sales success, Do you want to view invoice?');
						if (conf) {
							window.open('/sale_invoice_print/' + r.salesId, '_blank');
							await new Promise(r => setTimeout(r, 1000));
							window.location = this.sales.isService == 'false' ? '/sales/product' : '/sales/service';
						} else {
							window.location = this.sales.isService == 'false' ? '/sales/product' : '/sales/service';
						}
						// window.open('/sale_invoice_print_auto/' + r.salesId, '_blank');
						// await new Promise(r => setTimeout(r, 1000));
						// window.location = this.sales.isService == 'false' ? '/sales/product' : '/sales/service';
					} else {
						alert(r.message);
						this.saleOnProgress = false;
					}
				})
			},
			async getSales() {
				await axios.post('/get_sales', {
					salesId: this.sales.salesId
				}).then(res => {
					let r = res.data;
					let sales = r.sales[0];
					this.sales.salesBy = sales.AddBy;
					this.sales.salesFrom = sales.SaleMaster_branchid;
					this.sales.salesDate = sales.SaleMaster_SaleDate;
					this.sales.paymentType = sales.payment_type;
					this.sales.account_id = sales.account_id;
					this.sales.customerId = sales.SalseCustomer_IDNo;
					this.sales.employeeId = sales.Employee_SlNo;
					this.sales.subTotal = sales.SaleMaster_SubTotalAmount;
					this.sales.returnTotal = sales.SaleMaster_ReturnTotal;
					this.sales.damageTotal = sales.SaleMaster_DamageTotal;
					this.sales.discount = sales.SaleMaster_TotalDiscountAmount;
					this.sales.vat = sales.SaleMaster_TaxAmount;
					this.sales.transportCost = sales.SaleMaster_Freight;
					this.sales.total = sales.SaleMaster_TotalSaleAmount;
					this.sales.paid = sales.SaleMaster_PaidAmount;
					this.sales.previousDue = sales.SaleMaster_Previous_Due;
					this.sales.due = sales.SaleMaster_DueAmount;
					this.sales.note = sales.SaleMaster_Description;

					this.oldCustomerId = sales.SalseCustomer_IDNo;
					this.oldPreviousDue = sales.SaleMaster_Previous_Due;
					this.sales_due_on_update = sales.SaleMaster_DueAmount;

					this.vatPercent = parseFloat(this.sales.vat) * 100 / parseFloat(this.sales.subTotal);
					this.discountPercent = parseFloat(this.sales.discount) * 100 / parseFloat(this.sales.subTotal);

					this.selectedEmployee = {
						Employee_SlNo: sales.employee_id,
						Employee_Name: sales.Employee_Name
					}

					this.selectedAccount = {
						account_id: sales.account_id,
						account_name: sales.account_name,
						display_text: sales.account_name + ' - ' + sales.account_number
					}

					this.selectedCustomer = {
						Customer_SlNo: sales.SalseCustomer_IDNo,
						Customer_Code: sales.Customer_Code,
						Customer_Name: sales.Customer_Name,
						display_name: sales.Customer_Type == 'G' ? 'General Customer' : `${sales.Customer_Code} - ${sales.Customer_Name}`,
						Customer_Mobile: sales.Customer_Mobile,
						Customer_Address: sales.Customer_Address,
						Customer_Type: sales.Customer_Type
					}

					r.saleDetails.forEach(product => {
						let cartProduct = {
							productCode: product.Product_Code,
							productId: product.Product_IDNo,
							categoryName: product.ProductCategory_Name,
							name: product.Product_Name,
							salesRate: product.SaleDetails_Rate,
							vat: product.SaleDetails_Tax,
							discount: product.SaleDetails_Discount,
							discountAmount: product.Discount_amount,
							quantity: product.SaleDetails_TotalQuantity,
							total: product.SaleDetails_TotalAmount,
							purchaseRate: product.Purchase_Rate,
							is_free: product.is_free
						}

						this.cart.push(cartProduct);
					})

					let gCustomerInd = this.customers.findIndex(c => c.Customer_Type == 'G');
					this.customers.splice(gCustomerInd, 1);
				})
			}
		}
	})
</script>