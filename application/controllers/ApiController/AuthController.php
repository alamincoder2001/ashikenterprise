<?php
defined('BASEPATH') or exit('No direct script access allowed');

class AuthController extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		error_reporting(0);
		$this->load->model('ApiModel');

		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: GET, OPTIONS, POST, GET, PUT");
		header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
	}

	public function login()
	{

		$jwt = new JWT();
		$JwtSecretKey = "KBTradELinkS_HS256";

		$username = $this->input->post('username');
		$password = $this->input->post('password');

		$error = [];
		if ($username == '') {
			$error['username'] = 'Username is Required';
		}
		if ($password == '') {
			$error['password'] = 'Password is Required';
		}

		if (count($error) > 0) {
			echo json_encode([
				'success' => false,
				'message' => 'Validation failed',
				'errors'  => $error
			]);
			exit;
		}

		$result = $this->ApiModel->check_login($username, $password);
	

		if (!$result) {
			echo json_encode([
				'status'  => false,
				'message' => 'User not found'
			]);
		} else {
			$user     = array(
				'id'         => $result->User_SlNo,
				'name'       => $result->FullName,
				'usertype'   => $result->UserType,
				'employeeId' => $result->employee_id,
				'image_name' => $result->image_name,
				'branch'     => $result->Brunch_ID,
				'branch_name'     => $result->Brunch_name,
			);

			$token = $jwt->encode($user, $JwtSecretKey, 'HS256');
			echo json_encode([
				'status'  => true,
				'message' => 'User login successfully',
				'token'   => $token,
				'data'    => $user,
			]);
		}
	}



	// public function getUsers()
	// {

	// 	$headerToken = $this->input->get_request_header('Authorization');
	// 	$splitToken = explode(" ", $headerToken);
	// 	$token =  $splitToken[1];

	// 	try {
	// 		$token = verifyAuthToken($token);
	// 		if ($token) {
	// 			$users = $this->AuthModel->getUsers();
	// 			echo json_encode($users);
	// 		}
	// 	} catch (Exception $e) {
	// 		// echo 'Message: ' .$e->getMessage();
	// 		$error = array(
	// 			"status" => 401,
	// 			"message" => "Invalid Token provided",
	// 			"success" => false
	// 		);

	// 		echo json_encode($error);
	// 	}
	// }
}
