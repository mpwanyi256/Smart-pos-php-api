<?php 
    require_once '../DbOperations.php';
    $response = array();
    if( $_SERVER['REQUEST_METHOD'] == 'POST' ){
        $db = new DbOperations();
        $Menu = $db->getMenu();
        //$response['error'] = false;
        $response['MenuItems'] = $Menu;
    } else{
        $response['error'] = true;
        $response['message'] = "SOMETHING WENT WRONG";
    }
    echo json_encode($response);