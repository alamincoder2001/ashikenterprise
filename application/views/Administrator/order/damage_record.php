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

<div id="DamageRecord">
    <div class="row" style="border-bottom: 1px solid #ccc;padding: 3px 0;">
        <div class="col-md-12">
            <form class="form-inline" id="searchForm" @submit.prevent="getDamages">
                <div class="form-group">
					<input type="date" class="form-control" v-model="dateFrom">
				</div>

				<div class="form-group">
					<input type="date" class="form-control" v-model="dateTo">
				</div>

				<div class="form-group" style="margin-top: -5px;">
					<input type="submit" value="Search">
				</div>
            </form>
        </div>
    </div>
    <div class="row" style="margin-top:15px; display:none;" v-bind:style="{display: damages.length > 0 ? '' : 'none'}">
        <div class="col-md-12" style="margin-bottom: 10px;">
            <a href="" @click.prevent="print"><i class="fa fa-print"></i> Print</a>
        </div>
        <div class="col-md-12">
            <div class="table-responsive" id="reportContent">
                <table class="record-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Sales Invoice</th>
                            <th>Custome</th>
                            <th>Product Name</th>
                            <th>Product Code</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Amont</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="damage in damages">
                            <td>{{ damage.date }}</td>
                            <td>{{ damage.SaleMaster_InvoiceNo }}</td>
                            <td>{{ damage.Customer_Name }}</td>
                            <td>{{ damage.Product_Name }}</td>
                            <td>{{ damage.Product_Code }}</td>
                            <td>{{ damage.Product_SellingPrice }}</td>
                            <td style="text-align:center;">{{ damage.quantity }}</td>
                            <td style="text-align:right;">{{ damage.total }}</td>
                        </tr>
                        <tr style="font-weight:bold;">
                            <td colspan="6" style="text-align:right;">Total:</td>
                            <td style="text-align:center;">{{ damages.reduce((prev, curr) => {return prev + parseFloat(curr.quantity)}, 0) }}</td>
                            <td style="text-align:right;">{{ parseFloat(damages.reduce((prev, curr) => {return prev + parseFloat(curr.total)}, 0)).toFixed(2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="7"></td>
                            <td style="text-align:right;"><button type="button" @click="damageClaim" style="padding: 3px 10px;" class="btn btn-success">Claim </button></td>
                        </tr>
                    </tbody>
                </table>
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
        el: '#DamageRecord',
		data(){
			return {
				dateFrom: moment().format('YYYY-MM-DD'),
				dateTo: moment().format('YYYY-MM-DD'),
				
				damages: [],
				products: [],
				selectedProduct: null,
			}
		},
       
		methods: {
            getDamages() {
                let filter = {
                    dateFrom: this.dateFrom,
					dateTo: this.dateTo
                }
                axios.post('/get_damage_record', filter).then(res => {
                    this.damages = res.data;
                })
            },

            damageClaim() {
                let cliamConf = confirm('Are you sure?');
				if(cliamConf == false){
					return;
				}

                let filter = {
                    dateFrom: this.dateFrom,
					dateTo: this.dateTo,
                    damage: this.damages
                }

                axios.post('/damage_cliam', filter).then(res => {
                    let r = res.data;
                    alert(r.message);
					if(r.success){
						this.getDamages();
					}
                })
            },

            async print(){
				let dateText = '';
				if(this.dateFrom != '' && this.dateTo != ''){
					dateText = `Statement from <strong>${this.dateFrom}</strong> to <strong>${this.dateTo}</strong>`;
				}

				let reportContent = `
					<div class="container">
						<div class="row">
							<div class="col-xs-12 text-center">
								<h3>Damage Record</h3>
							</div>
						</div>
						<div class="row">
							<div class="col-xs-6">
								
							</div>
							<div class="col-xs-6 text-right">
								${dateText}
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

				if(this.searchType == '' || this.searchType == 'user'){
					let rows = reportWindow.document.querySelectorAll('.record-table tr');
					rows.forEach(row => {
						row.lastChild.remove();
					})
				}


				reportWindow.focus();
				await new Promise(resolve => setTimeout(resolve, 1000));
				reportWindow.print();
				reportWindow.close();
			}
        }
    })
</script>