<?php 
    date_default_timezone_set("Africa/Kampala");
    include_once dirname(__FILE__).'/Constants.php';
    //$this->con = new mysqli(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
    $con = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);