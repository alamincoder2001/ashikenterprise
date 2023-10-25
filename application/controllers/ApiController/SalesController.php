<?php
defined('BASEPATH') or exit('No direct script access allowed');

class SalesController extends CI_Controller
{
	// public $userdata;
	public $branch = '';
	public $userFullName = '';
	public function __construct()
	{
		parent::__construct();

		// error_reporting(0);
		$this->load->model('ApiModel');
		$this->load->helper('verifyauthtoken');

		$this->load->model('Billing_model');
		$this->load->library('cart');
		$this->load->model('Model_table', "mt", TRUE);
		$this->load->helper('form');
		$this->load->model('SMS_model', 'sms', true);

		$headerToken = $this->input->get_request_header('Authorization');
		$splitToken = explode(" ", $headerToken);
		$token =  $splitToken[1];

		try {
			$token = verifyAuthToken($token);
			$array = get_object_vars($token);
			$this->branch = $array['branch'];
			$this->userFullName = $array['name'];
		} catch (Exception $e) {
			echo json_encode([
				"status" => 401,
				"message" => "Invalid Token provided",
				"success" => false
			]);
			exit;
		}

		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: GET, OPTIONS, POST, GET, PUT");
		header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
	}

	public function addSales()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$data = json_decode($this->input->raw_input_stream);

			$invoice = $data->sales->invoiceNo;
			$invoiceCount = $this->db->query("select * from tbl_salesmaster where SaleMaster_InvoiceNo = ?", $invoice)->num_rows();
			if ($invoiceCount != 0) {
				$invoice = $this->mt->generateSalesInvoice();
			}
			$customerId = $data->sales->customerId;
			if (isset($data->customer)) {
				$customer = (array)$data->customer;
				unset($customer['Customer_SlNo']);
				unset($customer['display_name']);
				$customer['Customer_Code'] = $this->mt->generateCustomerCode();
				$customer['status'] = 'a';
				$customer['AddBy'] = $this->userFullName;
				$customer['AddTime'] = date("Y-m-d H:i:s");
				$customer['Customer_brunchid'] = $this->branch;

				$this->db->insert('tbl_customer', $customer);
				$customerId = $this->db->insert_id();
			}
			$sales = array(
				'SaleMaster_InvoiceNo' => $invoice,
				'SalseCustomer_IDNo' => $customerId,
				'employee_id' => $data->sales->employeeId,
				'SaleMaster_SaleDate' => $data->sales->salesDate,
				'SaleMaster_SaleType' => $data->sales->salesType,
				'SaleMaster_TotalSaleAmount' => $data->sales->total,
				'SaleMaster_TotalDiscountAmount' => $data->sales->discount,
				'SaleMaster_TaxAmount' => $data->sales->vat,
				'SaleMaster_Freight' => $data->sales->transportCost,
				'SaleMaster_SubTotalAmount' => $data->sales->subTotal,
				'SaleMaster_PaidAmount' => $data->sales->paid,
				'SaleMaster_DueAmount' => $data->sales->due,
				'SaleMaster_Previous_Due' => $data->sales->previousDue,
				'SaleMaster_Description' => $data->sales->note,
				'Status' => 'a',
				'is_service' => $data->sales->isService,
				"AddBy" => $this->userFullName,
				'AddTime' => date("Y-m-d H:i:s"),
				'SaleMaster_branchid' => $this->branch
			);

			$this->db->insert('tbl_salesmaster', $sales);

			$salesId = $this->db->insert_id();

