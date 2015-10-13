<?php

/* namespace app\models;

use Yii;
use yii\base\Model;
use yii\web\Session; */


class SocialLogin //extends Model

{
    
    public $oauth_url;
    
    public $access_token_url;
    
    public $user_data_url;
	
    public $app_id;
    public $app_secret_code;
    
    public $redirect_uri = 'http://localhost/'; //The domain be setted in the Facebook App settings
    public $response_type;
    public $scope;
	
    public $state = '123456789';
	
	private $network_name;

	public function __construct($network_name = 'facebook'){
		
		$network_name = strtolower($network_name);
		$this->network_name = $network_name;
		//$this->redirect_uri = $_SERVER['HTTP_HOST'];
		
		switch ($network_name){
			case 'facebook':
				$this->oauth_url = 'https://www.facebook.com/dialog/oauth';
				$this->access_token_url = 'https://graph.facebook.com/oauth/access_token';
				$this->user_data_url = 'https://graph.facebook.com/me';
				$this->response_type = 'code';
				$this->scope = 'email,user_birthday,user_location,public_profile';
				break;
			case 'linkedin':
				$this->oauth_url = 'https://www.linkedin.com/uas/oauth2/authorization';
				$this->access_token_url = 'https://www.linkedin.com/uas/oauth2/accessToken';
				$this->user_data_url = 'https://api.linkedin.com/v1/people/~';
				$this->response_type = 'code';
				$this->scope = 'r_basicprofile r_emailaddress';
				break;
			case 'googleplus':
				$this->oauth_url = 'https://accounts.google.com/o/oauth2/auth';
				$this->access_token_url = 'https://accounts.google.com/o/oauth2/token';
				$this->user_data_url = 'https://www.googleapis.com/oauth2/v1/userinfo';
				$this->response_type = 'code';
				$this->scope = 'profile email';

		}
	}
	
    public function getAuthCode(){
            $app_id = 'app_id';
            $redirect_uri = 'redirect_uri';
            $additional_parameters = array();
            switch ($this->network_name){
                    case 'facebook':
                            break;
                    case 'linkedin':
                            $app_id = 'client_id';
                            $additional_parameters = array(
                                'state' => $this->state,
                            );
                            break;
                    case 'googleplus':
                            $app_id = 'client_id';
                            break;

            }
           $link = $this->oauth_url . '?' . urldecode(http_build_query(array(
                $app_id         => $this->app_id,
                $redirect_uri   => $this->redirect_uri,
                'response_type' => $this->response_type,    
                'scope'         => $this->scope,
            ) + $additional_parameters));

           header('Location:' . $link);
           die();
    }
    
