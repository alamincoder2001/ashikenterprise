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

<div id="pendingClam">
    <div class="row" style="margin-top:15px; display:none;" v-bind:style="{display: claims.length > 0 ? '' : 'none'}">
        <!-- <div class="col-md-12" style="margin-bottom: 10px;">
            <a href="" @click.prevent="print"><i class="fa fa-print"></i> Print</a>
        </div> -->
        <div class="col-md-12">
            <div class="table-responsive" id="reportContent">
                <table class="record-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>To Date</th>
                            <th>Form Date</th>
                            <th>Quantity</th>
                            <th>Amont</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="clam in claims">
                            <td>{{ clam.date }}</td>
                            <td>{{ clam.to_date }}</td>
                            <td>{{ clam.form_date }}</td>
                            <td style="text-align:center;">{{ clam.quantity }}</td>
                            <td style="text-align:right;">{{ clam.total }}</td>
                            <td class="text-center">
                                <!-- <a href="" title="Claim Invoice" target="_blank"><i class="fa fa-file"></i></a> -->
                                <a href="#" @click.prevent="ApprovedClaim(clam.id)" title="Approved Cliam"><i class="fa fa-check"></i></a>
                                <a href="#" @click.prevent="DeleteClaim(clam.id)" title="Delete Claim"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                        <tr style="font-weight:bold;">
                            <td colspan="3" style="text-align:right;">Total:</td>
                            <td style="text-align:center;">{{ claims.reduce((prev, curr) => {return prev + parseFloat(curr.quantity)}, 0) }}</td>
                            <td style="text-align:right;">{{ claims.reduce((prev, curr) => {return prev + parseFloat(curr.total)}, 0) }}</td>
                            <td>
                                
                            </td>
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
        el: '#pendingClam',
		data(){
			return {
				claims: [],
			}
		},
        
        created() {
            this.getPendingClaim();
        },

		methods: {
            getPendingClaim() {
                axios.get('/get_pending_claims').then(res => {
                    this.claims = res.data;
                })
            },

            ApprovedClaim(claimId) {
                let cliamConf = confirm('Are you sure?');
				if(cliamConf == false){
					return;
				}
                axios.post('/approved_claim', {claimId: claimId}).then(res => {
                    let r = res.data;
                    alert(r.message);
					if(r.success){
						this.getPendingClaim();
					}
                })
            },
            DeleteClaim(claimId) {
                let cliamConf = confirm('Are you sure?');
				if(cliamConf == false){
					return;
				}
                axios.post('/delete_claim', {claimId: claimId}).then(res => {
                    let r = res.data;
                    alert(r.message);
					if(r.success){
						this.getPendingClaim();
					}
                })
            }
        }
    })
</script>