<style>
	.v-select {
		margin-top: -2.5px;
		float: right;
		min-width: 180px;
		margin-left: 5px;
	}

	.v-select .dropdown-toggle {
		padding: 0px;
		height: 25px;
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

	#searchForm select {
		padding: 0;
		border-radius: 4px;
	}

	#searchForm .form-group {
		margin-right: 5px;
	}

	#searchForm * {
		font-size: 13px;
	}

	.record-table {
		width: 100%;
		border-collapse: collapse;
	}

	.record-table thead {
		background-color: #0097df;
		color: white;
	}

	.record-table th,
	.record-table td {
		padding: 3px;
		border: 1px solid #454545;
	}

	.record-table th {
		text-align: center;
	}
	.returnboxQty{
		display: none;
	}
	.returnpcsQty{
		display: none;
	}
</style>

<div id="chalanRecord">
	<div class="row" style="border-bottom: 1px solid #ccc;padding: 3px 0;">
		<div class="col-md-12">
			<form class="form-inline" id="searchForm" @submit.prevent="getSearchResult">
				<div class="form-group">
					<label>Search Type</label>
					<select class="form-control" v-model="searchType" @change="onChangeSearchType">
						<option value="employee">By Employee</option>
					</select>
				</div>

				<div class="form-group" style="display:none;" v-bind:style="{display: searchType == 'employee' ? '' : 'none'}">
					<label>Employee</label>
					<v-select v-bind:options="employees" v-model="selectedEmployee" label="Employee_Name"></v-select>
				</div>
				<div class="form-group">
					<input type="date" class="form-control" v-model="date"/>
				</div>
				<!-- <div class="form-group">
					<input type="date" class="form-control" v-model="dateTo"/>
				</div> -->
				<div class="form-group" style="margin-top: -5px;">
					<input type="submit" value="Search">
				</div>
			</form>
		</div>
	</div>

	<div class="row" style="margin-top:15px;display:none;" v-bind:style="{display: sales.length > 0 ? '' : 'none'}">
		<div class="col-md-12" style="margin-bottom: 10px;">
			<a href="" @click.prevent="print"><i class="fa fa-print"></i> Print</a>
		</div>

		<div class="col-md-12">
			<div class="table-responsive" id="reportContent">
				<div class="template">
					<table class="record-table">
						<thead>
							<tr>
								<th>Sl</th>
								<th>Code</th>
								<th>SKU Nmae</th>
								<th colspan="3">Issued</th>
								<th colspan="3">Returned</th>
								<th>Net Sales</th>
							</tr>
							<tr>
								<th></th>
								<th></th>
								<th></th>
								<th>Ctn</th>
								<th>Pcs</th>
								<th>Value</th>
								<th>Ctn</th>
								<th>Pcs</th>
								<th>Value</th>
								<th>Value</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="(product, sl) in sales">
								<td>{{sl + 1}}</td>
								<td>{{ product.Product_Code }}</td>
								<td>{{ product.Product_Name }}</td>
								<td style="text-align: center;">{{ product.boxQty }}</td>
								<td style="text-align: center;">{{ product.pcsQty }}</td>
								<td style="text-align: center;">{{ product.value }}</td>
								<td class="text-center">
									<input style="width: 60px; height:20px; text-align:center;" type="number" min="0" v-model="product.return_box_qty" @input="returnQtyIncrement(event, sl)">
									<span class="returnboxQty">{{product.return_box_qty}}</span>
								</td>
								<td class="text-center">
									<input style="width: 60px; height:20px; text-align:center;" type="number" min="0" v-model="product.return_pcs_qty" @input="returnQtyPcsIncrement(event, sl)">
									<span class="returnpcsQty">{{product.return_pcs_qty}}</span>
								</td>
								<td class="text-center">{{product.returnValue}}</td>
								<td class="text-center">{{product.netValue}}</td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<td align="right" colspan="10">
									<button @click="saveReturn" type="button" class="btn btn-sm btn-primary" :disabled="progress ? true: false">Save</button>
								</td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<script src="<?php echo base_url(); ?>assets/js/vue/vue.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/vue/axios.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/vue/vue-select.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/moment.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/lodash.min.js"></script>

