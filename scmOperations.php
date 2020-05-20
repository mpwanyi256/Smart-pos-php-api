<?php    
    date_default_timezone_set("Africa/Kampala");

    class scmOperations{
        private $con;
        function __construct(){
            require_once dirname(__FILE__).'/DbConnect.php';
            $db = new DbConnect();
            $this->con = $db->connect();
        }

        public function allOutlets($CompanyId,$Returns){
            if($Returns =="ISSUE"){
                $Outlets = $this->con->prepare("SELECT outlet_name AS OutletName, outlet_id AS Id FROM outlets WHERE company_id=".$CompanyId." AND status NOT IN(2) ORDER BY outlet_name ASC ");
            }else{
               $Outlets = $this->con->prepare("SELECT outlet_name AS OutletName, outlet_id AS Id FROM outlets WHERE company_id=".$CompanyId." AND status IN(2) ORDER BY outlet_name ASC "); 
            }
            
            $Outlets->execute();
            $RESULT = array();
            $Outlets->store_result();
            for ( $i = 0; $i < $Outlets->num_rows; $i++ ) {
                $Metadata = $Outlets->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Outlets, 'bind_result' ), $PARAMS );
                $Outlets->fetch();
            }
            return $RESULT;
        }
        
        public function getMainOutletId($CompanyId){
            $InvLast = $this->con->prepare("SELECT outlet_id AS Id FROM outlets WHERE status=2 AND company_id=".$CompanyId." LIMIT 1 ");
            $InvLast->execute();
            return $InvLast->get_result()->fetch_assoc();
        }
        
        public function getStoreId($Returns){
            $InvLast = $this->con->prepare("SELECT supplier_id AS Id FROM suppliers WHERE supplier_name='".$Returns."' LIMIT 1 ");
            $InvLast->execute();
            return $InvLast->get_result()->fetch_assoc();
        }
        
        public function getInvoicesList($CompanyId,$ReturnsType,$MainOutletId){
            if($ReturnsType == "ISSUED"){
                $Outlets = $this->con->prepare("SELECT invoices.inv_id AS Id, invoices.inv_no AS InvNumber, invoices.supplier_id AS SupplierId, 
                    suppliers.supplier_name AS SupplierName, outlets.outlet_name AS Outlet, invoices.inv_date AS InvoiceDate, 
                    pos_companies.user_name AS AddedBy FROM invoices INNER JOIN suppliers ON invoices.supplier_id=suppliers.supplier_id 
                    INNER JOIN outlets ON outlets.outlet_id=invoices.outlet INNER JOIN pos_companies ON invoices.added_by=pos_companies.user_id 
                    WHERE invoices.company_id=".$CompanyId." AND invoices.outlet NOT IN($MainOutletId) ORDER BY invoices.inv_id DESC LIMIT 50 ");
            }else if($ReturnsType == "RECE"){
                $Outlets = $this->con->prepare("SELECT invoices.inv_id AS Id, invoices.inv_no AS InvNumber, invoices.supplier_id AS SupplierId, 
                    suppliers.supplier_name AS SupplierName, outlets.outlet_name AS Outlet, invoices.inv_date AS InvoiceDate, 
                    pos_companies.user_name AS AddedBy FROM invoices INNER JOIN suppliers ON invoices.supplier_id=suppliers.supplier_id 
                    INNER JOIN outlets ON outlets.outlet_id=invoices.outlet INNER JOIN pos_companies ON invoices.added_by=pos_companies.user_id 
                    WHERE invoices.company_id=".$CompanyId." AND invoices.outlet IN($MainOutletId) ORDER BY invoices.inv_id DESC LIMIT 50 ");
            }
            
            $Outlets->execute();
            $RESULT = array();
            $Outlets->store_result();
            for ( $i = 0; $i < $Outlets->num_rows; $i++ ) {
                $Metadata = $Outlets->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Outlets, 'bind_result' ), $PARAMS );
                $Outlets->fetch();
            }
            return $RESULT;
        }
        
        public function dropInvoice($InvoiceId){
            $DropInv = $this->con->prepare("DELETE FROM invoices WHERE inv_id=".$InvoiceId." ");
            $DropInv->execute();
            
            $DropInvItems = $this->con->prepare("DELETE FROM stocks WHERE invoice_id=".$InvoiceId." ");
            $DropInvItems->execute();
            return true;
        }

        public function allSuppliers($CompanyId){
            $Outlets = $this->con->prepare("SELECT supplier_name AS SupplierName, supplier_id AS Id FROM suppliers WHERE company_id=? ORDER BY supplier_name ASC ");
            $Outlets->bind_param("i",$CompanyId);
            $Outlets->execute();
            $RESULT = array();
            $Outlets->store_result();
            for ( $i = 0; $i < $Outlets->num_rows; $i++ ) {
                $Metadata = $Outlets->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Outlets, 'bind_result' ), $PARAMS );
                $Outlets->fetch();
            }
            return $RESULT;
        }
        
        public function getStoreItems($CompanyId){
            $Outlets = $this->con->prepare("SELECT store_items.item_id AS ItemId, store_items.item_name AS ItemName, store_items.unit_price AS Price, store_items.pack_size AS PackSize ,unit_measurements.measurement AS ItemMeasure FROM store_items INNER JOIN unit_measurements ON store_items.measurement_id=unit_measurements.measure_id WHERE store_items.company_id=? ORDER BY store_items.item_name ASC ");
            $Outlets->bind_param("i",$CompanyId);
            $Outlets->execute();
            $RESULT = array();
            $Outlets->store_result();
            for ( $i = 0; $i < $Outlets->num_rows; $i++ ) {
                $Metadata = $Outlets->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Outlets, 'bind_result' ), $PARAMS );
                $Outlets->fetch();
            }
            return $RESULT;
        }

        public function supplierItemMapping($SupplierId){
            $Mappings = $this->con->prepare("SELECT supplier_item_mapping.store_item_id AS ItemId,store_items.item_name AS ItemName, store_items.unit_price AS Price, store_items.pack_size AS PackSize,unit_measurements.measurement AS ItemMeasure FROM supplier_item_mapping INNER JOIN store_items ON store_items.item_id=supplier_item_mapping.store_item_id INNER JOIN unit_measurements ON unit_measurements.measure_id=store_items.measurement_id WHERE supplier_id=? ");
            $Mappings->bind_param("i",$SupplierId);
            $Mappings->execute();

            $RESULT = array();
            $Mappings->store_result();
            for ( $i = 0; $i < $Mappings->num_rows; $i++ ) {
                $Metadata = $Mappings->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Mappings, 'bind_result' ), $PARAMS );
                $Mappings->fetch();
            }
            return $RESULT;
        }

        public function getInvoiceItems($InvoiceId){
            $Items = $this->con->prepare("SELECT stocks.st_id AS Id,stocks.packsize AS PackSize,stocks.items_count AS Quantity,stocks.quantity_received AS TotalQuantity,stocks.unit_price AS UnitPrice, stocks.item_cost AS ItemCostPrice, store_items.item_name AS ItemName,store_items.item_id AS ItemId,unit_measurements.measurement AS ItemMeasure FROM stocks INNER JOIN store_items ON stocks.store_item_id=store_items.item_id INNER JOIN unit_measurements ON unit_measurements.measure_id=store_items.measurement_id WHERE stocks.invoice_id=? ORDER BY stocks.st_id DESC");
            $Items->bind_param("i",$InvoiceId);
            $Items->execute();

            $RESULT = array();
            $Items->store_result();
            for ( $i = 0; $i < $Items->num_rows; $i++ ) {
                $Metadata = $Items->result_metadata();
                $PARAMS = array();
                while ( $Field = $Metadata->fetch_field() ) {
                    $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
                }
                call_user_func_array( array( $Items, 'bind_result' ), $PARAMS );
                $Items->fetch();
            }
            return $RESULT;

        }

        public function assignInvoiceNumber(){
            $InvLast = $this->con->prepare("SELECT COUNT(inv_id) AS InvNumber FROM invoices ");
            $InvLast->execute();
            return $InvLast->get_result()->fetch_assoc();
        }
        

        public function createInvoice($InvoiceNo,$Date,$SupplierId,$AddedBy,$CompanyId,$OutletId){

            $createInvoice = $this->con->prepare("INSERT INTO invoices(inv_no,inv_date,supplier_id,added_by,company_id,outlet)
            VALUES(".$InvoiceNo.",'".$Date."',".$SupplierId.",".$AddedBy.",".$CompanyId.",".$OutletId.") ");
            $CreatedInv = $createInvoice->execute();

            $getLastInvNo = $this->con->prepare("SELECT MAX(inv_id) AS InvId FROM invoices ");
            $getLastInvNo->execute();
            return $getLastInvNo->get_result()->fetch_assoc();
        }

        public function checkIfItemExixts($ItemId,$InvoiceId){
            $checkItem = $this->con->prepare("SELECT * FROM stocks WHERE store_item_id=? AND invoice_id=? ");
            $checkItem->bind_param("ii",$ItemId,$InvoiceId);
            $checkItem->execute();
            $checkItem->store_result();
            return $checkItem->num_rows > 0;
        }

        public function addInvoiceItem($ItemId,$InvoiceId,$PackSize,$QuantityInPacks,$TotalQuantity,$UnitPrice,$ItemTotalCost){
            $InvItem = $this->con->prepare("INSERT INTO stocks(store_item_id,invoice_id,packsize,items_count,quantity_received,unit_price,item_cost)
            VALUES(".$ItemId.",".$InvoiceId.",".$PackSize.",".$QuantityInPacks.",".$TotalQuantity.",".$UnitPrice.",".$ItemTotalCost.") ");
            if($InvItem->execute()){
                return true;
            } else {
                return false;
            }

        }
        
        public function getItemTotalPurchase($ItemId,$MainOutletId){
            $stk = $this->con->prepare("SELECT SUM(stocks.quantity_received) AS ttlQnty FROM stocks INNER JOIN invoices ON invoices.inv_id=stocks.invoice_id WHERE invoices.outlet IN(".$MainOutletId.") AND stocks.store_item_id IN(".$ItemId.") ");
            $stk->execute();
            $mQty = $stk->get_result()->fetch_assoc();
            return $mQty['ttlQnty'];
        }
        
        public function getItemTotalSupply($ItemId,$MainOutletId){
            $stk = $this->con->prepare("SELECT SUM(stocks.quantity_received) AS ttlQnty FROM stocks INNER JOIN invoices ON invoices.inv_id=stocks.invoice_id WHERE invoices.outlet NOT IN(".$MainOutletId.") AND stocks.store_item_id IN(".$ItemId.") ");
            $stk->execute();
            $mQty = $stk->get_result()->fetch_assoc();
            return $mQty['ttlQnty'];
        }

        public function deleteInvoiceItem($Id){
            $DropItem = $this->con->prepare("DELETE FROM stocks WHERE st_id=".$Id." ");
            if($DropItem->execute()){
                return true;
            } else {
                return false;
            }
        }

    }