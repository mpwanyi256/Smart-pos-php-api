<?php 
    require_once '../scmOperations.php';
    header('Content-Type: application/json');
    session_start();

    $response = array();
    if( $_SERVER['REQUEST_METHOD'] == 'POST' ){
        $db = new scmOperations();

        if( isset($_POST['OutletListing']) && isset($_POST['CompanyId']) ){
            $CompanyId = (float)$_POST['CompanyId'];
            $Returns = $_POST['OutletListing'];
            $AllOutlets = $db->allOutlets($CompanyId, $Returns);
            $response['error'] = false;
            $response['message'] = $AllOutlets;

        } else if( isset($_POST['Suppliers']) && isset($_POST['CompanyId']) ){
            $CompanyId = (float)$_POST['CompanyId'];
            $AllSuppliers = $db->allSuppliers($CompanyId);
            $response['error'] = false;
            $response['message'] = $AllSuppliers;

        } else if(isset($_POST['getAllInvoices']) && isset($_POST['CompanyId'])){
            $CompanyId = (float)$_POST['CompanyId'];
            $ReturnsType = $_POST['getAllInvoices'];
            $getMainOutletId = $db->getMainOutletId($CompanyId);
            $MainOutletId = $getMainOutletId['Id'];
            $Invoices = $db->getInvoicesList($CompanyId,$ReturnsType,$MainOutletId);
            $response['error'] = false;
            $response['message'] = $Invoices;
            
            
        } else if( isset($_POST['SupplierMapping']) && isset($_POST['SupplierId']) && isset($_POST['CompanyId']) ){
            $Returns = $_POST['SupplierId'];
            $CompanyId = $_POST['CompanyId'];
            
            if($Returns == "FARM"){
                $dbMainOutlet = $db->getStoreId($Returns);
                $StoreId = $dbMainOutlet['Id'];
                
                $SupplierId = $StoreId;
            }else{
               $SupplierId = (float)$_POST['SupplierId']; 
            }
            
            
            $Mapping = $db->supplierItemMapping($SupplierId);
            $response['error'] = false;
            $response['message'] = $Mapping;

        } else if( isset($_POST['InvoiceItems']) && isset($_POST['InvoiceId']) ){
            $InvoiceId= (float)$_POST['InvoiceId'];
            $Items = $db->getInvoiceItems($InvoiceId);
            $response['error'] = false;
            $response['message'] = $Items;
        
        } else if( isset($_POST['CreateInvoice']) && isset($_POST['SupplierId']) && isset($_POST['UserId']) && isset($_POST['CompanyId']) && isset($_POST['OutletId']) ){
            $SupplierId= (float)$_POST['SupplierId'];
            $AddedBy   = (float)$_POST['UserId'];
            $CompanyId = (float)$_POST['CompanyId'];
            $OutletId  = (float)$_POST['OutletId'];
            $EntryType = $_POST['CreateInvoice'];

            $LastInvoiceNumber = $db->assignInvoiceNumber();
            $NewInvoiceNumber = (float)$LastInvoiceNumber['InvNumber']+1;
            $getMainOutletId = $db->getMainOutletId($CompanyId);
            $MainOutletId = $getMainOutletId['Id'];
            
            $Date = date('Y-m-d');
            
            if($EntryType == "RECE"){//Receiving
                $OutletToUse = $MainOutletId;
                $SupplierIdToUse = $SupplierId;
            } else if($EntryType == "ISSUE"){//Issuing
                $OutletToUse = $OutletId;
                $mainSupplyer = $db->getStoreId("FARM EGGS");
                $SupplierIdToUse = $mainSupplyer['Id'];
            }

            $NewInvoice = $db->createInvoice($NewInvoiceNumber,$Date,$SupplierIdToUse,$AddedBy,$CompanyId,$OutletToUse);
            $SupplierMappings = $db->supplierItemMapping($SupplierId);

            if($NewInvoice != false){
                $response['error'] = false;
                $response['mappings'] = $SupplierMappings;
                $response['invoiceId'] = $NewInvoice['InvId'];
                $response['supplierId'] = $SupplierIdToUse;
            } else {
                $response['error'] = true;
                $response['message'] = "INVOICE CREATION FAILED";
            }

        } else if( isset($_POST['AddItemToInvoice']) && 
        isset($_POST['StoreItemId']) && isset($_POST['PackSize']) && isset($_POST['UnitPrice']) 
        && isset($_POST['InvoiceId']) && isset($_POST['Quantity']) && isset($_POST['ExtraQuantity']) ) {

            $ItemId = (float)$_POST['StoreItemId'];
            $InvoiceId = (float)$_POST['InvoiceId'];
            $PackSize = (float)$_POST['PackSize']; //
            $Quantity = (float)$_POST['Quantity'];//4 Trays 4X30 = 120
            $TotalReceived =  $PackSize*$Quantity;
            $ExtraQuantity = (float)$_POST['ExtraQuantity']; // 20 eggs
            $ItemUnitPrice = (float)$_POST['UnitPrice'];

            $TotalQntyReceived = $TotalReceived+$ExtraQuantity; // 140
            $PacksReceived =  $Quantity + round(($ExtraQuantity/$PackSize),1);  //round(($TotalReceived+ round(($ExtraQuantity/$PackSize),2)),2);
            $ItemCost = ($ItemUnitPrice / $PackSize)*$TotalQntyReceived; // (300/1)*140 = 42,000
            $dbQuantity = round(($TotalQntyReceived/$PackSize),1); // 140/1 = 140

            if($db->checkIfItemExixts($ItemId,$InvoiceId)){
                $response['error'] = true;
                $response['message'] = "ITEM ALREADY EXISTS";
            }else {
                $AddItem = $db->addInvoiceItem($ItemId,$InvoiceId,$PackSize,$PacksReceived,$TotalQntyReceived,$ItemUnitPrice,$ItemCost);
                if($AddItem){
                    $response['message'] = "SUCCESS, ITEM WAS ADDED";
                    $response['error'] = false;
                }else{
                    $response['message'] = "SORRY, SOMETHING WENT WRONG";
                    $response['error'] = true;
                }
                
            }
            

        } else if( isset($_POST['DeleteInvoiceItem']) && isset($_POST['Id']) ){
            $Id = (float)$_POST['Id'];
            $dropped = $db->deleteInvoiceItem($Id);
            if($dropped){
                $response['error'] = false;
                $response['message'] = "SUCCESS, ITEM DELETED"; 
            }else{
                $response['error'] = true;
                $response['message'] = "SORRY, OPERATION FAILED";
            }
            
        } else if( isset($_POST['GetStoreItems']) && isset($_POST['CompanyId']) ){
            $CompanyId = (float)$_POST['CompanyId'];
            $response['message'] = $dropped = $db->getStoreItems($CompanyId);
            $response['error'] = false;
            
        } else if(isset($_POST['GetItemSTockOverView']) && isset($_POST['StoreItemId']) && isset($_POST['CompanyId']) ){
            $ItemId = (float)$_POST['StoreItemId'];
            $CompanyId = (float)$_POST['CompanyId'];
            
            //Get Main Store Id
            $getMainOutletId = $db->getMainOutletId($CompanyId);
            $MainOutletId = $getMainOutletId['Id'];
            
            //Get TotalItem Purchase
            $Purchase = (float)$db->getItemTotalPurchase($ItemId,$MainOutletId);
            $Issued   = (float)$db->getItemTotalSupply($ItemId,$MainOutletId);
            
            $response['error'] = false;
            $response['Purchase'] = $Purchase;
            $response['Issued'] = $Issued;
            $response['InStock'] = ($Purchase - $Issued);
            
            
        } else if( isset($_POST['DropInvoice']) && isset($_POST['InvoiceId']) ){
            $Id = (float)$_POST['InvoiceId'];
            $Dropped = $db->dropInvoice($Id);
            if($Dropped){
                $response['error'] = false;
                $response['message'] = "SUCCESS, INVOICE DELETED";
            }else{
                $response['error'] = true;
                $response['message'] = "SORRY, OPERATION FAILED";
            }
        }

        else{
            $response['error'] = true;
            $response['message'] = "MISSING PARAMS";
        }

    }  else{
        $response['error'] = true;
        $response['message'] = "BAD REQUEST";
    }
    echo json_encode($response);