<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\EbayKey;
use App\EbayAPI;

class EbayController extends Controller
{
    public function checkToken() {

    }

    public function refreshTokens() {

        $ebayKey  = EbayKey::active()->first(); // add catch if theres no active
        // $authCode = $request->code;
        $authCode = null;
        
        $ebay = new EbayAPI($ebayKey->devId, $ebayKey->appId, $ebayKey->certId, $ebayKey->appId, $ebayKey->ruName, $authCode);

        $ebay->refreshToken();

        $ebayKey->access_token  = $ebay->access_token;
        $ebayKey->refresh_token = $ebay->refresh_token;

        $ebayKey->save();

        return redirect()->route('tickets.myTickets');

    }

    public function connect() {

    	$ebayKey = EbayKey::active()->first();  // add catch if theres no active

    	// dd($ebayKey->redirect_url);

		return redirect($ebayKey->redirect_url);
    }

    public function oauth(Request $request) {

		$ebayKey  = EbayKey::active()->first(); // add catch if theres no active
		$authCode = $request->code;
  //       dump($authCode);
		// dump($ebayKey);


		$ebay = new EbayAPI($ebayKey->devId, $ebayKey->appId, $ebayKey->certId, $ebayKey->appId, $ebayKey->ruName, $authCode);
		// dump($ebay);

        $ebay->authorizationToken();

        $ebayKey->access_token  = $ebay->access_token;
        $ebayKey->refresh_token = $ebay->refresh_token;

        $ebayKey->save();

        return redirect()->route('tickets.myTickets');

    }

}

// https://auth.ebay.com/oauth2/authorize?client_id=Frankies-Intellig-PRD-d7cee19be-11488eef&response_type=code&redirect_uri=FrankiesAutoEle-Frankies-Intell-qgaqqyiiy&scope=https://api.ebay.com/oauth/api_scope