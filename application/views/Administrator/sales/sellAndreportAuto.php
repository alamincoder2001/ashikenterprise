<div id="salesInvoice">
	<div class="row">
		<div class="col-md-8 col-md-offset-2">
			<sales-invoice-auto v-bind:sales_id="salesId"></sales-invoice-auto>
		</div>
	</div>
</div>

<script src="<?php echo base_url();?>assets/js/vue/vue.min.js"></script>
<script src="<?php echo base_url();?>assets/js/vue/axios.min.js"></script>
<script src="<?php echo base_url();?>assets/js/vue/components/salesInvoiceAuto.js"></script>
<script src="<?php echo base_url();?>assets/js/moment.min.js"></script>
<script>
	new Vue({
		el: '#salesInvoice',
		components: {
			salesInvoiceAuto
		},
		data(){
			return {
				salesId: parseInt('<?php echo $salesId;?>')
			}
		}
	})
</script>

