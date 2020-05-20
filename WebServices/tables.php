<?php 
    require_once '../DbOperations.php';
    $response = array();
    if( $_SERVER['REQUEST_METHOD'] =='POST' ) {

            $Section = $_POST['SectionId'];
            $db = new DbOperations();
            $Sections = $db->getSectionTables($Section);

            $response = $Sections;
    } 
    echo json_encode($response);