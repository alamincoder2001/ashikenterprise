<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PurchaseController extends CI_Controller
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

	public function addPurchase()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$data = json_decode($this->input->raw_input_stream);
			$invoice = $data->purchase->invoice;
			$invoiceCount = $this->db->query("select * from tbl_purchasemaster where PurchaseMaster_InvoiceNo = ?", $invoice)->num_rows();
			if ($invoiceCount != 0) {
				$invoice = $this->mt->generatePurchaseInvoice();
			}
			$supplierId = $data->purchase->supplierId;
			if (isset($data->supplier)) {
				$supplier = (array)$data->supplier;
				unset($supplier['Supplier_SlNo']);
				unset($supplier['display_name']);
				$supplier['Supplier_Code'] = $this->mt->generateSupplierCode();
				$supplier['Status'] = 'a';
				$supplier['AddBy'] = $this->userFullName;
				$supplier['AddTime'] = date('Y-m-d H:i:s');
				$supplier['Supplier_brinchid'] = $this->branch;
				$this->db->insert('tbl_supplier', $supplier);
				$supplierId = $this->db->insert_id();
			}

			$purchase = array(
				'Supplier_SlNo' => $supplierId,
				'PurchaseMaster_InvoiceNo' => $invoice,
				'PurchaseMaster_OrderDate' => $data->purchase->purchaseDate,
				'PurchaseMaster_PurchaseFor' => $data->purchase->purchaseFor,
				'PurchaseMaster_TotalAmount' => $data->purchase->total,
				'PurchaseMaster_DiscountAmount' => $data->purchase->discount,
				'PurchaseMaster_Tax' => $data->purchase->vat,
				'PurchaseMaster_Freight' => $data->purchase->freight,
				'own_freight' => $data->purchase->ownFreight,
				'PurchaseMaster_SubTotalAmount' => $data->purchase->subTotal,
				'PurchaseMaster_PaidAmount' => $data->purchase->paid,
				'PurchaseMaster_DueAmount' => $data->purchase->due,
				'previous_due' => $data->purchase->previousDue,
				'PurchaseMaster_Description' => $data->purchase->note,
				'status' => 'a',
				'AddBy' => $this->userFullName,
				'AddTime' => date('Y-m-d H:i:s'),
				'PurchaseMaster_BranchID' => $this->branch,
			);
			$this->db->insert('tbl_purchasemaster', $purchase);
			$purchaseId = $this->db->insert_id();

			$totalQty = array_reduce($data->cartProducts, function ($item1, $item2) {
				return $item1 + $item2->quantity;
			}, 0);

			$transportCostDiv = $data->purchase->ownFreight / $totalQty;
			foreach ($data->cartProducts as $product) {
				$newProductId = $product->productId;
				if ($product->productId == "new") {
					// Add new product script
					$productCode = $this->mt->generateProductCode();
					$getUnit = $this->db->query("SELECT Unit_SlNo FROM tbl_unit ORDER BY Unit_SlNo DESC")->row();
					$productData = array(
						'Product_Code' => $productCode,
						'Product_Name' => $product->name,
						'ProductCategory_ID' => $product->categoryId,
						'Product_Purchase_Rate' => $product->purchaseRate,
						'Product_SellingPrice' => $product->salesRate,
						'Product_branchid' => $product->branchId,
						'Product_Code' => $productCode,
						'Product_branchid' => $product->branchId,
						'status' => 'a',
						'AddBy' => $this->userFullName,
						'AddTime' => date('Y-m-d H:i:s'),
						'Unit_ID' => $getUnit->Unit_SlNo
					);
					$this->db->insert('tbl_product', $productData);
					$newProductId = $this->db->insert_id();
					// end new product script
				}
				$productId =  $newProductId;
				$purchaseDetails = array(
					'PurchaseMaster_IDNo' => $purchaseId,
					'Product_IDNo' => $productId,
					'PurchaseDetails_TotalQuantity' => $product->quantity,
					'PurchaseDetails_Rate' => $product->purchaseRate,
					'PurchaseDetails_TotalAmount' => $product->total,
					'Status' => 'a',
					'AddBy' => $this->userFullName,
					'AddTime' => date('Y-m-d H:i:s'),
					'PurchaseDetails_branchID' => $product->branchId,
					'cat_id' => $product->categoryId
				);
				$this->db->insert('tbl_purchasedetails', $purchaseDetails);
				$inventoryCount = $this->db->query("select * from tbl_currentinventory where product_id = ? and branch_id = ? and cat_id=?", [$product->productId, $product->branchId, $product->categoryId])->num_rows();
				if ($inventoryCount == 0) {
					$inventory = array(
						'product_id' => $productId,
						'purchase_quantity' => $product->quantity,
						'branch_id' => $product->branchId,
						'cat_id' => $product->categoryId
					);
					$this->db->insert('tbl_currentinventory', $inventory);
				} else {
					$this->db->query("
                        update tbl_currentinventory 
                        set purchase_quantity = purchase_quantity + ? 
                        where product_id = ? 
                        and branch_id = ? and cat_id=?
                    ", [$product->quantity, $productId, $product->branchId, $product->categoryId]);
				}

				$newPurchaseRate = $product->purchaseRate + $transportCostDiv;
				$this->db->query("update tbl_product set Product_Purchase_Rate = ? , Product_SellingPrice = ? where Product_SlNo = ? and ProductCategory_ID=?", [$newPurchaseRate, $product->salesRate, $productId, $product->categoryId]);
			}
			$res = ['success' => true, 'message' => 'Purchase Success', 'purchaseId' => $purchaseId];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}
		echo json_encode($res);
	}

	public function updatePurchase()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$data = json_decode($this->input->raw_input_stream);
			$purchaseId = $data->purchase->purchaseId;
			if (isset($data->supplier)) {
				$supplier = (array)$data->supplier;
				unset($supplier['Supplier_SlNo']);
				unset($supplier['display_name']);
				$supplier['UpdateBy'] = $this->userFullName;
				$supplier['UpdateTime'] = date('Y-m-d H:i:s');

				$this->db->where('Supplier_SlNo', $data->purchase->supplierId)->update('tbl_supplier', $supplier);
			}
			$purchase = array(
				'Supplier_SlNo' => $data->purchase->supplierId,
				'PurchaseMaster_InvoiceNo' => $data->purchase->invoice,
				'PurchaseMaster_OrderDate' => $data->purchase->purchaseDate,
				'PurchaseMaster_PurchaseFor' => $data->purchase->purchaseFor,
				'PurchaseMaster_TotalAmount' => $data->purchase->total,
				'PurchaseMaster_DiscountAmount' => $data->purchase->discount,
				'PurchaseMaster_Tax' => $data->purchase->vat,
				'PurchaseMaster_Freight' => $data->purchase->freight,
				'own_freight' => $data->purchase->ownFreight,
				'PurchaseMaster_SubTotalAmount' => $data->purchase->subTotal,
				'PurchaseMaster_PaidAmount' => $data->purchase->paid,
				'PurchaseMaster_DueAmount' => $data->purchase->due,
				'previous_due' => $data->purchase->previousDue,
				'PurchaseMaster_Description' => $data->purchase->note,
				'status' => 'a',
				'UpdateBy' => $this->userFullName,
				'UpdateTime' => date('Y-m-d H:i:s'),
				'PurchaseMaster_BranchID' => $this->branch
			);

			$this->db->where('PurchaseMaster_SlNo', $purchaseId);
			$this->db->update('tbl_purchasemaster', $purchase);
			$oldPurchaseDetails = $this->db->query("select * from tbl_purchasedetails where PurchaseMaster_IDNo = ?", $purchaseId)->result();
			$this->db->query("delete from tbl_purchasedetails where PurchaseMaster_IDNo = ?", $purchaseId);

			foreach ($oldPurchaseDetails as $product) {
				$this->db->query("
                    update tbl_currentinventory 
                    set purchase_quantity = purchase_quantity - ? 
                    where product_id = ?
                    and branch_id = ?
                    and cat_id = ?
                ", [$product->PurchaseDetails_TotalQuantity, $product->Product_IDNo, $product->PurchaseDetails_branchID, $product->cat_id]);
			}

			foreach ($data->cartProducts as $product) {
				$newProductId = $product->productId;
				if ($product->productId == "new") {
					// Add new product script
					$productCode = $this->mt->generateProductCode();
					$getUnit = $this->db->query("SELECT Unit_SlNo FROM tbl_unit ORDER BY Unit_SlNo DESC")->row();
					$productData = array(
						'Product_Code' => $productCode,
						'Product_Name' => $product->name,
						'ProductCategory_ID' => $product->categoryId,
						'Product_Purchase_Rate' => $product->purchaseRate,
						'Product_SellingPrice' => $product->salesRate,
						'Product_Code' => $productCode,
						'Product_branchid' => $product->branchId,
						'status' => 'a',
						'AddBy' => $this->userFullName,
						'AddTime' => date('Y-m-d H:i:s'),
						'Unit_ID' => $getUnit->Unit_SlNo
					);
					$this->db->insert('tbl_product', $productData);
					$newProductId = $this->db->insert_id();
					// end new product script
				}
				$productId =  $newProductId;
				$purchaseDetails = array(
					'PurchaseMaster_IDNo' => $purchaseId,
					'Product_IDNo' => $productId,
					'PurchaseDetails_TotalQuantity' => $product->quantity,
					'PurchaseDetails_Rate' => $product->purchaseRate,
					'PurchaseDetails_TotalAmount' => $product->total,
					'Status' => 'a',
					'UpdateBy' => $this->userFullName,
					'UpdateTime' => date('Y-m-d H:i:s'),
					'PurchaseDetails_branchID' => $product->branchId,
					'cat_id' => $product->categoryId
				);
				$this->db->insert('tbl_purchasedetails', $purchaseDetails);

				$inventoryCount = $this->db->query("select * from tbl_currentinventory where product_id = ? and branch_id = ? and cat_id=?", [$productId,  $product->branchId, $product->categoryId])->num_rows();
				if ($inventoryCount == 0) {
					$inventory = array(
						'product_id' => $productId,
						'purchase_quantity' => $product->quantity,
						'branch_id' =>  $product->branchId,
						'cat_id' => $product->categoryId
					);
					$this->db->insert('tbl_currentinventory', $inventory);
				} else {
					$this->db->query("
                        update tbl_currentinventory 
                        set purchase_quantity = purchase_quantity + ? 
                        where product_id = ?
                        and branch_id = ?
                        and cat_id=?
                    ", [$product->quantity, $productId, $product->branchId, $product->categoryId]);
				}
			}

			$res = ['success' => true, 'message' => 'Purchase Success', 'purchaseId' => $purchaseId];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function getPurchases()
	{
		$data = json_decode($this->input->raw_input_stream);
		$branchId = $this->branch;

		$dateClause = "";
		if (isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != '') {
			$dateClause = " and pm.PurchaseMaster_OrderDate between '$data->dateFrom' and '$data->dateTo'";
		}

		$purchaseIdClause = "";
		if (isset($data->purchaseId) && $data->purchaseId != null) {
			$purchaseIdClause = " and pm.PurchaseMaster_SlNo = '$data->purchaseId'";

			$res['purchaseDetails'] = $this->db->query("
                select
                    pd.*,
                    p.Product_Name,
                    p.Product_Code,
                    p.ProductCategory_ID,
                    p.Product_SellingPrice,
                    pc.ProductCategory_Name,
                    u.Unit_Name,
                    b.Brunch_name,
                    b.brunch_id
                from tbl_purchasedetails pd 
                join tbl_product p on p.Product_SlNo = pd.Product_IDNo
                join tbl_productcategory pc on pc.ProductCategory_SlNo = pd.cat_id
                join tbl_unit u on u.Unit_SlNo = p.Unit_ID
                join  tbl_brunch b on b.brunch_id = pd.PurchaseDetails_branchID 
                where pd.PurchaseMaster_IDNo = '$data->purchaseId'
            ")->result();
		}
		$purchases = $this->db->query("
            select
            pm.*,
            s.Supplier_Name,
            s.Supplier_Mobile,
            s.Supplier_Email,
            s.Supplier_Code,
            s.Supplier_Address,
            s.Supplier_Type
            from tbl_purchasemaster pm
            join tbl_supplier s on s.Supplier_SlNo = pm.Supplier_SlNo
            where pm.PurchaseMaster_BranchID = '$branchId' 
            and pm.status = 'a'
            $purchaseIdClause $dateClause
            order by pm.PurchaseMaster_SlNo desc
        ")->result();

		$res['purchases'] = $purchases;
		echo json_encode($res);
	}

	public function  deletePurchase()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$data = json_decode($this->input->raw_input_stream);
			$purchase = $this->db->select('*')->where('PurchaseMaster_SlNo', $data->purchaseId)->get('tbl_purchasemaster')->row();
			if ($purchase->status != 'a') {
				$res = ['success' => false, 'message' => 'Purchase not found'];
				echo json_encode($res);
				exit;
			}

			/*Get Purchase Details Data*/
			$purchaseDetails = $this->db->query("
                select 
                    pd.*,
                    p.Product_Name
                from tbl_purchasedetails pd
                join tbl_product p on p.Product_SlNo = pd.Product_IDNo
                where pd.PurchaseMaster_IDNo = ?
            ", $data->purchaseId)->result();

			foreach ($purchaseDetails as $product) {
				$stock = $this->db->where(['product_id' => $product->Product_IDNo, 'branch_id' => $purchase->PurchaseMaster_BranchID])->get('tbl_currentinventory')->row();
				$stockQuantity = (($stock->purchase_quantity + $stock->transfer_to_quantity + $stock->sales_return_quantity)
					- ($stock->sales_quantity + $stock->purchase_return_quantity + $stock->damage_quantity + $stock->transfer_from_quantity)) - $product->PurchaseDetails_TotalQuantity;

				if ($stockQuantity < 0) {
					$res = ['success' => false, 'message' => "{$product->Product_Name} stock will negative. Purchase can not be deleted"];
					echo json_encode($res);
					exit;
				}
			}

			foreach ($purchaseDetails as $detail) {
				/*Get Product Current Quantity*/
				$totalQty = $this->db->where(['product_id' => $detail->Product_IDNo, 'branch_id' => $purchase->PurchaseMaster_BranchID])->get('tbl_currentinventory')->row()->purchase_quantity;

				/* Subtract Product Quantity form  Current Quantity  */
				$newQty = $totalQty - $detail->PurchaseDetails_TotalQuantity;

				/*Update Purchase Inventory*/
				$this->db->set('purchase_quantity', $newQty)->where(['product_id' => $detail->Product_IDNo, 'branch_id' => $purchase->PurchaseMaster_BranchID])->update('tbl_currentinventory');
			}

			/*Delete Purchase Details*/
			$this->db->set('Status', 'd')->where('PurchaseMaster_IDNo', $data->purchaseId)->update('tbl_purchasedetails');

			/*Delete Purchase Master Data*/
			$this->db->set('status', 'd')->where('PurchaseMaster_SlNo', $data->purchaseId)->update('tbl_purchasemaster');

			$res = ['success' => true, 'message' => 'Successfully deleted'];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}
	public function getPurchaseRecord()
	{
		$data = json_decode($this->input->raw_input_stream);
		$branchId = $this->branch;

		$clauses = "";
		if (isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != '') {
			$clauses .= " and pm.PurchaseMaster_OrderDate between '$data->dateFrom' and '$data->dateTo'";
		}

		if (isset($data->userFullName) && $data->userFullName != '') {
			$clauses .= " and pm.AddBy = '$data->userFullName'";
		}

		$purchases = $this->db->query("
            select 
                pm.*,
                s.Supplier_Code,
                s.Supplier_Name,
                s.Supplier_Mobile,
                s.Supplier_Address,
                br.Brunch_name
            from tbl_purchasemaster pm
            left join tbl_supplier s on s.Supplier_SlNo = pm.Supplier_SlNo
            left join tbl_brunch br on br.brunch_id = pm.PurchaseMaster_BranchID
            where pm.PurchaseMaster_BranchID = '$branchId'
            and pm.status = 'a'
            $clauses
        ")->result();

		foreach ($purchases as $purchase) {
			$purchase->purchaseDetails = $this->db->query("
                select 
                    pd.*,
                    p.Product_Name,
                    pc.ProductCategory_Name
                from tbl_purchasedetails pd
                join tbl_product p on p.Product_SlNo = pd.Product_IDNo
                join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
                where pd.PurchaseMaster_IDNo = ?
                and pd.Status != 'd'
            ", $purchase->PurchaseMaster_SlNo)->result();
		}

		echo json_encode($purchases);
	}

	// MATERIAL PARTS START-------------------------------------------------------------------------------

	public function addMaterialPurchase()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$data = json_decode($this->input->raw_input_stream);

			$countPurchaseCode = $this->db->query("select * from tbl_material_purchase where invoice_no = ?", $data->purchase->invoice_no)->num_rows();
			if ($countPurchaseCode > 0) {
				$data->purchase->invoice_no = $this->mt->generateMaterialPurchaseCode();
			}

			$supplierId = $data->purchase->supplier_id;
			if (isset($data->supplier)) {
				$supplier = (array)$data->supplier;
				unset($supplier['Supplier_SlNo']);
				unset($supplier['display_name']);
				$supplier['Supplier_Code']     = $this->mt->generateSupplierCode();
				$supplier['Status']            = 'a';
				$supplier['AddBy']             = $this->userFullName;
				$supplier['AddTime']           = date('Y-m-d H:i:s');
				$supplier['Supplier_brinchid'] = $this->branch;

				$this->db->insert('tbl_supplier', $supplier);
				$supplierId = $this->db->insert_id();
			}

			$purchase = array(
				"supplier_id"    => $supplierId,
				"invoice_no"     => $data->purchase->invoice_no,
				"purchase_date"  => $data->purchase->purchase_date,
				"purchase_for"   => $data->purchase->purchase_for,
				"sub_total"      => $data->purchase->sub_total,
				"vat"            => $data->purchase->vat,
				"transport_cost" => $data->purchase->transport_cost,
				"discount"       => $data->purchase->discount,
				"total"          => $data->purchase->total,
				"paid"           => $data->purchase->paid,
				"due"            => $data->purchase->due,
				"previous_due"   => $data->purchase->previous_due,
				"note"           => $data->purchase->note,
				"status"         => 'a'
			);
			$this->db->insert('tbl_material_purchase', $purchase);
			$lastId = $this->db->insert_id();

			foreach ($data->purchasedMaterials as $purchasedMaterial) {
				$pm = array(
					"purchase_id"   => $lastId,
					"material_id"   => $purchasedMaterial->material_id,
					"purchase_rate" => $purchasedMaterial->purchase_rate,
					"quantity"      => $purchasedMaterial->quantity,
					"total"         => $purchasedMaterial->total,
					"status"        => 'a'
				);
				$this->db->insert('tbl_material_purchase_details', $pm);
			}

			$res = ['success' => true, 'message' => 'Material Purchase Success', 'purchaseId' => $lastId];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function updateMaterialPurchase()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$data = json_decode($this->input->raw_input_stream);

			if (isset($data->supplier)) {
				$supplier = (array)$data->supplier;
				unset($supplier['Supplier_SlNo']);
				unset($supplier['display_name']);
				$supplier['UpdateBy']   = $this->userFullName;
				$supplier['UpdateTime'] = date('Y-m-d H:i:s');

				$this->db->where('Supplier_SlNo', $data->purchase->supplier_id)->update('tbl_supplier', $supplier);
			}

			$purchase = array(
				"supplier_id"    => $data->purchase->supplier_id,
				"invoice_no"     => $data->purchase->invoice_no,
				"purchase_date"  => $data->purchase->purchase_date,
				"purchase_for"   => $data->purchase->purchase_for,
				"sub_total"      => $data->purchase->sub_total,
				"vat"            => $data->purchase->vat,
				"transport_cost" => $data->purchase->transport_cost,
				"discount"       => $data->purchase->discount,
				"total"          => $data->purchase->total,
				"paid"           => $data->purchase->paid,
				"due"            => $data->purchase->due,
				"previous_due"   => $data->purchase->previous_due,
				"note"           => $data->purchase->note
			);
			$this->db->where('purchase_id', $data->purchase->purchase_id);
			$this->db->set($purchase);
			$this->db->update('tbl_material_purchase');

			$this->db->delete('tbl_material_purchase_details', array('purchase_id' => $data->purchase->purchase_id));

			foreach ($data->purchasedMaterials as $purchasedMaterial) {
				$pm = array(
					"purchase_id"   => $data->purchase->purchase_id,
					"material_id"   => $purchasedMaterial->material_id,
					"purchase_rate" => $purchasedMaterial->purchase_rate,
					"quantity"      => $purchasedMaterial->quantity,
					"total"         => $purchasedMaterial->total,
					"status"        => 'a'
				);
				$this->db->insert('tbl_material_purchase_details', $pm);
			}

			$res = ['success' => true, 'message' => 'Updated Successfully', 'purchaseId' => $data->purchase->purchase_id];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function deleteMaterialPurchase()
	{
		$data = json_decode($this->input->raw_input_stream);
		$res = ['success' => false, 'message' => ''];
		try {
			$this->db->query("update tbl_material_purchase p set p.status = 'd' where p.purchase_id = ?", $data->purchase_id);
			$this->db->query("update tbl_material_purchase_details pd set pd.status = 'd' where pd.purchase_id = ?", $data->purchase_id);
			$res = ['success' => true, 'message' => 'Purchase deleted'];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function getMaterialPurchase()
	{
		$options = json_decode($this->input->raw_input_stream);
		$idClause = "";
		$supplierClause = "";
		$dateClause = "";

		if (isset($options->purchase_id)) {
			$idClause = " and p.purchase_id = '$options->purchase_id'";
		}

		if (isset($options->supplier_id) && $options->supplier_id != null) {
			$supplierClause = " and p.supplier_id = '$options->supplier_id'";
		}

		if (isset($options->dateFrom) && isset($options->dateTo) && $options->dateFrom != null && $options->dateTo != null) {
			$dateClause = " and p.purchase_date between '$options->dateFrom' and '$options->dateTo'";
		}
		$purchases = $this->db->query("
            select 
                p.*,
                s.Supplier_Name as supplier_name,
                s.Supplier_Code as supplier_code,
                s.Supplier_Mobile as supplier_mobile,
                s.Supplier_Address as supplier_address,
                s.Supplier_Type as supplier_type
            from tbl_material_purchase p
            join tbl_supplier s on s.Supplier_SlNo = p.supplier_id
            where p.status = 'a' and p.branch_id = '$this->branch' $idClause $supplierClause $dateClause
        ")->result();

		$totalPurchase = 0.00;
		$totalPaid = 0.00;
		$totalDue = 0.00;
		foreach ($purchases as $purchase) {
			$totalPurchase += $purchase->total;
			$totalPaid += $purchase->paid;
			$totalDue += $purchase->due;
		}

		$data['purchases'] = $purchases;
		$data['totalPurchase'] = $totalPurchase;
		$data['totalPaid'] = $totalPaid;
		$data['totalDue'] = $totalDue;

		echo json_encode($data);
	}

	public function getMaterialPurchaseDetails()
	{
		$options = json_decode($this->input->raw_input_stream);
		$purchaseDetails = $this->db->query("
            select
                pd.*,
                m.name,
                c.ProductCategory_Name as category_name,
                u.Unit_Name as unit_name

            from tbl_material_purchase_details pd
            join tbl_materials m on m.material_id = pd.material_id
            join tbl_productcategory c on c.ProductCategory_SlNo = m.category_id
            join tbl_unit u on u.Unit_SlNo = m.unit_id
            where pd.status = 'a' and purchase_id = '$options->purchase_id'
        ")->result();

		echo json_encode($purchaseDetails);
	}

	public function getPurchaseDetails()
	{
		$data = json_decode($this->input->raw_input_stream);

		$clauses = "";
		if (isset($data->supplierId) && $data->supplierId != '') {
			$clauses .= " and s.Supplier_SlNo = '$data->supplierId'";
		}

		if (isset($data->productId) && $data->productId != '') {
			$clauses .= " and p.Product_SlNo = '$data->productId'";
		}

		if (isset($data->categoryId) && $data->categoryId != '') {
			$clauses .= " and pc.ProductCategory_SlNo = '$data->categoryId'";
		}

		if (isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != '') {
			$clauses .= " and pm.PurchaseMaster_OrderDate between '$data->dateFrom' and '$data->dateTo'";
		}

		$saleDetails = $this->db->query("
            select 
                pd.*,
                p.Product_Name,
                pc.ProductCategory_Name,
                pm.PurchaseMaster_InvoiceNo,
                pm.PurchaseMaster_OrderDate,
                s.Supplier_Code,
                s.Supplier_Name
            from tbl_purchasedetails pd
            join tbl_product p on p.Product_SlNo = pd.Product_IDNo
            join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
            join tbl_purchasemaster pm on pm.PurchaseMaster_SlNo = pd.PurchaseMaster_IDNo
            join tbl_supplier s on s.Supplier_SlNo = pm.Supplier_SlNo
            where pd.Status != 'd'
            $clauses
        ")->result();

		echo json_encode($saleDetails);
	}

	public function getPurchaseInvoice()
	{
		$invoice = $this->mt->generatePurchaseInvoice();
		echo json_encode($invoice);
	}
}
