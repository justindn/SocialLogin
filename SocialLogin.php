<?php

//namespace app\models;

//use Yii;
//use yii\base\Model;
//use yii\web\Session;


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
				$this->scope = 'r_basicprofile';
				break;
		}
	}
	
    public function getAuthCode(){
			$app_id = 'app_id';
			switch ($this->network_name){
				case 'facebook':
					$app_id = 'app_id';
					
					break;
				case 'linkedin':
					$app_id = 'client_id';
					break;
			}
           $link = $this->oauth_url . '?' . urldecode(http_build_query([
                $app_id         => $this->app_id,
                'redirect_uri'  => $this->redirect_uri,
                'response_type' => $this->response_type,    
                'scope'         => $this->scope,
				'state' 		=> $this->state,
            ]));
           //var_dump($link);
           header('Location:' . $link);
    }
    
    public function getFacebookLoginLink(){
	
        $link = $this->oauth_url . '?' . urldecode(http_build_query(array(
                'app_id'        => $this->app_id,
                'redirect_uri'  => $this->redirect_uri,
                'response_type' => $this->response_type,    
                'scope'         => $this->scope,
            )));
        return $link;
    }
    
    public function getAccessToken($code = '', $state = ''){
        
        $tokenInfo = array();
        
        if ($code != '') {
			$url_params = array();
			switch ($this->network_name){
				case 'facebook':
					$url_params = array(
						'client_id'     => $this->app_id,
						'redirect_uri'  => $this->redirect_uri,
						'client_secret' => $this->app_secret_code,
						'code'          => $code,
					);
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
					break;
			}
            
             $url =  $this->access_token_url . '?' .urldecode(http_build_query($url_params));
            
        } else {
             return false;
        }
        
        $ch = curl_init();
        $options = array(
                CURLOPT_URL 			=> $url,
				CURLOPT_POST			=> true,
                CURLOPT_RETURNTRANSFER  => 1,
                CURLOPT_TIMEOUT 		=> 10,
                CURLOPT_SSL_VERIFYHOST  => 0,
                CURLOPT_SSL_VERIFYPEER  => false
        );
        
        curl_setopt_array($ch, $options);
		
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
		switch ($this->network_name){
			case 'facebook':
				$url_params = array('access_token' => $accessToken);
				break;
			case 'linkedin':
				$url_params = array();
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
		$context  = stream_context_create($options);
        $userInfo = json_decode(file_get_contents($url, false, $context), true);
        
        return $userInfo;
    }
    

 
     
}
