<?php


$debug = false;

include('config.php');
   $app_id = $config['app_id'] ;
   $app_secret = $config['app_secret '] ;
   $my_url = $config['my_url'];
   //session_destroy();
   session_start();
//echo print_r($_SESSION);
//echo "<br/>";
//echo print_r($_REQUEST);
$code = $_REQUEST["code"];
//echo "<br/>";

   if(empty($code)) {
     $_SESSION['state'] = md5(uniqid(rand(), TRUE)); // CSRF protection
     $dialog_url = "https://www.facebook.com/dialog/oauth?client_id=" 
       . $app_id . "&redirect_uri=" . urlencode($my_url) . "&state="
       . $_SESSION['state'] . "&scope=user_photos,friends_photos";
//echo $dialog_url;
//echo "<br/>";
     header("Location: " . $dialog_url);
   }

   if(empty($_SESSION['access_token'])){
       $token_url = "https://graph.facebook.com/oauth/access_token?"
       . "client_id=" . $app_id . "&redirect_uri=" . urlencode($my_url)
       . "&client_secret=" . $app_secret . "&code=" . $code;
       if($debug){
       	echo 'getting the token: ' . $token_url . "</br>";
       }
		$response = file_get_contents($token_url);
		$params = null;
		parse_str($response, $params);
		if($debug){
			echo "print our token response: " . print_r($params);
			
		}
		
		$_SESSION['access_token'] = $params['access_token'];
		}
		 $graph_url = "https://graph.facebook.com/me?access_token=" 
       . $params['access_token'];
       if($debug){
	       echo "<br/>";
	       echo "accessing the graph api" . $graph_url;
      }
     $user = json_decode(file_get_contents($graph_url));
     
     
     
     // run fql query
  $fql_query_url = 'https://graph.facebook.com/'
    . 'fql?q=select+object_id,created,text+from+photo_tag+where+subject=me()+limit+9999'
    . '&access_token=' . $_SESSION['access_token'];
    
    if($debug){
	    echo "our fql query: <a href='" . $fql_query_url . "'>this is the link</a><br/>";
	    
    }
  $fql_query_result = file_get_contents($fql_query_url);
 
  
  $fql_query_obj = json_decode($fql_query_result);
  echo "You have " . sizeof($fql_query_obj->data) . " pictures.";
  
  $i=0;
  foreach($fql_query_obj-> data as $pic){
  		$i++;
  		error_log('getting picture #'.$i);
  				  		$graph_url = "https://graph.facebook.com/".$pic->object_id."?access_token=" 
		       . $_SESSION['access_token'];
		      $response = file_get_contents($graph_url);
		      $picture = json_decode($response);
		      $image = file_get_contents($picture->images[0]->source);
		      $name = date("m.d.y",$pic->created);
		      $name = $name.'-'.rand(1,10000) . '-'.$picture->from->name;
		      file_put_contents($name.'.jpg', $image);
		     
		  }
 	 

?>