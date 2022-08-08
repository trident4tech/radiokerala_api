<?php
$schema=array("users","books","store");
$schema_details = array( 
    "users" => array (
       "table" => "User",
       "primary_key" => "u_id",	
       "foriegn_key" => "u_f_id"
    ),
    
    "books" => array (
       "table" => "books",
       "primary_key" => "b_id",
       "foriegn_key" => "b_f_key"
    ),
    
    "store" => array (
       "table" => "stores",
       "primary_key" => "s_id",
       "foriegn_key" => "s_id"
    )
    );
?>