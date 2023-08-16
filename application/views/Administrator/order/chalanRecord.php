<style>
    .v-select{
		margin-top:-2.5px;
        float: right;
        min-width: 180px;
        margin-left: 5px;
	}
	.v-select .dropdown-toggle{
		padding: 0px;
        height: 25px;
	}
	.v-select input[type=search], .v-select input[type=search]:focus{
		margin: 0px;
	}
	.v-select .vs__selected-options{
		overflow: hidden;
		flex-wrap:nowrap;
	}
	.v-select .selected-tag{
		margin: 2px 0px;
		white-space: nowrap;
		position:absolute;
		left: 0px;
	}
	.v-select .vs__actions{
		margin-top:-5px;
	}
	.v-select .dropdown-menu{
		width: auto;
		overflow-y:auto;
	}
	#searchForm select{
		padding:0;
		border-radius: 4px;
	}
	#searchForm .form-group{
		margin-right: 5px;
	}
	#searchForm *{
		font-size: 13px;
	}
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
</style>

<div id="chalanRecord">
    <div class="row" style="border-bottom: 1px solid #ccc;padding: 3px 0;">
		<div class="col-md-12">
			<form class="form-inline" id="searchForm" @submit.prevent="getSearchResult">
				<div class="form-group">
					<label>Search Type</label>
					<select class="form-control" v-model="searchType" @change="onChangeSearchType">
						<option value="">All</option>
						<option value="employee">By Employee</option>
					</select>
				</div>

				<div class="form-group" style="display:none;" v-bind:style="{display: searchType == 'employee' ? '' : 'none'}">
					<label>Employee</label>
					<v-select v-bind:options="employees" v-model="selectedEmployee" label="Employee_Name"></v-select>
				</div>
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
                                <th>Code</th>
                                <th>SKU Nmae</th>
                                <th colspan="3">Issued</th>
                                <th colspan="3">Returned</th>
                                <th>Net Sales</th>
                            </tr>
                            <tr>
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
                            <template v-for="sale in sales">
                                <tr v-for="product in sale.products">
                                    <td>{{ product.product_code }}</td>
                                    <td>{{ product.product_name }}</td>
                                    <td style="text-align: center;">{{ product.box_qty }}</td>
                                    <td style="text-align: center;">{{ product.pcs_qty }}</td>
                                    <td style="text-align: right;">{{ product.value }}</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url();?>assets/js/vue/vue.min.js"></script>
<script src="<?php echo base_url();?>assets/js/vue/axios.min.js"></script>
<script src="<?php echo base_url();?>assets/js/vue/vue-select.min.js"></script>
<script src="<?php echo base_url();?>assets/js/moment.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/lodash.min.js"></script>

<script>
    Vue.component('v-select', VueSelect.VueSelect);

    new Vue({
        el: "#chalanRecord",

        data() {
            return {
                searchType: '',
                employees: [],
				selectedEmployee: null,
                sales: [],
            }
        },

        methods: {
            onChangeSearchType() {
				this.sales = [];
				if(this.searchType == 'employee'){
					this.getEmployees();
				}
			},

            getEmployees() {
				axios.get('/get_employees').then(res => {
					this.employees = res.data;
				})
			},

            getSearchResult() {
                if(this.searchType != 'employee'){
					this.selectedEmployee = null;
				}

                this.getSaleDetails();
            },

            getSaleDetails() {
				let filter = {
					employeeId: this.selectedEmployee == null || this.selectedEmployee.Employee_SlNo == '' ? '' : this.selectedEmployee.Employee_SlNo,
				}

				axios.post('/get_orderdetails', filter)
				.then(res => {
					let sales = res.data;

                    sales = _.chain(sales)
                        .groupBy('ProductCategory_ID')
                        .map(sale => {
                            return {
                                category_name: sale[0].ProductCategory_Name,
                                products: _.chain(sale)
                                    .groupBy('Product_IDNo')
                                    .map(product => {
                                        return {
                                            product_code: product[0].Product_Code,
                                            product_name: product[0].Product_Name,
                                            box_qty: _.sumBy(product, item => Number(item.boxQty)),
                                            pcs_qty: _.sumBy(product, item => Number(item.pcsQty)),
                                            quantity: _.sumBy(product, item => Number(item.SaleDetails_TotalQuantity)),
                                            value: _.sumBy(product, item => Number(item.SaleDetails_TotalAmount))
                                        }
                                    })
                                    .value()
                            }
                        })
                        .value();

					this.sales = sales;
				})
				.catch(error => {
					if(error.response){
						alert(`${error.response.status}, ${error.response.statusText}`);
					}
				})
			},

            async print() {
				let dateText = '';


				let employeeText = '';
				if(this.selectedEmployee != null && this.selectedEmployee.Employee_SlNo != '' && this.searchType == 'employee'){
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
					<?php $this->load->view('Administrator/reports/reportHeader.php');?>
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
