<?php
    require_once('../DbOperations.php');
    $response = array();


    if( $_SERVER['REQUEST_METHOD']=='POST' ){
        
        if( isset($_POST['username']) and isset($_POST['password']) ){
            $db = new DbOperations();

            if($db->createUser( $_POST['username'],$_POST['password'],1)){//Returns true or false
                $response['error'] = false;
                $response['message'] = "User Created Successfully";
              } else{
                $response['error'] = true;
                $response['message'] = "Something Went Wrong";
              }
       

        }else{
            $response['error'] = true;
            $response['message'] = "Please Enter Username And Password";
        }

    }else{
        $response['error'] = true;
        $response['message'] = "Invalid Request";
    }

    echo json_encode($response);