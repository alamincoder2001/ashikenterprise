<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ProductionController extends CI_Controller
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

	public function addProduction()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$data = json_decode($this->input->raw_input_stream);

			$countProductionSl = $this->db->query("select * from tbl_productions where production_sl = ?", $data->production->production_sl)->num_rows();
			if ($countProductionSl > 0) {
				$data->production->production_sl = $this->mt->generateProductionCode();
			}
			$production = array(
				'production_sl'  => $data->production->production_sl,
				'date'           => $data->production->date,
				'incharge_id'    => $data->production->incharge_id,
				'shift'          => $data->production->shift,
				'note'           => $data->production->note,
				'labour_cost'    => $data->production->labour_cost,
				'transport_cost' => $data->production->transport_cost,
				'other_cost'     => $data->production->other_cost,
				'total_cost'     => $data->production->total_cost,
				'status'         => 'a'
			);

			$this->db->insert('tbl_productions', $production);
			$productionId = $this->db->insert_id();

			foreach ($data->materials as $material) {
				$material = array(
					'production_id' => $productionId,
					'material_id'   => $material->material_id,
					'quantity'      => $material->quantity,
					'purchase_rate' => $material->purchase_rate,
					'status'        => 'a'
				);
				$this->db->insert('tbl_production_details', $material);
			}

			foreach ($data->products as $product) {
				$productionProduct = array(
					'production_id' => $productionId,
					'product_id'    => $product->product_id,
					'quantity'      => $product->quantity,
					'price'         => $product->price,
					'status'        => 'a'
				);

				$this->db->insert('tbl_production_products', $productionProduct);

				$productInventoryCount = $this->db->query("select * from tbl_currentinventory ci where ci.product_id = ? and ci.branch_id = ?", [$product->product_id, $this->branch])->num_rows();
				if ($productInventoryCount == 0) {
					$inventory = array(
						'product_id'          => $product->product_id,
						'production_quantity' => $product->quantity,
						'branch_id'           => $this->branch
					);

					$this->db->insert('tbl_currentinventory', $inventory);
				} else {
					$this->db->query("update tbl_currentinventory set production_quantity = production_quantity + ? where product_id = ? and branch_id = ?", [$product->quantity, $product->product_id, $this->branch]);
				}
			}

			$res = ['success' => true, 'message' => 'Production entry success', 'productionId' => $productionId];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function updateProduction()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$data = json_decode($this->input->raw_input_stream);
			$productionId = $data->production->production_id;
			$production = array(
				'production_sl'  => $data->production->production_sl,
				'date'           => $data->production->date,
				'incharge_id'    => $data->production->incharge_id,
				'shift'          => $data->production->shift,
				'note'           => $data->production->note,
				'labour_cost'    => $data->production->labour_cost,
				'transport_cost' => $data->production->transport_cost,
				'other_cost'     => $data->production->other_cost,
				'total_cost'     => $data->production->total_cost
			);

			$this->db->where('production_id', $productionId)->update('tbl_productions', $production);

			$this->db->delete('tbl_production_details', array('production_id' => $productionId));
			foreach ($data->materials as $material) {
				$material = array(
					'production_id' => $productionId,
					'material_id'   => $material->material_id,
					'quantity'      => $material->quantity,
					'purchase_rate' => $material->purchase_rate,
					'status'        => 'a'
				);
				$this->db->insert('tbl_production_details', $material);
			}

			$oldProducts = $this->db->query("select * from tbl_production_products where production_id = ?", $productionId)->result();
			$this->db->delete('tbl_production_products', array('production_id' => $productionId));
			foreach ($oldProducts as $oldProduct) {
				$this->db->query("update tbl_currentinventory set production_quantity = production_quantity - ? where product_id = ? and branch_id = ?", [$oldProduct->quantity, $oldProduct->product_id, $this->branch]);
			}
			foreach ($data->products as $product) {
				$productionProduct = array(
					'production_id' => $productionId,
					'product_id'    => $product->product_id,
					'quantity'      => $product->quantity,
					'price'         => $product->price,
					'status'        => 'a'
				);

				$this->db->insert('tbl_production_products', $productionProduct);

				$productInventoryCount = $this->db->query("select * from tbl_currentinventory ci where ci.product_id = ? and ci.branch_id = ?", [$product->product_id, $this->branch])->num_rows();
				if ($productInventoryCount == 0) {
					$inventory = array(
						'product_id'          => $product->product_id,
						'production_quantity' => $product->quantity,
						'branch_id'           => $this->branch
					);

					$this->db->insert('tbl_currentinventory', $inventory);
				} else {
					$this->db->query("update tbl_currentinventory set production_quantity = production_quantity + ? where product_id = ? and branch_id = ?", [$product->quantity, $product->product_id, $this->branch]);
				}
			}

			$res = ['success' => true, 'message' => 'Production update success', 'productionId' => $productionId];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function getProductions()
	{
		$options = json_decode($this->input->raw_input_stream);

		$idClause = '';
		$dateClause = '';


		if (isset($options->production_id) && $options->production_id != 0) {
			$idClause = " and pr.production_id = '$options->production_id'";
		}

		if (isset($options->dateFrom) && isset($options->dateTo) && $options->dateFrom != null && $options->dateTo != null) {
			$dateClause = " and pr.date between '$options->dateFrom' and '$options->dateTo'";
		}
		$productions = $this->db->query("
            select 
            pr.*,
            e.Employee_Name as incharge_name
            from tbl_productions pr
            join tbl_employee e on e.Employee_SlNo = pr.incharge_id
            where pr.status = 'a' $idClause $dateClause
        ")->result();
		echo json_encode($productions);
	}

	public function getProductionRecord()
	{
		$options = json_decode($this->input->raw_input_stream);

		$dateClause = '';

		if (isset($options->dateFrom) && isset($options->dateTo) && $options->dateFrom != null && $options->dateTo != null) {
			$dateClause = " and pr.date between '$options->dateFrom' and '$options->dateTo'";
		}
		$productions = $this->db->query("
            select 
            pr.*,
            e.Employee_Name as incharge_name
            from tbl_productions pr
            join tbl_employee e on e.Employee_SlNo = pr.incharge_id
            where pr.status = 'a' $dateClause
        ")->result();

		foreach ($productions as $production) {
			$production->products = $this->db->query("
                select
                    pp.*,
                    p.Product_Code as product_code,
                    p.Product_Name as name,
                    p.ProductCategory_ID as category_id,
                    pc.ProductCategory_Name as category_name,
                    u.Unit_Name as unit_name
                from tbl_production_products pp
                join tbl_product p on p.Product_SlNo = pp.product_id
                join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
                join tbl_unit u on u.Unit_SlNo = p.unit_id
                where pp.status = 'a'
                and pp.production_id = ?
            ", $production->production_id)->result();
		}
		echo json_encode($productions);
	}

	public function getProductionDetails()
	{
		$options = json_decode($this->input->raw_input_stream);
		$productionDetails = $this->db->query("
            select
            pd.*,
            m.name,
            m.category_id,
            u.Unit_Name as unit_name,
            pc.ProductCategory_Name as category_name
            from tbl_production_details pd
            join tbl_materials m on m.material_id = pd.material_id
            join tbl_productcategory pc on pc.ProductCategory_SlNo = m.category_id
            join tbl_unit u on u.Unit_SlNo = m.unit_id
            where pd.status = 'a' 
            and pd.production_id = '$options->production_id'
        ")->result();

		echo json_encode($productionDetails);
	}

	public function getProductionProducts()
	{
		$options = json_decode($this->input->raw_input_stream);
		$productionProducts = $this->db->query("
            select
                pp.*,
                p.Product_Code as product_code,
                p.Product_Name as name,
                p.ProductCategory_ID as category_id,
                pc.ProductCategory_Name as category_name,
                u.Unit_Name as unit_name
            from tbl_production_products pp
            join tbl_product p on p.Product_SlNo = pp.product_id
            join tbl_productcategory pc on pc.ProductCategory_SlNo = p.ProductCategory_ID
            join tbl_unit u on u.Unit_SlNo = p.unit_id
            where pp.status = 'a'
            and pp.production_id = '$options->production_id'
        ")->result();

		echo json_encode($productionProducts);
	}

	public function deleteProduction()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$data = json_decode($this->input->raw_input_stream);

			$productionDetails = $this->db->query("
                select 
                    p.*
                from tbl_production_products p 
                where p.production_id = ?
            ", $data->productionId)->result();

			foreach ($productionDetails as $product) {
				$this->db->query("
                    update tbl_currentinventory 
                    set production_quantity = production_quantity - ?
                    where product_id = ?
                    and branch_id = ?
                ", [$product->quantity, $product->product_id, $this->branch]);
			}

			$this->db->query("update tbl_productions set status = 'd' where production_id = ?", $data->productionId);
			$this->db->query("update tbl_production_details set status = 'd' where production_id = ?", $data->productionId);
			$this->db->query("update tbl_production_products set status = 'd' where production_id = ?", $data->productionId);
			$res = ['success' => true, 'message' => 'Production deleted successfully'];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function getMaterials()
	{
		$materials = $this->db->query("
            select 
            m.*, 
            concat(m.name, ' - ', m.code) as display_text,
            c.ProductCategory_Name as category_name, 
            u.Unit_Name as unit_name,
            case
                when m.status = 1 then 'Active'
                when m.status = 0 then 'Inactive'
            end as status_text
            from tbl_materials m
            join tbl_productcategory c on c.ProductCategory_SlNo = m.category_id
            join tbl_unit u on u.Unit_SlNo = m.unit_id
        ")->result();
		echo json_encode($materials);
	}

	public function getMaterialInvoice()
	{
		$pCode = $this->mt->generateMaterialPurchaseCode();
		echo json_encode($pCode);
	}
}
