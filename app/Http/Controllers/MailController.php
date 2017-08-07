<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Google_Client;
use Google_Service_Gmail;
use Google_Service_Exception;

class MailController extends Controller
{
    //

    function index(){
    	return view('admin.mailbox.mailbox');
    }


    function gmailApiCall(){

    	session_start();
		if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
		  
		  $client=$this->getGmailConfigClient();
		  $client->setAccessToken($_SESSION['access_token']);

			if ($client->isAccessTokenExpired()) {

			$refreshTokenPath = base_path().'/refreshToken.json';
	        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

    		file_put_contents($refreshTokenPath, json_encode($client->getAccessToken()));

	        // pass access token to some variable
	        $_SESSION['access_token'] = $client->getAccessToken();

	        $client->setAccessToken($_SESSION['access_token']);

	         $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/gmailapi';
		  	return redirect()->to(filter_var($redirect_uri, FILTER_SANITIZE_URL));

		  	}else{
		  		
				  $service = new Google_Service_Gmail($client);
				  //Prepare the message in message/rfc822
				  //$q = 'after:2017/06/01';
				  $optParams = [];
		          $optParams['maxResults'] = 5; // Return Only 5 Messages
		          $optParams['labelIds'] = 'INBOX'; // Only show messages in Inbox
				  $emailDatas=$this->fetchMails($service, $optParams);
				  //$this->getEmailBody($service,$q);
		  	}

		} else {
		  $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/gmailapi/callback';
		  return redirect()->to(filter_var($redirect_uri, FILTER_SANITIZE_URL));
		}
         
			
			return view('mailview')->with('emailDatas',$emailDatas);
      }
	

function gmailApiCallback(){

	session_start();
	
	$client=$this->getGmailConfigClient();

	 $refreshTokenPath = base_path().'/refreshToken.json';
	 
	 	//note: Only at first the token has both access token and refresh token
	 // so the first one should be stored locally so that it can be used later to get new fresh access token other wise throws errors adn token should be stored in database 
	  if (file_exists($refreshTokenPath)) {
	    $accessToken = json_decode(file_get_contents($refreshTokenPath), true);
	    //dd($accessToken);
	  }else{
			 
		  if (! isset($_GET['code'])) {
		  $auth_url = $client->createAuthUrl();
		  return redirect()->to(filter_var($auth_url, FILTER_SANITIZE_URL));
			} else {
			  	$client->authenticate($_GET['code']);
			  	$accessToken=$client->getAccessToken();
			  	// Store the token to disk.
			  	$myfile = fopen($refreshTokenPath, "w") or die("Unable to open file!");
			 	fclose($myfile);
			  	file_put_contents($refreshTokenPath, json_encode($accessToken));
			    	}
	    
	  			}	

		  $_SESSION['access_token'] = $accessToken;
		  $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/gmailapi';
		  return redirect()->to(filter_var($redirect_uri, FILTER_SANITIZE_URL));

}

function getGmailConfigClient(){

		$credentialsPath=base_path().'/client_secrets.json';
    	$client = new Google_Client();
    	$client->setApplicationName('mailbox');
		$client->setAuthConfig($credentialsPath);
		$client->setDeveloperKey('AIzaSyD62KL4RKoDDRSHlgPUwalH8_9qapgvh8Y');
		/*$client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $Client->setDeveloperKey(config('services.google.api_key'));*/
		$client->setAccessType("offline");        // offline access and refresh token
		$client->setApprovalPrompt("force");
		$client->setIncludeGrantedScopes(true);   // incremental auth
		$client->setScopes(['https://www.googleapis.com/auth/gmail.readonly']);

		return $client;
}

function getRefreshToken(){

}

function fetchMails($service, $q) {
	
$emailDatas = collect([]);

try {

	//$messages = $service->users_messages->listUsersMessages('me',array('q'=>$q));
	$messages = $service->users_messages->listUsersMessages('me',$q);
    $list = $messages->getMessages();
    //dd($list);
    for ($i=0; $i<sizeof($list); $i++) {
    	# code...
    	$messageId = $list[$i]->getId(); // Grab  Message one by one

	    $message = $service->users_messages->get('me', $messageId);
	    $payload=$message->getPayload();
	    $headers=$payload->getHeaders();
	    foreach ($headers as $header) {
	    	if($header['name']=='Subject')
	    		$subject=$header['value'];

	    	if($header['name']=='Date')
	    		$date=$header['value'];
	    }
	    $emailDatas->push([

	    	'id'=>$messageId,
	    	'subject'=>'<a href = "/gmailapi/useremail/'
	    				.$messageId.'">'.$subject.'</a>',
	    	'date'=>$date


	    	]);
    }
    

    return $emailDatas;


    //for attachments
    $payload = $message->getPayload();
    //dd($payload);
    $body = $payload->getBody();
    //dd($body);
    $attachmentId = '';
    $fileName = '';
    $parts = $payload->getParts();
    //dd($parts);
    foreach ($parts  as $part) {
    		$body=$part->getBody();
			if($body['attachmentId'] != null){
				$attachmentId = $body['attachmentId'];
				$fileName=$part->getFileName();
				//dd($fileName);
			}
    		
                    // if($part['body'] && $part['mimeType'] == 'text/html') {
                    //     $FOUND_BODY = $this->decodeBody($part['body']->data);
                    //     break;
                    // }
                }
     $attachments = $service->users_messages_attachments->get('me',$messageId,$attachmentId);
     //dd($this->decodeBody($attachments['data']));
     $attachmentFile=$this->decodeBody($attachments['data']);
     $myfile = fopen(base_path().'/'.$fileName, "w") or die("Unable to open file!");
	fwrite($myfile, $attachmentFile);
	fclose($myfile);
	echo "success!";
  } catch (Exception $e) {
    print 'An error occurred: ' . $e->getMessage();
  }

}

