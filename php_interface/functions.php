<?php
if(!function_exists('pr')){
function pr($data,$d=false,$admin = true)
{
    global $USER;
    if(!is_object($USER))
    	$USER = new CUser();
    if(($admin == true && $USER->isAdmin()) || $admin == false):
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    endif;
    if($d==true) die();
}
}
?>