<?php

class ApiModel extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        error_reporting(0);

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS, POST, GET, PUT");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
    }

    function check_login($username, $password)
    {

        $query = $this->db->query("
            select 
            	u.*,
                b.Brunch_name
            from tbl_user u 
            join tbl_brunch b on b.brunch_id = u.Brunch_ID
            where u.status = 'a'
            and u.User_Name = ?
            and u.User_Password = ?
        ", [$username, md5($password)]);



        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return false;
        }
    }


    function signup($data)
    {
        $this->db->insert('tbl_user', $data);
        return $this->db->insert_id();
    }

    function getRoles()
    {
        $this->db->select('*');
        $this->db->from('role');
        $query = $this->db->get();

        return $query->result_array();
    }

    public function customerDue($clauses = "", $branch)
    {
        $branchId = $branch;
        $dueResult = $this->db->query("
            select
            c.Customer_SlNo,
            c.Customer_Name,
            c.Customer_Code,
            c.Customer_Address,
            c.Customer_Mobile,
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
                where cp.CPayment_customerID = c.Customer_SlNo and cp.CPayment_TransactionType = 'CR'
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
                and sr.Status = 'a'
                ) as returnedAmount,

            (select invoicePaid + cashReceived) as paidAmount,

            (select (billAmount + paidOutAmount) - (paidAmount + returnedAmount)) as dueAmount
            
            from tbl_customer c
            where c.Customer_brunchid = '$branchId' 
            and c.Customer_Type != 'G'
            and c.status = 'a'
            $clauses
        ")->result();

        return $dueResult;
    }
    
    public function supplierDue($clauses = "" , $date = null)
    {
        $branchId = $this->branch;

        $supplierDues = $this->db->query("
            select
            s.Supplier_SlNo,
            s.Supplier_Code,
            s.Supplier_Name,
            s.Supplier_Mobile,
            s.Supplier_Address,
            s.contact_person,
            (select (ifnull(sum(pm.PurchaseMaster_TotalAmount), 0.00) + ifnull(s.previous_due, 0.00)) from tbl_purchasemaster pm
                where pm.Supplier_SlNo = s.Supplier_SlNo
                " . ($date == null ? "" : " and pm.PurchaseMaster_OrderDate < '$date'") . "
                and pm.status = 'a'
            ) as bill,

            (select ifnull(sum(pm2.PurchaseMaster_PaidAmount), 0.00) from tbl_purchasemaster pm2
                where pm2.Supplier_SlNo = s.Supplier_SlNo
                " . ($date == null ? "" : " and pm2.PurchaseMaster_OrderDate < '$date'") . "
                and pm2.status = 'a'
            ) as invoicePaid,

            (select ifnull(sum(sp.SPayment_amount), 0.00) from tbl_supplier_payment sp 
                where sp.SPayment_customerID = s.Supplier_SlNo 
                and sp.SPayment_TransactionType = 'CP'
                " . ($date == null ? "" : " and sp.SPayment_date < '$date'") . "
                and sp.SPayment_status = 'a'
            ) as cashPaid,
                
            (select ifnull(sum(sp2.SPayment_amount), 0.00) from tbl_supplier_payment sp2 
                where sp2.SPayment_customerID = s.Supplier_SlNo 
                and sp2.SPayment_TransactionType = 'CR'
                " . ($date == null ? "" : " and sp2.SPayment_date < '$date'") . "
                and sp2.SPayment_status = 'a'
            ) as cashReceived,

            (select ifnull(sum(pr.PurchaseReturn_ReturnAmount), 0.00) from tbl_purchasereturn pr
                join tbl_purchasemaster rpm on rpm.PurchaseMaster_InvoiceNo = pr.PurchaseMaster_InvoiceNo
                where rpm.Supplier_SlNo = s.Supplier_SlNo
                " . ($date == null ? "" : " and pr.PurchaseReturn_ReturnDate < '$date'") . "
            ) as returned,
            
            (select invoicePaid + cashPaid) as paid,
            
            (select (bill + cashReceived) - (paid + returned)) as due

            from tbl_supplier s
            where s.Supplier_brinchid = '$branchId' $clauses
        ")->result();

        return $supplierDues;
    }

    public function getBankTransactionSummary($accountId = null, $date = null, $branch)
    {
        $branchId = $branch;
        $bankTransactionSummary = $this->db->query("
            select 
                ba.*,
                (
                    select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
                    where bt.account_id = ba.account_id
                    and bt.transaction_type = 'deposit'
                    and bt.status = 1
                    and bt.branch_id = '$branchId'
                    " . ($date == null ? "" : " and bt.transaction_date < '$date'") . "
                ) as total_deposit,
                (
                    select ifnull(sum(bt.amount), 0) from tbl_bank_transactions bt
                    where bt.account_id = ba.account_id
                    and bt.transaction_type = 'withdraw'
                    and bt.status = 1
                    and bt.branch_id = '$branchId'
                    " . ($date == null ? "" : " and bt.transaction_date < '$date'") . "
                ) as total_withdraw,
                (
                    select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
                    where cp.account_id = ba.account_id
                    and cp.CPayment_status = 'a'
                    and cp.CPayment_TransactionType = 'CR'
                    and cp.CPayment_brunchid = '$branchId'
                    " . ($date == null ? "" : " and cp.CPayment_date < '$date'") . "
                ) as total_received_from_customer,
                (
                    select ifnull(sum(cp.CPayment_amount), 0) from tbl_customer_payment cp
                    where cp.account_id = ba.account_id
                    and cp.CPayment_status = 'a'
                    and cp.CPayment_TransactionType = 'CP'
                    and cp.CPayment_brunchid = '$branchId'
                    " . ($date == null ? "" : " and cp.CPayment_date < '$date'") . "
                ) as total_paid_to_customer,
                (
                    select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
                    where sp.account_id = ba.account_id
                    and sp.SPayment_status = 'a'
                    and sp.SPayment_TransactionType = 'CP'
                    and sp.SPayment_brunchid = '$branchId'
                    " . ($date == null ? "" : " and sp.SPayment_date < '$date'") . "
                ) as total_paid_to_supplier,
                (
                    select ifnull(sum(sp.SPayment_amount), 0) from tbl_supplier_payment sp
                    where sp.account_id = ba.account_id
                    and sp.SPayment_status = 'a'
                    and sp.SPayment_TransactionType = 'CR'
                    and sp.SPayment_brunchid = '$branchId'
                    " . ($date == null ? "" : " and sp.SPayment_date < '$date'") . "
                ) as total_received_from_supplier,
                (
                    select (ba.initial_balance + total_deposit + total_received_from_customer + total_received_from_supplier) - (total_withdraw + total_paid_to_customer + total_paid_to_supplier)
                ) as balance
            from tbl_bank_accounts ba
            where ba.branch_id = '$branchId'
            " . ($accountId == null ? "" : " and ba.account_id = '$accountId'") . "
        ")->result();

        return $bankTransactionSummary;
    }
    
    public function getTransactionSummary($date = null)
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

        return $transactionSummary;
    }
}
