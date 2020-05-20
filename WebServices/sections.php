<?php
    require_once '../DbOperations.php';
    $response = array();

    if( $_SERVER['REQUEST_METHOD'] =='GET' ) {
        if( isset($_GET['restaurantSections']) ){
            $db = new DbOperations();
            $Sections = $db->getSections();
            $DayOpen = $db->getDayOpen();
            
            $response['error'] = false;
            $response['sections'] = $Sections;
            $response['date'] = $DayOpen;
        } else{
            $response['error'] = true;
            $response['message'] = "SORRY, NO GET REGQUEST SENT";
        }

    } else if( $_SERVER['REQUEST_METHOD'] == 'POST' ){
        //if( isset($_POST['all']) ){
            $db = new DbOperations();
            $Sections = $db->getSections();
            $DayOpen = $db->getDayOpen();
            
            //$response['error'] = false;
            //$response['message'] = "All Sections Are Loaded";
            $response = $Sections;
            //$response['date'] = $DayOpen;

        //} 

    }  else{
        $response['error'] = true;
        $response['message'] = "SORRY, YOU NEED A GET REQUEST";
    }
    echo json_encode($response);