function getEmailById($id){

	session_start();
	$messageId=$id;
	//dd($messageId);
	
    	$client = new Google_Client();
    	$client->setApplicationName('mailbox');
		$client->setAuthConfig(base_path().'/client_secrets.json');
		$client->setDeveloperKey('AIzaSyD62KL4RKoDDRSHlgPUwalH8_9qapgvh8Y');
		$client->setAccessType("offline");        // offline access
		$client->setApprovalPrompt ("force");
		$client->setIncludeGrantedScopes(true);   // incremental auth
		$client->setScopes(['https://www.googleapis.com/auth/gmail.readonly']);
		
		if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
		  $client->setAccessToken($_SESSION['access_token']);
		  $service = new Google_Service_Gmail($client);

		} else {
		  $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/gmailapi/callback';
		  return redirect()->to(filter_var($redirect_uri, FILTER_SANITIZE_URL));
		}

	
	$this->getEmailBody($service,$messageId);


}


//To reconstruct the acutal body of email received
function getEmailBody($service,$messageId){
	
	try{
            $optParams['format'] = 'full';
            $single_message = $service->users_messages->get('me', $messageId, $optParams);
            //dd($single_message);
           //while ($single_message != null) {
            $payload = $single_message->getPayload();

            // With no attachment, the payload might be directly in the body, encoded.
            $body = $payload->getBody();
            $FOUND_BODY = $this->decodeBody($body['data']);

            // If we didn't find a body, let's look for the parts
            if(!$FOUND_BODY) {
                $parts = $payload->getParts();
                foreach ($parts  as $part) {
                    if($part['body'] && $part['mimeType'] == 'text/html') {
                        $FOUND_BODY = $this->decodeBody($part['body']->data);
                        break;
                    }
                }
            } if(!$FOUND_BODY) {
                foreach ($parts  as $part) {
                    // Last try: if we didn't find the body in the first parts, 
                    // let's loop into the parts of the parts (as @Tholle suggested).
                    if($part['parts'] && !$FOUND_BODY) {
                        foreach ($part['parts'] as $p) {
                            // replace 'text/html' by 'text/plain' if you prefer
                            if($p['mimeType'] === 'text/html' && $p['body']) {
                                $FOUND_BODY = $this->decodeBody($p['body']->data);
                                break;
                            }
                        }
                    }
                    if($FOUND_BODY) {
                        break;
                    }
                }
            }
        //}
            // Finally, print the message ID and the body
            print_r($messageId . " <br> <br> <br> *-*-*- " . $FOUND_BODY);
        

        /*if ($list->getNextPageToken() != null) {
            $pageToken = $list->getNextPageToken();
            $list = $service->users_messages->listUsersMessages('me', array('pageToken' => $pageToken));
        } else {
            break;
        }*/
} catch (Exception $e) {
    echo $e->getMessage();
}

}

//To sanitize and decode base64 encode data received from internet
function decodeBody($body) {
    $rawData = $body;
    $sanitizedData = strtr($rawData,'-_', '+/');
    $decodedMessage = base64_decode($sanitizedData);
    if(!$decodedMessage){
        $decodedMessage = FALSE;
    }
    return $decodedMessage;
}

/*function getAllEmails(){}
function getEmailByQuery(){}

function getEmailDetails(){}
function getEmailAttachment(){}*/



	    	/**
	 * Get list of Messages in user's mailbox.
	 *
	 * @param  Google_Service_Gmail $service Authorized Gmail API instance.
	 * @param  string $userId User's email address. The special value 'me'
	 * can be used to indicate the authenticated user.
	 * @return array Array of Messages.
	 */
	function listMessages($service, $userId) {
	  
	  $pageToken = NULL;
	  $messages = array();
	  $opt_param = array();
	  do {
	    try {
	      if ($pageToken) {
	        $opt_param['pageToken'] = $pageToken;
	      }
	      $messagesResponse = $service->users_messages->listUsersMessages($userId, $opt_param);
	      if ($messagesResponse->getMessages()) {
	        $messages = array_merge($messages, $messagesResponse->getMessages());
	        $pageToken = $messagesResponse->getNextPageToken();
	      }
	    } catch (Exception $e) {
	      print 'An error occurred: ' . $e->getMessage();
	    }
	  } while ($pageToken);

	  foreach ($messages as $message) {
	    print 'Message with ID: ' . $message->getId() . '<br/>';
	  }

	  return $messages;
	}

}