<script>
	Vue.component('v-select', VueSelect.VueSelect);

	new Vue({
		el: "#chalanRecord",

		data() {
			return {
				searchType: 'employee',
				date: moment().format('YYYY-MM-DD'),
				employees: [],
				selectedEmployee: null,
				sales: [],

				progress: false,
			}
		},

		created() {
			this.getEmployees();
		},

		methods: {
			onChangeSearchType() {
				this.sales = [];
				if (this.searchType == 'employee') {
					this.getEmployees();
				}
			},

			getEmployees() {
				axios.get('/get_employees').then(res => {
					this.employees = res.data;
				})
			},

			getSearchResult() {
				if (this.searchType != 'employee') {
					this.selectedEmployee = null;
				}

				this.getSaleDetails();
			},

			returnQtyIncrement(event, ind) {
				let product = this.sales[ind]
				let totalqty = parseFloat(product.boxQty) * product.per_unit_convert + parseFloat(product.pcsQty);
				let boxQty = product.return_box_qty > 0 ? product.per_unit_convert * product.return_box_qty : 0;
				let pcsQty = product.return_pcs_qty > 0 ? product.return_pcs_qty : 0;

				product.returnQty = parseFloat(boxQty) + parseFloat(pcsQty);
				if (totalqty < product.returnQty) {
					alert('Qty not enough');
					product.return_box_qty = product.boxQty;
					product.returnQty = (product.boxQty * product.per_unit_convert)+ +product.pcsQty;
					return
				}
				product.returnValue = parseFloat(product.returnQty) * parseFloat(product.SaleDetails_Rate);

				product.netValue = product.value - product.returnValue
			},

			returnQtyPcsIncrement(event, ind) {
				let product = this.sales[ind]
				let totalqty = parseFloat(product.boxQty) * product.per_unit_convert + parseFloat(product.pcsQty);
				let boxQty = product.return_box_qty > 0 ? product.per_unit_convert * product.return_box_qty : 0;
				let pcsQty = product.return_pcs_qty > 0 ? product.return_pcs_qty : 0;

				product.returnQty = parseFloat(boxQty) + parseFloat(pcsQty);
				if (totalqty < product.returnQty) {
					alert('Qty not enough');
					product.return_pcs_qty = product.pcsQty;
					product.returnQty = (product.boxQty * product.per_unit_convert)+ +product.pcsQty;
					return
				}
				product.returnValue = parseFloat(product.returnQty) * parseFloat(product.SaleDetails_Rate);

				product.netValue = product.value - product.returnValue
			},

			saveReturn() {
				let filter = {
					products: this.sales,
					date: this.date
				}

				this.progress = true;
				axios.post('/calan_qty_return', filter)
					.then(res => {
						if (res.data.success) {
							alert(res.data.message);
							this.progress = false;
						} else {
							console.log(res.data.message);
						}
					})
			},

			getSaleDetails() {
				if (this.selectedEmployee == null) {
					alert("Employee Select")
					return
				}

				let filter = {
					date: this.date,
					// dateTo: this.dateTo,
					employeeId: this.selectedEmployee == null || this.selectedEmployee.Employee_SlNo == '' ? '' : this.selectedEmployee.Employee_SlNo,
				}

				axios.post('/get_orderdetails', filter)
					.then(res => {
						let sales = res.data;
						this.sales = res.data;

						// sales = _.chain(sales)
						//     .groupBy('ProductCategory_ID')
						//     .map(sale => {
						//         return {
						//             category_name: sale[0].ProductCategory_Name,
						//             products: _.chain(sale)
						//                 .groupBy('Product_IDNo')
						//                 .map(product => {
						//                     return {
						//                         productId       : product[0].Product_IDNo,
						//                         product_code    : product[0].Product_Code,
						//                         product_name    : product[0].Product_Name,
						//                         per_unit_convert: product[0].per_unit_convert,
						//                         unit_price      : product[0].SaleDetails_Rate,
						//                         box_qty         : _.sumBy(product, item => Number(item.boxQty)),
						//                         pcs_qty         : _.sumBy(product, item => Number(item.pcsQty)),
						//                         quantity        : _.sumBy(product, item => Number(item.SaleDetails_TotalQuantity)),
						//                         value           : _.sumBy(product, item => Number(item.SaleDetails_TotalAmount))
						//                     }
						//                 })
						//                 .value()
						//         }
						//     })
						//     .value();

						// this.sales = sales;
					})
			},

			async print() {
				let dateText = '';

				let employeeText = '';
				if (this.selectedEmployee != null && this.selectedEmployee.Employee_SlNo != '' && this.searchType == 'employee') {
					employeeText = `<strong>Employee: </strong> ${this.selectedEmployee.Employee_Name}<br>`;
				}


				let reportContent = `
					<div class="container">
						<div class="row">
							<div class="col-xs-12 text-center">
								<h3>Chalan Record</h3>
							</div>
						</div>
						<div class="row">
							<div class="col-xs-6">
								${employeeText}
							</div>
							<div class="col-xs-6 text-right">
								
							</div>
						</div>
						<div class="row">
							<div class="col-xs-12">
								${document.querySelector('#reportContent').innerHTML}
							</div>
						</div>
					</div>
				`;

				var reportWindow = window.open('', 'PRINT', `height=${screen.height}, width=${screen.width}`);
				reportWindow.document.write(`
					<?php $this->load->view('Administrator/reports/reportHeader.php'); ?>
				`);

				reportWindow.document.head.innerHTML += `
					<style>
						.record-table{
							width: 100%;
							border-collapse: collapse;
						}
						.record-table thead{
							background-color: #0097df;
							color:white;
						}
						.record-table th, .record-table td{
							padding: 3px;
							border: 1px solid #454545;
						}
						.record-table th{
							text-align: center;
						}
						.returnpcsQty{
							display:block;
						}
						.returnboxQty{
							display:block;
						}
						input{
							display:none;
						}
						table tfoot{
							display: none;
						}
					</style>
				`;
				reportWindow.document.body.innerHTML += reportContent;

				reportWindow.focus();
				await new Promise(resolve => setTimeout(resolve, 1000));
				reportWindow.print();
				reportWindow.close();
			}
		}
	})
</script>