    public function getAccessToken($code = '', $state = '') {

	$post = false;
        $tokenInfo = array();
        $header = '';
        
        if ($code != '') {
            
            $url_params = array();
            
            switch ($this->network_name) {
                    case 'facebook':
                            $url_params = array(
                                    'client_id'     => $this->app_id,
                                    'redirect_uri'  => $this->redirect_uri,
                                    'client_secret' => $this->app_secret_code,
                                    'code'          => $code,
                            );
                            $post = false;
                            $header = '';
                            $url =  $this->access_token_url . '?' .urldecode(http_build_query($url_params));

                            break;
                    case 'linkedin':
                            $url_params = array(
                                    'code' => $code, 
                                    'state' => $_GET['state'],
                                    'grant_type' => 'authorization_code',
                                    'redirect_uri' => $this->redirect_uri,
                                    'client_id' => $this->app_id,
                                    'client_secret' => $this->app_secret_code,
                                    'format' => 'json',
                            );
                            $post = false;
                            $header = '';
                            $url =  $this->access_token_url . '?' .urldecode(http_build_query($url_params));

                            break;
                    
                    case 'googleplus':
                        //For google plus only
                            $url_params = 
                                    'code=' . urlencode($code) . '&' .  
                                    'redirect_uri=' . $this->redirect_uri . '&' . 
                                    'client_id=' . urlencode($this->app_id) . '&' . 
                                    'client_secret=' . urlencode($this->app_secret_code) . '&' . 
                                    //'format' => 'json',
                                    'grant_type=' . 'authorization_code'
                            ;
                            $post = true;
                            $header = array(
                                        'Content-Type: application/x-www-form-urlencoded' ,
                                        'Content-length:' . strlen($url_params)
                                    );
                            $url =  $this->access_token_url;
                            break;
                   

            }
            
		
        } else {
             return false;
        }
        $ch = curl_init();
        $options = array(
                CURLOPT_URL 		=> $url,
                CURLOPT_POST		=> $post,
                CURLOPT_RETURNTRANSFER  => 1,
                CURLOPT_TIMEOUT 	=> 10,
                CURLOPT_SSL_VERIFYHOST  => 0,
                CURLOPT_SSL_VERIFYPEER  => false,
                
        );
       
        curl_setopt_array($ch, $options);
        
        if ($post) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $url_params);
        }
        
        $response = @curl_exec($ch);

        $response_json = json_decode($response);

        if (!isset($response_json->error->code)){ 
            if ($response){
			
                switch ($this->network_name){
                    case 'facebook':
                        parse_str($response, $tokenInfo);
                        break;
                    case 'linkedin':
                        return array('access_token' => $response_json->access_token);
                        break;
                }
				
            } else {
                return $response_json;
            }
            
            //If is exists access token
            if (count($tokenInfo) > 0 && isset($tokenInfo['access_token'])) {
                
                return $tokenInfo;
            } else {
                return $response_json;
            }
        } else {
            return $response_json;
        }
    }
    
    public function getUserData($accessToken = '') {
        
        $url_params = array();
        $json = true;
        
        switch ($this->network_name){
                case 'facebook':
                        $url_params = array('access_token' => $accessToken);
                        $json = true;
                        break;
                case 'linkedin':
                        $url_params = array();
                        $json = true;
                        break;
                case 'googleplus':
                        $url_params = array();
                        $json = true;
                        break;

        }
        
        $options = array(
                'http' => array(
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Bearer " . $accessToken . "\r\nx-li-format: json",
                        'Connection' => 'Keep-Alive',
                        'host'    => $this->user_data_url,
                        'method'  => 'GET',
                        'content' => http_build_query($url_params),
                ),
        );
		
        $url =  $this->user_data_url . '?' . urldecode(http_build_query($url_params));
        $userRawInfo = file_get_contents($url, false, $context);
        $context  = stream_context_create($options);
        if ($json) {
            $userRawInfo = json_decode(file_get_contents($url, false, $context), true);
        } else {
            $userRawInfo = file_get_contents($url, false, $context);
        }
        
        $userInfo = [];
        $userInfo['raw'] = $userRawInfo;
        switch ($this->network_name){
                case 'facebook':
                        $userInfo['first_name'] = $userRawInfo['first_name'];
                        $userInfo['last_name'] = $userRawInfo['last_name'];
                        $userInfo['birthday'] = (string)strtotime($userRawInfo['birthday']);
                        $userInfo['email'] = $userRawInfo['email'];
                        $userInfo['headline'] = '';
                        $userInfo['location'] = $userRawInfo['location']['name'];
                        $userInfo['avatar'] = '';
                        $userInfo['facebook_id'] = $userRawInfo['id'];
                        $userInfo['linkedin_id'] = '';
                        $userInfo['facebook_link'] = $userRawInfo['link'];
                        $userInfo['linkedin_link'] = '';
                        break;
                case 'linkedin':
                        $userInfo['first_name'] = $userRawInfo['firstName'];
                        $userInfo['last_name'] = $userRawInfo['lastName'];
                        $userInfo['birthday'] = '';
                        $userInfo['email'] = '';
                        $userInfo['headline'] = $userRawInfo['headline'];
                        $userInfo['location'] = '';
                        $userInfo['avatar'] = '';
                        $userInfo['facebook_id'] = '';
                        $userInfo['linkedin_id'] = $userRawInfo['id'];
                        $userInfo['facebook_link'] = '';
                        $userInfo['linkedin_link'] =  $userRawInfo['siteStandardProfileRequest']['url'];
                        
                        break;
                    case 'googleplus':
                        
                        $userInfo['first_name'] = $userRawInfo['given_name'];
                        $userInfo['last_name'] = $userRawInfo['family_name'];
                        $userInfo['birthday'] = '';
                        $userInfo['email'] = $userRawInfo['email'];
                        $userInfo['headline'] = '';
                        $userInfo['location'] = '';
                        $userInfo['avatar'] = $userRawInfo['picture'];
                        $userInfo['facebook_id'] = '';
                        $userInfo['linkedin_id'] = '';
                        $userInfo['google_id'] = $userRawInfo['id'];
                        $userInfo['facebook_link'] = '';
                        $userInfo['linkedin_link'] =  '';
                        $userInfo['google_link'] = $userRawInfo['link'];

                        break;
        }
        
        return $userInfo;
    }
    

 
     
}
