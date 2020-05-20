<?php
  //error_reporting(0);
$con=mysqli_connect("localhost","root","","nawab");

    if( isset($_POST['username']) && isset($_POST['password'])  ){
      $InputUsername = $_POST['username'];
      $InputPassword = $_POST['password'];
      $response = array();

      $Query = mysqli_query($con, "SELECT * FROM `pos_companies` WHERE user_name='".$InputUsername."' AND user_key='".$InputPassword."' AND is_active=1 ");
      $Check = mysqli_num_rows($Query);
      if($Check >0){
          $dbUsers = mysqli_fetch_array($Query);
          echo json_encode($dbUsers);
          
      }else{
          $response['error'] = true;
          $response['user_name'] = "No User";        
          echo json_encode($response);
      }
      $con->close();
    } else if( isset($_GET['Orders']) ){

      $sql = "SELECT * FROM client_orders";
      $result = $con->query($sql);      
      if ( $result->num_rows >0) {
       while($row[] = $result->fetch_assoc()) {       
          $tem = $row;       
          $json = json_encode($tem); 
       }
       echo $json;
    }

    $con->close();
  }


?>