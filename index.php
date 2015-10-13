<?php
	session_start();
?>
<!doctype html>
<html>
<head>
	<meta charset='utf-8'>
	<title>Social Login test</title>
	<style>
		html, body{
			font-family:arial, sans-serif;
		}
		li {
			list-style-type:none;
			margin:4px auto ;
		}
	</style>
</head>
<body>
<h3>
	Login test
</h3>
<form method='post'>
	<ul>
            <li>
                <input type='radio' name='login_method' value='facebook' id='facebook'><label for='facebook'>Facebook</label>
            </li>
            <li>
                <input type='radio' name='login_method' value='linkedin' id='linkedin'><label for='linkedin'>LinkedIn</label>
            </li>
            <li>
                <input type='radio' name='login_method' value='googleplus' id='googleplus'><label for='googleplus'>Google Plus</label>
            </li>

	</ul>
	<div style='align:center;'>
		<input type='submit' value='Login' name='login'>
		<?php if (isset($_SESSION['login_method'])) { ?>
			<input type='submit' value='Logout' name='logout'>
		<?php } ?>
	</div>
</form>

</body>
</html>

<?php

error_reporting(E_ALL);

include 'SocialLogin.php';

if (isset($_POST['login_method'])) {
	$_SESSION['login_method'] = $_POST['login_method'];
}
if (isset($_POST['logout'])) {
	session_destroy();
	header('Location: ./');
	die();
}
switch ($_SESSION['login_method']) {
	case 'facebook':
                $socialLogin = new SocialLogin('facebook');
                $socialLogin->app_id = '';
                $socialLogin->app_secret_code = '';
                $socialLogin->redirect_uri = 'http://test.local/sociallogin'; //The domain be setted in the Facebook App settings
                break;
	case 'linkedin':
                $socialLogin = new SocialLogin('linkedin');
                $socialLogin->app_id = '';
                $socialLogin->app_secret_code = '';
                $socialLogin->redirect_uri = 'http://test.local/sociallogin'; 
                break;
	case 'googleplus':
                $socialLogin = new SocialLogin('googleplus');
                $socialLogin->app_id = '';
                $socialLogin->app_secret_code = '';
                $socialLogin->redirect_uri = 'http://localhost:8000/'; 
                break;

	default:
		die();
}

	
echo '<pre>';

	if (!isset($_GET['code'])) {
		$socialLogin->getAuthCode();
		
	} else if (isset($socialLogin)) {

                
		$socialLogin_response = $socialLogin->getAccessToken($_GET['code']);
                
		if (!isset($socialLogin_response->error->code)) {
			if (is_array($socialLogin_response)) {
                            $user_data = $socialLogin->getUserData($socialLogin_response['access_token']);
                        } else {
                            $user_data = $socialLogin->getUserData($socialLogin_response->access_token);
                        }

			var_dump($user_data);
			
		} else {
		}
	}
echo '</pre>';

?>