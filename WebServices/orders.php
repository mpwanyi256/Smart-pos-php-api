<?php 
    require_once '../DbOperations.php';
    date_default_timezone_set("Africa/Kampala");
    //header('Content-Type: application/json');
    error_reporting(0);
    
    $response = array();
    if( $_SERVER['REQUEST_METHOD'] == 'POST' ){
        
        if( isset($_POST['AllPendingOrders']) && isset($_POST['UserRole']) && isset($_POST['UserId']) ) {
            $LoggedUserId = (float)$_POST['UserId'];
            $UserRole = (float)$_POST['UserRole'];
            $Type = $_POST['AllPendingOrders'];

            $db = new DbOperations();
            $OrdersPending = $db->getAllPendingOrders($UserRole,$LoggedUserId,$Type);
            $response['Orders'] = $OrdersPending;
        } 
        else{
            $response['error'] = true;
            $response['message'] = "Missing Parrams";
        }
        
    } else{
            $response['error'] = true;
            $response['message'] = "NO REQUEST SENT";
        }
    echo json_encode($response);