<?php 
    require_once '../DbOperations.php';
    date_default_timezone_set("Africa/Kampala");
    $response = array();
    if( $_SERVER['REQUEST_METHOD'] =='POST' ) {

        if(isset($_POST['Date']) && isset($_POST['TableId']) && isset($_POST['CompanyId']) && isset($_POST['UserId']) && isset($_POST['ClientId']) && isset($_POST['OutletId']) ){
            $db = new DbOperations();
            $OrderDate = date('Y-m-d'); //$_POST['Date'];
            $TableId = (float)$_POST['TableId'];
            $CompanyId = (float)$_POST['CompanyId'];
            $ClientId = (float)$_POST['ClientId'];
            $UserId = (float)$_POST['UserId'];
            $CompanyOutletId = (float)$_POST['OutletId'];
            $Time  =  date("H:i:a", time());
            if( strlen($OrderDate) > 3 ) {
                $LastBillNumber = $db->getLastBillNumber();
                $SystemLastBill = $LastBillNumber['Last_Bill_No']+1;
                $NewOrder = $db->createClientOrder($SystemLastBill,$TableId,$OrderDate,$Time,$CompanyId,$UserId,$ClientId,$CompanyOutletId);
                $LastOrder = $db->getLastOrderNumber();
                $CreatedOrderId = $LastOrder;
                $response['OrderId'] = $CreatedOrderId['order_id'];
            } 

        }
    } 
    echo json_encode($response);