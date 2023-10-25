<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ProductController extends CI_Controller
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
				"status"  => 401,
				"message" => "Invalid Token provided",
				"success" => false
			]);
			exit;
		}

		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: GET, OPTIONS, POST, GET, PUT");
		header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
	}

	public function addProduct()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$productObj = json_decode($this->input->raw_input_stream);

			$productNameCount = $this->db->query("select * from tbl_product where Product_Name = ? and Product_branchid = ?", [$productObj->Product_Name, $this->branch])->num_rows();
			if ($productNameCount > 0) {
				$res = ['success' => false, 'message' => 'Product name already exists'];
				echo json_encode($res);
				exit;
			}

			$productCodeCount = $this->db->query("select * from tbl_product where Product_Code = ? and Product_branchid = ?", [$productObj->Product_Code, $this->branch])->num_rows();
			if ($productCodeCount > 0) {
				$productObj->Product_Code = $this->mt->generateProductCode();
			}

			$product                     = (array)$productObj;
			$product['is_service']       = $productObj->is_service == true ? 'true' : 'false';
			$product['status']           = 'a';
			$product['AddBy']            = $this->userFullName;
			$product['AddTime']          = date('Y-m-d H:i:s');
			$product['Product_branchid'] = $this->branch;

			$this->db->insert('tbl_product', $product);

			$res = ['success' => true, 'message' => 'Product added successfully', 'productId' => $this->mt->generateProductCode()];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function updateProduct()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$productObj = json_decode($this->input->raw_input_stream);

			$productNameCount = $this->db->query("select * from tbl_product where Product_Name = ? and Product_branchid = ? and Product_SlNo != ?", [$productObj->Product_Name, $this->branch, $productObj->Product_SlNo])->num_rows();
			if ($productNameCount > 0) {
				$res = ['success' => false, 'message' => 'Product name already exists'];
				echo json_encode($res);
				exit;
			}

			$product = (array)$productObj;
			unset($product['Product_SlNo']);
			$product['is_service'] = $productObj->is_service == true ? 'true' : 'false';
			$product['UpdateBy']   = $this->userFullName;
			$product['UpdateTime'] = date('Y-m-d H:i:s');

			$this->db->where('Product_SlNo', $productObj->Product_SlNo)->update('tbl_product', $product);

			$res = ['success' => true, 'message' => 'Product updated successfully', 'productId' => $this->mt->generateProductCode()];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}
	public function deleteProduct()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$data = json_decode($this->input->raw_input_stream);

			$this->db->set(['status' => 'd'])->where('Product_SlNo', $data->productId)->update('tbl_product');

			$res = ['success' => true, 'message' => 'Product deleted successfully'];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function activeProduct()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$productId = $this->input->post('productId');
			$this->db->query("update tbl_product set status = 'a' where Product_SlNo = ?", $productId);
			$res = ['success' => true, 'message' => 'Product activated'];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function getProducts()
	{
		$data = json_decode($this->input->raw_input_stream);

		$clauses = "";
		if (isset($data->categoryId) && $data->categoryId != '') {
			$clauses .= " and p.ProductCategory_ID = '$data->categoryId'";
		}

		if (isset($data->catId) && $data->catId != null && $data->catId != '') {
			$clauses .= " and p.ProductCategory_ID = '$data->catId'";
		}

		if (isset($data->proName) && $data->proName != null && $data->proName != '') {
			$clauses .= " and p.Product_Name like '%$data->proName%'";
		}

		if (isset($data->isService) && $data->isService != null && $data->isService != '') {
			$clauses .= " and p.is_service = '$data->isService'";
		}

		$products = $this->db->query("
            select
                p.*,
                concat(p.Product_Name, ' - ', p.Product_Code) as display_text,
                pc.ProductCategory_Name,
                br.brand_name,
                u.Unit_Name
            from tbl_product p
            left join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
            left join tbl_brand br on br.brand_SiNo = p.brand
            left join tbl_unit u on u.Unit_SlNo = p.Unit_ID
            where p.status = 'a'
            $clauses
            order by p.Product_SlNo desc
        ")->result();

		echo json_encode($products);
	}

	public function getCustomerProducts()
	{

		$data = json_decode($this->input->raw_input_stream);

		$clauses = "";
		if (isset($data->customerId) && $data->customerId != '') {
			$clauses .= " and c.Customer_SlNo = '$data->customerId'";
		}

		// if (isset($data->isService) && $data->isService != null && $data->isService != '') {
		//     $clauses .= " and p.is_service = '$data->isService'";
		// }

		$products = $this->db->query("
            SELECT p.*,
           concat(p.Product_Name, ' - ', p.Product_Code) as display_text
            from tbl_product p 
            left join tbl_saledetails sd on sd.Product_IDNo = p.Product_SlNo
            left join tbl_salesmaster sm on sm.SaleMaster_SlNo = sd.SaleMaster_IDNo
            left join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo
            WHERE p.status = 'a'
            $clauses
            order by p.Product_SlNo desc
        ", $this->branch)->result();

		echo json_encode($products);
	}

	public function getCustomerCategories()
	{

		$data = json_decode($this->input->raw_input_stream);

		$clauses = "";

		if (isset($data->customerId) && $data->customerId != '') {
			$clauses .= " and sm.SalseCustomer_IDNo = '$data->customerId'";

			$categories = $this->db->query("SELECT pc.ProductCategory_SlNo,pc.ProductCategory_Name
            FROM tbl_saledetails sd
            LEFT JOIN tbl_salesmaster sm on sm.SaleMaster_SlNo = sd.SaleMaster_IDNo
            LEFT JOIN tbl_product p on p.Product_SlNo = sd.Product_IDNo
            left JOIN tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
            WHERE sm.Status = 'a'
            and sm.SaleMaster_branchid = ?
            $clauses
            GROUP by pc.ProductCategory_SlNo
        ", $this->branch)->result();

			echo json_encode($categories);
		}
	}

	public function getProductStock()
	{
		$data = json_decode($this->input->raw_input_stream);

		$branchId = '';
		
		$clauses = '';
		
		if(isset($data->productId) && $data->productId != '') {
		    $clauses .= " and c.product_id = $data->productId";
		}
		
		
		if(isset($data->catId) && $data->catId != '') {
		    $clauses .= " and c.cat_id = $data->catId";
		}
		
		if(isset($data->branchId) && $data->branchId != '') {
		    $branchId = $data->branchId;
		} else {
		    $branchId = $this->branch;
		}
		

		$stockQuery = $this->db->query("
		    select
            	c.* 
            from tbl_currentinventory c 
            where c.branch_id = ?
            $clauses
		 ", $branchId);
	
		$stockCount = $stockQuery->num_rows();
		$stock = 0;
		if ($stockCount != 0) {
			$stockRow = $stockQuery->row();
			$stock = ($stockRow->purchase_quantity + $stockRow->transfer_to_quantity + $stockRow->sales_return_quantity + $stockRow->production_quantity)
				- ($stockRow->sales_quantity + $stockRow->purchase_return_quantity + $stockRow->damage_quantity + $stockRow->transfer_from_quantity);
		}

		echo $stock;
	}

	public function getCurrentStock()
	{
		$data = json_decode($this->input->raw_input_stream);

		$clauses = "";
		if (isset($data->stockType) && $data->stockType == 'low') {
			$clauses .= " and current_quantity <= Product_ReOrederLevel";
		}
		$stock = $this->db->query("
            select * from(
                select
                    ci.*,
                    (select (ci.purchase_quantity + ci.sales_return_quantity + ci.transfer_to_quantity) - (ci.sales_quantity + ci.purchase_return_quantity + ci.damage_quantity + ci.transfer_from_quantity)) as current_quantity,
                    p.Product_Name,
                    p.Product_Code,
                    p.Product_ReOrederLevel,
					p.Product_Purchase_Rate,
                    (select (p.Product_Purchase_Rate * current_quantity)) as stock_value,
                    pc.ProductCategory_Name,
                    b.brand_name,
                    u.Unit_Name,
					br.Brunch_name
                from tbl_currentinventory ci
                join tbl_product p on p.Product_SlNo = ci.product_id
                left join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
                left join tbl_brand b on b.brand_SiNo = p.brand
                left join tbl_unit u on u.Unit_SlNo = p.Unit_ID
				left join tbl_brunch br on ci.branch_id = br.brunch_id
                where p.status = 'a'
                and p.is_service = 'false'
                and ci.branch_id = ?
            ) as tbl
            where 1 = 1
            $clauses
        ", $this->branch)->result();

		$res['stock'] = $stock;
		$res['totalValue'] = array_sum(
			array_map(function ($product) {
				return $product->stock_value;
			}, $stock)
		);

		echo json_encode($res);
	}

	public function getTotalStock()
	{
		$data = json_decode($this->input->raw_input_stream);

		$branchId = $this->branch;
		$clauses = "";
		if (isset($data->categoryId) && $data->categoryId != null) {
			$clauses .= " and p.ProductCategory_ID = '$data->categoryId'";
		}

		if (isset($data->productId) && $data->productId != null) {
			$clauses .= " and p.Product_SlNo = '$data->productId'";
		}

		if (isset($data->brandId) && $data->brandId != null) {
			$clauses .= " and p.brand = '$data->brandId'";
		}

		$stock = $this->db->query("
            select
                p.*,
                pc.ProductCategory_Name,
                b.brand_name,
                u.Unit_Name,
				br.Brunch_name,
                (select ifnull(sum(pd.PurchaseDetails_TotalQuantity), 0) 
                        from tbl_purchasedetails pd 
                        where pd.Product_IDNo = p.Product_SlNo
                        and pd.PurchaseDetails_branchID = '$branchId'
                        and pd.Status = 'a') as purchased_quantity,
                        
                (select ifnull(sum(prd.PurchaseReturnDetails_ReturnQuantity), 0) 
                        from tbl_purchasereturndetails prd 
                        where prd.PurchaseReturnDetailsProduct_SlNo = p.Product_SlNo
                        and prd.PurchaseReturnDetails_brachid = '$branchId') as purchase_returned_quantity,
                        
                (select ifnull(sum(sd.SaleDetails_TotalQuantity), 0) 
                        from tbl_saledetails sd
                        where sd.Product_IDNo = p.Product_SlNo
                        and sd.SaleDetails_BranchId  = '$branchId'
                        and sd.Status = 'a') as sold_quantity,
                        
                (select ifnull(sum(srd.SaleReturnDetails_ReturnQuantity), 0)
                        from tbl_salereturndetails srd 
                        where srd.SaleReturnDetailsProduct_SlNo = p.Product_SlNo
                        and srd.SaleReturnDetails_brunchID = '$branchId') as sales_returned_quantity,
                        
                (select ifnull(sum(dmd.DamageDetails_DamageQuantity), 0) 
                        from tbl_damagedetails dmd
                        join tbl_damage dm on dm.Damage_SlNo = dmd.Damage_SlNo
                        where dmd.Product_SlNo = p.Product_SlNo
                        and dm.Damage_brunchid = '$branchId') as damaged_quantity,
            
                (select ifnull(sum(trd.quantity), 0)
                        from tbl_transferdetails trd
                        join tbl_transfermaster tm on tm.transfer_id = trd.transfer_id
                        where trd.product_id = p.Product_SlNo
                        and tm.transfer_from = '$branchId') as transferred_from_quantity,

                (select ifnull(sum(trd.quantity), 0)
                        from tbl_transferdetails trd
                        join tbl_transfermaster tm on tm.transfer_id = trd.transfer_id
                        where trd.product_id = p.Product_SlNo
                        and tm.transfer_to = '$branchId') as transferred_to_quantity,
                        
                (select (purchased_quantity + sales_returned_quantity + transferred_to_quantity) - (sold_quantity + purchase_returned_quantity + damaged_quantity + transferred_from_quantity)) as current_quantity,
                (select p.Product_Purchase_Rate * current_quantity) as stock_value
            from tbl_product p
            left join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
            left join tbl_brand b on b.brand_SiNo = p.brand
            left join tbl_unit u on u.Unit_SlNo = p.Unit_ID
			left join tbl_brunch br on br.brunch_id = p.Product_branchid
            where p.status = 'a' and p.Product_branchid = '$branchId' and p.is_service = 'false'  $clauses
        ")->result();

		$res['stock'] = $stock;
		$res['totalValue'] = array_sum(
			array_map(function ($product) {
				return $product->stock_value;
			}, $stock)
		);

		echo json_encode($res);
	}


		public function getProductCategoryWise ()
	{

		 $reqBody = json_decode($this->input->raw_input_stream);

		 $products = $this->db->query("select
            p.*,
            concat(p.Product_Name, ' - ', p.Product_Code) as display_text,
            ? as b,
            pc.ProductCategory_Name,
            br.brand_name,
            u.Unit_Name
        from tbl_product p
        left join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
        left join tbl_brand br on br.brand_SiNo = p.brand
        left join tbl_unit u on u.Unit_SlNo = p.Unit_ID
        where p.status = 'a'
        AND  p.ProductCategory_ID=?", [$reqBody->branchId, $reqBody->catId])->result();
        // Check product Stock Ava 

        if(isset($reqBody->stockFilter) && $reqBody->stockFilter != ''){
            $avaProducts = array_filter($products, function ($product) {
                $branchId =  $this->session->userdata('BRANCHid');
                $stockQuery = $this->db->query("select * from tbl_currentinventory where product_id = ? and branch_id = ? and cat_id=?", [$product->Product_SlNo, $product->b, $product->ProductCategory_ID]);
                $stockCount = $stockQuery->num_rows();
                if ($stockCount > 0) {
                    $stock = 0;
                    $stockRow = $stockQuery->row();
                    $stock = ($stockRow->purchase_quantity + $stockRow->transfer_to_quantity + $stockRow->sales_return_quantity)
                        - ($stockRow->sales_quantity + $stockRow->purchase_return_quantity + $stockRow->damage_quantity + $stockRow->transfer_from_quantity);
                    if ($stock > 0) {
                        return $product;
                    }
                }
            });
        } else {
            $avaProducts  = $products;
        }

        echo json_encode(array_values($avaProducts));
	}
	
	public function view_all_product()
	{
		$data['title']  = 'Product';
		$data['allproduct'] =  $allproduct = $this->Billing_model->select_Product_without_limit();

?>
<br />
<div class="table-responsive">
    <table id="dynamic-table" class="table table-striped table-bordered table-hover">
        <thead>
            <tr>
                <th class="center">
                    <label class="pos-rel">
                        <input type="checkbox" class="ace" />
                        <span class="lbl"></span>
                    </label>
                </th>
                <th>Product ID</th>
                <th>Categoty Name</th>
                <th>Product Name</th>
                <th class="hidden-480">Brand</th>

                <th>Color</th>
                <!--<th class="hidden-480">Purchase Rate</th>
					<th class="hidden-480">Sell Rate</th>--->

                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php
					foreach ($allproduct as $vallproduct) {
					?>
            <tr>
                <td class="center">
                    <label class="pos-rel">
                        <input type="checkbox" class="ace" />
                        <span class="lbl"></span>
                    </label>
                </td>

                <td>
                    <a href="#"><?php echo $vallproduct->Product_Code; ?></a>
                </td>
                <td><?php echo $vallproduct->ProductCategory_Name; ?></td>
                <td class="hidden-480"><?php echo $vallproduct->Product_Name; ?></td>
                <td><?php echo $vallproduct->brand_name; ?></td>

                <td class="hidden-480">
                    <span class="label label-sm label-info arrowed arrowed-righ">
                        <?php echo $vallproduct->color_name; ?>
                    </span>
                </td>
                <!--<td class="hidden-480"><?php echo $vallproduct->Product_Purchase_Rate; ?></td>
								<td class="hidden-480"><?php echo $vallproduct->Product_SellingPrice; ?></td>-->

                <td>
                    <div class="hidden-sm hidden-xs action-buttons">
                        <span class="blue" onclick="Edit_product(<?php echo $vallproduct->Product_SlNo; ?>)"
                            style="cursor:pointer;">
                            <i class="ace-icon fa fa-pencil bigger-130"></i>
                        </span>

                        <a class="green" href="" onclick="deleted(<?php echo $vallproduct->Product_SlNo; ?>)">
                            <i class="ace-icon fa fa-trash bigger-130 text-danger"></i>
                        </a>

                        <a class="black"
                            href="<?php echo base_url(); ?>Administrator/Products/barcodeGenerate/<?php echo $vallproduct->Product_SlNo; ?>"
                            target="_blank">
                            <i class="ace-icon fa fa-barcode bigger-130"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<?php
		//echo "<pre>";print_r($data['allproduct']);exit;
		//$this->load->view('Administrator/products/all_product', $data, TRUE);
		//$this->load->view('Administrator/index', $data);
	}


    public function getProductLedger()
    {
        $data = json_decode($this->input->raw_input_stream);
                
        $result = $this->db->query("
            select
                'a' as sequence,
                pd.PurchaseDetails_SlNo as id,
                pm.PurchaseMaster_OrderDate as date,
                concat('Purchase - ', pm.PurchaseMaster_InvoiceNo, ' - ', s.Supplier_Name) as description,
                pd.PurchaseDetails_Rate as rate,
                pd.PurchaseDetails_TotalQuantity as in_quantity,
                0 as out_quantity
            from tbl_purchasedetails pd
            join tbl_purchasemaster pm on pm.PurchaseMaster_SlNo = pd.PurchaseMaster_IDNo
            join tbl_supplier s on s.Supplier_SlNo = pm.Supplier_SlNo
            where pd.Status = 'a'
            and pd.Product_IDNo = " . $data->productId . "
            and pd.PurchaseDetails_branchID = " . $this->branch . "
            
            UNION
            select 
                'b' as sequence,
                sd.SaleDetails_SlNo as id,
                sm.SaleMaster_SaleDate as date,
                concat('Sale - ', sm.SaleMaster_InvoiceNo, ' - ', c.Customer_Name) as description,
                sd.SaleDetails_Rate as rate,
                0 as in_quantity,
                sd.SaleDetails_TotalQuantity as out_quantity
            from tbl_saledetails sd
            join tbl_salesmaster sm on sm.SaleMaster_SlNo = sd.SaleMaster_IDNo
            join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo
            where sd.Status = 'a'
            and sd.Product_IDNo = " . $data->productId . "
            and sd.SaleDetails_BranchId = " . $this->branch . "
            
            UNION
            select 
                'c' as sequence,
                prd.PurchaseReturnDetails_SlNo as id,
                pr.PurchaseReturn_ReturnDate as date,
                concat('Purchase Return - ', pr.PurchaseMaster_InvoiceNo, ' - ', s.Supplier_Name) as description,
                (prd.PurchaseReturnDetails_ReturnAmount / prd.PurchaseReturnDetails_ReturnQuantity) as rate,
                0 as in_quantity,
                prd.PurchaseReturnDetails_ReturnQuantity as out_quantity
            from tbl_purchasereturndetails prd
            join tbl_purchasereturn pr on pr.PurchaseReturn_SlNo = prd.PurchaseReturn_SlNo
            join tbl_supplier s on s.Supplier_SlNo = pr.Supplier_IDdNo
            where prd.Status = 'a'
            and prd.PurchaseReturnDetailsProduct_SlNo = " . $data->productId . "
            and prd.PurchaseReturnDetails_brachid = " . $this->branch . "
            
            UNION
            select
                'd' as sequence, 
                srd.SaleReturnDetails_SlNo as id,
                sr.SaleReturn_ReturnDate as date,
                concat('Sale Return - ', sr.SaleMaster_InvoiceNo, ' - ', c.Customer_Name) as description,
                (srd.SaleReturnDetails_ReturnAmount / srd.SaleReturnDetails_ReturnQuantity) as rate,
                srd.SaleReturnDetails_ReturnQuantity as in_quantity,
                0 as out_quantity
            from tbl_salereturndetails srd
            join tbl_salereturn sr on sr.SaleReturn_SlNo = srd.SaleReturn_IdNo
            join tbl_salesmaster sm on sm.SaleMaster_InvoiceNo = sr.SaleMaster_InvoiceNo
            join tbl_customer c on c.Customer_SlNo = sm.SalseCustomer_IDNo
            where srd.Status = 'a'
            and srd.SaleReturnDetailsProduct_SlNo = " . $data->productId . "
            and srd.SaleReturnDetails_brunchID = " . $this->branch . "
            
            UNION
            select
                'e' as sequence, 
                trd.transferdetails_id as id,
                tm.transfer_date as date,
                concat('Transferred From: ', b.Brunch_name, ' - ', tm.note) as description,
                0 as rate,
                trd.quantity as in_quantity,
                0 as out_quantity
            from tbl_transferdetails trd
            join tbl_transfermaster tm on tm.transfer_id = trd.transfer_id
            join tbl_brunch b on b.brunch_id = tm.transfer_from
            where trd.product_id = " . $data->productId . "
            and tm.transfer_to = " . $this->branch . "
            
            UNION
            select 
                'f' as sequence,
                trd.transferdetails_id as id,
                tm.transfer_date as date,
                concat('Transferred To: ', b.Brunch_name, ' - ', tm.note) as description,
                0 as rate,
                0 as in_quantity,
                trd.quantity as out_quantity
            from tbl_transferdetails trd
            join tbl_transfermaster tm on tm.transfer_id = trd.transfer_id
            join tbl_brunch b on b.brunch_id = tm.transfer_to
            where trd.product_id = " . $data->productId . "
            and tm.transfer_from = " . $this->branch . "
            
            UNION
            select 
                'g' as sequence,
                dmd.DamageDetails_SlNo as id,
                d.Damage_Date as date,
                concat('Damaged - ', d.Damage_Description) as description,
                0 as rate,
                0 as in_quantity,
                dmd.DamageDetails_DamageQuantity as out_quantity
            from tbl_damagedetails dmd
            join tbl_damage d on d.Damage_SlNo = dmd.Damage_SlNo
            where dmd.Product_SlNo = " . $data->productId . "
            and d.Damage_brunchid = " . $this->branch . "

            order by date, sequence, id
        ")->result();

        $ledger = array_map(function ($key, $row) use ($result) {
            $row->stock = $key == 0 ? $row->in_quantity - $row->out_quantity : ($result[$key - 1]->stock + ($row->in_quantity - $row->out_quantity));
            return $row;
        }, array_keys($result), $result);

        $previousRows = array_filter($ledger, function ($row) use ($data) {
                return $row->date < $data->dateFrom;
            });

        $previousStock = empty($previousRows) ? 0 : end($previousRows)->stock;

        $ledger = array_filter($ledger, function ($row) use ($data) {
            return $row->date >= $data->dateFrom && $row->date <= $data->dateTo;
        });
        
        $ledger = array_values($ledger);

        echo json_encode(['ledger' => $ledger, 'previousStock' => $previousStock]);
    }

	public function getProductCode()
	{
		$productCode = $this->mt->generateProductCode();
		echo json_encode($productCode);
	}

    public function getBranchWiseStock()
    {
        $data = json_decode($this->input->raw_input_stream);

        $clauses = "";

        if (isset($data->branchId) && $data->branchId > 0) {
            $clauses .= " and ci.branch_id = '$data->branchId'";
        }


        $stock = $this->db->query("
                select * from(
                    select
                        ci.*,
                        (select (ci.purchase_quantity + ci.sales_return_quantity + ci.transfer_to_quantity) - (ci.sales_quantity + ci.purchase_return_quantity + ci.damage_quantity + ci.transfer_from_quantity)) as current_quantity,
                        (select sum(current_quantity) as current_quantity),
                        p.Product_Name,
                        p.Product_Code,
                        p.Product_ReOrederLevel,
                        p.Product_Purchase_Rate,
                        (select (p.Product_Purchase_Rate * current_quantity)) as stock_value,
                        pc.ProductCategory_Name,
                        b.brand_name,
                        u.Unit_Name
                    from tbl_currentinventory ci
                    join tbl_product p on p.Product_SlNo = ci.product_id
                    left join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
                    left join tbl_brand b on b.brand_SiNo = p.brand
                    left join tbl_unit u on u.Unit_SlNo = p.Unit_ID
                    where p.status = 'a'
                    and p.is_service = 'false'
                    $clauses
                    group by p.Product_Code
                   
                ) as tbl
                where 1 = 1
               
            ")->result();

        $res['stock'] = $stock;
        $res['totalValue'] = array_sum(
            array_map(function ($product) {
                return $product->stock_value;
            }, $stock)
        );
        $stock = array_map(function ($brance) {
            $brance->brances = $this->db->query("select b.brunch_id,b.Brunch_name,
                        (select (cin.purchase_quantity + cin.sales_return_quantity + cin.transfer_to_quantity) - (cin.sales_quantity + cin.purchase_return_quantity + cin.damage_quantity + cin.transfer_from_quantity)) as current_quantity
                        FROM tbl_brunch b 
                        INNER join tbl_currentinventory cin 
                        on b.brunch_id = cin.branch_id 
                        where   cin.product_id=?
                        ", [$brance->product_id])->result();
            $totalQty = 0;
            foreach ($brance->brances as $key => $value) {
                $totalQty = $totalQty + $value->current_quantity;
            }
            $brance->totalQty = $totalQty;
            return $brance;
        }, $stock);


        $stock = array_filter($stock, function ($product) {
            if ($product->totalQty > 0) {
                return  $product;
            }
        });


        $res['stock'] = array_values($stock);
        $transportCost  = $this->db->query("SELECT sum(own_freight) as totalOwnCost FROM tbl_purchasemaster 
        WHERE PurchaseMaster_BranchID=?
        ", $data->branchId)->row();
        $res['totalTransportCost'] = $transportCost->totalOwnCost;
        echo json_encode($res);
    }

}