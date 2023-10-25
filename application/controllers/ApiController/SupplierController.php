<?php
defined('BASEPATH') or exit('No direct script access allowed');

class SupplierController extends CI_Controller
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
            // $this->userdata = $token;
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


    public function addSupplier()
    {
        $res = ['success' => false, 'message' => ''];
        try {
            $supplierObj = json_decode($this->input->post('data'));
            $supplierCodeCount = $this->db->query("select * from tbl_supplier where Supplier_Code = ?", $supplierObj->Supplier_Code)->num_rows();
            if ($supplierCodeCount > 0) {
                $supplierObj->Supplier_Code = $this->mt->generateSupplierCode();
            }

            $supplierMobileCount = $this->db->query("select * from tbl_supplier where Supplier_Mobile = ?", $supplierObj->Supplier_Mobile)->num_rows();
            if ($supplierMobileCount > 0) {
                $res = ['success' => false, 'message' => 'Mobile number already exists'];
                echo Json_encode($res);
                exit;
            }

            $supplier = (array)$supplierObj;

            $supplier["Supplier_brinchid"] = $this->branch;
            $supplier["AddBy"]             = $this->userFullName;
            $supplier["AddTime"]           = date("Y-m-d H:i:s");

            $this->db->insert('tbl_supplier', $supplier);
            $supplierId = $this->db->insert_id();

            if (!empty($_FILES)) {
                $config['upload_path'] = './uploads/suppliers/';
                $config['allowed_types'] = 'gif|jpg|png';

                $imageName = $supplierObj->Supplier_Code;
                $config['file_name'] = $imageName;
                $this->load->library('upload', $config);
                $this->upload->do_upload('image');
                //$imageName = $this->upload->data('file_ext'); /*for geting uploaded image name*/

                $config['image_library'] = 'gd2';
                $config['source_image'] = './uploads/suppliers/' . $imageName;
                $config['new_image'] = './uploads/suppliers/';
                $config['maintain_ratio'] = TRUE;
                $config['width']    = 640;
                $config['height']   = 480;

                $this->load->library('image_lib', $config);
                $this->image_lib->resize();

                $imageName = $supplierObj->Supplier_Code . $this->upload->data('file_ext');

                $this->db->query("update tbl_supplier set image_name = ? where Supplier_SlNo = ?", [$imageName, $supplierId]);
            }

            $res = ['success' => true, 'message' => 'Supplier added successfully', 'supplierCode' => $this->mt->generateSupplierCode()];
        } catch (Exception $ex) {
            $res = ['success' => false, 'message' => $ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function updateSupplier()
    {
        $res = ['success' => false, 'message' => ''];
        try {
            $supplierObj = json_decode($this->input->post('data'));
            $supplierMobileCount = $this->db->query("select * from tbl_supplier where Supplier_Mobile = ? and Supplier_SlNo != ?", [$supplierObj->Supplier_Mobile, $supplierObj->Supplier_SlNo])->num_rows();
            if ($supplierMobileCount > 0) {
                $res = ['success' => false, 'message' => 'Mobile number already exists'];
                echo Json_encode($res);
                exit;
            }
            $supplier   = (array)$supplierObj;
            $supplierId = $supplierObj->Supplier_SlNo;

            unset($supplier["Supplier_SlNo"]);
            $supplier["Supplier_brinchid"] = $this->branch;
            $supplier["UpdateBy"]          = $this->userFullName;
            $supplier["UpdateTime"]        = date("Y-m-d H:i:s");

            $this->db->where('Supplier_SlNo', $supplierId)->update('tbl_supplier', $supplier);

            if (!empty($_FILES)) {
                $config['upload_path'] = './uploads/suppliers/';
                $config['allowed_types'] = 'gif|jpg|png';

                $imageName           = $supplierObj->Supplier_Code;
                $config['file_name'] = $imageName;
                $this->load->library('upload', $config);
                $this->upload->do_upload('image');
                //$imageName = $this->upload->data('file_ext'); /*for geting uploaded image name*/

                $config['image_library'] = 'gd2';
                $config['source_image'] = './uploads/suppliers/' . $imageName;
                $config['new_image'] = './uploads/suppliers/';
                $config['maintain_ratio'] = TRUE;
                $config['width']    = 640;
                $config['height']   = 480;

                $this->load->library('image_lib', $config);
                $this->image_lib->resize();

                $imageName = $supplierObj->Supplier_Code . $this->upload->data('file_ext');

                $this->db->query("update tbl_supplier set image_name = ? where Supplier_SlNo = ?", [$imageName, $supplierId]);
            }

            $res = ['success' => true, 'message' => 'Supplier updated successfully', 'supplierCode' => $this->mt->generateSupplierCode()];
        } catch (Exception $ex) {
            $res = ['success' => false, 'message' => $ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function deleteSupplier()
    {
        $res = ['success' => false, 'message' => ''];
        try {
            $data = json_decode($this->input->raw_input_stream);

            $this->db->query("update tbl_supplier set status = 'd' where Supplier_SlNo = ?", $data->supplierId);

            $res = ['success' => true, 'message' => 'Supplier deleted'];
        } catch (Exception $ex) {
            $res = ['success' => false, 'message' => $ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function getSuppliers()
    {
        $suppliers = $this->db->query("SELECT *,
            concat(Supplier_Code, ' - ', Supplier_Name) as display_name
            from tbl_supplier
            where Status = 'a'
            and Supplier_Type != 'G'
            and Supplier_brinchid = ? or Supplier_brinchid = 0
            order by Supplier_SlNo desc
        ", $this->branch)->result();

        echo json_encode($suppliers);
    }

    public function getSupplierDue()
    {
        $data = json_decode($this->input->raw_input_stream);
        $branchId = $this->branch;

        $supplierClause = "";
        if (isset($data->supplierId) && $data->supplierId != null) {
            $supplierClause = " and s.Supplier_SlNo = '$data->supplierId'";
        }
        $supplierDues = $this->db->query("SELECT
            s.Supplier_SlNo,
            s.Supplier_Code,
            s.Supplier_Name,
            s.Supplier_Mobile,
            s.Supplier_Address,
            (select (ifnull(sum(pm.PurchaseMaster_TotalAmount), 0.00) + ifnull(s.previous_due, 0.00)) from tbl_purchasemaster pm
                where pm.Supplier_SlNo = s.Supplier_SlNo
                and pm.status = 'a') as bill,

            (select ifnull(sum(pm2.PurchaseMaster_PaidAmount), 0.00) from tbl_purchasemaster pm2
                where pm2.Supplier_SlNo = s.Supplier_SlNo
                and pm2.status = 'a') as invoicePaid,

            (select ifnull(sum(sp.SPayment_amount), 0.00) from tbl_supplier_payment sp 
                where sp.SPayment_customerID = s.Supplier_SlNo 
                and sp.SPayment_TransactionType = 'CP'
                and sp.SPayment_status = 'a') as cashPaid,
                
            (select ifnull(sum(sp2.SPayment_amount), 0.00) from tbl_supplier_payment sp2 
                where sp2.SPayment_customerID = s.Supplier_SlNo 
                and sp2.SPayment_TransactionType = 'CR'
                and sp2.SPayment_status = 'a') as cashReceived,

            (select ifnull(sum(pr.PurchaseReturn_ReturnAmount), 0.00) from tbl_purchasereturn pr
                join tbl_purchasemaster rpm on rpm.PurchaseMaster_InvoiceNo = pr.PurchaseMaster_InvoiceNo
                where rpm.Supplier_SlNo = s.Supplier_SlNo) as returned,
            
            (select invoicePaid + cashPaid) as paid,
            
            (select (bill + cashReceived) - (paid + returned)) as due

            from tbl_supplier s
            where s.Supplier_brinchid = '$branchId' $supplierClause
        ")->result();

        echo json_encode($supplierDues);
    }

    public function getSupplierLedger()
    {
        $data = json_decode($this->input->raw_input_stream);
        $previousDueQuery = $this->db->query("select ifnull(previous_due, 0.00) as previous_due from tbl_supplier where Supplier_SlNo = ?", $data->supplierId)->row();
        $payments = $this->db->query("SELECT
                'a' as sequence,
                pm.PurchaseMaster_SlNo as id,
                pm.PurchaseMaster_OrderDate date,
                concat('Purchase ', pm.PurchaseMaster_InvoiceNo) as description,
                pm.PurchaseMaster_TotalAmount as bill,
                pm.PurchaseMaster_PaidAmount as paid,
                (pm.PurchaseMaster_TotalAmount - pm.PurchaseMaster_PaidAmount) as due,
                0.00 as returned,
                0.00 as cash_received,
                0.00 as balance
            from tbl_purchasemaster pm
            where pm.Supplier_SlNo = '$data->supplierId'
            and pm.status = 'a'
            
            UNION
            select
                'b' as sequence,
                sp.SPayment_id as id,
                sp.SPayment_date as date,
                concat('Paid - ', 
                    case sp.SPayment_Paymentby
                        when 'bank' then concat('Bank - ', ba.account_name, ' - ', ba.account_number, ' - ', ba.bank_name)
                        else 'Cash'
                    end, ' ', sp.SPayment_notes
                ) as description,
                0.00 as bill,
                sp.SPayment_amount as paid,
                0.00 as due,
                0.00 as returned,
                0.00 as cash_received,
                0.00 as balance
            from tbl_supplier_payment sp 
            left join tbl_bank_accounts ba on ba.account_id = sp.account_id
            where sp.SPayment_customerID = '$data->supplierId'
            and sp.SPayment_TransactionType = 'CP'
            and sp.SPayment_status = 'a'
            
            UNION
            select 
                'c' as sequence,
                sp2.SPayment_id as id,
                sp2.SPayment_date as date,
                concat('Received - ', 
                    case sp2.SPayment_Paymentby
                        when 'bank' then concat('Bank - ', ba.account_name, ' - ', ba.account_number, ' - ', ba.bank_name)
                        else 'Cash'
                    end, ' ', sp2.SPayment_notes
                ) as description,
                0.00 as bill,
                0.00 as paid,
                0.00 as due,
                0.00 as returned,
                sp2.SPayment_amount as cash_received,
                0.00 as balance
            from tbl_supplier_payment sp2
            left join tbl_bank_accounts ba on ba.account_id = sp2.account_id
            where sp2.SPayment_customerID = '$data->supplierId'
            and sp2.SPayment_TransactionType = 'CR'
            and sp2.SPayment_status = 'a'
            
            UNION
            select
                'd' as sequence,
                pr.PurchaseReturn_SlNo as id,
                pr.PurchaseReturn_ReturnDate as date,
                'Purchase Return' as description,
                0.00 as bill,
                0.00 as paid,
                0.00 as due,
                pr.PurchaseReturn_ReturnAmount as returned,
                0.00 as cash_received,
                0.00 as balance
            from tbl_purchasereturn pr
            where pr.Supplier_IDdNo = '$data->supplierId'
            
            order by date, sequence, id
        ")->result();

        $previousBalance = $previousDueQuery->previous_due;

        foreach ($payments as $key => $payment) {
            $lastBalance = $key == 0 ? $previousDueQuery->previous_due : $payments[$key - 1]->balance;
            $payment->balance = ($lastBalance + $payment->bill + $payment->cash_received) - ($payment->paid + $payment->returned);
        }

        if ((isset($data->dateFrom) && $data->dateFrom != null) && (isset($data->dateTo) && $data->dateTo != null)) {
            $previousPayments = array_filter($payments, function ($payment) use ($data) {
                return $payment->date < $data->dateFrom;
            });

            $previousBalance = count($previousPayments) > 0 ? $previousPayments[count($previousPayments) - 1]->balance : $previousBalance;

            $payments = array_values(array_filter($payments, function ($payment) use ($data) {
                return $payment->date >= $data->dateFrom && $payment->date <= $data->dateTo;
            }));
        }

        $res['previousBalance'] = $previousBalance;
        $res['payments'] = $payments;
        echo json_encode($res);
    }

    public function addSupplierPayment()
    {
        $res = ['success' => false, 'message' => ''];
        try {
            $paymentObj = json_decode($this->input->raw_input_stream);

            $payment = (array)$paymentObj;
            $payment['SPayment_invoice'] = $this->mt->generateSupplierPaymentCode();
            $payment['SPayment_status'] = 'a';
            $payment['SPayment_Addby'] = $this->userFullName;
            $payment['SPayment_AddDAte'] = date('Y-m-d H:i:s');
            $payment['SPayment_brunchid'] = $this->branch;

            $this->db->insert('tbl_supplier_payment', $payment);
            $paymentId = $this->db->insert_id();

            $res = ['success' => true, 'message' => 'Payment added successfully', 'paymentId' => $paymentId];
        } catch (Exception $ex) {
            $res = ['success' => false, 'message' => $ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function updateSupplierPayment()
    {
        $res = ['success' => false, 'message' => ''];
        try {
            $paymentObj = json_decode($this->input->raw_input_stream);
            $paymentId = $paymentObj->SPayment_id;

            $payment = (array)$paymentObj;
            unset($payment['SPayment_id']);
            $payment['update_by'] = $this->userFullName;
            $payment['SPayment_UpdateDAte'] = date('Y-m-d H:i:s');

            $this->db->where('SPayment_id', $paymentObj->SPayment_id)->update('tbl_supplier_payment', $payment);

            $res = ['success' => true, 'message' => 'Payment updated successfully', 'paymentId' => $paymentId];
        } catch (Exception $ex) {
            $res = ['success' => false, 'message' => $ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function getSupplierPayments()
    {
        $data = json_decode($this->input->raw_input_stream);

        $paymentTypeClause = "";
        if (isset($data->paymentType) && $data->paymentType != '' && $data->paymentType == 'received') {
            $paymentTypeClause = " and sp.SPayment_TransactionType = 'CR'";
        }
        if (isset($data->paymentType) && $data->paymentType != '' && $data->paymentType == 'paid') {
            $paymentTypeClause = " and sp.SPayment_TransactionType = 'CP'";
        }

        $dateClause = "";
        if (isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != '') {
            $dateClause = " and sp.SPayment_date between '$data->dateFrom' and '$data->dateTo'";
        }

        $payments = $this->db->query("
            select
                sp.*,
                s.Supplier_Code,
                s.Supplier_Name,
                s.Supplier_Mobile,
                ba.account_name,
                ba.account_number,
                ba.bank_name,
                case sp.SPayment_TransactionType
                when 'CR' then 'Received'
                    when 'CP' then 'Paid'
                end as transaction_type,
                case sp.SPayment_Paymentby
                    when 'bank' then concat('Bank - ', ba.account_name, ' - ', ba.account_number, ' - ', ba.bank_name)
                    else 'Cash'
                end as payment_by
            from tbl_supplier_payment sp
            left join tbl_bank_accounts ba on ba.account_id = sp.account_id
            join tbl_supplier s on s.Supplier_SlNo = sp.SPayment_customerID
            where sp.SPayment_status = 'a'
            and sp.SPayment_brunchid = ? $paymentTypeClause $dateClause
            order by sp.SPayment_id desc
        ", $this->branch)->result();

        echo json_encode($payments);
    }

    public function deleteSupplierPayment()
    {
        $res = ['success' => false, 'message' => ''];
        try {
            $data = json_decode($this->input->raw_input_stream);

            $this->db->set(['SPayment_status' => 'd'])->where('SPayment_id', $data->paymentId)->update('tbl_supplier_payment');

            $res = ['success' => true, 'message' => 'Payment deleted successfully'];
        } catch (Exception $ex) {
            $res = ['success' => false, 'message' => $ex->getMessage()];
        }

        echo json_encode($res);
    }

    public function getSupplierId()
    {
        $customerId = $this->mt->generateSupplierCode();
        echo json_encode($customerId);
    }
}
