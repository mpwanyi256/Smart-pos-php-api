<?php 
    require_once '../DbOperations.php';
    date_default_timezone_set("Africa/Kampala");
    header('Content-Type: application/json');
    session_start();

    $response = array();
    if( $_SERVER['REQUEST_METHOD'] == 'POST' ){
        
        if(isset($_POST['Clients']) &&  isset($_POST['Company'])) {
            $db = new DbOperations();
            $CompanyId = (float)$_POST['Company'];
            $Clients = $db->getClientList($CompanyId);
            $response['Clients'] = $Clients;

        } else if(isset($_POST['UpdateClientDetails']) &&  isset($_POST['ClientId']) &&  isset($_POST['Name']) && isset($_POST['Address']) && isset($_POST['Contact']) && isset($_POST['Email']) ){
            $db = new DbOperations();
            $ClientId = (float)$_POST['ClientId'];
            $Name = $_POST['Name'];
            $Address = $_POST['Address'];
            $Contact = $_POST['Contact'];
            $Email = $_POST['Email'];
            $UpdateClientDetails = $db->updateClientDetails($ClientId, $Name, $Address, $Contact, $Email);
            if($UpdateClientDetails){
                $response['Response'] = true;
            } else{
                $response['Response'] = false;
            }
            
            
        } else if( isset($_POST['GetPayments']) &&  isset($_POST['ClientId'])){
            $db = new DbOperations();
            $ClientId = (float)$_POST['ClientId'];
            $LastPayments = $db->getPayments($ClientId);
            $response['Payments'] = $LastPayments;
            
        } else if(isset($_POST['NewOrder']) && isset($_POST['Date']) && isset($_POST['TableId']) && isset($_POST['CompanyId']) && isset($_POST['UserId']) && isset($_POST['ClientId']) && isset($_POST['OutletId']) ){
            $db = new DbOperations();
            $OrderDate = date('Y-m-d'); //$_POST['Date'];
            $TableId = (float)$_POST['TableId'];
            $CompanyId = (float)$_POST['CompanyId'];
            $ClientId = (float)$_POST['ClientId'];
            $UserId = (float)$_POST['UserId'];
            $CompanyOutletId = (float)$_POST['OutletId'];
            $OrderType = $_POST['NewOrder'];
            $Time  =  date("H:i:a", time());
            $License = $db->checkLicense();
            
            if($License){
                if( strlen($OrderDate) > 3 ) {
                    $LastBillNumber = $db->getLastBillNumber();
                    $SystemLastBill = $LastBillNumber['Last_Bill_No']+1;
                    $NewOrder = $db->createClientOrder($SystemLastBill,$TableId,$OrderDate,$Time,$CompanyId,$UserId,$ClientId,$CompanyOutletId,$OrderType);
                    $response['OrderId'] = $NewOrder['order_id'];
                    $response['error'] = false;
                }
            }else{
                $response['error'] = true;
                $response['message'] = "SORRY, LICENSE EXPIRED";
            }
            
             

        } else if( isset($_POST['AddItemToOrder']) && isset($_POST['OrderId']) && isset($_POST['MenuItemId']) && isset($_POST['MenuItemPrice']) && isset($_POST['Quantity']) && isset($_POST['UserId']) ) {
            $db = new DbOperations();
            $OrderId = (float)$_POST['OrderId'];
            $MenuItemId = (float)$_POST['MenuItemId'];
            $MenuItemPrice = (float)$_POST['MenuItemPrice'];
            $Quantity = (float)$_POST['Quantity'];
            $UserId = (float)$_POST['UserId'];

            $AddItemToOrder = $db->addItemToOrder($OrderId,$MenuItemId,$MenuItemPrice,$Quantity,$UserId);
            $response = $AddItemToOrder;

        } else if( isset($_POST['AllPendingOrders']) && isset($_POST['UserRole']) && isset($_POST['UserId']) ) {
            $db = new DbOperations();
            $LoggedUserId = $_POST['UserId'];
            $UserRole = $_POST['UserRole'];
            $BillNumber = (float)$_POST['BillNo'];
            $Returns = $_POST['AllPendingOrders'];

            $OrdersPending = $db->getAllPendingOrders($UserRole,$LoggedUserId);
            
            $response = $OrdersPending;
        } else if(isset($_POST['SearchBillNumber'])){
            $BillNumber = (float)$_POST['SearchBillNumber'];
             $db = new DbOperations();
             
            $OrdersPending = $db->searchBillNo($BillNumber);
            
            $response['Orders'] = $OrdersPending;
        } else if( isset($_POST['GetOrderSum']) &&  isset($_POST['OrderId']) ){
            $db = new DbOperations();
            $OrderId = $_POST['OrderId'];
            $OrderSum = $db->getOrderSum($OrderId);
            $response = $OrderSum;
        } else if( isset($_POST['SetOrderAsPrinted']) &&  isset($_POST['OrderId']) ){
            $db = new DbOperations();
            $OrderId = (float)$_POST['OrderId'];
            $Printed = $db->setOrderAsPrinted($OrderId);
            $response = $Printed;
        } else if( isset($_POST['GetOrderItems']) &&  isset($_POST['OrderId']) ){
            $db = new DbOperations();
            $OrderId = (float)$_POST['OrderId'];
            $OrderSum = $db->getOrderItems($OrderId);
            $response['Items'] = $OrderSum;
        } else if(isset($_POST['UpdateQuantity']) && isset($_POST['NewQuantity']) && isset($_POST['RecordId']) ) {
            $Quantity = $_POST['NewQuantity'];
            $RecordId = $_POST['RecordId'];
            $db = new DbOperations();
            $UpdateItem = $db->updateItemQuantity($RecordId, $Quantity);
            $response['Response'] = $UpdateItem;
            
        } else if(isset($_POST['DeleteItemOnOrder']) && isset($_POST['OrderItemRecordId']) ) {
            $RecordId = $_POST['OrderItemRecordId'];
            $db = new DbOperations();
            $DeleteItem = $db->deleteItemOnOrder($RecordId);
            $response['Response'] = $DeleteItem;
            
        } else if(isset($_POST['SetOrderAsSeen']) && isset($_POST['OrderId']) && isset($_POST['UserName']) && isset($_POST['UserId']) ) {
            $OrderId = (float)$_POST['OrderId'];
            $Username= $_POST['UserName'];
            $UserId = (float)$_POST['UserId'];
            $mDate = date('Y-m-d');
            $db = new DbOperations();
            $UpdateRecord = $db->setOrderAsSeen($OrderId,$Username,$mDate,$UserId);
            $response['Response'] = $UpdateRecord;
            
        } else if(isset($_POST['NewClient']) && isset($_POST['Name']) && isset($_POST['Address']) && isset($_POST['Email']) && isset($_POST['Contact']) && isset($_POST['Tin']) && isset($_POST['Company']) ) {
            $Name = $_POST['Name'];
            $Address = $_POST['Address'];
            $Contact = $_POST['Contact'];
            $Tin = $_POST['Tin'];
            $Email = $_POST['Email'];
            $Company = $_POST['Company'];
            $db = new DbOperations();
            if($db->checkIfClientExists($Name, $Address)){
                $response['Response'] = "Already Exists";
            } else {
                $newCompany = $db->createNewClient($Name,$Address,$Tin,$Email,$Contact,$Company);
                $response['Response'] = $newCompany;
            }
            
        } else if( isset($_POST['CancelOrderWithReason']) && isset($_POST['OrderId']) && isset($_POST['Reason']) ){
            $OrderId = (float)$_POST['OrderId'];
            $Reason = $_POST['Reason'];
            $db = new DbOperations();
            $Cancel = $db->cancelOrder($OrderId,$Reason);
            if($Cancel){
               $response['Response'] = "Success Order Cancelled"; 
               $response['error'] = false;
            }else{
                $response['Response'] = "Sorry, Something Went Wrong";
               $response['error'] = true;
            }
            
            
        } else if(isset($_POST['CheckClientBalance']) && isset($_POST['CLientId']) && isset($_POST['OrderDate'])  ) {
            $db = new DbOperations();
            $ClientId = $_POST['CLientId'];
            $OrderDate = $_POST['OrderDate'];
            
            $getAccumulatedTotal = $db->getClientAccumulatedBillSum($ClientId,$OrderDate);
            $TotalBillAccumulation = $getAccumulatedTotal[0]['BillSum'];//ok
            
            $TotalCashPaid = $db->getClientCashPayments($ClientId,$OrderDate);
            $CashPaymentTotal = (float)$TotalCashPaid[0]['billSumToDate'];//ok
            
            $mCreditPayments = $db->getClientCreditPayments($ClientId,$OrderDate);
            $TotalCreditPayments = (float)$mCreditPayments[0]['ttlPayments'];//ok
            
            $ClientOutstandingBalance = $TotalBillAccumulation - ($CashPaymentTotal+$TotalCreditPayments); // Todate Including Bill Viewed
            $response['Balance'] = $ClientOutstandingBalance;
            $response['CashPaid'] = $CashPaymentTotal;
            $response['CreditPayments'] = $TotalCreditPayments;
            
        } else if( isset($_POST['NewPayment']) && isset($_POST['ClientId'])  && isset($_POST['Amount']) && isset($_POST['PaymentMode']) && isset($_POST['AddedBy']) && isset($_POST['Reference']) && isset($_POST['Remarks']) ){
            $ClientId = (float)$_POST['ClientId'];
            $OrderId  = (float)$_POST['OrderId']; //Not Necessary for credit payment
            $Amount   = (float)$_POST['Amount'];
            $PaymentMode = (float)$_POST['PaymentMode'];
            $Date =  date('Y-m-d');//$_POST['Date'];
            $AddedBy = (float)$_POST['AddedBy'];
            $Reference = $_POST['Reference'];
            $Remarks = $_POST['Remarks'];
            $db = new DbOperations();
            
            if($ClientId <= 0) {
                $AddPayment = $db->settleCashOrder($OrderId,$Amount,$PaymentMode,$Date,$AddedBy,$Reference);
                if($AddPayment){
                    $response['Response'] = "OK";
                }else {
                    $response['Response'] = "Sorry Something Went Wrong";
                }
            }else{
                $AddPayment = $db->createCreditPayment($ClientId,$Amount,$PaymentMode,$Date,$AddedBy,$Reference,$Remarks);
                if($AddPayment){
                    $response['Response'] = "OK";
                }else {
                    $response['Response'] = "Sorry Something Went Wrong";
                } 
            }
            
            
            
        } else if( isset($_POST['CancelOrderWithReason']) && isset($_POST['OrderId']) && isset($_POST['Reason']) && isset($_POST['UserId']) ) {
            $OrderId = (float)$_POST['OrderId'];
            $Reason = $_POST['Reason'];
            $UserId = $_POST['UserId'];
            $db = new DbOperations();
            $CancelOrder = $db->cancelOrderWithReason($OrderId, $Reason, $UserId);
            
            //Accounting Functions
        } else if( isset($_POST['GetExpenseHeads']) && isset($_POST['CompanyId']) ) {
            $CompanyId = (float)$_POST['CompanyId'];
            $Returns = $_POST['GetExpenseHeads'];
            $db = new DbOperations();
            $response['Ledgers'] = $db->getExpenseLedgers($CompanyId,$Returns);
            
        } else if( isset($_POST['GetPettyCash']) && isset($_POST['CompanyId']) && isset($_POST['OutletId']) ) {
            $CompanyId = (float)$_POST['CompanyId'];
            $OutletId = (float)$_POST['OutletId'];
            $Date = date('Y-m-d');
            $db = new DbOperations();
            $response['Ledgers'] = $db->getExpensesByDate($Date,$Date,$CompanyId,$OutletId);
            
        } else if(isset($_POST['CreateExpense']) && isset($_POST['LedgerId']) && isset($_POST['PaymentRef']) 
        && isset($_POST['Remarks']) && isset($_POST['Amount']) && isset($_POST['PaymentMode']) && isset($_POST['UserId']) && isset($_POST['CompanyId']) && isset($_POST['OutletId']) ) {
            $Date = date('Y-m-d');
            $LedgerId = (float)$_POST['LedgerId'];
            $SupplierId = 0; //(float)$_POST['SupplierId'];
            $PaymentRef = $_POST['PaymentRef'];
            $Remarks = $_POST['Remarks'];
            $Amount = (float)$_POST['Amount'];
            $PaymentMode = (float)$_POST['PaymentMode'];
            $UserId = (float)$_POST['UserId'];
            $CompanyId = (float)$_POST['CompanyId'];
            $OutletId = (float)$_POST['OutletId'];
            
            $db = new DbOperations();
            $response = $db->createExpense($Date,$LedgerId,$SupplierId,$PaymentRef,$Remarks,$Amount,$PaymentMode,$UserId,$CompanyId,$OutletId);
            
        } else if(isset($_POST['UpdateExpense']) && isset($_POST['ExpenseId']) && isset($_POST['Amount']) && isset($_POST['Remarks']) && isset($_POST['PaymentReference']) ){
            $ExpenseId = (float)$_POST['ExpenseId'];
            $Amount = (float)$_POST['Amount'];
            $Remarks = $_POST['Remarks'];
            $Reference = $_POST['PaymentReference'];
            $db = new DbOperations();
            $response['response'] = $db->updateExpense($ExpenseId, $Amount, $Remarks, $Reference);
        } else if(isset($_POST['DeleteExpenseRecord']) && isset($_POST['ExpenseId']) ){
            $ExpenseId = (float)$_POST['ExpenseId'];
            $db = new DbOperations();
            $response['response'] = $db->deleteExpense($ExpenseId);
        }
        
    } else{
        $response['error'] = true;
        $response['message'] = "SOMETHING WENT WRONG";
    }
    echo json_encode($response);