<?php
defined('BASEPATH') or exit('No direct script access allowed');

class UserController extends CI_Controller
{

	public $userdata;
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
			$this->userdata = get_object_vars($token);
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

	public function userSignup()
	{
		$fullname = $this->input->post('full_name', TRUE);
		$username = $this->input->post('user_name', TRUE);
		$email    = $this->input->post('email', TRUE);
		$password = $this->input->post('password', TRUE);
		$usertype = $this->input->post('user_type', TRUE);
		$branchId = $this->input->post('branch_id', TRUE);

		$error = [];
		if ($fullname == '') {
			$error['fullname'] = 'Fullname is required';
		}
		if ($username == '') {
			$error['username'] = 'User name is required';
		}
		if ($email == '') {
			$error['email'] = 'Email is required';
		}
		if ($password == '') {
			$error['password'] = 'Password is required';
		}
		if ($usertype == '') {
			$error['usertype'] = 'User type is required';
		}
		if ($branchId == '') {
			$error['branchId'] = 'Branch id is required';
		}

		if (count($error) > 0) {
			echo json_encode([
				'success' => false,
				'message' => 'Validation failed',
				'errors'  => $error
			]);
			exit;
		}

		$checkUsername = $this->db->query("select * from tbl_user where User_Name = ?", $username)->num_rows();
		if ($checkUsername > 0) {
			$res = ['success' => false, 'message' => 'Username already exists'];
			echo json_encode($res);
			exit;
		}

		$data     = array(
			'FullName'      => $fullname,
			'User_Name'     => $username,
			'UserEmail'     => $email,
			'User_Password' => md5($password),
			'UserType'      => $usertype,
			'Brunch_ID'     => $branchId,
		);

		$userId = $this->ApiModel->signup($data);

		if ($userId) {
			echo json_encode([
				'status'  => true,
				'message' => 'User registered successfully!',
			]);
		} else {
			echo json_encode([
				'status'  => false,
				'message' => 'User registeration failed!',
			]);
		}
	}


	public function getUsers()
	{
		$data = json_decode($this->input->raw_input_stream);

		$clause = '';
		if (isset($data->userId) && $data->userId != '') {
			$clause = " and User_SlNo = '$data->userId' ";
		}
		$users = $this->db->query("SELECT * FROM tbl_user WHERE status = 'a' $clause")->result();

		echo json_encode([
			"success" => true,
			"data"    => $users,
		]);
	}
}
