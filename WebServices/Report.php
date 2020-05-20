<?php
    require_once '../Reports.php';
    date_default_timezone_set("Africa/Kampala");
    header('Content-Type: application/json');

    $response = array();
    if( $_SERVER['REQUEST_METHOD'] == 'POST' ){
        
        if(isset($_POST['OutletsDistribution']) && isset($_POST['OutletId']) && isset($_POST['CompanyId']) ) {
            $ReturnType = $_POST['OutletsDistribution'];
            $OutletId = (float)$_POST['OutletId'];
            $CompanyId = $_POST['CompanyId'];
            $MainItemId = 461;
            $DateTo = date('Y-m-d');

            $dbItemDetails = mysqli_query($con, "SELECT store_items.item_name AS ItemName, store_items.pack_size AS PackSize, unit_measurements.measurement AS Measure FROM store_items 
            INNER JOIN unit_measurements ON store_items.measurement_id=unit_measurements.measure_id WHERE store_items.item_id=".$MainItemId." LIMIT 1 ");
            $dnItem = mysqli_fetch_array($dbItemDetails);
            $ItemMeasure = $dnItem['Measure'];
            $PackSize = $dnItem['PackSize'];
            $ItemName = $dnItem['ItemName'];
            
            //Get first Issue Date  test Outlet 8
            $dbIssueDate = mysqli_query($con, "SELECT MIN(inv_date) AS FirstIssued FROM invoices WHERE outlet=".$OutletId." ");
            $dbIssued = mysqli_fetch_array($dbIssueDate);
            $IssueNums = mysqli_num_rows($dbIssueDate);

            if( $IssueNums >0 ){
                $FirstIssuingDate = $dbIssued['FirstIssued'];
                //Get Sum Issued
                $StockIssued = mysqli_query($con, "SELECT SUM(stocks.quantity_received) AS totalReceived FROM stocks INNER JOIN invoices ON stocks.invoice_id=invoices.inv_id WHERE stocks.store_item_id=".$MainItemId." AND invoices.outlet=".$OutletId." AND invoices.inv_date>='".$FirstIssuingDate."'  ");
                $dIssued = mysqli_fetch_array($StockIssued);
                $QuantityPurchased = $dIssued['totalReceived'];

                //Total Quantity Sold
                $MenuItemsAttachedToItem   = mysqli_query($con,"SELECT menu_item_id,ko_quantity FROM 
                    store_menu_item_map WHERE store_item_id=".$MainItemId." ");
                $TotalQuantitySold =0;
                $TotalDamageCollection=0;
                while($Menu        = mysqli_fetch_array($MenuItemsAttachedToItem)){
                    $MenuItemId    = $Menu['menu_item_id'];
                    $KnockOffQnty  = $Menu['ko_quantity'];

                    //Quantity Sold
                    $QuantitySoldPerItemAttached = mysqli_query($con, "SELECT SUM(quantity) AS ItemsCount FROM order_items INNER JOIN 
                            client_orders ON client_orders.order_id = order_items.order_id WHERE 
                            order_items.menu_item_id=".$MenuItemId." AND client_orders.status IN(4) AND client_orders.order_type NOT IN('REPLACE')
                            AND client_orders.date BETWEEN '".$FirstIssuingDate."' AND '".$DateTo."' AND client_orders.outlet_id=".$OutletId." ");

                    $DbMenuItemsCounter = mysqli_fetch_array($QuantitySoldPerItemAttached);
                    $DbCountedItems     = $DbMenuItemsCounter['ItemsCount'];//eg 6 paxk = 6*count = i.e 6*10 = 60
                    $TotalQuantitySold+=($DbCountedItems*($KnockOffQnty*$PackSize));

                    //Quantity Returned Or Damages
                    $Damages = mysqli_query($con, "SELECT SUM(quantity) AS ItemsCount FROM order_items INNER JOIN 
                            client_orders ON client_orders.order_id = order_items.order_id WHERE 
                            order_items.menu_item_id=".$MenuItemId." AND client_orders.status IN(4) AND client_orders.order_type IN('REPLACE')
                            AND client_orders.date BETWEEN '".$FirstIssuingDate."' AND '".$DateTo."' AND client_orders.outlet_id=".$OutletId." ");

                    $DamagesCounter = mysqli_fetch_array($Damages);
                    $DamagedItems     = $DamagesCounter['ItemsCount'];//eg 6 paxk = 6*count = i.e 6*10 = 60
                    $TotalDamageCollection+=($DamagedItems*($KnockOffQnty*$PackSize));

                }

                //Get Total Returns 
                $mReturns = mysqli_query($con, "SELECT SUM(damage_items.quantity) AS Returned FROM damage_items 
                INNER JOIN damage_returns ON damage_returns.returns_id=damage_items.dm_id 
                WHERE damage_returns.outlet_id=".$OutletId." AND damage_returns.date>='".$FirstIssuingDate."' 
                AND damage_items.store_item_id=".$MainItemId." AND damage_returns.status NOT IN(0) ");
                $dbTotalReturnsToStore = mysqli_fetch_array($mReturns);
                $TotalReturns = $dbTotalReturnsToStore['Returned'];

                //Balance In Store
                $MainStock = $QuantityPurchased - $TotalQuantitySold; //60
                $AvailableForeSale = $MainStock - $TotalDamageCollection; // 60 - 15
                $DamagePending = $TotalDamageCollection - $TotalReturns;
                
                $Records = [];

                $data = [
                    "FirstDate" => $FirstIssuingDate,
                    "Purchases" => $QuantityPurchased,
                    "Sold" => $TotalQuantitySold,
                    "GeneralStock" => $MainStock,
                    "AvForSale" => $AvailableForeSale,
                    "Damages" => $TotalDamageCollection,
                    "PendingReturns" => $DamagePending,
                    "Measure" => $ItemMeasure,
                    "PackSize" => $PackSize
                ];

                array_push($Records, $data );

                $response['Inventory'] = $Records;
                $response['error'] = false;
            }else{//No Issued Stock
                $response['Message'] = "No Record Of Stock Issued For This Outlet. Please Contact Store Manager.";
                $response['error'] = true;
            }

        } else if( isset($_POST['ReturnsHistory']) && isset($_POST['OutletId']) ){
            $OutletId = (float)$_POST['OutletId'];
            
            if( $_POST['OutletId'] == "SCM" ){
                $GetHistory = mysqli_query($con, "SELECT damage_returns.returns_id AS Id, damage_returns.date AS Date, damage_returns.status AS Status, pos_companies.user_name AS AddedBy FROM damage_returns 
            INNER JOIN pos_companies ON damage_returns.created_by=pos_companies.user_id ORDER BY damage_returns.returns_id DESC LIMIT 50 ");
           
            }else{
                $GetHistory = mysqli_query($con, "SELECT damage_returns.returns_id AS Id, damage_returns.date AS Date, damage_returns.status AS Status, pos_companies.user_name AS AddedBy FROM damage_returns 
                INNER JOIN pos_companies ON damage_returns.created_by=pos_companies.user_id WHERE damage_returns.outlet_id IN(".$OutletId.") ORDER BY damage_returns.returns_id DESC LIMIT 50 ");
           
            }
            
           
           $Records = [];
            while($Item = mysqli_fetch_array($GetHistory) ){
                $Date = $Item['Date'];
                $Status = $Item['Status'];
                $AddedBy = $Item['AddedBy'];
                $Id = $Item['Id'];
                
                //Get Quantity
                $mQuantity = mysqli_query($con, "SELECT SUM(quantity) AS Quantity FROM damage_items WHERE dm_id=".$Id." ");
                $dbQty = mysqli_fetch_array($mQuantity);
                $ReturnedQty = $dbQty['Quantity'];
                
                $data = [
                    "Date" => date('D d M,Y', strtotime($Date)),
                    "Status" => $Status,
                    "AddedBy" => $AddedBy,
                    "Id" => $Id,
                    "Quantity" => $ReturnedQty
                ];

                array_push($Records, $data );
            }
            
            $response['History'] = $Records;
            $response['error'] = false;
            
            
        } else if( isset($_POST['DeleteReturn']) && isset($_POST['ReturnId']) ){
            $RecordId = (float)$_POST['ReturnId'];
            $Drop = mysqli_query($con, "DELETE FROM damage_returns WHERE returns_id=".$RecordId." ");
            if($Drop){
                $DropItems = mysqli_query($con, "DELETE FROM damage_items WHERE dm_id=".$RecordId." ");
                if($DropItems){
                    $response['Response'] = "SUCCESS, RECORD DELETED";
                    $response['error'] = false;
                }else{
                    $response['Response'] = "Something Went Wrong";
                    $response['error'] = true;
                }
            }else{
                    $response['Response'] = "Record Deletion Failed";
                    $response['error'] = true;
                }
            
            
        } else if(isset($_POST['ConfirmReturn']) && isset($_POST['ReturnId'])){
            $RecordId = (float)$_POST['ReturnId'];
            $DropItems = mysqli_query($con, "UPDATE damage_returns SET status=1 WHERE returns_id=".$RecordId." ");
                if($DropItems){
                    $response['Response'] = "SUCCESS, RECORD CONFIRMED";
                    $response['error'] = false;
                }else{
                    $response['Response'] = "Something Went Wrong";
                    $response['error'] = true;
                }
            
        } else if(isset($_POST['CreateNewStoreReturn']) && isset($_POST['User'])  && isset($_POST['OutletId']) && isset($_POST['Extra']) && isset($_POST['Trays']) ){
            $AddedBy = (float)$_POST['User'];
            $OutletId = (float)$_POST['OutletId'];
            $Trays = (float)$_POST['Trays'];
            $Extra = (float)$_POST['Extra'];
            $Date = date('Y-m-d');
            $MainItemId = 461;
            
            $TotalQuantity = ($Trays * 30) + $Extra;
            
            $createNewOrder = mysqli_query($con, "INSERT INTO damage_returns(outlet_id,date,created_by,status) 
            VALUES(".$OutletId.",'".$Date."',".$AddedBy.",0) ");
            
            if( $createNewOrder ){
                $CreatedOrder = mysqli_insert_id($con);
                $AddQuantities = mysqli_query($con, "INSERT INTO damage_items(dm_id,store_item_id,quantity,added_by) 
                VALUES(".$CreatedOrder.",".$MainItemId.",".$TotalQuantity.",".$AddedBy.") ");
                
                if( $AddQuantities ){
                    $response['Message'] = "Success, Record Created!!";
                    $response['error'] = false;
                }else{
                    $DropCreated = mysqli_query($con, "DELETE FROM damage_returns WHERE returns_id=".$CreatedOrder." ");
                    $response['Message'] = "Sorry Couldn't Add Item Quantities To Order";
                $response['error'] = true;
                }
                
            }else{
                $response['Message'] = "Sorry Couldn't Create Returns Order";
                $response['error'] = true;
            }
            
            
        } else if(isset($_POST['Statement']) && isset($_POST['ClientId'])  && isset($_POST['Duration']) ) {
            $ClientCrId = $_POST['ClientId'];
            $Days = ((float)$_POST['Duration']*30);
            $To         = date('Y-m-d');
            $From       = date('Y-m-d', strtotime('-'.$Days.' days', strtotime($To)));
            $DateOpen   = date ("Y-m-d", strtotime("-1 days", strtotime($From)));
    
            $Records = []; // Date, Particulars, BillNo, Amount, PayMode, Balance
    
            $PrevBillSum = mysqli_query($con, "SELECT SUM(order_items.total) AS billSum FROM order_items
            INNER JOIN client_orders ON client_orders.order_id=order_items.order_id 
            WHERE client_orders.status IN(0,4) AND client_orders.date<='".$DateOpen."' AND client_company_id=".$ClientCrId." " );
            $dbPrevBills = mysqli_fetch_array($PrevBillSum);
            $TotalPrevBillSum =  $dbPrevBills['billSum'];
    
            //Prev Payments
            $PrevPymts = mysqli_query($con, "SELECT SUM(credit_payments.amount) AS ttlPayments FROM credit_payments WHERE payent_date<='".$DateOpen."' AND client_id=".$ClientCrId." ");
            $dbPyts    = mysqli_fetch_array($PrevPymts);
            $dbTtlPayments = $dbPyts['ttlPayments'];
    
            $CummulativeAmount=$TotalPrevBillSum-$dbTtlPayments;
    
            $data = [
                "Date" => date('d M,Y', strtotime($DateOpen)),
                "Particulars" => "OPENING",
                "BillNo" => "",
                "Amount" => "",
                "PayMode" => "",
                "Type" => "O",
                "Balance" => number_format($CummulativeAmount)
            ];
            array_push($Records, $data );
    
            while (strtotime($From) <= strtotime($To)){
    
                //Get All Bills DateWise
                $cBills = mysqli_query($con, "SELECT * FROM client_orders WHERE date='".$From."' AND client_company_id=".$ClientCrId." AND status NOT IN(9) ");
                while($mBill = mysqli_fetch_array($cBills)){
    
                    $OrderId = $mBill['order_id'];
                    $Date = $mBill['date'];
                    $BillNumber = $mBill['bill_No'];
    
                    //Get Order Sum
                    $dbSum = mysqli_query($con, "SELECT SUM(order_items.total) AS billSum FROM order_items WHERE order_id=".$OrderId." ");
                    $mBillSum = mysqli_fetch_array($dbSum);
                    $OrderTotal = $mBillSum['billSum'];
    
                    //Get Any Payments Done
                    $dbPayment = mysqli_query($con, "SELECT SUM(amount_settled) AS AmountPaid FROM order_settements WHERE order_id=".$OrderId." AND settlement_type NOT IN(4) " );
                    $mPay = mysqli_fetch_array($dbPayment);
                    $BillPayment = $mPay['AmountPaid'];
                    $AmountToAddCummulatively = ($OrderTotal-$BillPayment);//bill 900, paid 900 ---- 0
    
                    $CummulativeAmount+=$AmountToAddCummulatively;
    
                    $data = [
                        "Date" => date('d M,Y', strtotime($From)),
                        "Particulars" => "BILL",
                        "BillNo" => $BillNumber,
                        "Amount" => number_format($OrderTotal),
                        "PayMode" => number_format($BillPayment),
                        "Type" => "B",
                        "Balance" => number_format($CummulativeAmount)
                    ];
                    array_push($Records, $data );
    
                }
    
                //Check If There Is A Credit Payment
                $dbPayment = mysqli_query($con, "SELECT credit_payments.*,client_companies.*,settlements.settlement,pos_companies.user_name FROM credit_payments 
                INNER JOIN client_companies ON client_companies.clientCompany_id=credit_payments.client_id 
                INNER JOIN settlements ON settlements.settlement_id=credit_payments.payment_mode 
                INNER JOIN pos_companies ON pos_companies.user_id=credit_payments.added_by 
                WHERE credit_payments.payent_date='".$From."'
                AND credit_payments.client_id=".$ClientCrId." ");
                
    
                while($mPay    = mysqli_fetch_array($dbPayment)){
                    $Amount = $mPay['amount'];
                    $Pmode   = $mPay['settlement'];
                    $Refrence= $mPay['payent_reference'];
                    $Remarks = $mPay['remarks'];
    
                    if( $Amount >0 ){
                        $CummulativeAmount-=$Amount;
    
                        $data = [
                            "Date" => date('d M,Y', strtotime($From)),
                            "Particulars" => "PAYMENT",
                            "BillNo" => "",
                            "Amount" => number_format($Amount),
                            "PayMode" => $Pmode,
                            "Type" => "P",
                            "Balance" => number_format($CummulativeAmount)
                        ];
                        array_push($Records, $data );
    
                    }
    
                }
    
                $From = date ("Y-m-d", strtotime("+1 days", strtotime($From)));
            }
    
            $response['Statement'] = $Records;
            $response['error'] = false;
    
        }

    }  else{
        $response['error'] = true;
        $response['message'] = "SOMETHING WENT WRONG";
    }
    echo json_encode($response);