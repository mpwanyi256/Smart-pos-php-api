<?php
require_once '../DbOperations.php';
$response = array();

if( $_SERVER['REQUEST_METHOD']=='POST' ){

    if( isset($_POST['username']) && isset($_POST['password']) ){
        $db = new DbOperations();
        $License = $db->checkLicense();
        
        if($License){
            //Check If User Exists
            if( $db->userExists($_POST['username'],$_POST['password']) ){
               $user = $db->getUserByUsername($_POST['username']);
               $response['UserInfo'] =  $user;
               $response['error'] = false;
               /*$response['error'] = false;
               $response['id'] = $user['user_id'];
               $response['name'] = $user['user_name'];
               $response['role'] = $user['user_role'];
               $response['OutletId'] = $user['outlet_id'];
               $response['CompanyName'] = $user['company_name'];
               $response['Location'] = $user['company_location'];
               $response['Contact'] = $user['company_mobile'];
               $response['Footer'] = $user['receipt'];
               $response['Currency'] = $user['currency'];*/
            } else{
                $response['error'] = true;
                $response['message'] = "Invalid Username Or Password";
            }
            
        } else{
            $response['error'] = true;
            $response['message'] = "SORRY, LICENSE EXPIRED";
        }
        

    } else{
        $response['error'] = true;
        $response['message'] = "Required Fields Are Missing";
    } 

} else if($_SERVER['REQUEST_METHOD']=='GET') {

if( isset($_GET['username']) && isset($_GET['password']) ){
    $db = new DbOperations();
    //Check If User Exists
    if( $db->userExists($_GET['username'],$_GET['password']) ){
        $user = $db->getUserByUsername($_GET['username']);
        $response['error'] = false;
        $response['id'] = $user['user_id'];
        $response['name'] = $user['user_name'];
        $response['role'] = $user['user_role'];
        $response['outlet_id'] = $user['outlet_id'];
    } else{
        $response['error'] = true;
        $response['message'] = "Invalid Username Or Password";
    }

} else {
    $response['error'] = true;
    $response['message'] = "Please Enter Username And Password";
}

}  else{
    $response['error'] = true;
    $response['message'] = "SORRY, WRONG POST REQUEST";
}

echo json_encode($response);