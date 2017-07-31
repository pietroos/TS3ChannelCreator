<?php session_start(); ?>
<?php 

if(!isset($_SESSION['ts3_last_query']))
    $_SESSION['ts3_last_query'] = microtime(true);
	
	date_default_timezone_set('Europa/Italy/Rome'); //Cambia qui!
	require_once("libraries/TeamSpeak3/TeamSpeak3.php");
	include 'data/config.php';
	
 
    function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            return $_SERVER['HTTP_CLIENT_IP'];
        else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(!empty($_SERVER['HTTP_X_FORWARDED']))
            return $_SERVER['HTTP_X_FORWARDED'];
        else if(!empty($_SERVER['HTTP_FORWARDED_FOR']))
            return $_SERVER['HTTP_FORWARDED_FOR'];
        else if(!empty($_SERVER['HTTP_FORWARDED']))
            return $_SERVER['HTTP_FORWARDED'];
        else if(!empty($_SERVER['REMOTE_ADDR']))
            return $_SERVER['REMOTE_ADDR'];
        else
            return false;
    }
	
    $connect = "serverquery://".$USER_QUERY.":".$PASS_QUERY."@".$HOST_QUERY.":".$PORT_QUERY."/?server_port=".$SERVER_PORT."";
    $ts3 = TeamSpeak3::factory($connect);
    $ts3->execute("clientupdate", array("client_nickname" => $NICK_QUERY));
    $FLAG = false;
	
    foreach ($ts3->clientList(array('client_type' => '0', 'connection_client_ip' => getClientIp())) as $client) {
        $clientuid = $client->client_unique_identifier;
		$client_nickname = $client->client_nickname;
		$client_clid = $client->clid;
        $FLAG = true;
        break;
    }
    if (!$FLAG){
        echo "<p><b>".$lang['f_connectts'].".</b></p><br/>";
        header("refresh: 10; url = ./");
        die;  
    }
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
    <head>
        <meta charset="UTF-8" />
        <title>Creazione di un canale</title>
        <link rel="stylesheet" type="text/css" href="css/demo.css" />
        <link rel="stylesheet" type="text/css" href="css/style.css" />
		<link rel="stylesheet" type="text/css" href="css/animate-custom.css" />
		<script src='https://www.google.com/recaptcha/api.js'></script>
	</head>
    <body>
        <div class="container">
            <header>
                <h1>Creatore<span> Di canali</span></h1>
			</header>
            <section>				
                <div id="container_demo" >
                    <div id="wrapper">
                        <div id="login" class="animate form">
                            <form  method="post" autocomplete="off" action=""> 
                                <h1>Impostazioni</h1> 
								<p> 
                                    <label  class="uname" data-icon="u" > Ciao, <?php echo $client_nickname; ?></label>
                                    <input  name="uid" readonly type="text" value="<?php echo $clientuid; ?>"/>
								</p>
                                <p> 
                                    <label  class="uname" data-icon="u" > Nome del canale</label>
                                    <input  value="" name="channelname" required="required" type="text" placeholder="Il mio canale"/>
								</p>
                                <p> 
                                    <label class="youpasswd" data-icon="p"> Password del canale</label>
                                    <input value="" name="password" required="required" type="password" placeholder="es. X8df!90EO" /> 
								</p>
								
								<div class="g-recaptcha" data-sitekey="keypubblica"></div>
								
                                <p class="login button"> 
                                    <input type="submit" name="submit" value="Crealo!" /> 
								</p>
							</form>
						</div>
						
					</div>
				</div>  
			</section>
			<p>Versione 0.1 by Pietroos</p>
		</div>
	</body>
</html>	

<?php
include 'data/config.php';
$error = 0;
if(isset($_POST['submit']) && !empty($_POST['submit'])) {
	if(isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
		$secret = $secret_key;
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$_POST['g-recaptcha-response']);
        $responseData = json_decode($verifyResponse);
        if($responseData->success) {
			}
		else {
			$error++;
		}
		}
	else {
		$error++;
	}
}
else {
	$errMsg = '';
    $succMsg = '';
}

if ($error == 0) {
	if(isset($_POST['submit'])) {
	$channelname = $_POST['channelname'];
	$password = $_POST['password'];
	$unixTime = time();
	$realTime = date('[Y-m-d]-[H:i]',$unixTime);
	$channel_admin_group = xx; //ID del admin del canale

	try
	{
		$cid1 = $ts3->channelCreate(array(
		"channel_name" => "$channelname",
		"channel_password" => "$password",
		"channel_flag_permanent" => "1",
		"channel_description" => '[center][b][u]'.$channelname.'[/u][/b][/center][hr][b][list][*]Creato il: '.$realTime.'[*]Propietario: ' . $client_nickname . '[/list][/b]',
		"channel_order" => "$order"));

		$ts3->clientGetByUid($clientuid)->setChannelGroup($cid1, $channel_admin_group);
		$ts3->clientMove($client_clid, $cid1);

	}
	catch(Exception $e)
	{
		echo "Error (ID " . $e->getCode() . ") <b>" . $e->getMessage() . "</b>";
	}
}
}

else {
	echo "<center>Hai sbagliato a fare il reCaptcha</center>";
}

?>