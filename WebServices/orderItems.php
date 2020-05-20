<?php 
    require_once '../DbOperations.php';
    $response = array();
    if( isset($_POST['GetOrderItems']) &&  isset($_POST['OrderId']) ){
            $db = new DbOperations();
            $OrderId = (float)$_POST['OrderId'];
            $OrderSum = $db->getOrderItems($OrderId);
            $response['Items'] = $OrderSum;
        } else{
        $response['error'] = true;
        $response['message'] = "SOMETHING WENT WRONG";
    }
    echo json_encode($response);