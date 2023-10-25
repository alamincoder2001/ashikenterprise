<?php
defined('BASEPATH') or exit('No direct script access allowed');

class AccountController extends CI_Controller
{
    // public $userdata;
    public $branch;
    public $userId;
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
            // $this->userdata = $token;
            $array = get_object_vars($token);
            $this->branch = $array['branch'];
            $this->userId = $array['id'];
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

    public function getCashTransactions()
    {
        $data = json_decode($this->input->raw_input_stream);

        $dateClause = "";
        if (isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != '') {
            $dateClause = " and ct.Tr_date between '$data->dateFrom' and '$data->dateTo'";
        }

        $transactionTypeClause = "";
        if (isset($data->transactionType) && $data->transactionType != '' && $data->transactionType == 'received') {
            $transactionTypeClause = " and ct.Tr_Type = 'In Cash'";
        }
        if (isset($data->transactionType) && $data->transactionType != '' && $data->transactionType == 'paid') {
            $transactionTypeClause = " and ct.Tr_Type = 'Out Cash'";
        }

        $accountClause = "";
        if (isset($data->accountId) && $data->accountId != '') {
            $accountClause = " and ct.Acc_SlID = '$data->accountId'";
        }

        $transactions = $this->db->query("
            select 
                ct.*,
                a.Acc_Name
            from tbl_cashtransaction ct
            join tbl_account a on a.Acc_SlNo = ct.Acc_SlID
            where ct.status = 'a'
            and ct.Tr_branchid = ?
            $dateClause $transactionTypeClause $accountClause
            order by ct.Tr_SlNo desc
        ", $this->branch)->result();

        echo json_encode($transactions);
    }

    public function getCashTransactionCode()
    {
        echo json_encode($this->mt->generateCashTransactionCode());
    }

    public function addCashTransaction()
    {
        $res = ['success' => false, 'message' => ''];
        try {
            $transactionObj = json_decode($this->input->raw_input_stream);

            $transaction = (array)$transactionObj;
            $transaction['status'] = 'a';
            $transaction['AddBy'] = $this->userFullName;
            $transaction['AddTime'] = date('Y-m-d H:i:s');
            $transaction['Tr_branchid'] = $this->branch;

            $this->db->insert('tbl_cashtransaction', $transaction);

            $res = ['success' => true, 'message' => 'Transaction added'];
        } catch (Exception $ex) {
            $res = ['success' => false, 'message' => $ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function updateCashTransaction()
    {
        $res = ['success' => false, 'message' => ''];
        try {
            $transactionObj = json_decode($this->input->raw_input_stream);

            $transaction = (array)$transactionObj;
            unset($transaction['Tr_SlNo']);
            $transaction['UpdateBy'] = $this->userFullName;
            $transaction['UpdateTime'] = date('Y-m-d H:i:s');

            $this->db->where('Tr_SlNo', $transactionObj->Tr_SlNo)->update('tbl_cashtransaction', $transaction);

            $res = ['success' => true, 'message' => 'Transaction updated'];
        } catch (Exception $ex) {
            $res = ['success' => false, 'message' => $ex->getMessage()];
        }

        echo json_encode($res);
    }
    public function deleteCashTransaction()
    {
        $res = ['success' => false, 'message' => ''];
        try {
            $data = json_decode($this->input->raw_input_stream);

            $this->db->set(['status' => 'd'])->where('Tr_SlNo', $data->transactionId)->update('tbl_cashtransaction');

            $res = ['success' => true, 'message' => 'Transaction deleted'];
        } catch (Exception $ex) {
            $res = ['success' => false, 'message' => $ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function getAccounts()
    {
        $accounts = $this->db->query("select * from tbl_account where status = 'a' and branch_id = ?", $this->branch)->result();
        echo json_encode($accounts);
    }

    public function addBankTransaction()
    {
        $res = ['success' => false, 'message' => ''];
        try {
            $data = json_decode($this->input->raw_input_stream);
            $transaction = (array)$data;
            $transaction['saved_by'] = $this->userId;
            $transaction['saved_datetime'] = date('Y-m-d H:i:s');
            $transaction['branch_id'] = $this->branch;

            $this->db->insert('tbl_bank_transactions', $transaction);

            $res = ['success' => true, 'message' => 'Transaction added successfully'];
        } catch (Exception $ex) {
            $res = ['success' => false, 'message' => $ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function updateBankTransaction()
    {
        $res = ['success' => false, 'message' => ''];
        try {
            $data = json_decode($this->input->raw_input_stream);
            $transactionId = $data->transaction_id;
            $transaction = (array)$data;
            unset($transaction['transaction_id']);
            $transaction['saved_by'] = $this->userId;
            $transaction['saved_datetime'] = date('Y-m-d H:i:s');

            $this->db->where('transaction_id', $transactionId)->update('tbl_bank_transactions', $transaction);

            $res = ['success' => true, 'message' => 'Transaction update successfully'];
        } catch (Exception $ex) {
            $res = ['success' => false, 'message' => $ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function getBankTransactions()
    {
        $data = json_decode($this->input->raw_input_stream);

        $accountClause = "";
        if (isset($data->accountId) && $data->accountId != null) {
            $accountClause = " and bt.account_id = '$data->accountId'";
        }

        $dateClause = "";
        if (
            isset($data->dateFrom) && $data->dateFrom != ''
            && isset($data->dateTo) && $data->dateTo != ''
        ) {
            $dateClause = " and bt.transaction_date between '$data->dateFrom' and '$data->dateTo'";
        }

        $typeClause = "";
        if (isset($data->transactionType) && $data->transactionType != '') {
            $typeClause = " and bt.transaction_type = '$data->transactionType'";
        }


        $transactions = $this->db->query("
            select 
                bt.*,
                ac.account_name,
                ac.account_number,
                ac.bank_name,
                ac.branch_name,
                u.FullName as saved_by
            from tbl_bank_transactions bt
            join tbl_bank_accounts ac on ac.account_id = bt.account_id
            join tbl_user u on u.User_SlNo = bt.saved_by
            where bt.status = 1
            and bt.branch_id = ?
            $accountClause $dateClause $typeClause
            order by bt.transaction_id desc
        ", $this->branch)->result();

        echo json_encode($transactions);
    }

    public function getAllBankTransactions()
    {
        $data = json_decode($this->input->raw_input_stream);

        $clauses = "";
        if (isset($data->accountId) && $data->accountId != null) {
            $clauses .= " and account_id = '$data->accountId'";
        }

        if (
            isset($data->dateFrom) && $data->dateFrom != ''
            && isset($data->dateTo) && $data->dateTo != ''
        ) {
            $clauses .= " and transaction_date between '$data->dateFrom' and '$data->dateTo'";
        }

        if (isset($data->transactionType) && $data->transactionType != '') {
            $clauses .= " and transaction_type = '$data->transactionType'";
        }

        $transactions = $this->db->query("
            select * from(
                select 
                    'a' as sequence,
                    bt.transaction_id as id,
                    bt.transaction_type as description,
                    bt.account_id,
                    bt.transaction_date,
                    bt.transaction_type,
                    bt.amount,
                    bt.note,
                    ac.account_name,
                    ac.account_number,
                    ac.bank_name,
                    ac.branch_name
                from tbl_bank_transactions bt
                join tbl_bank_accounts ac on ac.account_id = bt.account_id
                where bt.status = 1
                and bt.branch_id = " . $this->branch . "
                
                UNION
                select
                    'b' as sequence,
                    cp.CPayment_id as id,
                    concat('payment received - ', c.Customer_Name, ' (', c.Customer_Code, ')') as description, 
                    cp.account_id,
                    cp.CPayment_date as transaction_date,
                    'deposit' as transaction_type,
                    cp.CPayment_amount as amount,
                    cp.CPayment_notes as note,
                    ac.account_name,
                    ac.account_number,
                    ac.bank_name,
                    ac.branch_name
                from tbl_customer_payment cp
                join tbl_bank_accounts ac on ac.account_id = cp.account_id
                join tbl_customer c on c.Customer_SlNo = cp.CPayment_customerID
                where cp.account_id is not null
                and cp.CPayment_status = 'a'
                and cp.CPayment_TransactionType = 'CR'
                and cp.CPayment_brunchid = " . $this->branch . "
                
                UNION
                select
                    'd' as sequence,
                    cp.CPayment_id as id,
                    concat('paid to customer - ', c.Customer_Name, ' (', c.Customer_Code, ')') as description, 
                    cp.account_id,
                    cp.CPayment_date as transaction_date,
                    'withdraw' as transaction_type,
                    cp.CPayment_amount as amount,
                    cp.CPayment_notes as note,
                    ac.account_name,
                    ac.account_number,
                    ac.bank_name,
                    ac.branch_name
                from tbl_customer_payment cp
                join tbl_bank_accounts ac on ac.account_id = cp.account_id
                join tbl_customer c on c.Customer_SlNo = cp.CPayment_customerID
                where cp.account_id is not null
                and cp.CPayment_status = 'a'
                and cp.CPayment_TransactionType = 'CP'
                and cp.CPayment_brunchid = " . $this->branch . "
                
                UNION
                select 
                    'c' as sequence,
                    sp.SPayment_id as id,
                    concat('paid - ', s.Supplier_Name, ' (', s.Supplier_Code, ')') as description, 
                    sp.account_id,
                    sp.SPayment_date as transaction_date,
                    'withdraw' as transaction_type,
                    sp.SPayment_amount as amount,
                    sp.SPayment_notes as note,
                    ac.account_name,
                    ac.account_number,
                    ac.bank_name,
                    ac.branch_name
                from tbl_supplier_payment sp
                join tbl_bank_accounts ac on ac.account_id = sp.account_id
                join tbl_supplier s on s.Supplier_SlNo = sp.SPayment_customerID
                where sp.account_id is not null
                and sp.SPayment_status = 'a'
                and sp.SPayment_TransactionType = 'CP'
                and sp.SPayment_brunchid = " . $this->branch . "
                
                UNION
                select 
                    'e' as sequence,
                    sp.SPayment_id as id,
                    concat('received from supplier - ', s.Supplier_Name, ' (', s.Supplier_Code, ')') as description, 
                    sp.account_id,
                    sp.SPayment_date as transaction_date,
                    'deposit' as transaction_type,
                    sp.SPayment_amount as amount,
                    sp.SPayment_notes as note,
                    ac.account_name,
                    ac.account_number,
                    ac.bank_name,
                    ac.branch_name
                from tbl_supplier_payment sp
                join tbl_bank_accounts ac on ac.account_id = sp.account_id
                join tbl_supplier s on s.Supplier_SlNo = sp.SPayment_customerID
                where sp.account_id is not null
                and sp.SPayment_status = 'a'
                and sp.SPayment_TransactionType = 'CR'
                and sp.SPayment_brunchid = " . $this->branch . "
            ) as tbl
            where 1 = 1 $clauses
            order by transaction_date desc, sequence, id desc
        ")->result();

        echo json_encode($transactions);
    }

    public function removeBankTransaction()
    {
        $res = ['success' => false, 'message' => ''];
        try {
            $data = json_decode($this->input->raw_input_stream);
            $this->db->query("update tbl_bank_transactions set status = 0 where transaction_id = ?", $data->transaction_id);

            $res = ['success' => true, 'message' => 'Transaction removed'];
        } catch (Exception $ex) {
            $res = ['success' => false, 'message' => $ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function getBankBalance()
    {
        $data = json_decode($this->input->raw_input_stream);

        $accountId = null;
        if (isset($data->accountId) && $data->accountId != '') {
            $accountId = $data->accountId;
        }

        $date = null;
        if (isset($data->date) && $data->date != '') {
            $date = $data->date;
        }

        $bankBalance = $this->ApiModel->getBankTransactionSummary($accountId, $date, $this->branch);

        echo json_encode($bankBalance);
    }

    public function getBankAccounts()
    {
        $accounts = $this->db->query("
            select 
            *,
            case status 
            when 1 then 'Active'
            else 'Inactive'
            end as status_text
            from tbl_bank_accounts 
            where branch_id = ?
        ", $this->branch)->result();
        echo json_encode($accounts);
    }




    function getOtherIncomeExpense()
    {
        $data = json_decode($this->input->raw_input_stream);

        $transactionDateClause = "";
        $employePaymentDateClause = "";
        $damageClause = "";
        $returnClause = "";
        if (isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != '') {
            $transactionDateClause = " and ct.Tr_date between '$data->dateFrom' and '$data->dateTo'";
            $employePaymentDateClause = " and ep.payment_date between '$data->dateFrom' and '$data->dateTo'";
            $damageClause = " and d.Damage_Date between '$data->dateFrom' and '$data->dateTo'";
            $returnClause = " and r.SaleReturn_ReturnDate between '$data->dateFrom' and '$data->dateTo'";
        }

        $result = $this->db->query("
            select
            (
                select ifnull(sum(ct.In_Amount), 0)
                from tbl_cashtransaction ct
                where ct.Tr_branchid = '" . $this->branch . "'
                and ct.status = 'a'
                $transactionDateClause
            ) as income,
        
            (
                select ifnull(sum(ct.Out_Amount), 0)
                from tbl_cashtransaction ct
                where ct.Tr_branchid = '" . $this->branch . "'
                and ct.status = 'a'
                $transactionDateClause
            ) as expense,
        
            (
                select ifnull(sum(ep.payment_amount), 0)
                from tbl_employee_payment ep
                where ep.paymentBranch_id = '" . $this->branch . "'
                and ep.status = 'a'
                $employePaymentDateClause
            ) as employee_payment,

            (
                select ifnull(sum(dd.damage_amount), 0) 
                from tbl_damagedetails dd
                join tbl_damage d on d.Damage_SlNo = dd.Damage_SlNo
                where d.Damage_brunchid = '" . $this->branch . "'
                and dd.status = 'a'
                $damageClause
            ) as damaged_amount,

            (
                select ifnull(sum(rd.SaleReturnDetails_ReturnAmount) - sum(sd.Purchase_Rate * rd.SaleReturnDetails_ReturnQuantity), 0)
                from tbl_salereturndetails rd
                join tbl_salereturn r on r.SaleReturn_SlNo = rd.SaleReturn_IdNo
                join tbl_salesmaster sm on sm.SaleMaster_InvoiceNo = r.SaleMaster_InvoiceNo
                join tbl_saledetails sd on sd.Product_IDNo = rd.SaleReturnDetailsProduct_SlNo and sd.SaleMaster_IDNo = sm.SaleMaster_SlNo
                where r.Status = 'a'
                and r.SaleReturn_brunchId = '" . $this->branch . "'
                $returnClause
            ) as returned_amount
        ")->row();

        echo json_encode($result);
    }

    public function cashTransactionSummery($date = null)
    {

        $transactionSummary = $this->db->query("
		select
		/* Received */
		(
			select ifnull(sum(sm.SaleMaster_PaidAmount), 0) from tbl_salesmaster sm
			where sm.SaleMaster_branchid= " . $this->branch . "
			and sm.Status = 'a'
			" . ($date == null ? "" : " and sm.SaleMaster_SaleDate < '$date'") . "
		) as received_sales,
		(
			select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
			where cp.CPayment_TransactionType = 'CR'
			and cp.CPayment_status = 'a'
			and cp.CPayment_Paymentby != 'bank'
			and cp.CPayment_brunchid= " . $this->branch . "
			" . ($date == null ? "" : " and cp.CPayment_date < '$date'") . "
		) as received_customer,
		(
			select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
			where sp.SPayment_TransactionType = 'CR'
			and sp.SPayment_status = 'a'
			and sp.SPayment_Paymentby != 'bank'
			and sp.SPayment_brunchid= " . $this->branch . "
			" . ($date == null ? "" : " and sp.SPayment_date < '$date'") . "
		) as received_supplier,
		(
			select ifnull(sum(ct.In_Amount), 0) from tbl_cashtransaction ct
			where ct.Tr_Type = 'In Cash'
			and ct.status = 'a'
			and ct.Tr_branchid= " . $this->branch . "
			" . ($date == null ? "" : " and ct.Tr_date < '$date'") . "
		) as received_cash,
		(
			select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
			where bt.transaction_type = 'withdraw'
			and bt.status = 1
			and bt.branch_id= " . $this->branch . "
			" . ($date == null ? "" : " and bt.transaction_date < '$date'") . "
		) as bank_withdraw,
		/* paid */
		(
			select ifnull(sum(pm.PurchaseMaster_PaidAmount), 0) from tbl_purchasemaster pm
			where pm.status = 'a'
			and pm.PurchaseMaster_BranchID= " . $this->branch . "
			" . ($date == null ? "" : " and pm.PurchaseMaster_OrderDate < '$date'") . "
		) as paid_purchase,
        (
            select ifnull(sum(pm.own_freight), 0) from tbl_purchasemaster pm
            where pm.status = 'a'
            and pm.PurchaseMaster_BranchID= " . $this->branch . "
            " . ($date == null ? "" : " and pm.PurchaseMaster_OrderDate < '$date'") . "
        ) as own_freight,
		(
			select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
			where sp.SPayment_TransactionType = 'CP'
			and sp.SPayment_status = 'a'
			and sp.SPayment_Paymentby != 'bank'
			and sp.SPayment_brunchid= " . $this->branch . "
			" . ($date == null ? "" : " and sp.SPayment_date < '$date'") . "
		) as paid_supplier,
		(
			select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
			where cp.CPayment_TransactionType = 'CP'
			and cp.CPayment_status = 'a'
			and cp.CPayment_Paymentby != 'bank'
			and cp.CPayment_brunchid= " . $this->branch . "
			" . ($date == null ? "" : " and cp.CPayment_date < '$date'") . "
		) as paid_customer,
		(
			select ifnull(sum(ct.Out_Amount), 0) from tbl_cashtransaction ct
			where ct.Tr_Type = 'Out Cash'
			and ct.status = 'a'
			and ct.Tr_branchid= " . $this->branch . "
			" . ($date == null ? "" : " and ct.Tr_date < '$date'") . "
		) as paid_cash,
		(
			select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
			where bt.transaction_type = 'deposit'
			and bt.status = 1
			and bt.branch_id= " . $this->branch . "
			" . ($date == null ? "" : " and bt.transaction_date < '$date'") . "
		) as bank_deposit,
		(
			select ifnull(sum(ep.payment_amount), 0) from tbl_employee_payment ep
			where ep.paymentBranch_id = " . $this->branch . "
			and ep.status = 'a'
			" . ($date == null ? "" : " and ep.payment_date < '$date'") . "
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
	")->row();

        echo json_encode($transactionSummary);
    }

    public function bankTransactionSummery($date = null, $accountId = null)
    {
        $branchId = $this->branch;

        $bankTransactionSummary = $this->db->query("
            select 
                ba.*,
                (
                    select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
                    where bt.account_id = ba.account_id
                    and bt.transaction_type = 'deposit'
                    and bt.status = 1
                    and bt.branch_id = " . $branchId . "
                    " . ($date == null ? "" : " and bt.transaction_date < '$date'") . "
                ) as total_deposit,
                (
                    select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
                    where bt.account_id = ba.account_id
                    and bt.transaction_type = 'withdraw'
                    and bt.status = 1
                    and bt.branch_id = " . $branchId . "
                    " . ($date == null ? "" : " and bt.transaction_date < '$date'") . "
                ) as total_withdraw,
                (
                    select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
                    where cp.account_id = ba.account_id
                    and cp.CPayment_status = 'a'
                    and cp.CPayment_TransactionType = 'CR'
                    and cp.CPayment_brunchid = " . $branchId . "
                    " . ($date == null ? "" : " and cp.CPayment_date < '$date'") . "
                ) as total_received_from_customer,
                (
                    select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
                    where cp.account_id = ba.account_id
                    and cp.CPayment_status = 'a'
                    and cp.CPayment_TransactionType = 'CP'
                    and cp.CPayment_brunchid = " . $branchId . "
                    " . ($date == null ? "" : " and cp.CPayment_date < '$date'") . "
                ) as total_paid_to_customer,
                (
                    select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
                    where sp.account_id = ba.account_id
                    and sp.SPayment_status = 'a'
                    and sp.SPayment_TransactionType = 'CP'
                    and sp.SPayment_brunchid = " . $branchId . "
                    " . ($date == null ? "" : " and sp.SPayment_date < '$date'") . "
                ) as total_paid_to_supplier,
                (
                    select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
                    where sp.account_id = ba.account_id
                    and sp.SPayment_status = 'a'
                    and sp.SPayment_TransactionType = 'CR'
                    and sp.SPayment_brunchid = " . $branchId . "
                    " . ($date == null ? "" : " and sp.SPayment_date < '$date'") . "
                ) as total_received_from_supplier,
                (
                    select (ba.initial_balance + total_deposit + total_received_from_customer + total_received_from_supplier) - (total_withdraw + total_paid_to_customer + total_paid_to_supplier)
                ) as balance
            from tbl_bank_accounts ba
            where ba.branch_id = " . $branchId . "
            " . ($accountId == null ? "" : " and ba.account_id = '$accountId'") . "
        ")->result();

        echo json_encode($bankTransactionSummary);
    }

    public function getCashView($accountId = null, $date = null)
    {


        $res['transaction_summary'] =  $this->db->query("SELECT
            /* Received */
            (
                select ifnull(sum(sm.SaleMaster_PaidAmount), 0) from tbl_salesmaster sm
                where sm.SaleMaster_branchid= " . $this->branch . "
                and sm.Status = 'a'
                " . ($date == null ? "" : " and sm.SaleMaster_SaleDate < '$date'") . "
            ) as received_sales,
            (
                select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
                where cp.CPayment_TransactionType = 'CR'
                and cp.CPayment_status = 'a'
                and cp.CPayment_Paymentby != 'bank'
                and cp.CPayment_brunchid= " . $this->branch . "
                " . ($date == null ? "" : " and cp.CPayment_date < '$date'") . "
            ) as received_customer,
            (
                select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
                where sp.SPayment_TransactionType = 'CR'
                and sp.SPayment_status = 'a'
                and sp.SPayment_Paymentby != 'bank'
                and sp.SPayment_brunchid= " . $this->branch . "
                " . ($date == null ? "" : " and sp.SPayment_date < '$date'") . "
            ) as received_supplier,
            (
                select ifnull(sum(ct.In_Amount), 0) from tbl_cashtransaction ct
                where ct.Tr_Type = 'In Cash'
                and ct.status = 'a'
                and ct.Tr_branchid= " . $this->branch . "
                " . ($date == null ? "" : " and ct.Tr_date < '$date'") . "
            ) as received_cash,
            (
                select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
                where bt.transaction_type = 'withdraw'
                and bt.status = 1
                and bt.branch_id= " . $this->branch . "
                " . ($date == null ? "" : " and bt.transaction_date < '$date'") . "
            ) as bank_withdraw,
            /* paid */
            (
                select ifnull(sum(pm.PurchaseMaster_PaidAmount), 0) from tbl_purchasemaster pm
                where pm.status = 'a'
                and pm.PurchaseMaster_BranchID= " . $this->branch . "
                " . ($date == null ? "" : " and pm.PurchaseMaster_OrderDate < '$date'") . "
            ) as paid_purchase,
            (
                select ifnull(sum(pm.own_freight), 0) from tbl_purchasemaster pm
                where pm.status = 'a'
                and pm.PurchaseMaster_BranchID= " . $this->branch . "
                " . ($date == null ? "" : " and pm.PurchaseMaster_OrderDate < '$date'") . "
            ) as own_freight,
            (
                select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
                where sp.SPayment_TransactionType = 'CP'
                and sp.SPayment_status = 'a'
                and sp.SPayment_Paymentby != 'bank'
                and sp.SPayment_brunchid= " . $this->branch . "
                " . ($date == null ? "" : " and sp.SPayment_date < '$date'") . "
            ) as paid_supplier,
            (
                select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
                where cp.CPayment_TransactionType = 'CP'
                and cp.CPayment_status = 'a'
                and cp.CPayment_Paymentby != 'bank'
                and cp.CPayment_brunchid= " . $this->branch . "
                " . ($date == null ? "" : " and cp.CPayment_date < '$date'") . "
            ) as paid_customer,
            (
                select ifnull(sum(ct.Out_Amount), 0) from tbl_cashtransaction ct
                where ct.Tr_Type = 'Out Cash'
                and ct.status = 'a'
                and ct.Tr_branchid= " . $this->branch . "
                " . ($date == null ? "" : " and ct.Tr_date < '$date'") . "
            ) as paid_cash,
            (
                select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
                where bt.transaction_type = 'deposit'
                and bt.status = 1
                and bt.branch_id= " . $this->branch . "
                " . ($date == null ? "" : " and bt.transaction_date < '$date'") . "
            ) as bank_deposit,
            (
                select ifnull(sum(ep.payment_amount), 0) from tbl_employee_payment ep
                where ep.paymentBranch_id = " . $this->branch . "
                and ep.status = 'a'
                " . ($date == null ? "" : " and ep.payment_date < '$date'") . "
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
        ")->row();

        $res['bank_account_summary'] = $this->db->query("
            select 
                ba.*,
                (
                    select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
                    where bt.account_id = ba.account_id
                    and bt.transaction_type = 'deposit'
                    and bt.status = 1
                    and bt.branch_id = " . $this->branch . "
                    " . ($date == null ? "" : " and bt.transaction_date < '$date'") . "
                ) as total_deposit,
                (
                    select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
                    where bt.account_id = ba.account_id
                    and bt.transaction_type = 'withdraw'
                    and bt.status = 1
                    and bt.branch_id = " . $this->branch . "
                    " . ($date == null ? "" : " and bt.transaction_date < '$date'") . "
                ) as total_withdraw,
                (
                    select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
                    where cp.account_id = ba.account_id
                    and cp.CPayment_status = 'a'
                    and cp.CPayment_TransactionType = 'CR'
                    and cp.CPayment_brunchid = " . $this->branch . "
                    " . ($date == null ? "" : " and cp.CPayment_date < '$date'") . "
                ) as total_received_from_customer,
                (
                    select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
                    where cp.account_id = ba.account_id
                    and cp.CPayment_status = 'a'
                    and cp.CPayment_TransactionType = 'CP'
                    and cp.CPayment_brunchid = " . $this->branch . "
                    " . ($date == null ? "" : " and cp.CPayment_date < '$date'") . "
                ) as total_paid_to_customer,
                (
                    select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
                    where sp.account_id = ba.account_id
                    and sp.SPayment_status = 'a'
                    and sp.SPayment_TransactionType = 'CP'
                    and sp.SPayment_brunchid = " . $this->branch . "
                    " . ($date == null ? "" : " and sp.SPayment_date < '$date'") . "
                ) as total_paid_to_supplier,
                (
                    select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
                    where sp.account_id = ba.account_id
                    and sp.SPayment_status = 'a'
                    and sp.SPayment_TransactionType = 'CR'
                    and sp.SPayment_brunchid = " . $this->branch . "
                    " . ($date == null ? "" : " and sp.SPayment_date < '$date'") . "
                ) as total_received_from_supplier,
                (
                    select (ba.initial_balance + total_deposit + total_received_from_customer + total_received_from_supplier) - (total_withdraw + total_paid_to_customer + total_paid_to_supplier)
                ) as balance
            from tbl_bank_accounts ba
            where ba.branch_id = " . $this->branch . "
            " . ($accountId == null ? "" : " and ba.account_id = '$accountId'") . "
        ")->result();

        echo json_encode($res);
    }
    
    public function getBankLedger()
    {
        $data = json_decode($this->input->raw_input_stream);
        $res = new stdClass;

        $clauses = "";
        $order = "transaction_date desc, sequence, id desc";

        if(isset($data->accountId) && $data->accountId != null){
            $clauses .= " and account_id = '$data->accountId'";
        }

        if(isset($data->dateFrom) && $data->dateFrom != '' 
        && isset($data->dateTo) && $data->dateTo != ''){
            $clauses .= " and transaction_date between '$data->dateFrom' and '$data->dateTo'";
        }

        if(isset($data->transactionType) && $data->transactionType != ''){
            $clauses .= " and transaction_type = '$data->transactionType'";
        }

        if(isset($data->ledger)) {
            $order = "transaction_date, sequence, id";
        }

        $transactions = $this->db->query("
            select * from(
                select 
                    'a' as sequence,
                    bt.transaction_id as id,
                    bt.transaction_type as description,
                    bt.account_id,
                    bt.transaction_date,
                    bt.transaction_type,
                    bt.amount as deposit,
                    0.00 as withdraw,
                    bt.note,
                    ac.account_name,
                    ac.account_number,
                    ac.bank_name,
                    ac.branch_name,
                    0.00 as balance
                from tbl_bank_transactions bt
                join tbl_bank_accounts ac on ac.account_id = bt.account_id
                where bt.status = 1
                and bt.transaction_type = 'deposit'
                and bt.branch_id = " . $this->branch . "

                UNION
                select 
                    'b' as sequence,
                    bt.transaction_id as id,
                    bt.transaction_type as description,
                    bt.account_id,
                    bt.transaction_date,
                    bt.transaction_type,
                    0.00 as deposit,
                    bt.amount as withdraw,
                    bt.note,
                    ac.account_name,
                    ac.account_number,
                    ac.bank_name,
                    ac.branch_name,
                    0.00 as balance
                from tbl_bank_transactions bt
                join tbl_bank_accounts ac on ac.account_id = bt.account_id
                where bt.status = 1
                and bt.transaction_type = 'withdraw'
                and bt.branch_id = " . $this->branch . "
                
                UNION
                select
                    'c' as sequence,
                    cp.CPayment_id as id,
                    concat('Payment Received - ', c.Customer_Name, ' (', c.Customer_Code, ')') as description, 
                    cp.account_id,
                    cp.CPayment_date as transaction_date,
                    'deposit' as transaction_type,
                    cp.CPayment_amount as deposit,
                    0.00 as withdraw,
                    cp.CPayment_notes as note,
                    ac.account_name,
                    ac.account_number,
                    ac.bank_name,
                    ac.branch_name,
                    0.00 as balance
                from tbl_customer_payment cp
                join tbl_bank_accounts ac on ac.account_id = cp.account_id
                join tbl_customer c on c.Customer_SlNo = cp.CPayment_customerID
                where cp.account_id is not null
                and cp.CPayment_status = 'a'
                and cp.CPayment_TransactionType = 'CR'
                and cp.CPayment_brunchid = " . $this->branch . "
                
                UNION
                select
                    'd' as sequence,
                    cp.CPayment_id as id,
                    concat('paid to customer - ', c.Customer_Name, ' (', c.Customer_Code, ')') as description, 
                    cp.account_id,
                    cp.CPayment_date as transaction_date,
                    'withdraw' as transaction_type,
                    0.00 as deposit,
                    cp.CPayment_amount as withdraw,
                    cp.CPayment_notes as note,
                    ac.account_name,
                    ac.account_number,
                    ac.bank_name,
                    ac.branch_name,
                    0.00 as balance
                from tbl_customer_payment cp
                join tbl_bank_accounts ac on ac.account_id = cp.account_id
                join tbl_customer c on c.Customer_SlNo = cp.CPayment_customerID
                where cp.account_id is not null
                and cp.CPayment_status = 'a'
                and cp.CPayment_TransactionType = 'CP'
                and cp.CPayment_brunchid = " . $this->branch . "
                
                UNION
                select 
                    'e' as sequence,
                    sp.SPayment_id as id,
                    concat('paid - ', s.Supplier_Name, ' (', s.Supplier_Code, ')') as description, 
                    sp.account_id,
                    sp.SPayment_date as transaction_date,
                    'withdraw' as transaction_type,
                    0.00 as deposit,
                    sp.SPayment_amount as withdraw,
                    sp.SPayment_notes as note,
                    ac.account_name,
                    ac.account_number,
                    ac.bank_name,
                    ac.branch_name,
                    0.00 as balance
                from tbl_supplier_payment sp
                join tbl_bank_accounts ac on ac.account_id = sp.account_id
                join tbl_supplier s on s.Supplier_SlNo = sp.SPayment_customerID
                where sp.account_id is not null
                and sp.SPayment_status = 'a'
                and sp.SPayment_TransactionType = 'CP'
                and sp.SPayment_brunchid = " . $this->branch . "
                
                UNION
                select 
                    'f' as sequence,
                    sp.SPayment_id as id,
                    concat('received from supplier - ', s.Supplier_Name, ' (', s.Supplier_Code, ')') as description, 
                    sp.account_id,
                    sp.SPayment_date as transaction_date,
                    'deposit' as transaction_type,
                    sp.SPayment_amount as deposit,
                    0.00 as withdraw,
                    sp.SPayment_notes as note,
                    ac.account_name,
                    ac.account_number,
                    ac.bank_name,
                    ac.branch_name,
                    0.00 as balance
                from tbl_supplier_payment sp
                join tbl_bank_accounts ac on ac.account_id = sp.account_id
                join tbl_supplier s on s.Supplier_SlNo = sp.SPayment_customerID
                where sp.account_id is not null
                and sp.SPayment_status = 'a'
                and sp.SPayment_TransactionType = 'CR'
                and sp.SPayment_brunchid = " . $this->branch. "
            ) as tbl
            where 1 = 1 $clauses
            order by $order
        ")->result();

        $previousBalance = $this->getBankTransactionSummary($data->accountId, $data->dateFrom)[0]->balance;

        $transactions = array_map(function($key, $trn) use($previousBalance, $transactions) {
            $trn->balance = (($key == 0 ? $previousBalance : $transactions[$key - 1]->balance) + $trn->deposit) - $trn->withdraw;
            return $trn;
        }, array_keys($transactions), $transactions);
        
        $res->previousBalance = $previousBalance;
        $res->transactions = $transactions;

        echo json_encode($res);
    }
    
    public function getBankTransactionSummary($accountId = null, $date = null)
     {
        $bankTransactionSummary = $this->db->query("
            select 
                ba.*,
                (
                    select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
                    where bt.account_id = ba.account_id
                    and bt.transaction_type = 'deposit'
                    and bt.status = 1
                    and bt.branch_id = " . $this->branch . "
                    " . ($date == null ? "" : " and bt.transaction_date < '$date'") . "
                ) as total_deposit,
                (
                    select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
                    where bt.account_id = ba.account_id
                    and bt.transaction_type = 'withdraw'
                    and bt.status = 1
                    and bt.branch_id = " . $this->branch . "
                    " . ($date == null ? "" : " and bt.transaction_date < '$date'") . "
                ) as total_withdraw,
                (
                    select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
                    where cp.account_id = ba.account_id
                    and cp.CPayment_status = 'a'
                    and cp.CPayment_TransactionType = 'CR'
                    and cp.CPayment_brunchid = " . $this->branch . "
                    " . ($date == null ? "" : " and cp.CPayment_date < '$date'") . "
                ) as total_received_from_customer,
                (
                    select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
                    where cp.account_id = ba.account_id
                    and cp.CPayment_status = 'a'
                    and cp.CPayment_TransactionType = 'CP'
                    and cp.CPayment_brunchid = " . $this->branch . "
                    " . ($date == null ? "" : " and cp.CPayment_date < '$date'") . "
                ) as total_paid_to_customer,
                (
                    select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
                    where sp.account_id = ba.account_id
                    and sp.SPayment_status = 'a'
                    and sp.SPayment_TransactionType = 'CP'
                    and sp.SPayment_brunchid = " . $this->branch . "
                    " . ($date == null ? "" : " and sp.SPayment_date < '$date'") . "
                ) as total_paid_to_supplier,
                (
                    select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
                    where sp.account_id = ba.account_id
                    and sp.SPayment_status = 'a'
                    and sp.SPayment_TransactionType = 'CR'
                    and sp.SPayment_brunchid = " . $this->branch . "
                    " . ($date == null ? "" : " and sp.SPayment_date < '$date'") . "
                ) as total_received_from_supplier,
                (
                    select (ba.initial_balance + total_deposit + total_received_from_customer + total_received_from_supplier) - (total_withdraw + total_paid_to_customer + total_paid_to_supplier)
                ) as balance
            from tbl_bank_accounts ba
            where ba.branch_id = " . $this->branch . "
            " . ($accountId == null ? "" : " and ba.account_id = '$accountId'") . "
        ")->result();

        return $bankTransactionSummary;
    }
    
    public function getBalanceReport()
    {
        $res = [ 'success'   => false, 'message'  => 'Invalid' ];

        try {
            $branchId = $this->branch;
            $data       = json_decode($this->input->raw_input_stream);
            $date       = null;

            if(isset($data->date) && $data->date != ''){
                list(, , $last_date) = explode('-', $data->date);
                $last_date +=1;
                $date = substr_replace($data->date, $last_date, 8);
            }
            
            $cash_balance = $this->ApiModel->getTransactionSummary($date)->cash_balance;
            
            $bank_accounts = $this->ApiModel->getBankTransactionSummary(null, $date, $this->branch);

            $customer_prev_due = $this->db->query("
                SELECT ifnull(sum(previous_due), 0) as amount
                from tbl_customer
                where Customer_brunchid = '$this->branch'
            ")->row()->amount;

            //customer dues
            $customer_dues = $this->customerDue(" and c.status = 'a'", $date);
        
            
            $bad_debts = $this->customerDue(" and c.status = 'd'", $date);

            $wholesale_due = $this->customerDue(" and c.status = 'a' and c.Customer_Type = 'wholesale'", $date);
            $retail_due = $this->customerDue(" and c.status = 'a' and c.Customer_Type = 'retail'", $date);

            $customer_dues = array_reduce($customer_dues, function($prev, $curr){ return $prev + $curr->dueAmount;});
            $bad_debts = array_reduce($bad_debts, function($prev, $curr){ return $prev + $curr->dueAmount;});
            $wholesale_due = array_reduce($wholesale_due, function($prev, $curr){ return $prev + $curr->dueAmount;});
            $retail_due = array_reduce($retail_due, function($prev, $curr){ return $prev + $curr->dueAmount;});
            
            //stock values
            $stock_value =  $this->getBranchStockValue($branchId);
            $kbEntrprise = [];
            if($branchId == 5) {
                $kbEntrprise['enterprise'] = $this->getBranchStockValue(5);
                $kbEntrprise['sementgodwon2'] = $this->getBranchStockValue(4);
            }

            if($branchId == 1) {
                $kbEntrprise['agrofood'] = $this->getBranchStockValue(1);
                $kbEntrprise['challgodwon2'] = $this->getBranchStockValue(2);
                $kbEntrprise['challgodwon3'] = $this->getBranchStockValue(3);
            }

            //supplier prev due adjust
            $supplier_prev_due = $this->db->query("
                SELECT ifnull(sum(previous_due), 0) as amount
                from tbl_supplier
                where Supplier_brinchid = '$this->branch'
            ")->row()->amount;
            

            //supplier due
            $supplier_dues = $this->ApiModel->supplierDue("", $date);

            $total_supplier_dues = array_reduce($supplier_dues, function($prev, $curr){ return $prev + $curr->due;});

            //profit loss
            $sales = $this->db->query("
                select 
                    sm.*
                from tbl_salesmaster sm
                where sm.SaleMaster_branchid = ? 
                and sm.Status = 'a'
                " . ($date == null ? "" : " and sm.SaleMaster_SaleDate < '$date'") . "
            ", $this->branch)->result();
            

            foreach($sales as $sale){
                $sale->saleDetails = $this->db->query("
                    select
                        sd.*,
                        (sd.Purchase_Rate * sd.SaleDetails_TotalQuantity) as purchased_amount,
                        (select sd.SaleDetails_TotalAmount - purchased_amount) as profit_loss
                    from tbl_saledetails sd
                    where sd.SaleMaster_IDNo = ?
                ", $sale->SaleMaster_SlNo)->result();
            }

            $profits = array_reduce($sales, function($prev, $curr){ 
                return $prev + array_reduce($curr->saleDetails, function($p, $c){
                    return $p + $c->profit_loss;
                });
            });
            

            $total_transport_cost = array_reduce($sales, function($prev, $curr){ 
                return $prev + $curr->SaleMaster_Freight;
            });
            
            $total_discount = array_reduce($sales, function($prev, $curr){ 
                return $prev + $curr->SaleMaster_TotalDiscountAmount;
            });

            $total_vat = array_reduce($sales, function($prev, $curr){ 
                return $prev + $curr->SaleMaster_TaxAmount;
            });
            


            $other_income_expense = $this->db->query("
                select
                (
                    select ifnull(sum(ct.In_Amount), 0)
                    from tbl_cashtransaction ct
                    where ct.Tr_branchid = '" . $this->branch . "'
                    and ct.status = 'a'
                    " . ($date == null ? "" : " and ct.Tr_date < '$date'") . "
                ) as income,
            
                (
                    select ifnull(sum(ct.Out_Amount), 0)
                    from tbl_cashtransaction ct
                    where ct.Tr_branchid = '" . $this->branch. "'
                    and ct.status = 'a'
                    " . ($date == null ? "" : " and ct.Tr_date < '$date'") . "
                ) as expense,
                /*
                (
                    select ifnull(sum(it.amount), 0)
                    from tbl_investment_transactions it
                    where it.branch_id = '" . $this->branch. "'
                    and it.transaction_type = 'Profit'
                    and it.status = 1
                    " . ($date == null ? "" : " and it.transaction_date < '$date'") . "
                ) as profit_distribute,

                (
                    select ifnull(sum(lt.amount), 0)
                    from tbl_loan_transactions lt
                    where lt.branch_id = '" . $this->branch. "'
                    and lt.transaction_type = 'Interest'
                    and lt.status = 1
                    " . ($date == null ? "" : " and lt.transaction_date < '$date'") . "
                ) as loan_interest,

                (
                    select ifnull(sum(a.valuation - a.as_amount), 0)
                    from tbl_assets a
                    where a.branchid = '" . $this->branch . "'
                    and a.buy_or_sale = 'sale'
                    and a.status = 'a'
                    " . ($date == null ? "" : " and a.as_date < '$date'") . "
                ) as assets_sales_profit_loss,
                */
            
                (
                    select ifnull(sum(ep.payment_amount), 0)
                    from tbl_employee_payment ep
                    where ep.paymentBranch_id = '" . $this->branch . "'
                    and ep.status = 'a'
                    " . ($date == null ? "" : " and ep.payment_date < '$date'") . "
                ) as employee_payment,

                (
                    select ifnull(sum(dd.damage_amount), 0) 
                    from tbl_damagedetails dd
                    join tbl_damage d on d.Damage_SlNo = dd.Damage_SlNo
                    where d.Damage_brunchid = '" . $this->branch . "'
                    and dd.status = 'a'
                    " . ($date == null ? "" : " and d.Damage_Date  < '$date'") . "
                ) as damaged_amount,

                (
                    select ifnull(sum(rd.SaleReturnDetails_ReturnAmount) - sum(sd.Purchase_Rate * rd.SaleReturnDetails_ReturnQuantity), 0)
                    from tbl_salereturndetails rd
                    join tbl_salereturn r on r.SaleReturn_SlNo = rd.SaleReturn_IdNo
                    join tbl_salesmaster sm on sm.SaleMaster_InvoiceNo = r.SaleMaster_InvoiceNo
                    join tbl_saledetails sd on sd.Product_IDNo = rd.SaleReturnDetailsProduct_SlNo and sd.SaleMaster_IDNo = sm.SaleMaster_SlNo
                    where r.Status = 'a'
                    and r.SaleReturn_brunchId = '" . $this->branch. "'
                    " . ($date == null ? "" : " and r.SaleReturn_ReturnDate  < '$date'") . "
                ) as returned_amount
            ")->row();

            // $net_profit = ($profits + $total_transport_cost + $other_income_expense->income + $total_vat) - ($total_discount + $other_income_expense->returned_amount + $other_income_expense->damaged_amount + $other_income_expense->expense + $other_income_expense->employee_payment + $other_income_expense->profit_distribute + $other_income_expense->loan_interest + $other_income_expense->assets_sales_profit_loss );

            $net_profit = ($profits + $total_transport_cost + $other_income_expense->income + $total_vat) - ($total_discount + $other_income_expense->returned_amount + $other_income_expense->damaged_amount + $other_income_expense->expense + $other_income_expense->employee_payment );
           


            $statements = [
                // 'assets'            => $assets,
                'cash_balance'          => $cash_balance,
                'bank_accounts'         => $bank_accounts,
                // 'loan_accounts'     => $loan_accounts,
                // 'invest_accounts'   => $invest_accounts,
                'customer_dues'         => $customer_dues,
                'wholesale_dues'        => $wholesale_due,
                'retail_dues'           => $retail_due,
                'supplier_dues'         => $supplier_dues,
                'total_supplier_dues'   => $total_supplier_dues,
                'bad_debts'             => $bad_debts,
                'supplier_prev_due'     => $supplier_prev_due,
                'customer_prev_due'     => $customer_prev_due,
                'stock_value'           => $stock_value,
                'kbenterprise'          => $kbEntrprise,
                'net_profit'            => $net_profit,
            ];

            $res = [ 'success'   => true, 'statements'  => $statements ];

        } catch (Exception $ex){
            $res = ['success'=>false, 'message'=>$ex->getMessage()];
        }

        echo json_encode($res);
    }
    
     public function getBranchStockValue($branchId) 
     {
        $stocks = $this->db->query("
            select * from (
                select
                    ci.*,
                    (select (ci.purchase_quantity + ci.sales_return_quantity + ci.transfer_to_quantity) - (ci.sales_quantity + ci.purchase_return_quantity + ci.damage_quantity + ci.transfer_from_quantity)) as current_quantity,
                    (select (p.Product_Purchase_Rate * current_quantity)) as stock_value
                from tbl_currentinventory ci
                join tbl_product p on p.Product_SlNo = ci.product_id
                where ci.branch_id = ?)as tbl
            where stock_value > 0
        ", $branchId)->result();

        $stock_value = array_sum(array_map(function($product){
            return $product->stock_value;
        }, $stocks));
       
        return $stock_value;
    }
    
    public function customerDue($clauses = "", $date = null) 
    {
        $branchId = $this->branch;
        $dueResult = $this->db->query("
            select
            (select ifnull(sum(sm.SaleMaster_TotalSaleAmount), 0.00) + ifnull(c.previous_due, 0.00)
                from tbl_salesmaster sm 
                where sm.SalseCustomer_IDNo = c.Customer_SlNo
                " . ($date == null ? "" : " and sm.SaleMaster_SaleDate < '$date'") . "
                and sm.Status = 'a') as billAmount,

            (select ifnull(sum(sm.SaleMaster_PaidAmount), 0.00)
                from tbl_salesmaster sm
                where sm.SalseCustomer_IDNo = c.Customer_SlNo
                " . ($date == null ? "" : " and sm.SaleMaster_SaleDate < '$date'") . "
                and sm.Status = 'a') as invoicePaid,

            (select ifnull(sum(cp.CPayment_amount), 0.00) 
                from tbl_customer_payment cp 
                where cp.CPayment_customerID = c.Customer_SlNo 
                and cp.CPayment_TransactionType = 'CR'
                " . ($date == null ? "" : " and cp.CPayment_date < '$date'") . "
                and cp.CPayment_status = 'a') as cashReceived,

            (select ifnull(sum(cp.CPayment_amount), 0.00) 
                from tbl_customer_payment cp 
                where cp.CPayment_customerID = c.Customer_SlNo 
                and cp.CPayment_TransactionType = 'CP'
                " . ($date == null ? "" : " and cp.CPayment_date < '$date'") . "
                and cp.CPayment_status = 'a') as paidOutAmount,

            (select ifnull(sum(sr.SaleReturn_ReturnAmount), 0.00) 
                from tbl_salereturn sr 
                join tbl_salesmaster smr on smr.SaleMaster_InvoiceNo = sr.SaleMaster_InvoiceNo 
                where smr.SalseCustomer_IDNo = c.Customer_SlNo 
                and sr.Status = 'a'
                " . ($date == null ? "" : " and sr.SaleReturn_ReturnDate < '$date'") . "
            ) as returnedAmount,

            (select invoicePaid + cashReceived) as paidAmount,

            (select (billAmount + paidOutAmount) - (paidAmount + returnedAmount)) as dueAmount
            
            from tbl_customer c
            where c.Customer_brunchid = '$branchId' 
            and c.Customer_Type != 'G'
            $clauses
            order by c.Customer_Name asc
        ")->result();

        return $dueResult;
    }
}
