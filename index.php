<?php
error_reporting(E_ALL);
include 'SocialLogin.php';

$facebook = new SocialLogin('facebook');


$facebook->app_id = '981066008590919';
$facebook->app_secret_code = '6101529c823a704526074e2c937f9519';

$facebook->redirect_uri = 'http://localhost/'; //The domain be setted in the Facebook App settings

//For creating facebook login link e.g. for making <a href="http://facebook...">Login</a>,
//use $facebook->getFacebookLoginLink();

if (!isset($_GET['code'])) {
 
    $facebook->getAuthCode();
} else {
    
    $facebook_response = $facebook->getAccessToken($_GET['code']);
   
    if (!isset($facebook_response->error->code)) {
        
        $user_data = $facebook->getUserData($facebook_response['access_token']);
       
        echo '<pre>';
        var_dump($user_data);
        echo '</pre>';
    } else {
        var_dump($facebook_response);
    }
}
 

?>