			foreach ($data->cart as $cartProduct) {
				$saleDetails = array(
					'SaleMaster_IDNo' => $salesId,
					'Product_IDNo' => $cartProduct->productId,
					'SaleDetails_TotalQuantity' => $cartProduct->quantity,
					'Purchase_Rate' => $cartProduct->purchaseRate,
					'SaleDetails_Rate' => $cartProduct->salesRate,
					'SaleDetails_Tax' => $cartProduct->vat,
					'SaleDetails_TotalAmount' => $cartProduct->total,
					'Status' => 'a',
					'AddBy' => $this->userFullName,
					'AddTime' => date('Y-m-d H:i:s'),
					'SaleDetails_BranchId' => $cartProduct->branchId,
					'cat_id' => $cartProduct->categoryId
				);

				$this->db->insert('tbl_saledetails', $saleDetails);

				//update stock
				$this->db->query("
                    update tbl_currentinventory 
                    set sales_quantity = sales_quantity + ? 
                    where product_id = ?
                    and branch_id = ?
                    and cat_id=? 
                ", [$cartProduct->quantity, $cartProduct->productId, $cartProduct->branchId, $cartProduct->categoryId]);
			}
			$currentDue = $data->sales->previousDue + ($data->sales->total - $data->sales->paid);
			//Send sms
			if ($data->sales->send_sms == true) {

				$customerInfo = $this->db->query("select * from tbl_customer where Customer_SlNo = ?", $customerId)->row();
				$sendToName = $customerInfo->owner_name != '' ? $customerInfo->owner_name : $customerInfo->Customer_Name;

				// $message = "Dear {$sendToName},\nYour bill is tk. {$data->sales->total}. Received tk. {$data->sales->paid} and current due is tk. {$currentDue} for invoice {$invoice}";
				$message = "মি: {$sendToName},\nআপনার  ইনভয়েস নং:{$invoice}\nবিল: tk.{$data->sales->total}.\nরিসিভ: tk.{$data->sales->paid}\nবাকী: tk.{$data->sales->due}\nমোট বাকী: tk.{$currentDue}.";
				$recipient = $customerInfo->Customer_Mobile;
				$this->sms->sendSms($recipient, $message);
			}

			$res = ['success' => true, 'message' => 'Sales Success', 'salesId' => $salesId];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

    public function addOrder() 
    {
        $res = ['success' => false, 'message' => ''];
        
        try {
            $data = json_decode($this->input->raw_input_stream);

            $customer = $this->db->query("select * from tbl_customer where Customer_SlNo = ?", $data->order->customerId)->row();

            $order = array(
				'SaleMaster_InvoiceNo' => $this->mt->generateSalesInvoice(),
				'SalseCustomer_IDNo' => $customer->Customer_SlNo,
				'employee_id' => $data->order->employeeId,
				'SaleMaster_SaleDate' => date("Y-m-d H:i:s"),
				'SaleMaster_SaleType' => $customer->Customer_Type,
				'SaleMaster_TotalSaleAmount' => $data->order->subTotal,
				'SaleMaster_TotalDiscountAmount' => 0,
				'SaleMaster_TaxAmount' => 0,
				'SaleMaster_Freight' => 0,
				'SaleMaster_SubTotalAmount' => $data->order->subTotal,
				'SaleMaster_PaidAmount' => 0,
				'SaleMaster_DueAmount' => $data->order->subTotal,
				'SaleMaster_Previous_Due' => $data->order->prevDue,
				'SaleMaster_Description' => 'Apps Order',
				'Status' => 'a',
				'is_service' => 'false',
				"AddBy" => $this->userFullName,
				'AddTime' => date("Y-m-d H:i:s"),
				'SaleMaster_branchid' => $this->branch
			);

			$this->db->insert('tbl_salesmaster', $order);

			$orderId = $this->db->insert_id();

			foreach ($data->cart as $cartProduct) {
				$orderDetails = array(
					'SaleMaster_IDNo' => $orderId,
					'Product_IDNo' => $cartProduct->productId,
					'SaleDetails_TotalQuantity' => $cartProduct->quantity,
					'Purchase_Rate' => $cartProduct->purchaseRate,
					'SaleDetails_Rate' => $cartProduct->salesRate,
				// 	'SaleDetails_Discount' => $cartProduct->discount,
					'SaleDetails_TotalAmount' => $cartProduct->total,
					'Status' => 'a',
					'AddBy' => $this->userFullName,
					'AddTime' => date('Y-m-d H:i:s'),
					'SaleDetails_BranchId' => $this->branch,
				);

				$this->db->insert('tbl_saledetails', $orderDetails);

				//update stock
				$this->db->query("
                    update tbl_currentinventory 
                    set sales_quantity = sales_quantity + ? 
                    where product_id = ?
                    and branch_id = ?
                ", [$cartProduct->quantity, $cartProduct->productId, $this->branch]);
			}

            $res = ['success' => true, 'message' => 'Order Success'];

        } catch (\Exception $ex) {
            $res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
    }

	public function updateSales()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$data = json_decode($this->input->raw_input_stream);
			$salesId = $data->sales->salesId;

			if (isset($data->customer)) {
				$customer = (array)$data->customer;
				unset($customer['Customer_SlNo']);
				unset($customer['display_name']);
				$customer['UpdateBy'] = $this->userFullName;
				$customer['UpdateTime'] = date("Y-m-d H:i:s");

				$this->db->where('Customer_SlNo', $data->sales->customerId)->update('tbl_customer', $customer);
			}
			$sales = array(
				'SalseCustomer_IDNo' => $data->sales->customerId,
				'employee_id' => $data->sales->employeeId,
				'SaleMaster_SaleDate' => $data->sales->salesDate,
				'SaleMaster_SaleType' => $data->sales->salesType,
				'SaleMaster_TotalSaleAmount' => $data->sales->total,
				'SaleMaster_TotalDiscountAmount' => $data->sales->discount,
				'SaleMaster_TaxAmount' => $data->sales->vat,
				'SaleMaster_Freight' => $data->sales->transportCost,
				'SaleMaster_SubTotalAmount' => $data->sales->subTotal,
				'SaleMaster_PaidAmount' => $data->sales->paid,
				'SaleMaster_DueAmount' => $data->sales->due,
				'SaleMaster_Previous_Due' => $data->sales->previousDue,
				'SaleMaster_Description' => $data->sales->note,
				"UpdateBy" => $this->userFullName,
				'UpdateTime' => date("Y-m-d H:i:s"),
				"SaleMaster_branchid" => $this->branch
			);

			$this->db->where('SaleMaster_SlNo', $salesId);
			$this->db->update('tbl_salesmaster', $sales);

			$currentSaleDetails = $this->db->query("select * from tbl_saledetails where SaleMaster_IDNo = ?", $salesId)->result();
			$this->db->query("delete from tbl_saledetails where SaleMaster_IDNo = ?", $salesId);

			foreach ($currentSaleDetails as $product) {
				$this->db->query("
                    update tbl_currentinventory 
                    set sales_quantity = sales_quantity - ? 
                    where product_id = ?
                    and cat_id =? 
                    and branch_id = ?
                ", [$product->SaleDetails_TotalQuantity, $product->Product_IDNo, $product->cat_id, $product->SaleDetails_BranchId]);
			}

			foreach ($data->cart as $cartProduct) {
				$saleDetails = array(
					'SaleMaster_IDNo' => $salesId,
					'Product_IDNo' => $cartProduct->productId,
					'SaleDetails_TotalQuantity' => $cartProduct->quantity,
					'Purchase_Rate' => $cartProduct->purchaseRate,
					'SaleDetails_Rate' => $cartProduct->salesRate,
					'SaleDetails_Tax' => $cartProduct->vat,
					'SaleDetails_TotalAmount' => $cartProduct->total,
					'Status' => 'a',
					'AddBy' => $this->userFullName,
					'AddTime' => date('Y-m-d H:i:s'),
					'SaleDetails_BranchId' => $cartProduct->branchId,
					'cat_id' => $cartProduct->categoryId
				);

				$this->db->insert('tbl_saledetails', $saleDetails);

				$this->db->query("
                    update tbl_currentinventory 
                    set sales_quantity = sales_quantity + ? 
                    where product_id = ?
                    and branch_id = ?
                    and cat_id=?
                ", [$cartProduct->quantity, $cartProduct->productId, $cartProduct->branchId, $cartProduct->categoryId]);
			}

			$res = ['success' => true, 'message' => 'Sales Updated', 'salesId' => $salesId];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function getSaleDetails()
	{
		$data = json_decode($this->input->raw_input_stream);

		$clauses = "";
		if (isset($data->customerId) && $data->customerId != '') {
			$clauses .= " and c.Customer_SlNo = '$data->customerId'";
		}

		if (isset($data->productId) && $data->productId != '') {
			$clauses .= " and p.Product_SlNo = '$data->productId'";
		}

		if (isset($data->categoryId) && $data->categoryId != '') {
			$clauses .= " and pc.ProductCategory_SlNo = '$data->categoryId'";
		}

		if (isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != '') {
			$clauses .= " and sm.SaleMaster_SaleDate between '$data->dateFrom' and '$data->dateTo'";
		}

		$saleDetails = $this->db->query("
            select 
                sd.*,
                p.Product_Code,
                p.Product_Name,
                pc.ProductCategory_Name,
                u.Unit_Name,
                sm.SaleMaster_InvoiceNo,
                sm.SaleMaster_SaleDate,
                c.Customer_Code,
                c.Customer_Name
            from tbl_saledetails sd
            join tbl_product p on p.Product_SlNo = sd.Product_IDNo
            join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
            join tbl_unit u on u.Unit_SlNo = p.Unit_ID
            join tbl_salesmaster sm on sm.SaleMaster_SlNo = sd.SaleMaster_IDNo
            join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo
            where sd.Status != 'd'
            and sm.SaleMaster_branchid = ?
            $clauses
        ", $this->branch)->result();

		echo json_encode($saleDetails);
	}

	public function getSales()
	{
        
		$data = json_decode($this->input->raw_input_stream);
		$branchId = $this->branch;

		$clauses = "";
		if (isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != '') {
			$clauses .= " and sm.SaleMaster_SaleDate between '$data->dateFrom' and '$data->dateTo'";
		}

		if (isset($data->userFullName) && $data->userFullName != '') {
			$clauses .= " and sm.AddBy = '$data->userFullName'";
		}

		if (isset($data->customerId) && $data->customerId != '') {
			$clauses .= " and sm.SalseCustomer_IDNo = '$data->customerId'";
		}

		if (isset($data->employeeId) && $data->employeeId != '') {
			$clauses .= " and sm.employee_id = '$data->employeeId'";
		}

		if (isset($data->productId) && $data->productId != '') {
			$clauses .= " and sd.Product_IDNo = '$data->productId'";
		}

		// 		echo $data->salesId;
		// 	    exit;

		if (isset($data->salesId) && $data->salesId != 0 && $data->salesId != '') {
			$clauses .= " and SaleMaster_SlNo = '$data->salesId'";
			$saleDetails = $this->db->query("
                select 
                    sd.*,
                    p.Product_Name,
                    pc.ProductCategory_Name,
                    u.Unit_Name,
                    b.Brunch_name,
                    b.brunch_id
                from tbl_saledetails sd
                join tbl_product p on p.Product_SlNo = sd.Product_IDNo
                join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
                join tbl_brunch b on sd.SaleDetails_BranchId= b.brunch_id
                join tbl_unit u on u.Unit_SlNo = p.Unit_ID
                where sd.SaleMaster_IDNo = ?
            ", $data->salesId)->result();

			$res['saleDetails'] = $saleDetails;
		}
		$sales = $this->db->query("
            select 
            sm.*,
            c.Customer_Code,
            c.Customer_Name,
            c.owner_name,
            c.Customer_Mobile,
            c.Customer_Address,
            c.Customer_Type,
            e.Employee_Name,
            br.Brunch_name
            from tbl_salesmaster sm
            left join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo
            left join tbl_employee e on e.Employee_SlNo = sm.employee_id
            left join tbl_brunch br on br.brunch_id = sm.SaleMaster_branchid
            where sm.SaleMaster_branchid = '$branchId'
            and sm.Status = 'a'
            $clauses
            order by sm.SaleMaster_SlNo desc
        ")->result();

		$res['sales'] = $sales;

		echo json_encode($res);
	}

	public function  deleteSales()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$data = json_decode($this->input->raw_input_stream);
			$saleId = $data->saleId;

			$sale = $this->db->select('*')->where('SaleMaster_SlNo', $saleId)->get('tbl_salesmaster')->row();
			if ($sale->Status != 'a') {
				$res = ['success' => false, 'message' => 'Sale not found'];
				echo json_encode($res);
				exit;
			}

			/*Get Sale Details Data*/
			$saleDetails = $this->db->select('Product_IDNo, SaleDetails_TotalQuantity,cat_id,SaleDetails_BranchId')->where('SaleMaster_IDNo', $saleId)->get('tbl_saledetails')->result();

			foreach ($saleDetails as $detail) {
				/*Get Product Current Quantity*/
				$totalQty = $this->db->where(['product_id' => $detail->Product_IDNo, 'cat_id' => $detail->cat_id, 'branch_id' => $detail->SaleDetails_BranchId])->get('tbl_currentinventory')->row()->sales_quantity;

				/* Subtract Product Quantity form  Current Quantity  */
				$newQty = $totalQty - $detail->SaleDetails_TotalQuantity;

				/*Update Sales Inventory*/
				$this->db->set('sales_quantity', $newQty)->where(['product_id' => $detail->Product_IDNo, 'cat_id' => $detail->cat_id,  'branch_id' => $detail->SaleDetails_BranchId])->update('tbl_currentinventory');
			}

			/*Delete Sale Details*/
			$this->db->set('Status', 'd')->where('SaleMaster_IDNo', $saleId)->update('tbl_saledetails');

			/*Delete Sale Master Data*/
			$this->db->set('Status', 'd')->where('SaleMaster_SlNo', $saleId)->update('tbl_salesmaster');
			$res = ['success' => true, 'message' => 'Sale deleted'];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function getSalesRecord()
	{

		$data = json_decode($this->input->raw_input_stream);

		$branchId = $this->branch;

		$clauses = "";
		if (isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != '') {
			$clauses .= " and sm.SaleMaster_SaleDate between '$data->dateFrom' and '$data->dateTo'";
		}

		if (isset($data->userFullName) && $data->userFullName != '') {
			$clauses .= " and sm.AddBy = '$data->userFullName'";
		}

		if (isset($data->customerId) && $data->customerId != '') {
			$clauses .= " and sm.SalseCustomer_IDNo = '$data->customerId'";
		}

		if (isset($data->employeeId) && $data->employeeId != '') {
			$clauses .= " and sm.employee_id = '$data->employeeId'";
		}

		$sales = $this->db->query("
            select 
                sm.*,
                c.Customer_Code,
                c.Customer_Name,
                c.Customer_Mobile,
                c.Customer_Address,
                e.Employee_Name,
                br.Brunch_name,
                (
                    select ifnull(count(*), 0) from tbl_saledetails sd 
                    where sd.SaleMaster_IDNo = 1
                    and sd.Status != 'd'
                ) as total_products
            from tbl_salesmaster sm
            left join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo
            left join tbl_employee e on e.Employee_SlNo = sm.employee_id
            left join tbl_brunch br on br.brunch_id = sm.SaleMaster_branchid
            where sm.SaleMaster_branchid = '$branchId'
            and sm.Status = 'a'
            $clauses
            order by sm.SaleMaster_SlNo desc
        ")->result();

		foreach ($sales as $sale) {
			$sale->saleDetails = $this->db->query("
                select 
                    sd.*,
                    p.Product_Name,
                    pc.ProductCategory_Name
                from tbl_saledetails sd
                join tbl_product p on p.Product_SlNo = sd.Product_IDNo
                join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
                where sd.SaleMaster_IDNo = ?
                and sd.Status != 'd'
            ", $sale->SaleMaster_SlNo)->result();
		}

		echo json_encode($sales);
	}

    public function getProfitLoss()
    {
        $data = json_decode($this->input->raw_input_stream);

        $customerClause = "";
        if ($data->customer != null && $data->customer != '') {
            $customerClause = " and sm.SalseCustomer_IDNo = '$data->customer'";
        }

        $dateClause = "";
        if (($data->dateFrom != null && $data->dateFrom != '') && ($data->dateTo != null && $data->dateTo != '')) {
            $dateClause = " and sm.SaleMaster_SaleDate between '$data->dateFrom' and '$data->dateTo'";
        }


        $sales = $this->db->query("
            select 
                sm.*,
                c.Customer_Code,
                c.Customer_Name,
                c.Customer_Mobile
            from tbl_salesmaster sm
            join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo
            where sm.SaleMaster_branchid = ? 
            and sm.Status = 'a'
            $customerClause $dateClause
        ", $this->branch)->result();

        foreach ($sales as $sale) {
            $sale->saleDetails = $this->db->query("
                select
                    sd.*,
                    p.Product_Code,
                    p.Product_Name,
                    (sd.Purchase_Rate * sd.SaleDetails_TotalQuantity) as purchased_amount,
                    (select sd.SaleDetails_TotalAmount - purchased_amount) as profit_loss
                from tbl_saledetails sd 
                join tbl_product p on p.Product_SlNo = sd.Product_IDNo
                where sd.SaleMaster_IDNo = ?
            ", $sale->SaleMaster_SlNo)->result();
        }

        echo json_encode($sales);
    }


    public function getProfitLossFilters()
    {
        $data = json_decode($this->input->raw_input_stream);
        $clauses = '';

        if (isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != '') {
            $clauses .= " and sm.SaleMaster_SaleDate between '$data->dateFrom' and '$data->dateTo'";
        }
        if (isset($data->productId) && $data->productId != '') {
            $clauses .= " and sd.Product_IDNo = $data->productId";
        }

        $sales = $this->db->query("
            select
                sm.SaleMaster_SaleDate,
                sm.SaleMaster_InvoiceNo,
                sd.*,
                p.Product_Code,
                p.Product_Name,
                (sd.Purchase_Rate * sd.SaleDetails_TotalQuantity) as purchased_amount,
                (select sd.SaleDetails_TotalAmount - purchased_amount) as profit_loss
            from tbl_saledetails sd 
            join tbl_product p on p.Product_SlNo = sd.Product_IDNo
            join tbl_salesmaster sm on sm.SaleMaster_SlNo = sd.SaleMaster_IDNo
            where sm.Status = 'a'
            and sm.SaleMaster_branchid = ?
            $clauses
        ", $this->branch)->result();

        echo json_encode($sales);
    }

	public function getSaleSummary()
	{
		$data = json_decode($this->input->raw_input_stream);

		$clauses = "";
		// if (isset($data->customerId) && $data->customerId != '') {
		//     $clauses .= " and c.Customer_SlNo = '$data->customerId'";
		// }

		if (isset($data->productId) && $data->productId != '') {
			$clauses .= " and p.Product_SlNo = '$data->productId'";
		}

		// if (isset($data->categoryId) && $data->categoryId != '') {
		//     $clauses .= " and pc.ProductCategory_SlNo = '$data->categoryId'";
		// }

		if (isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != '') {
			$clauses .= " and sm.SaleMaster_SaleDate between '$data->dateFrom' and '$data->dateTo'";
		}

		$saleDetails = $this->db->query("
            select 
                p.*,
                sum(sd.SaleDetails_TotalQuantity) as totalSaleQty,
                sum(sd.SaleDetails_Rate) as totalSaleRate,
                sum(sd.SaleDetails_TotalAmount) as totalSaleAmount
            from tbl_saledetails sd
            join tbl_product p on p.Product_SlNo = sd.Product_IDNo
            join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
            join tbl_salesmaster sm on sm.SaleMaster_SlNo = sd.SaleMaster_IDNo
            join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo
            where sd.Status != 'd'
            and sm.SaleMaster_branchid = ?
            $clauses
            group by p.Product_SlNo 
        ", $this->branch)->result();

		echo json_encode($saleDetails);
	}

	public function getSaleInvoice()
	{
		$invoice = $this->mt->generateSalesInvoice();
		echo json_encode($invoice);
	}

	public function getTopData()
	{
		$data = json_decode($this->input->raw_input_stream);
		$branchId = $this->branch;
		
		$dateTo = date('Y-m-d');
// 		$d = strtotime("-1 month");
	   // $dateFrom = date("Y-m-d", $d);
	    $dateFrom = date('Y-m-d');
	    
	    $today = date('Y-m-d');
	    $monthFirstDay = date('Y-m-01', strtotime($today));
	    $monthLastDay = date('Y-m-t', strtotime($today));

		$res['todaySale'] = $this->db->query("
		    select 
            	ifnull(sum(sm.SaleMaster_TotalSaleAmount), 0) as total
            from tbl_salesmaster sm
            where sm.SaleMaster_branchid = ?
            and sm.Status = 'a'
            and sm.SaleMaster_SaleDate between '$dateFrom' and '$dateTo'
            ", $this->branch)->row()->total;
		
		$res['monthlySale'] = $this->db->query("
		    select 
            	ifnull(sum(sm.SaleMaster_TotalSaleAmount), 0) as total
            from tbl_salesmaster sm
            where sm.SaleMaster_branchid = ?
            and sm.Status = 'a'
            and sm.SaleMaster_SaleDate between '$monthFirstDay' and '$monthLastDay'
            ", $this->branch)->row()->total;
		
        $totalDue = $this->db->query("
	        select * from (
	           select
	                (
	                    select
	                    ifnull(sum(previous_due),0)
	                    from tbl_customer
	                    where status = 'a'
	                    and Customer_brunchid ='$this->branch'
	                ) as previous_due,
    	            (
    	                select 
                        	ifnull(sum(sm.SaleMaster_TotalSaleAmount), 0)
                        from tbl_salesmaster sm 
                        join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo 
                        where sm.Status = 'a'
                        and sm.SaleMaster_branchid = '$this->branch'
                        and c.status = 'a'
                        and c.Customer_Type != 'G'
    	            ) as bill_amount,
    	            (
    	                select 
                        	ifnull(sum(sm.SaleMaster_PaidAmount), 0)
                        from tbl_salesmaster sm 
                        join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo 
                        where sm.Status = 'a'
                        and sm.SaleMaster_branchid = '$this->branch'
                        and sm.Status = 'a'
                        and c.status = 'a'
                        and c.Customer_Type != 'G'
    	            ) as invoice_paid,
            	    (select 
                    	ifnull(sum(cp.CPayment_amount), 0) as total
                    from tbl_customer_payment cp
                    join tbl_customer c on c.Customer_SlNo = cp.CPayment_customerID
                    where cp.CPayment_brunchid = '$this->branch'
                    and cp.CPayment_TransactionType = 'CR'
                    and cp.CPayment_status = 'a'
                    and c.Customer_Type != 'G'
                    and c.status = 'a') as customer_receive,
                    
                    (select 
                    	ifnull(sum(cp.CPayment_amount), 0) as total
                    from tbl_customer_payment cp
                    join tbl_customer c on c.Customer_SlNo = cp.CPayment_customerID
                    where cp.CPayment_brunchid = '$this->branch'
                    and cp.CPayment_TransactionType = 'CP'
                    and cp.CPayment_status = 'a'
                    and c.Customer_Type != 'G'
                    and c.status = 'a') as customer_payment,
                    (
                        select
                            ifnull(sum(sr.SaleReturn_ReturnAmount), 0.00)  
                        from tbl_salereturn sr 
                        join tbl_salesmaster smr on smr.SaleMaster_InvoiceNo = sr.SaleMaster_InvoiceNo 
                        join tbl_customer c on c.Customer_SlNo = smr.SalseCustomer_IDNo
                        where sr.Status = 'a'
                        and c.status = 'a'
                        and sr.SaleReturn_brunchId = '$this->branch'
                        and c.Customer_Type != 'G'
                    )as sale_return,
                    
                    (select (bill_amount + customer_payment + previous_due) - (invoice_paid + customer_receive + sale_return)) as dueAmount
	        
	        ) as tbl
        ")->row()->dueAmount;
        
        $res['totalDue'] = $totalDue;
        
        $res['cashBalance'] =  $this->db->query("SELECT
            /* Received */
            (
                  select ifnull(sum(sm.SaleMaster_PaidAmount), 0) from tbl_salesmaster sm
                where sm.SaleMaster_branchid= " . $this->branch ."
                and sm.Status = 'a'
            ) as received_sales,
            (
                     select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
                where cp.CPayment_TransactionType = 'CR'
                and cp.CPayment_status = 'a'
                and cp.CPayment_Paymentby != 'bank'
                and cp.CPayment_brunchid= " . $this->branch ."
            ) as received_customer,
            (
                   select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
                where sp.SPayment_TransactionType = 'CR'
                and sp.SPayment_status = 'a'
                and sp.SPayment_Paymentby != 'bank'
                and sp.SPayment_brunchid= " . $this->branch ."
            ) as received_supplier,
            (
                  select ifnull(sum(ct.In_Amount), 0) from tbl_cashtransaction ct
                where ct.Tr_Type = 'In Cash'
                and ct.status = 'a'
                and ct.Tr_branchid= " . $this->branch ."
            ) as received_cash,
            (
                 select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
                where bt.transaction_type = 'withdraw'
                and bt.status = 1
                and bt.branch_id= " . $this->branch ."
            ) as bank_withdraw,
            
            /* paid */
			
            (
                select ifnull(sum(pm.PurchaseMaster_PaidAmount), 0) from tbl_purchasemaster pm
                where pm.status = 'a'
                and pm.PurchaseMaster_BranchID= " . $this->branch ."
            ) as paid_purchase,

			(
                select ifnull(sum(pm.own_freight), 0) from tbl_purchasemaster pm
                where pm.status = 'a'
                and pm.PurchaseMaster_BranchID= " . $this->branch ."
            ) as own_freight,

            (
 				select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
                where sp.SPayment_TransactionType = 'CP'
                and sp.SPayment_status = 'a'
                and sp.SPayment_Paymentby != 'bank'
                and sp.SPayment_brunchid= " . $this->branch ."
            ) as paid_supplier,

            (
                select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
                where cp.CPayment_TransactionType = 'CP'
                and cp.CPayment_status = 'a'
                and cp.CPayment_Paymentby != 'bank'
                and cp.CPayment_brunchid= " . $this->branch ."
            ) as paid_customer,

            (
                select ifnull(sum(ct.Out_Amount), 0) from tbl_cashtransaction ct
                where ct.Tr_Type = 'Out Cash'
                and ct.status = 'a'
                and ct.Tr_branchid= " . $this->branch ."
            ) as paid_cash,

            (
             select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
                where bt.transaction_type = 'deposit'
                and bt.status = 1
                and bt.branch_id= " . $this->branch ."
            ) as bank_deposit,

            (
            select ifnull(sum(ep.payment_amount), 0) from tbl_employee_payment ep
                where ep.paymentBranch_id = " . $this->branch . "
                and ep.status = 'a'
            ) as employee_payment,

            
           /* total */
            (
                select received_sales + received_customer + received_supplier + received_cash + bank_withdraw
            ) as total_in,
            (
                select paid_purchase + own_freight + paid_customer + paid_supplier + paid_cash + bank_deposit + employee_payment
            ) as total_out,
            (
                select total_in - total_out
            ) as cash_balance
        ")->row()->cash_balance;
		
		echo json_encode($res);
	}

	public function getGraphData()
	{
		$inputs = json_decode($this->input->raw_input_stream);
		// Monthly Record
		$monthlyRecord = [];
		$year = date('Y');
		$month = date('m');
		$dayNumber = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		for ($i = 1;
			$i <= $dayNumber;
			$i++
		) {
			$date = $year . '-' . $month . '-' . sprintf("%02d", $i);
			$query = $this->db->query("
                    select ifnull(sum(sm.SaleMaster_TotalSaleAmount), 0) as sales_amount 
                    from tbl_salesmaster sm 
                    where sm.SaleMaster_SaleDate = ?
                    and sm.Status = 'a'
                    and sm.SaleMaster_branchid = ?
                    group by sm.SaleMaster_SaleDate
                ", [$date, $this->branch]);

			$amount = 0.00;

			if ($query->num_rows() == 0) {
				$amount = 0.00;
			} else {
				$amount = $query->row()->sales_amount;
			}
			$sale = [sprintf("%02d", $i), $amount];
			array_push($monthlyRecord, $sale);
		}

		$yearlyRecord = [];
		for ($i = 1;
			$i <= 12;
			$i++
		) {
			$yearMonth = $year . sprintf("%02d", $i);
			$query = $this->db->query("
                    select ifnull(sum(sm.SaleMaster_TotalSaleAmount), 0) as sales_amount 
                    from tbl_salesmaster sm 
                    where extract(year_month from sm.SaleMaster_SaleDate) = ?
                    and sm.Status = 'a'
                    and sm.SaleMaster_branchid = ?
                    group by extract(year_month from sm.SaleMaster_SaleDate)
                ", [$yearMonth, $this->branch]);

			$amount = 0.00;
			$monthName = date("M", mktime(0, 0, 0, $i, 10));

			if ($query->num_rows() == 0) {
				$amount = 0.00;
			} else {
				$amount = $query->row()->sales_amount;
			}
			$sale = [$monthName, $amount];
			array_push($yearlyRecord, $sale);
		}

		// Sales text for marquee
		$sales = $this->db->query("
                select 
                    concat(
                        'Invoice: ', sm.SaleMaster_InvoiceNo,
                        ', Customer: ', c.Customer_Code, ' - ', c.Customer_Name,
                        ', Amount: ', sm.SaleMaster_TotalSaleAmount,
                        ', Paid: ', sm.SaleMaster_PaidAmount,
                        ', Due: ', sm.SaleMaster_DueAmount
                    ) as sale_text
                from tbl_salesmaster sm 
                join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo
                where sm.Status = 'a'
                and sm.SaleMaster_branchid = ?
                order by sm.SaleMaster_SlNo desc limit 20
            ", $this->branch)->result();

		// Today's Sale
		$todaysSale = $this->db->query("
                select 
                    ifnull(sum(ifnull(sm.SaleMaster_TotalSaleAmount, 0)), 0) as total_amount
                from tbl_salesmaster sm
                where sm.Status = 'a'
                and sm.SaleMaster_SaleDate = ?
                and sm.SaleMaster_branchid = ?
            ", [date('Y-m-d'), $this->branch])->row()->total_amount;

		// This Month's Sale
		$thisMonthSale = $this->db->query("
                select 
                    ifnull(sum(ifnull(sm.SaleMaster_TotalSaleAmount, 0)), 0) as total_amount
                from tbl_salesmaster sm
                where sm.Status = 'a'
                and month(sm.SaleMaster_SaleDate) = ?
                and year(sm.SaleMaster_SaleDate) = ?
                and sm.SaleMaster_branchid = ?
            ", [date('m'), date('Y'), $this->branch])->row()->total_amount;

		// Today's Cash Collection
		$todaysCollection = $this->db->query("
                select 
                ifnull((
                    select sum(ifnull(sm.SaleMaster_PaidAmount, 0)) 
                    from tbl_salesmaster sm
                    where sm.Status = 'a' 
                    and sm.SaleMaster_branchid = " . $this->branch . "
                    and sm.SaleMaster_SaleDate = '" . date('Y-m-d') ."'
                ), 0) +
                ifnull((
                    select sum(ifnull(cp.CPayment_amount, 0)) 
                    from tbl_customer_payment cp
                    where cp.CPayment_status = 'a'
                    and cp.CPayment_TransactionType = 'CR'
                    and cp.CPayment_brunchid = " . $this->branch . "
                    and cp.CPayment_date = '" . date('Y-m-d') ."'
                ), 0) +
                ifnull((
                    select sum(ifnull(ct.In_Amount, 0)) 
                    from tbl_cashtransaction ct
                    where ct.status = 'a'
                    and ct.Tr_branchid = " . $this->branch . "
                    and ct.Tr_date = '" . date('Y-m-d') . "'
                ), 0) as total_amount
            ")->row()->total_amount;


		// Cash Balance
		// $cashBalance = $this->mt->getTransactionSummary()->cash_balance;




		// Customer Due

		// $customerDueResult = $this->mt->customerDue();
		// $customerDue = array_sum(array_map(function ($due) {
		// 	return $due->dueAmount;
		// }, $customerDueResult));

		$cashBalance =  $this->db->query("SELECT
            /* Received */
            (
                  select ifnull(sum(sm.SaleMaster_PaidAmount), 0) from tbl_salesmaster sm
                where sm.SaleMaster_branchid= " . $this->branch ."
                and sm.Status = 'a'
            ) as received_sales,
            (
                     select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
                where cp.CPayment_TransactionType = 'CR'
                and cp.CPayment_status = 'a'
                and cp.CPayment_Paymentby != 'bank'
                and cp.CPayment_brunchid= " . $this->branch ."
            ) as received_customer,
            (
                   select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
                where sp.SPayment_TransactionType = 'CR'
                and sp.SPayment_status = 'a'
                and sp.SPayment_Paymentby != 'bank'
                and sp.SPayment_brunchid= " . $this->branch ."
            ) as received_supplier,
            (
                  select ifnull(sum(ct.In_Amount), 0) from tbl_cashtransaction ct
                where ct.Tr_Type = 'In Cash'
                and ct.status = 'a'
                and ct.Tr_branchid= " . $this->branch ."
            ) as received_cash,
            (
                 select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
                where bt.transaction_type = 'withdraw'
                and bt.status = 1
                and bt.branch_id= " . $this->branch ."
            ) as bank_withdraw,
            
            /* paid */
			
            (
                select ifnull(sum(pm.PurchaseMaster_PaidAmount), 0) from tbl_purchasemaster pm
                where pm.status = 'a'
                and pm.PurchaseMaster_BranchID= " . $this->branch ."
            ) as paid_purchase,

			(
                select ifnull(sum(pm.own_freight), 0) from tbl_purchasemaster pm
                where pm.status = 'a'
                and pm.PurchaseMaster_BranchID= " . $this->branch ."
            ) as own_freight,

            (
 				select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
                where sp.SPayment_TransactionType = 'CP'
                and sp.SPayment_status = 'a'
                and sp.SPayment_Paymentby != 'bank'
                and sp.SPayment_brunchid= " . $this->branch ."
            ) as paid_supplier,

            (
                select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
                where cp.CPayment_TransactionType = 'CP'
                and cp.CPayment_status = 'a'
                and cp.CPayment_Paymentby != 'bank'
                and cp.CPayment_brunchid= " . $this->branch ."
            ) as paid_customer,

            (
                select ifnull(sum(ct.Out_Amount), 0) from tbl_cashtransaction ct
                where ct.Tr_Type = 'Out Cash'
                and ct.status = 'a'
                and ct.Tr_branchid= " . $this->branch ."
            ) as paid_cash,

            (
             select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
                where bt.transaction_type = 'deposit'
                and bt.status = 1
                and bt.branch_id= " . $this->branch ."
            ) as bank_deposit,

            (
            select ifnull(sum(ep.payment_amount), 0) from tbl_employee_payment ep
                where ep.paymentBranch_id = " . $this->branch . "
                and ep.status = 'a'
            ) as employee_payment,

            
           /* total */
            (
                select received_sales + received_customer + received_supplier + received_cash + bank_withdraw
            ) as total_in,
            (
                select paid_purchase + own_freight + paid_customer + paid_supplier + paid_cash + bank_deposit + employee_payment
            ) as total_out,
            (
                select total_in - total_out
            ) as cash_balance
        ")->row()->cash_balance;


		// Total customer Due lIst
		$dueResult = $this->db->query("
       select
            c.Customer_SlNo,
            c.Customer_Name,
            c.Customer_Code,
            c.Customer_Address,
            c.Customer_Mobile,
            c.owner_name,
            (select ifnull(sum(sm.SaleMaster_TotalSaleAmount), 0.00) + ifnull(c.previous_due, 0.00)
                from tbl_salesmaster sm 
                where sm.SalseCustomer_IDNo = c.Customer_SlNo
                and sm.Status = 'a') as billAmount,

            (select ifnull(sum(sm.SaleMaster_PaidAmount), 0.00)
                from tbl_salesmaster sm
                where sm.SalseCustomer_IDNo = c.Customer_SlNo
                and sm.Status = 'a') as invoicePaid,

            (select ifnull(sum(cp.CPayment_amount), 0.00) 
                from tbl_customer_payment cp 
                where cp.CPayment_customerID = c.Customer_SlNo 
                and cp.CPayment_TransactionType = 'CR'
                and cp.CPayment_status = 'a') as cashReceived,

            (select ifnull(sum(cp.CPayment_amount), 0.00) 
                from tbl_customer_payment cp 
                where cp.CPayment_customerID = c.Customer_SlNo 
                and cp.CPayment_TransactionType = 'CP'
                and cp.CPayment_status = 'a') as paidOutAmount,

            (select ifnull(sum(sr.SaleReturn_ReturnAmount), 0.00) 
                from tbl_salereturn sr 
                join tbl_salesmaster smr on smr.SaleMaster_InvoiceNo = sr.SaleMaster_InvoiceNo 
                where smr.SalseCustomer_IDNo = c.Customer_SlNo 
            ) as returnedAmount,

            (select invoicePaid + cashReceived) as paidAmount,

            (select (billAmount + paidOutAmount) - (paidAmount + returnedAmount)) as dueAmount
            
            from tbl_customer c
            where c.Customer_brunchid = " . $this->branch . "
            order by c.Customer_Name asc
        ")->result();

		$totalDue = 0;
		foreach ($dueResult as $key => $due) {
			$totalDue += $due->dueAmount;
		}

		$responseData = [
			'todays_sale' => $todaysSale,
			'this_month_sale' => $thisMonthSale,
			 'cash_balance' => $cashBalance,
			 'customer_due' => $totalDue,
		];

		echo json_encode($responseData, JSON_NUMERIC_CHECK);
	}
	
}