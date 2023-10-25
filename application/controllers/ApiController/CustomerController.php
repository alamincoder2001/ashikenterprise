<?php
defined('BASEPATH') or exit('No direct script access allowed');

class CustomerController extends CI_Controller
{
	// public $userdata;
	public $branch;
	public $userFullName;
	public function __construct()
	{
		parent::__construct();
		error_reporting(0);
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
			// $this->userId = $array['id'];
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

	public function addCustomer()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$customerObj = json_decode($this->input->post('data'));

			$customerCodeCount = $this->db->query("select * from tbl_customer where Customer_Code = ?", $customerObj->Customer_Code)->num_rows();
			if ($customerCodeCount > 0) {
				$customerObj->Customer_Code = $this->mt->generateCustomerCode();
			}

			$customerMobileCount = $this->db->query("select * from tbl_customer where Customer_Mobile = ? and Customer_brunchid = ?", [$customerObj->Customer_Mobile, $this->branch])->num_rows();
			if ($customerMobileCount > 0) {
				$res = ['success' => false, 'message' => 'Mobile number already exists'];
				echo Json_encode($res);
				exit;
			}

			$customer = (array)$customerObj;
			$customer["Customer_brunchid"] = $this->branch;
			$customer["AddBy"] = $this->userFullName;
			$customer["AddTime"] = date("Y-m-d H:i:s");

			$this->db->insert('tbl_customer', $customer);
			$customerId = $this->db->insert_id();

			if (!empty($_FILES)) {
				$config['upload_path'] = './uploads/customers/';
				$config['allowed_types'] = 'gif|jpg|png';

				$imageName = $customerObj->Customer_Code;
				$config['file_name'] = $imageName;
				$this->load->library('upload', $config);
				$this->upload->do_upload('image');

				$config['image_library'] = 'gd2';
				$config['source_image'] = './uploads/customers/' . $imageName;
				$config['new_image'] = './uploads/customers/';
				$config['maintain_ratio'] = TRUE;
				$config['width']    = 640;
				$config['height']   = 480;

				$this->load->library('image_lib', $config);
				$this->image_lib->resize();

				$imageName = $customerObj->Customer_Code . $this->upload->data('file_ext');

				$this->db->query("update tbl_customer set image_name = ? where Customer_SlNo = ?", [$imageName, $customerId]);
			}

			$res = ['success' => true, 'message' => 'Customer added successfully', 'customerCode' => $this->mt->generateCustomerCode()];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function updateCustomer()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$customerObj = json_decode($this->input->post('data'));
			$customerMobileCount = $this->db->query("select * from tbl_customer where Customer_Mobile = ? and Customer_brunchid = ? and Customer_SlNo != ?", [$customerObj->Customer_Mobile, $this->cbrunch, $customerObj->Customer_SlNo])->num_rows();
			if ($customerMobileCount > 0) {
				$res = ['success' => false, 'message' => 'Mobile number already exists'];
				echo Json_encode($res);
				exit;
			}
			$customer = (array)$customerObj;
			$customerId = $customerObj->Customer_SlNo;

			unset($customer["Customer_SlNo"]);
			$customer["Customer_brunchid"] = $this->branch;
			$customer["UpdateBy"]          = $this->userFullName;
			$customer["UpdateTime"]        = date("Y-m-d H:i:s");

			$this->db->where('Customer_SlNo', $customerId)->update('tbl_customer', $customer);

			if (!empty($_FILES)) {
				$config['upload_path'] = './uploads/customers/';
				$config['allowed_types'] = 'gif|jpg|png';

				$imageName = $customerObj->Customer_Code;
				$config['file_name'] = $imageName;
				$this->load->library('upload', $config);
				$this->upload->do_upload('image');
				//$imageName = $this->upload->data('file_ext'); /*for geting uploaded image name*/

				$config['image_library']  = 'gd2';
				$config['source_image']   = './uploads/customers/' . $imageName;
				$config['new_image']      = './uploads/customers/';
				$config['maintain_ratio'] = TRUE;
				$config['width']          = 640;
				$config['height']         = 480;

				$this->load->library('image_lib', $config);
				$this->image_lib->resize();

				$imageName = $customerObj->Customer_Code . $this->upload->data('file_ext');

				$this->db->query("update tbl_customer set image_name = ? where Customer_SlNo = ?", [$imageName, $customerId]);
			}

			$res = ['success' => true, 'message' => 'Customer updated successfully', 'customerCode' => $this->mt->generateCustomerCode()];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function deleteCustomer()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$data = json_decode($this->input->raw_input_stream);

			$this->db->query("update tbl_customer set status = 'd' where Customer_SlNo = ?", $data->customerId);

			$res = ['success' => true, 'message' => 'Customer deleted'];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function getCustomers()
	{
		$data = json_decode($this->input->raw_input_stream);

		$date = date("Y-m-d");
		$nameOfDay = date('l', strtotime($date));
		$route = $this->db->query("select * from tbl_work_schedule where employee_id = ? and day = ?", [$data->employeeId, $nameOfDay])->row()->area_id;

		$clauses = "";
		if (isset($data->customerType) && $data->customerType != null) {
			$clauses .= " and Customer_Type = '$data->customerType'";
		}

		if(isset($data->employeeId) && $data->employeeId != null) {
			$clauses .= " and c.Employee_Id = '$data->employeeId'";
		}

		if(isset($route) && $route != '') {
			$clauses .= " and c.area_ID = '$route'";
		}

		$customers = $this->db->query("
            select
                c.*,
                d.District_Name,
				e.Employee_Name,
                concat(c.Customer_Code, ' - ', c.Customer_Name, ' - ', c.owner_name, ' - ', c.Customer_Mobile) as display_name
            from tbl_customer c
            left join tbl_district d on d.District_SlNo = c.area_ID
			left join tbl_employee e on e.Employee_SlNo = c.Employee_Id
            where c.status = 'a'
            and c.Customer_Type != 'G'
            and (c.Customer_brunchid = ? or c.Customer_brunchid = 0)
            $clauses
            order by c.Customer_SlNo desc
        ", $this->branch)->result();
		echo json_encode($customers);
	}

	public function getEmployeeWiseCustomer()
	{
		$customers = $this->db->query("
            select
                c.*,
                d.District_Name,
				e.Employee_Name,
				w.day,
                concat(c.Customer_Code, ' - ', c.Customer_Name, ' - ', c.owner_name, ' - ', c.Customer_Mobile) as display_name
            from tbl_customer c
            left join tbl_work_schedule w on w.employee_id = c.Employee_Id
            left join tbl_district d on d.District_SlNo = c.area_ID
			left join tbl_employee e on e.Employee_SlNo = c.Employee_Id
            where c.status = 'a'
            and c.Customer_Type != 'G'
            and (c.Customer_brunchid = ? or c.Customer_brunchid = 0)
          
            order by c.Customer_SlNo desc
        ", $this->branch)->result();
		echo json_encode($customers);
	}

	public function getCustomerDue()
	{
		$data = json_decode($this->input->raw_input_stream);

		$clauses = "";
		if (isset($data->customerId) && $data->customerId != null) {
			$clauses .= " and c.Customer_SlNo = '$data->customerId'";
		}
		
		if(isset($data->customerType) && $data->customerType != '') {
		    $clauses .= " and c.Customer_Type = '$data->customerType'";
		}

		$dueResult = $this->ApiModel->customerDue($clauses, $this->branch);

		echo json_encode($dueResult);
	}

	public function getCustomerPayments()
	{
		$data = json_decode($this->input->raw_input_stream);

		$clauses = "";
		if (isset($data->paymentType) && $data->paymentType != '' && $data->paymentType == 'received') {
			$clauses .= " and cp.CPayment_TransactionType = 'CR'";
		}
		if (isset($data->paymentType) && $data->paymentType != '' && $data->paymentType == 'paid') {
			$clauses .= " and cp.CPayment_TransactionType = 'CP'";
		}

		if (isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != '') {
			$clauses .= " and cp.CPayment_date between '$data->dateFrom' and '$data->dateTo'";
		}

		if (isset($data->customerId) && $data->customerId != '' && $data->customerId != null) {
			$clauses .= " and cp.CPayment_customerID = '$data->customerId'";
		}

		$payments = $this->db->query("
            select
                cp.*,
                c.Customer_Code,
                c.Customer_Name,
                c.Customer_Mobile,
                c.Customer_Type,
                ba.account_name,
                ba.account_number,
                ba.bank_name,
                case cp.CPayment_TransactionType
                    when 'CR' then 'Received'
                    when 'CP' then 'Paid'
                end as transaction_type,
                case cp.CPayment_Paymentby
                    when 'bank' then concat('Bank - ', ba.account_name, ' - ', ba.account_number, ' - ', ba.bank_name)
                    when 'By Cheque' then 'Cheque'
                    else 'Cash'
                end as payment_by
            from tbl_customer_payment cp
            join tbl_customer c on c.Customer_SlNo = cp.CPayment_customerID
            left join tbl_bank_accounts ba on ba.account_id = cp.account_id
            where cp.CPayment_status = 'a'
            and cp.CPayment_brunchid = ? $clauses
            order by cp.CPayment_id desc
        ", $this->branch)->result();

		echo json_encode($payments);
	}

	public function addCustomerPayment()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$paymentObj = json_decode($this->input->raw_input_stream);

			$payment = (array)$paymentObj;
			$payment['CPayment_invoice'] = $this->mt->generateCustomerPaymentCode();
			$payment['CPayment_status'] = 'a';
			$payment['CPayment_Addby'] = $this->userFullName;
			$payment['CPayment_AddDAte'] = date('Y-m-d H:i:s');
			$payment['CPayment_brunchid'] = $this->branch;

			$this->db->insert('tbl_customer_payment', $payment);
			$paymentId = $this->db->insert_id();

			if ($paymentObj->CPayment_TransactionType == 'CR') {
				$currentDue = $paymentObj->CPayment_previous_due - $paymentObj->CPayment_amount;
				//Send sms
				$customerInfo = $this->db->query("select * from tbl_customer where Customer_SlNo = ?", $paymentObj->CPayment_customerID)->row();
				$sendToName = $customerInfo->Customer_Name;

				$message = "Dear {$sendToName},\nThanks for your payment. Received amount is tk. {$paymentObj->CPayment_amount}. Current due is tk. {$currentDue}. Invoice : {$payment['CPayment_invoice']}";

				$recipient = $customerInfo->Customer_Mobile;
				$this->sms->sendSms($recipient, $message);
			}

			$res = ['success' => true, 'message' => 'Payment added successfully', 'paymentId' => $paymentId];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function updateCustomerPayment()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$paymentObj = json_decode($this->input->raw_input_stream);
			$paymentId = $paymentObj->CPayment_id;

			$payment = (array)$paymentObj;
			unset($payment['CPayment_id']);
			$payment['update_by'] = $this->userFullName;
			$payment['CPayment_UpdateDAte'] = date('Y-m-d H:i:s');

			$this->db->where('CPayment_id', $paymentObj->CPayment_id)->update('tbl_customer_payment', $payment);

			$res = ['success' => true, 'message' => 'Payment updated successfully', 'paymentId' => $paymentId];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function deleteCustomerPayment()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$data = json_decode($this->input->raw_input_stream);

			$this->db->set(['CPayment_status' => 'd'])->where('CPayment_id', $data->paymentId)->update('tbl_customer_payment');

			$res = ['success' => true, 'message' => 'Payment deleted successfully'];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	function getCustomerLedger()
	{
		$data = json_decode($this->input->raw_input_stream);
		$previousDueQuery = $this->db->query("select ifnull(previous_due, 0.00) as previous_due from tbl_customer where Customer_SlNo = '$data->customerId'")->row();

		$payments = $this->db->query("
            select 
                'a' as sequence,
                sm.SaleMaster_SlNo as id,
                sm.SaleMaster_SaleDate as date,
                concat('Sales ', sm.SaleMaster_InvoiceNo) as description,
                sm.SaleMaster_TotalSaleAmount as bill,
                sm.SaleMaster_PaidAmount as paid,
                sm.SaleMaster_DueAmount as due,
                0.00 as returned,
                0.00 as paid_out,
                0.00 as balance
            from tbl_salesmaster sm
            where sm.SalseCustomer_IDNo = '$data->customerId'
            and sm.Status = 'a'
            
            UNION
            select
                'b' as sequence,
                cp.CPayment_id as id,
                cp.CPayment_date as date,
                concat('Received - ', 
                    case cp.CPayment_Paymentby
                        when 'bank' then concat('Bank - ', ba.account_name, ' - ', ba.account_number, ' - ', ba.bank_name)
                        when 'By Cheque' then 'Cheque'
                        else 'Cash'
                    end, ' ', cp.CPayment_notes
                ) as description,
                0.00 as bill,
                cp.CPayment_amount as paid,
                0.00 as due,
                0.00 as returned,
                0.00 as paid_out,
                0.00 as balance
            from tbl_customer_payment cp
            left join tbl_bank_accounts ba on ba.account_id = cp.account_id
            where cp.CPayment_TransactionType = 'CR'
            and cp.CPayment_customerID = '$data->customerId'
            and cp.CPayment_status = 'a'

            UNION
            select
                'c' as sequence,
                cp.CPayment_id as id,
                cp.CPayment_date as date,
                concat('Paid - ', 
                    case cp.CPayment_Paymentby
                        when 'bank' then concat('Bank - ', ba.account_name, ' - ', ba.account_number, ' - ', ba.bank_name)
                        else 'Cash'
                    end, ' ', cp.CPayment_notes
                ) as description,
                0.00 as bill,
                0.00 as paid,
                0.00 as due,
                0.00 as returned,
                cp.CPayment_amount as paid_out,
                0.00 as balance
            from tbl_customer_payment cp
            left join tbl_bank_accounts ba on ba.account_id = cp.account_id
            where cp.CPayment_TransactionType = 'CP'
            and cp.CPayment_customerID = '$data->customerId'
            and cp.CPayment_status = 'a'
            
            UNION
            select
                'd' as sequence,
                sr.SaleReturn_SlNo as id,
                sr.SaleReturn_ReturnDate as date,
                'Sales return' as description,
                0.00 as bill,
                0.00 as paid,
                0.00 as due,
                sr.SaleReturn_ReturnAmount as returned,
                0.00 as paid_out,
                0.00 as balance
            from tbl_salereturn sr
            join tbl_salesmaster smr on smr.SaleMaster_InvoiceNo  = sr.SaleMaster_InvoiceNo
            where smr.SalseCustomer_IDNo = '$data->customerId'
            
            order by date, sequence, id
        ")->result();

		$previousBalance = $previousDueQuery->previous_due;

		foreach ($payments as $key => $payment) {
			$lastBalance = $key == 0 ? $previousDueQuery->previous_due : $payments[$key - 1]->balance;
			$payment->balance = ($lastBalance + $payment->bill + $payment->paid_out) - ($payment->paid + $payment->returned);
		}

		if ((isset($data->dateFrom) && $data->dateFrom != null) && (isset($data->dateTo) && $data->dateTo != null)) {
			$previousPayments = array_filter($payments, function ($payment) use ($data) {
				return $payment->date < $data->dateFrom;
			});

			$previousBalance = count($previousPayments) > 0 ? $previousPayments[count($previousPayments) - 1]->balance : $previousBalance;

			$payments = array_values(array_filter($payments, function ($payment) use ($data) {
				return $payment->date >= $data->dateFrom && $payment->date <= $data->dateTo;
			}));

			$payments = array_values($payments);
		}

		$res['previousBalance'] = $previousBalance;
		$res['payments'] = $payments;
		echo json_encode($res);
	}

	public function getCustomerId()
	{
		$customerId = $this->mt->generateCustomerCode();
		echo json_encode($customerId);
	}
}
