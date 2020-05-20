<?php
    date_default_timezone_set("Africa/Kampala");

    class DbOperations{
        private $con;

        function __construct(){
            require_once dirname(__FILE__).'/DbConnect.php';
            $db = new DbConnect();
            $this->con = $db->connect();
        }
        
        function checkLicense(){
            $License = $this->con->prepare("SELECT date_end FROM licences ORDER BY license_id DESC LIMIT 1 ");
            $License->execute();
            $DateNow = $License->get_result()->fetch_assoc();
            $dbDate = $DateNow['date_end'];
            $mToday       = date('Y-m-d');
            $DifferenceInDays =  round((strtotime($dbDate) - strtotime($mToday))/(3600*24));
            return $DifferenceInDays>1;
        }

        //Function to Create User
        function createUser($username, $pass, $Role){
            $Password = md5($pass);
            $DateToday = date('Y-m-d');
            $UserToken = random_bytes(7);
            $Sqlquery = $this->con->prepare("INSERT INTO `pos_companies` ( `company_id`, `user_name`, `user_key`, `token`, `user_role`, `date_joined`, `is_active`) 
            VALUES ('11', '".$username."', '".$Password."', '".$UserToken."', ".$Role.", '".$DateToday."', '1')");
            //$Sqlquery->bind_param("sss", $username, $Password, $UserToken, $Role, $DateToday);
            if($Sqlquery->execute()){
                return true;
            } else{
                return false;
            }
        }
        
        public function updateItemQuantity($RecordId,$Quantity){
            $updateQuery = $this->con->prepare("UPDATE order_items SET quantity=".$Quantity." WHERE order_item_id=".$RecordId." ");
            if($updateQuery->execute()){
                return true;
            }else{
                return false;
            }
        }
        
        public function getClientAccumulatedBillSum($ClientId,$Date){
            $Statement = $this->con->prepare("SELECT SUM(order_items.total) AS BillSum FROM order_items 
            INNER JOIN client_orders ON client_orders.order_id=order_items.order_id 
            WHERE client_orders.client_company_id=? AND client_orders.date<=? AND client_orders.status NOT IN(9,6)  ");
            $Statement->bind_param("is",$ClientId,$Date);
            $Statement->execute();

            $RESULT = array();
            $Statement->store_result();
            for ( $i = 0; $i < $Statement->num_rows; $i++ ) {
                $Metadata = $Statement->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Statement, 'bind_result' ), $PARAMS );
                $Statement->fetch();
            }
            return $RESULT;

        }
        
        public function getClientCashPayments($ClientId,$Date){
            $Statement = $this->con->prepare("SELECT SUM(order_settements.amount_settled) AS billSumToDate FROM order_settements 
            INNER JOIN client_orders ON client_orders.order_id=order_settements.order_id 
            WHERE order_settements.settlement_type NOT IN(88,4) AND client_orders.client_company_id=".$ClientId." AND client_orders.date<='".$Date."' ");
            $Statement->execute();
            $RESULT = array();
            $Statement->store_result();
            for ( $i = 0; $i < $Statement->num_rows; $i++ ) {
                $Metadata = $Statement->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Statement, 'bind_result' ), $PARAMS );
                $Statement->fetch();
            }
            return $RESULT;

        }
        
        public function getClientCreditPayments($ClientId,$Date){
            $Statement = $this->con->prepare("SELECT SUM(amount) AS ttlPayments FROM credit_payments WHERE client_id=".$ClientId." AND payent_date<='".$Date."' ");
            $Statement->execute();
            
            $RESULT = array();
            $Statement->store_result();
            for ( $i = 0; $i < $Statement->num_rows; $i++ ) {
                $Metadata = $Statement->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Statement, 'bind_result' ), $PARAMS );
                $Statement->fetch();
            }
            return $RESULT;

        }
        
        public function updateClientDetails($ClientId, $Name, $Address, $Contact, $Email){
            $Query = $this->con->prepare("UPDATE client_companies SET company_name='".$Name."', address='".$Address."', contact_number='".$Contact."', email_id='".$Email."' WHERE clientCompany_id=".$ClientId." ");
            if($Query->execute()){
                return true;
            } else {
                return false;
            }
            
        }
        
        public function createNewClient($Name,$Address,$Tin,$Email,$Contact,$Company){
            $newClient = $this->con->prepare(" INSERT INTO client_companies(company_name,tin,address,email_id,contact_number,restaurant_id)
            VALUES('".$Name."','".$Tin."','".$Address."','".$Email."','".$Contact."',".$Company.") ");
            if($newClient->execute()){
                return true;
            }else{
                return false;
            }
        }
        
        public function createCreditPayment($client_id,$amount,$payment_mode,$payent_date,$added_by,$payent_reference,$remarks){
            $AddPayment = $this->con->prepare("INSERT INTO credit_payments(client_id,bill_number,amount,payment_mode,payent_date,added_by,payent_reference,remarks) 
                                        VALUES(".$client_id.",0,".$amount.",".$payment_mode.",'".$payent_date."',".$added_by.",'".$payent_reference."','".$remarks."') ");
            if($AddPayment->execute()){
                return true;
            } else{
                return false;
            }
            
        }
        
        public function settleCashOrder($OrderId,$amount,$payment_mode,$payent_date,$added_by,$payent_reference){
            $AddPayment = $this->con->prepare("INSERT INTO credit_payments(bill_number,amount,payment_mode,payent_date,added_by,payent_reference,remarks) 
            VALUES(".$OrderId.",".$amount.",".$payment_mode.",'".$payent_date."',".$added_by.",'".$payent_reference."','CASH PURCHASE') ");
            if($AddPayment->execute()){
                $UpdateState = $this->con->prepare("UPDATE client_orders SET status=".$payment_mode." WHERE order_id=".$OrderId." ");
                if($UpdateState->execute()){
                    return true;   
                }
            } else{
                return false;
            }
        }
        
        public function deleteItemOnOrder($RecordId){
            $deleteQuery = $this->con->prepare("DELETE FROM order_items WHERE order_item_id=".$RecordId." LIMIT 1 ");
            if($deleteQuery->execute()){
                return true;
            }else{
                return false;
            }
        }

        public function getUserByUsername($username){
            $Statement = $this->con->prepare("SELECT pos_companies.*,company.* FROM `pos_companies` INNER JOIN company ON company.company_id=pos_companies.company_id WHERE pos_companies.user_name= ? AND pos_companies.is_active=1 LIMIT 1");
            $Statement->bind_param("s",$username);
            $Statement->execute();
            //return $stmt->get_result()->fetch_assoc();
            
            $RESULT = array();
            $Statement->store_result();
            for ( $i = 0; $i < $Statement->num_rows; $i++ ) {
                $Metadata = $Statement->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Statement, 'bind_result' ), $PARAMS );
                $Statement->fetch();
            }
            return $RESULT;
        }

        public function userExists($username, $Password){
            $stmt = $this->con->prepare("SELECT * FROM `pos_companies` WHERE user_name= ? AND user_key= ? AND is_active=1 ");
            $stmt->bind_param("ss",$username,$Password);
            $stmt->execute();
            $stmt->store_result();
            return $stmt->num_rows > 0;
        }
        
        public function setOrderAsCancelled($OrderId,$Username){
            $stmnt = $this->con->prepare("UPDATE client_orders SET status=9,seen=1,seen_by='".$Username."' WHERE order_id=".$OrderId." ");
            $stmnt->execute();
            return true;
        }
        
        public function setOrderAsPrinted($OrderId,$UserId){
            $stmnt = $this->con->prepare("UPDATE client_orders SET bill_printed=1,confirmed_by=".$UserId." WHERE order_id=".$OrderId." ");
            $stmnt->execute();
            return true;
        }
        
        public function checkIfClientExists($Name, $Loation){
            $stmt = $this->con->prepare("SELECT * FROM client_companies WHERE company_name= ? AND address= ? ");
            $stmt->bind_param("ss",$Name,$Loation);
            $stmt->execute();
            $stmt->store_result();
            return $stmt->num_rows > 0;
        }

        public function getDayOpen(){
            $stmt = $this->con->prepare("SELECT MAX(day_open) AS DayOpen FROM day_open");
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();

        }

        public function getSections(){
            $Statement = $this->con->prepare("SELECT section_id AS Id,section_name As Name FROM reastaurant_sections WHERE section_name NOT IN('TAKE AWAY','DEFAULT') ORDER BY section_name ASC ");
            $Statement->execute();
            $RESULT = array();
            $Statement->store_result();
            for ( $i = 0; $i < $Statement->num_rows; $i++ ) {
                $Metadata = $Statement->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Statement, 'bind_result' ), $PARAMS );
                $Statement->fetch();
            }
            return $RESULT;
        }

        public function getSectionTables($SectionId) {
            $Statement = $this->con->prepare("SELECT table_id,table_name FROM restaurant_tables WHERE section_id=".$SectionId." AND hide=0 ORDER BY table_name ASC ");
            $Statement->execute();
            $Statement->store_result();
            $RESULT = array();
            for ( $i = 0; $i < $Statement->num_rows; $i++ ) {
                $Metadata = $Statement->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Statement, 'bind_result' ), $PARAMS );
                $Statement->fetch();
            }
            return $RESULT;
        }

        public function getMenu() {
            $Statement = $this->con->prepare("SELECT menu_items.item_id AS Id,menu_items.item_name AS ItemName, menu_items.item_price AS Price FROM menu_items 
            INNER JOIN categoriies ON categoriies.category_id = menu_items.item_category_id 
            WHERE menu_items.item_category_id 
            NOT IN(0) AND menu_items.item_name NOT IN('OPEN DISH','VAT') AND categoriies.status=0 AND menu_items.hide=0 ORDER BY item_name ASC ");
            $Statement->execute();
            $Statement->store_result();
            $RESULT = array();
            for ( $i = 0; $i < $Statement->num_rows; $i++ ) {
                $Metadata = $Statement->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Statement, 'bind_result' ), $PARAMS );
                $Statement->fetch();
            }
            return $RESULT;
        }

        public function getClientList($CompanyId) {
            $Statement = $this->con->prepare("SELECT company_name AS ClientName,address AS Location,clientCompany_id AS ClientId, contact_number AS Mobile, email_id As Email
            FROM client_companies WHERE restaurant_id=".$CompanyId." ORDER BY ClientName ASC ");
            $Statement->execute();
            $Statement->store_result();
            $RESULT = array();
            for ( $i = 0; $i < $Statement->num_rows; $i++ ) {
                $Metadata = $Statement->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Statement, 'bind_result' ), $PARAMS );
                $Statement->fetch();
            }
            return $RESULT;
        }
        
        public function getPayments($ClientId){
            $Statement = $this->con->prepare("SELECT credit_payments.client_id AS ClientId,credit_payments.payment_id AS ReceitNo,credit_payments.amount AS Amount, credit_payments.payent_date AS PayDate, credit_payments.payent_reference AS Reference, credit_payments.remarks AS Remarks,client_companies.company_name AS ClientName, client_companies.address AS Address,settlements.settlement AS PaymentMode,pos_companies.user_name AS AddedBy FROM credit_payments INNER JOIN client_companies ON client_companies.clientCompany_id=credit_payments.client_id INNER JOIN settlements ON settlements.settlement_id=credit_payments.payment_mode INNER JOIN pos_companies ON pos_companies.user_id=credit_payments.added_by ORDER BY credit_payments.payment_id DESC LIMIT 50 ");
            $Statement->execute();
            $Statement->store_result();
            $RESULT = array();
            for ( $i = 0; $i < $Statement->num_rows; $i++ ) {
                $Metadata = $Statement->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Statement, 'bind_result' ), $PARAMS );
                $Statement->fetch();
            }
            return $RESULT;
        }

        public function getLastBillNumber(){
            $LastBillNum = $this->con->prepare("SELECT MAX(bill_No) AS Last_Bill_No FROM client_orders ");
            $LastBillNum->execute();
            return $LastBillNum->get_result()->fetch_assoc();
        }
        
        public function createClientOrder($NewBillNo, $TableSelected, $Date, $Time, $CompanyId, $UserId, $ClientId, $CompanyOutletId,$OrderType){
            $NewClientOrder = $this->con->prepare("INSERT INTO client_orders(bill_No,order_type,table_id,date,time,status,company_id,created_by,token_set_by,waiter,mode,client_company_id,outlet_id) 
            VALUES(".$NewBillNo.",'".$OrderType."',".$TableSelected.",'".$Date."','".$Time."',0,".$CompanyId.",".$UserId.",".$UserId.",".$UserId.",'dine',".$ClientId.",".$CompanyOutletId.")");
            
            $NewClientOrder->execute();
            $Statement = $this->con->prepare("SELECT MAX(order_id) AS order_id FROM client_orders");
            $Statement->execute();
            $CreatedId = $Statement->get_result()->fetch_assoc();
            return $CreatedId;
        }
        
        public function getLastOrderNumber(){
            $LastBillNum = $this->con->prepare("SELECT MAX(order_id) AS order_id FROM client_orders ");
            $LastBillNum->execute();
            return $LastBillNum->get_result()->fetch_assoc();
        }
        
        public function getOrderType($OrderId){
            $OrderType = $this->con->prepare("SELECT order_type AS Type,bill_No AS BillNumber,client_company_id AS CompanyId FROM client_orders WHERE order_id=".$OrderId." LIMIT 1 ");
            $OrderType->execute();
            return $OrderType->get_result()->fetch_assoc();
        }
        
        public function getBillSum($OrderId){
            $mBillSum = $this->con->prepare("SELECT SUM(total) AS TotalBill FROM order_items WHERE order_id=".$OrderId." ");
            $LastBillNum->execute();
            return $LastBillNum->get_result()->fetch_assoc();
        }
        
        public function setOrderAsSeen($OrderId,$Username,$DateToday,$UserId){
            $stmnt = $this->con->prepare("UPDATE client_orders SET status=4,seen=1,seen_by='".$Username."' WHERE order_id=".$OrderId." ");
            $stmnt->execute();
            $Statement = $this->con->prepare("SELECT order_type AS Type,bill_No AS BillNumber,client_company_id AS CompanyId FROM client_orders WHERE order_id=".$OrderId." LIMIT 1  ");
            $Statement->execute();

            $RESULT = array();
            $Statement->store_result();
            for ( $i = 0; $i < $Statement->num_rows; $i++ ) {
                $Metadata = $Statement->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Statement, 'bind_result' ), $PARAMS );
                $Statement->fetch();
            }
            $OrderResults = $RESULT[0];
            $mOrderType = $OrderResults['Type'];
            $mBillNumberRef = $OrderResults['BillNumber'];
            $ClientId = $OrderResults['CompanyId'];
            
            if($mOrderType == "REPLACE"){//If It is a Replace For Damage Or Expired
                $OrderSum = $this->con->prepare("SELECT SUM(total) AS billTotal FROM order_items WHERE order_id=".$OrderId." ");
                $OrderSum->execute();
                //$OrderSum->get_result()->fetch_assoc();
                $mRESULT = array();
                $OrderSum->store_result();
                for ( $i = 0; $i < $OrderSum->num_rows; $i++ ) {
                    $Metadata = $OrderSum->result_metadata();
                    $PARAMS = array();
                    while ( $Field = $Metadata->fetch_field() ) {
                        $PARAMS[] = &$mRESULT[ $i ][ $Field->name ];
                    }
                    call_user_func_array( array( $OrderSum, 'bind_result' ), $PARAMS );
                    $OrderSum->fetch();
                }
                $dbBillSum = $mRESULT[0];
                
                $CreditNoteAmount = $dbBillSum['billTotal'];
                $Reference = "CREDIT NOTE";
                $CrdtRemarks = "CONFIRMED BY ".$Username;
                
                //Check If Already Exists
                $checkCredit = $this->con->prepare("SELECT * FROM credit_payments WHERE client_id=".$ClientId." AND bill_number=".$mBillNumberRef." AND payment_mode=6 ");
                $checkCredit->execute();
                $checkCredit->store_result();
                if($checkCredit->num_rows > 0){//If Record Exists
                    return "Record Exists";
                } else{
                    //Create Credit Note
                    $CreateCreditNote = $this->con->prepare("INSERT INTO credit_payments(client_id,bill_number,amount,payment_mode,payent_date,added_by,payent_reference,remarks)
                    VALUES(".$ClientId.",".$mBillNumberRef.",".$CreditNoteAmount.",6,'".$DateToday."',".$UserId.",'".$Reference."','".$CrdtRemarks."') ");
                    if($CreateCreditNote->execute()){
                        $UpdateOrder =  $this->con->prepare("UPDATE client_orders SET description='".$Reference."',status=4 WHERE order_id=".$OrderId." ");
                        $UpdateOrder->execute();
                        return "Credit Added";
                    } else{
                        return "Credit Note Not Added";
                    }
                }
                
            }else{
                return "NOT A REPLACE";
            }
            
        }

        public function addItemToOrder($OrderId, $MenuItemId, $ItemPrice, $Quantity,$UserId) {
            /*Check If Item Exists
            $checkItem = $this->con->prepare("SELECT * FROM order_items WHERE menu_item_id=".$MenuItemId." AND order_id=".$OrderId." ");
            $itemExst = $checkItem->execute();
            $itemExst->store_result();
            $ItemCounter = $itemExst->num_rows>0;*/
            $Total = $ItemPrice*$Quantity;
            $AddItem = $this->con->prepare("INSERT INTO order_items(order_id,menu_item_id,item_unit_price,menu_item_price,quantity,total,status,added_by,kot) 
            VALUES(".$OrderId.",".$MenuItemId.",".$ItemPrice.",".$ItemPrice.",".$Quantity.",".$Total.",0,".$UserId.",0) ");
            if($AddItem->execute()){
                return true;
            } else {
                return false;
            }
            

        }
        
        public function cancelOrderWithReason($OrderId, $Reason, $UserId) {
            $UpdateClientOrder = $this->con->prepare("UPDATE client_orders SET status=9, description='".$Reason."', settled_by=".$UserId." WHERE order_id=".$OrderId." ");
            $UpdateClientOrder->execute();
            
            
        }

        public function getAllPendingOrders($UserRole,$UserId,$type) {
           
                if($type == 'Pending') {//Super User
                    $OrdersToReturn = "SELECT client_orders.order_type AS Type, client_orders.description As Reason,client_orders.client_company_id AS ClientId,client_orders.status AS Status,client_orders.bill_printed As Printed,client_orders.seen As Seen, client_orders.settled_by AS SeenBy,client_orders.order_id AS OrderId,client_orders.date AS OrderDate,client_orders.bill_No AS OrderNumber,client_orders.time AS OrderTime,pos_companies.user_name AS CreatedBy,client_companies.company_name AS Client FROM client_orders INNER JOIN pos_companies ON pos_companies.user_id=client_orders.waiter LEFT JOIN client_companies ON client_companies.clientCompany_id=client_orders.client_company_id WHERE client_orders.status=0 AND client_orders.seen=0 ORDER BY client_orders.order_id DESC";
                } else if($type == 'Confirmed') {//Confirmed
                    $OrdersToReturn = "SELECT client_orders.order_type AS Type,client_orders.description As Reason,client_orders.client_company_id AS ClientId,client_orders.status AS Status,client_orders.bill_printed As Printed,client_orders.seen As Seen, client_orders.settled_by AS SeenBy,client_orders.order_id AS OrderId,client_orders.date AS OrderDate,client_orders.bill_No AS OrderNumber,client_orders.time AS OrderTime,pos_companies.user_name AS CreatedBy,client_companies.company_name AS Client FROM client_orders INNER JOIN pos_companies ON pos_companies.user_id=client_orders.waiter LEFT JOIN client_companies ON client_companies.clientCompany_id=client_orders.client_company_id WHERE client_orders.status NOT IN(9) AND client_orders.seen IN(1) ORDER BY client_orders.order_id DESC";
                } else if($type == 'Delivered') {//Others
                    $OrdersToReturn = "SELECT client_orders.order_type AS Type,client_orders.description As Reason,client_orders.client_company_id AS ClientId,client_orders.status AS Status,client_orders.bill_printed As Printed,client_orders.seen As Seen, client_orders.settled_by AS SeenBy,client_orders.order_id AS OrderId,client_orders.date AS OrderDate,client_orders.bill_No AS OrderNumber,client_orders.time AS OrderTime,pos_companies.user_name AS CreatedBy,client_companies.company_name AS Client FROM client_orders INNER JOIN pos_companies ON pos_companies.user_id=client_orders.waiter LEFT JOIN client_companies ON client_companies.clientCompany_id=client_orders.client_company_id WHERE client_orders.bill_printed NOT IN(0) AND client_orders.seen=1 ORDER BY client_orders.order_id DESC LIMIT 50";
                } else if($type == 'Voided') {//Cancelled Orders
                    $OrdersToReturn = "SELECT client_orders.order_type AS Type,client_orders.description As Reason, client_orders.client_company_id AS ClientId,client_orders.status AS Status,client_orders.bill_printed As Printed,client_orders.seen As Seen, client_orders.settled_by AS SeenBy,client_orders.order_id AS OrderId,client_orders.date AS OrderDate,client_orders.bill_No AS OrderNumber,client_orders.time AS OrderTime,pos_companies.user_name AS CreatedBy,client_companies.company_name AS Client FROM client_orders INNER JOIN pos_companies ON pos_companies.user_id=client_orders.waiter LEFT JOIN client_companies ON client_companies.clientCompany_id=client_orders.client_company_id WHERE client_orders.status IN(9) ORDER BY client_orders.order_id DESC LIMIT 50";
                }
            
            

            $Orders = $this->con->prepare($OrdersToReturn);
            $Orders->execute();
            $Orders->store_result();
            $RESULT = array();
            for ( $i = 0; $i < $Orders->num_rows; $i++ ) {
                $Metadata = $Orders->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Orders, 'bind_result' ), $PARAMS );
                $Orders->fetch();
            }
            return $RESULT;
        }
        
        public function searchBillNo($BillNumber) {
            $OrdersToReturn = "SELECT client_orders.order_type AS Type, client_orders.description As Reason,client_orders.client_company_id AS ClientId,client_orders.status AS Status,client_orders.bill_printed As Printed,client_orders.seen As Seen, client_orders.settled_by AS SeenBy,client_orders.order_id AS OrderId,client_orders.date AS OrderDate,client_orders.bill_No AS OrderNumber,client_orders.time AS OrderTime,pos_companies.user_name AS CreatedBy,client_companies.company_name AS Client FROM client_orders INNER JOIN pos_companies ON pos_companies.user_id=client_orders.waiter LEFT JOIN client_companies ON client_companies.clientCompany_id=client_orders.client_company_id WHERE client_orders.bill_No=".$BillNumber." ";
            
            $Orders = $this->con->prepare($OrdersToReturn);
            $Orders->execute();
            $Orders->store_result();
            $RESULT = array();
            for ( $i = 0; $i < $Orders->num_rows; $i++ ) {
                $Metadata = $Orders->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Orders, 'bind_result' ), $PARAMS );
                $Orders->fetch();
            }
            return $RESULT;
        }

        public function getOrderSum($OrderId){
            $Statement = $this->con->prepare("SELECT SUM(menu_item_price*quantity) AS BillSum FROM order_items WHERE order_id=".$OrderId." ");
            $Statement->execute();
            $BillSum = $Statement->get_result()->fetch_assoc();
            return $BillSum;
        }
        
        public function getOrderItems($OrderId){
            $Orders = $this->con->prepare("SELECT order_items.quantity, order_items.order_item_id AS Id, order_items.menu_item_price, menu_items.item_name FROM order_items INNER JOIN menu_items ON menu_items.item_id=order_items.menu_item_id WHERE order_items.order_id=".$OrderId." ");
            $Orders->execute();
            $Orders->store_result();
            $RESULT = array();
            for ( $i = 0; $i < $Orders->num_rows; $i++ ) {
                $Metadata = $Orders->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Orders, 'bind_result' ), $PARAMS );
                $Orders->fetch();
            }
            return $RESULT;
        }
        //Accounting Functions
        public function getExpenseLedgers($CompanyId,$Returns){
            if($Returns == "SCM"){//FARm
                $Ledgers = $this->con->prepare("SELECT ledgers.ledger_id AS LedgerId,ledgers.ledger AS Ledger  FROM ledgers INNER JOIN expense_heads ON expense_heads.exp_id=ledgers.expense_head_id WHERE expense_heads.status=1 AND expense_heads.company_id=".$CompanyId." AND expense_heads.exp_id NOT IN(50) ORDER BY ledgers.ledger ASC");
            
            }else{//SHELL
               $Ledgers = $this->con->prepare("SELECT ledgers.ledger_id AS LedgerId,ledgers.ledger AS Ledger  FROM ledgers INNER JOIN expense_heads ON expense_heads.exp_id=ledgers.expense_head_id WHERE expense_heads.status=1 AND expense_heads.company_id=".$CompanyId." AND ledgers.ledger NOT IN('SUPPLIER PAYMENT') AND expense_heads.exp_id IN(50) ORDER BY ledgers.ledger ASC");
             
            }
            $Ledgers->execute();
            $Ledgers->store_result();
            $RESULT = array();
            for ( $i = 0; $i < $Ledgers->num_rows; $i++ ){
                $Metadata = $Ledgers->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Ledgers, 'bind_result' ), $PARAMS );
                $Ledgers->fetch();
            }
            return $RESULT;
        }
        
        
        public function createExpense($Date,$LedgerId,$SupplierId,$PaymentRef,$Remarks,$Amount,$PaymentMode,$UserId,$CompanyId,$OutletId){
            $CheckNewExpense = $this->con->prepare("INSERT INTO petty_cash(expense_date,ledger_id,supplier_id,payment_ref,remarks,amount,payment_mode,added_by,company_id,outlet_id) 
            VALUES('".$Date."',".$LedgerId.",".$SupplierId.",'".$PaymentRef."','".$Remarks."',".$Amount.",".$PaymentMode.",".$UserId.",".$CompanyId.",".$OutletId.") ");
            if($CheckNewExpense->execute()){
                return true;
            } else {
                return false;
            }
            
        }
        
        public function getExpensesByDate($DateFrom,$DateTo,$CompanyId,$OutletId){// AND petty_cash.expense_date BETWEEN '".$DateFrom."' AND '".$DateTo."'
            $Ledgers = $this->con->prepare("SELECT petty_cash.petty_id AS Id,petty_cash.expense_date AS Date,petty_cash.amount AS Amount,petty_cash.remarks AS Remarks,petty_cash.payment_ref AS Reference,ledgers.ledger AS Ledger,pos_companies.user_name AS AddedBy,settlements.settlement AS PaymentType FROM petty_cash 
            INNER JOIN settlements ON settlements.settlement_id=petty_cash.payment_mode INNER JOIN ledgers ON petty_cash.ledger_id=ledgers.ledger_id 
            INNER JOIN pos_companies ON petty_cash.added_by=pos_companies.user_id  
            WHERE ledgers.company_id=".$CompanyId." 
            AND petty_cash.outlet_id=".$OutletId." ORDER BY petty_cash.petty_id DESC LIMIT 100");
            $Ledgers->execute();
            $Ledgers->store_result();
            $RESULT = array();
            for ( $i = 0; $i < $Ledgers->num_rows; $i++ ){
                $Metadata = $Ledgers->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Ledgers, 'bind_result' ), $PARAMS );
                $Ledgers->fetch();
            }
            return $RESULT;
            
        }
        
        public function cancelOrder($OrderId,$Reason){
            $CancelOrder = $this->con->prepare("UPDATE client_orders SET status=9,description='".$Reason."' WHERE order_id=".$OrderId."  ");
            if($CancelOrder->execute()){
                $DateTime = date('D d M, Y H:i:s');
                $Notify = $this->con->prepare("INSERT INTO notifications(type,message,date_time,company_id) VALUES('Order Cancellation','".$Reason."','".$DateTime."',11)  ");
                $Notify->execute();
                return true;
            } else{
                return false;
            }
        }
        
        public function updateExpense($ExpenseId, $Amount, $Remarks, $Reference){
            $UpdatePayment = $this->con->prepare("UPDATE petty_cash SET amount=".$Amount.",remarks='".$Remarks."',payment_ref='".$Reference."' WHERE petty_id=".$ExpenseId."  ");
            if($UpdatePayment->execute()){
                return true;
            } else{
                return false;
            }
        }
        
        public function deleteExpense($ExpenseId){
            $DropExpense = $this->con->prepare("DELETE FROM petty_cash WHERE petty_id=".$ExpenseId." ");
            if($DropExpense->execute()){
                return true;
            } else{
                return false;
            }
        }

    }