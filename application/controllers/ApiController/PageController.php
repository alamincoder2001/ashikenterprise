<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PageController extends CI_Controller
{

	public $branch;
	public $userFullName;
	public function __construct()
	{
		parent::__construct();
		error_reporting(0);
		$this->load->model('ApiModel');
		$this->load->helper('verifyauthtoken');

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

	public function getBranches()
	{
		$branches = $this->db->query("SELECT *, case status when 'a' then 'Active' else 'Inactive'end as ctive_status from tbl_brunch")->result();

		echo json_encode([
			"success" => true,
			"data"    => $branches,
		]);
	}

	public function getCategories()
	{
		$categories = $this->db->query("select * from tbl_productcategory where status = 'a'")->result();
		echo json_encode($categories);
	}

	public function addCategory()
	{
		$res = ['success' => false, 'message' => ''];
		try {
			$categoryObj = json_decode($this->input->raw_input_stream);

			$categoryNameCount = $this->db->query("select * from tbl_productcategory where ProductCategory_Name = ? and category_branchid = ?", [$categoryObj->ProductCategory_Name, $this->branch])->num_rows();
			if ($categoryNameCount > 0) {
				$res = ['success' => false, 'message' => 'Category name already exists'];
				echo json_encode($res);
				exit;
			}

			$category                     = (array)$categoryObj;
			$category['status']           = 'a';
			$category['AddBy']            = $this->userFullName;
			$category['AddTime']          = date('Y-m-d H:i:s');
			$category['category_branchid'] = $this->branch;

			$this->db->insert('tbl_productcategory', $category);

			$res = ['success' => true, 'message' => 'Category added successfully'];
		} catch (Exception $ex) {
			$res = ['success' => false, 'message' => $ex->getMessage()];
		}

		echo json_encode($res);
	}

	public function getBrands()
	{
		$brands = $this->db->query("select * from tbl_brand where status = 'a'")->result();
		echo json_encode($brands);
	}

	public function getUnits()
	{
		$units = $this->db->query("select * from tbl_unit where status = 'a'")->result();
		echo json_encode($units);
	}

	public function getDistricts()
	{
		$districts = $this->db->query("select * from tbl_district d where d.status = 'a'")->result();
		echo json_encode($districts);
	}

	public function getCompanyProfile()
	{
		$companyProfile = $this->db->query("select * from tbl_company order by Company_SlNo desc limit 1")->row();
		echo json_encode($companyProfile);
	}

	public function getEmployees()
	{
		$employees = $this->db->query("
            select 
                e.*,
                (select e.salary_range / 30)as daily_salary,
                dp.Department_Name,
                ds.Designation_Name,
                concat(e.Employee_Name, ' - ', e.Employee_ID) as display_name
            from tbl_employee e 
            join tbl_department dp on dp.Department_SlNo = e.Department_ID
            join tbl_designation ds on ds.Designation_SlNo = e.Designation_ID
            where e.status = 'a'
            and e.Employee_brinchid = ?
        ", $this->branch)->result();

		echo json_encode($employees);
	}

	public function getShifts()
	{
		$shifts = $this->db->query("select * from tbl_shifts")->result();

		echo json_encode($shifts);
	}

	public function getEmployeePayments()
	{
		$data = json_decode($this->input->raw_input_stream);

		$clauses = "";
		if (isset($data->employeeId) && $data->employeeId != '') {
			$clauses .= " and e.Employee_SlNo = '$data->employeeId'";
		}

		if (isset($data->month) && $data->month != '') {
			$clauses .= " and ep.month_id = '$data->month'";
		}

		if (isset($data->dateFrom) && $data->dateFrom != '' && isset($data->dateTo) && $data->dateTo != '') {
			$clauses .= " and ep.payment_date between '$data->dateFrom' and '$data->dateTo'";
		}

		if (isset($data->paymentType) && ($data->paymentType == '' || $data->paymentType == null)) {
			$clauses .= " and ep.payment_amount > 0";
		} else if (isset($data->paymentType) && $data->paymentType == 'deduct') {
			$clauses .= " and ep.deduction_amount > 0";
		}

		$payments = $this->db->query("
            select 
                ep.*,
                e.Employee_Name,
                e.Employee_ID,
                e.salary_range,
                dp.Department_Name,
                ds.Designation_Name,
                m.month_name
            from tbl_employee_payment ep
            join tbl_employee e on e.Employee_SlNo = ep.Employee_SlNo
            join tbl_department dp on dp.Department_SlNo = e.Department_ID
            join tbl_designation ds on ds.Designation_SlNo = e.Designation_ID
            join tbl_month m on m.month_id = ep.month_id
            where ep.paymentBranch_id = ?
            and ep.status = 'a'
            $clauses
            order by ep.employee_payment_id desc
        ", $this->branch)->result();

		echo json_encode($payments);
	}

	public function getGraphData()
	{
		$data = json_decode($this->input->raw_input_stream);

		// Monthly Record
		$monthlyRecord = $this->db->query("
                    select ifnull(sum(sm.SaleMaster_TotalSaleAmount), 0) as sales_amount 
                    from tbl_salesmaster sm 
                    where Date_Format(sm.SaleMaster_SaleDate, '%Y-%m') = ?
                    and sm.Status = 'a'
					and sm.employee_id = '$data->employeeId'
                    and sm.SaleMaster_branchid = ?
                    and sm.AddBy = ?
                ", [date("Y-m"), $this->branch, $this->userFullName])->result();

		// Today's Sale
		$todaysSale = $this->db->query("
                select sm.SaleMaster_TotalSaleAmount
                from tbl_salesmaster sm
                where sm.Status = 'a'
				and sm.employee_id = '$data->employeeId'
                and sm.SaleMaster_SaleDate = ?
                and sm.SaleMaster_branchid = ?
				and sm.AddBy = ?
            ", [date('Y-m-d'), $this->branch, $this->userFullName])->result();

		$todayQty = $this->db->query("
					select
					ifnull(sum(sd.SaleDetails_TotalQuantity), 0) as totalQty
					from tbl_saledetails sd
					left join tbl_salesmaster sm on sm.SaleMaster_SlNo = sd.SaleMaster_IDNo
					where sd.Status = 'a'
					and sm.employee_id = '$data->employeeId'
					and sm.SaleMaster_SaleDate = ?
					and sm.SaleMaster_branchid = ?
					and sm.AddBy = ?
            ", [date('Y-m-d'), $this->branch, $this->userFullName])->result();

		$todayItem = $this->db->query("
					select *
					from tbl_saledetails sd
					left join tbl_salesmaster sm on sm.SaleMaster_SlNo = sd.SaleMaster_IDNo
					where sd.Status = 'a'
					and sm.employee_id = '$data->employeeId'
					and sm.SaleMaster_SaleDate = ?
					and sm.SaleMaster_branchid = ?
					and sm.AddBy = ?
					group by Product_IDNo
            ", [date('Y-m-d'), $this->branch, $this->userFullName])->result();

		$todayTotal = array_sum(array_column($todaysSale, 'SaleMaster_TotalSaleAmount'));;

		$customers = $this->db->query("
						select c.*
						from tbl_customer c
						where c.status = 'a'
						and c.Employee_Id = '$data->employeeId'
						and c.Customer_brunchid = ?
					", $this->branch)->result();

		$date = date("Y-m-d");
		$nameOfDay = date('l', strtotime($date));
		$route = $this->db->query("select * from tbl_work_schedule where employee_id = ? and day = ?", [$data->employeeId, $nameOfDay])->row()->area_id;
		$routeOutlate = $this->db->query("
						select c.*
						from tbl_customer c
						where c.status = 'a'
						and c.Employee_Id = '$data->employeeId'
						and c.area_ID = ?
						and c.Customer_brunchid = ?
					", [$route, $this->branch])->result();
		
		$routeName = $this->db->query("
			select 
				ws.*,
				d.District_Name
			from tbl_work_schedule ws
			left join tbl_district d on d.District_SlNo = ws.area_id
			where ws.employee_id = ? and ws.day = ?
		", [$data->employeeId, $nameOfDay])->row()->District_Name;
		
		$targetValue = $this->db->query("select * from tbl_employee where Employee_SlNo = ?", $data->employeeId)->row()->target;

		$responseData = [
			'totalitem'      => count($todayItem),
			'totalqty'       => $todayQty[0]->totalQty,
			'today_order'    => count($todaysSale),
			'todays_sale'    => $todayTotal,
			'monthly_sale'   => $monthlyRecord[0]->sales_amount,
			'total_customer' => count($customers),
			'target_amount' => $targetValue,
			'route_outlet' => count($routeOutlate),
			'route_name' => $routeName ?? 'Route not Assign',
		];

		echo json_encode($responseData, JSON_NUMERIC_CHECK);
	}
}