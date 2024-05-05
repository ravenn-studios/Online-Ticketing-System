<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebhookController extends Controller
{
    
    public function verify(Request $request)
    {
        // dd('test webhook route');
        // dd($request->all());
        /* validate verify token needed for setting up web hook */ 
        if (isset($_GET['hub_verify_token'])) { 
            if ($_GET['hub_verify_token'] === 'EAAK4TVAveZCABAJQlugfalVqsZBxAHWYdwrN9Vfd9GEjKH1id9Q0JYoHJ48x8kMdugZBDA2ThB8EZAoFrP3J0kdup9tOZApw6foNyGiyQz9xrghmqjZCFg5ZAh8zbAVWG0CB1Leo2tV7vCy1oeJncbCuzq4ONrV7YqkKY4ZAEZB4zOKOX2cZBxIwhU7vqO3WFrxXMZD') {
                echo $_GET['hub_verify_token'];
                return;
            } else {
                echo 'Invalid Verify Token';
                return;
            }
        }

        /* receive and send messages */
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['entry'][0]['messaging'][0]['sender']['id'])) {

            $sender = $input['entry'][0]['messaging'][0]['sender']['id']; //sender facebook id
            $message = $input['entry'][0]['messaging'][0]['message']['text']; //text that user sent

            $url = 'https://graph.facebook.com/v2.6/me/messages?access_token=PAGE_ACCESS_TOKEN';

            /*initialize curl*/
            $ch = curl_init($url);
            /*prepare response*/
            $jsonData = '{
            "recipient":{
                "id":"' . $sender . '"
                },
                "message":{
                    "text":"You said, ' . $message . '"
                }
            }';
            /* curl setting to send a json post data */
            // curl_setopt($ch, CURLOPT_POST, 1);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            // if (!empty($message)) {
            //     $result = curl_exec($ch); // user will get the message
            // }
        }

    }

}
