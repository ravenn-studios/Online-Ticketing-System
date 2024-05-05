<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\AuthGmail;
use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Google_Service_Gmail_Label;
use Google_Service_Gmail_Filter;
use Google_Service_Gmail_FilterCriteria;
use Google_Service_Gmail_FilterAction;
use Storage;

class AuthGmail extends Model
{

    public $is_connected = false;

    public function go() {
    
        if ( $this->is_connected() )
        {
    
            // $gmail = new Gmail($this->get_client());
    
            // return $this->readLabels();

            return '';
    
        }
        else
        {
            return $this->get_unauthenticated_data();
        }
    
    }
    
    //connection
    
    public function set_connection($suffix = '') {
        $suffix = (string)$suffix;
        $this->credentials = 'credentials'.$suffix.'.json';
        $this->client = $this->create_client($suffix);
    }
    
    public function get_client() {
        return $this->client;
    }
    
    public function get_credentials() {
        return $this->credentials;
    }
    
    public function is_connected() {
        return $this->is_connected;
    }
    
    public function get_unauthenticated_data() {
        // Request authorization from the user.
        $authUrl = $this->client->createAuthUrl();
    
        return '<a class="linkToAuthGmail" href='.$authUrl.' title="Authorize Account"><i class="fas fa-link"></i></a>';
    }
    
    public function credentials_in_browser() {
    
        if ( isset($_GET['code']) && $_GET['code'] )
        {
            return true;
        }
    
        return false;
    
    }
    
    public function create_client($suffix = '') {
    // public function create_client() {
    
        $client = new Google_Client();
        $client->setApplicationName('Gmail API PHP Quickstart');
        $client->setScopes([
            Google_Service_Gmail::GMAIL_READONLY,
            Google_Service_Gmail::GMAIL_SETTINGS_BASIC,
            Google_Service_Gmail::MAIL_GOOGLE_COM,
            Google_Service_Gmail::GMAIL_COMPOSE,
            Google_Service_Gmail::GMAIL_MODIFY,
            Google_Service_Gmail::GMAIL_READONLY
        ]);
        // $client->setAuthConfig('credentials'.$suffix.'.json');
        // $client->setAuthConfig(Storage::disk('local')->url('credentials/credentials'.$suffix.'.json'));
        // $path = 'credentials/credentials'.$suffix.'.json';
        $path = 'credentials/credentials.json';

        $client->setAuthConfig( Storage::path($path) );
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
    
        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        // $tokenPath = 'token.json';

        $_path = 'tokens/token'.$suffix.'.json';
        // $_path = 'tokens/token.json';
        // $tokenPath = 'token'.$suffix.'.json';
        $tokenPath = Storage::path($_path);
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }
    
        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired())
        {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken())
            {
    
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    
            }
            else if ( $this->credentials_in_browser() )
            {
                
                $authCode = $_GET['code'];
    
                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);
    
                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            else
            {
                $this->is_connected = false;
                return $client;
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath)))
            {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        else
        {
            // echo '<p>not expired.</p>';
        }
    
        $this->is_connected = true;
    
        return $client;
    
    } 
    
    public function readLabels() {
    
        // Get the API client and construct the service object.
        $service = new Google_Service_Gmail($this->client);
    
        // Print the labels in the user's account.
        $user = 'me';
        $results = $service->users_labels->listUsersLabels($user);
    
        if (count($results->getLabels()) == 0)
        {
            print "No labels found.\n";
        }
        else
        {
            print "Labels:\n";
            foreach ($results->getLabels() as $label)
            {
                printf("- %s\n", $label->getName());
            }
        }
    
    }

}
