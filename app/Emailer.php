<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Ticket;
use App\TicketType;
use App\EmailSupportAddress;
use App\GmailApi;
use Storage;
use Log;

class Emailer extends Model
{

	/**
     * Emailer using GmailAPI
     *
     */
	public function mailAwaitingFulfillment($orderNumber, $customerEmail, $customerName, $bcc = '', $isEbay)
    {

		$userNameEmail = $this->getUsernameEmail();
		$userEmail     = $this->getUserEmail();
		// $emailTo       = 'rodney@frankiesautoelectrics.com.au';
		$emailTo       = $customerEmail;
		$subject       = 'Frankies Order #' . $orderNumber . ' - Status Update';

		// $ticket = Ticket::where('requester', $emailTo)->where('subject', $subject)->where('type_id', TicketType::TYPE_AWAITING_FULFILLMENT)->count();
		$ticket = Ticket::where('order_number', $orderNumber)->where('requester', $emailTo)->where('type_id', TicketType::TYPE_AWAITING_FULFILLMENT)->count();

		if( !$isEbay )
		{

			if( !$ticket )
			{

				if ( !empty($bcc) )
				{
					$bcc = implode(',', $bcc);
					$bcc = "Bcc: $bcc\r\n";
				}

				$emailContent = '<table border="0" cellpadding="0" cellspacing="0" class="body" style="background-color:#eff4fa;width:100%">';
				$emailContent .= '<tbody>';
				$emailContent .= '<tr>';
				$emailContent .= '<td>&nbsp;</td>';
				$emailContent .= '<td class="container" style="display:block;margin:0 auto!important;max-width:580px;padding:10px;width:580px">';
				$emailContent .= '<div class="content" style="box-sizing:border-box;display:block;margin:0 auto;max-width:580px;padding:10px">';
				$emailContent .= '<table class="main" style="background:#fff;border-radius:3px;width:100%">';
				$emailContent .= '<tbody>';
				$emailContent .= '<tr>';
				$emailContent .= '<td class="wrapper" style="box-sizing:border-box;padding:20px">';
				$emailContent .= '<table border="0" cellpadding="0" cellspacing="0">';
				$emailContent .= '<tbody>';
				$emailContent .= '<tr>';
				$emailContent .= '<td>';
				$emailContent .= '<p style="margin-bottom: 25px;">Hi '.$customerName.', good day!</p>';
				$emailContent .= '<p style="margin-bottom: 25px;">We hope you are doing well!</p>';
				$emailContent .= '<p>Thank you for placing your order with us! We received your purchase with Order <b>#'.$orderNumber.'</b> and it has been lined up for dispatch.</p>';
				$emailContent .= '<p>We are chasing with our warehouse to check for any updates and we will revert back to you.</p>';
				$emailContent .= '<p>We sincerely apologize for any inconvenience in the delay and hope you will be waiting for your item.</p>';
				$emailContent .= '<p>Thank you for your patience, kind understanding, and continued overwhelming support by ordering with us.</p>';
				$emailContent .= '<p>Feel free to let us know if there’s anything else we can help you with.</p>';
				$emailContent .= '<p>Keep safe and have a lovely day!</p>';
				$emailContent .= '<br>';
				$emailContent .= '<p style="margin-bottom: 0px;">Many Thanks,</p>';
				$emailContent .= '<p style="margin-top: 0px;">Frankies Auto Electrics and Car Audio</p>';
				$emailContent .= '</td>';
				$emailContent .= '</tr>';
				$emailContent .= '</tbody>';
				$emailContent .= '</table>';
				$emailContent .= '</td>';
				$emailContent .= '</tr>';
				$emailContent .= '</tbody>';
				$emailContent .= '</table>';
				$emailContent .= '</div>';
				$emailContent .= '</td>';
				$emailContent .= '<td>&nbsp;</td>';
				$emailContent .= '</tr>';
				$emailContent .= '</tbody>';
				$emailContent .= '</table>';

		        /*$emailContent = '<table border="0" cellpadding="0" cellspacing="0" class="body" style="background-color:#eff4fa;width:100%">';
				$emailContent .= '<tbody>';
				$emailContent .= '<tr>';
				$emailContent .= '<td>&nbsp;</td>';
				$emailContent .= '<td class="container" style="display:block;margin:0 auto!important;max-width:580px;padding:10px;width:580px">';
				$emailContent .= '<div class="content" style="box-sizing:border-box;display:block;margin:0 auto;max-width:580px;padding:10px">';
				$emailContent .= '<table class="main" style="background:#fff;border-radius:3px;width:100%">';
				$emailContent .= '<tbody>';
				$emailContent .= '<tr>';
				$emailContent .= '<td class="wrapper" style="box-sizing:border-box;padding:20px">';
				$emailContent .= '<table border="0" cellpadding="0" cellspacing="0">';
				$emailContent .= '<tbody>';
				$emailContent .= '<tr>';
				$emailContent .= '<td>';
				$emailContent .= '<p style="margin-bottom: 25px;">Hi '.$customerName.', good day!</p>';
				$emailContent .= '<p style="margin-bottom: 25px;">We hope you are doing well!</p>';
				$emailContent .= '<p>Thank you for placing your order with us! We received your purchase with Order <b>#'.$orderNumber.'</b> and it has been lined up for dispatch, however, the warehouse had advised us that we are only waiting for the replenishment of our stocks, and we expect them to arrive in 1-2 business days at the latest.</p>';
				$emailContent .= '<p>We sincerely apologize for any inconvenience in the delay and hope you will be waiting for your item.</p>';
				$emailContent .= '<p>We guarantee that your order will be the first to leave the warehouse once stocks are received and we\'ll send you an email confirmation with the tracking details.</p>';
				$emailContent .= '<p>Thank you for your patience, kind understanding, and continued overwhelming support by ordering with us.</p>';
				$emailContent .= '<p>Feel free to let us know if there’s anything else we can help you with.</p>';
				$emailContent .= '<p>Keep safe and have a lovely day!</p>';
				$emailContent .= '<br>';
				$emailContent .= '<p style="margin-bottom: 0px;">Many Thanks,</p>';
				$emailContent .= '<p style="margin-top: 0px;">Frankies Auto Electrics and Car Audio</p>';
				$emailContent .= '</td>';
				$emailContent .= '</tr>';
				$emailContent .= '</tbody>';
				$emailContent .= '</table>';
				$emailContent .= '</td>';
				$emailContent .= '</tr>';
				$emailContent .= '</tbody>';
				$emailContent .= '</table>';
				$emailContent .= '</div>';
				$emailContent .= '</td>';
				$emailContent .= '<td>&nbsp;</td>';
				$emailContent .= '</tr>';
				$emailContent .= '</tbody>';
				$emailContent .= '</table>';*/


		        $boundary      = uniqid(rand(), true);
		        $strRawMessage = "From: $userNameEmail\r\n";
		        $strRawMessage .= "To: $emailTo\r\n";
		        $strRawMessage .= $bcc;
		        $strRawMessage .= "Subject: $subject\r\n";
		        $strRawMessage .= "In-Reply-To: $userEmail\r\n";
		        $strRawMessage .= "MIME-Version: 1.0\r\n";
		        // $strRawMessage .= 'Content-type: Multipart/Mixed; boundary="' . $boundary . '"' . "\r\n";
		        $strRawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
		        $strRawMessage .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
		        $strRawMessage .= $emailContent;

		        $this->send($strRawMessage, $emailTo, TicketType::TYPE_AWAITING_FULFILLMENT, $orderNumber);

		        logger('sending awaiting fulfillment to: ' . $emailTo);

	        }
	        else
	        {
	        	logger('awaiting fulfillment email exists.. #' . $orderNumber);
	        }

		}
		else
		{
			// $this->createTicketOnly($strRawMessage, $emailTo, TicketType::TYPE_AWAITING_FULFILLMENT, $orderNumber);

	  //       logger('creating ticket awaiting fulfillment to: ' . $emailTo);
		}
    	
    }

    public function mailAwaitingShipment($orderNumber, $customerEmail, $customerName, $bcc = '')
    {

		$userNameEmail = $this->getUsernameEmail();
		$userEmail     = $this->getUserEmail();
		$emailTo       = 'rodney@frankiesautoelectrics.com.au';
		// $subject       = 'TEST01 - DO NOT REPLY - AWAITING SHIPMENT';
		$subject       = 'Frankies Order #' . $orderNumber . ' - Status Update';

		// $ticket = Ticket::where('requester', $emailTo)->where('subject', $subject)->where('type_id', TicketType::TYPE_AWAITING_FULFILLMENT)->count();
		$ticket = Ticket::where('order_number', $orderNumber)->where('requester', $emailTo)->where('type_id', TicketType::TYPE_AWAITING_SHIPMENT)->count();

		if( !$ticket )
		{

			if ( !empty($bcc) )
			{
				$bcc = implode(',', $bcc);
				$bcc = "Bcc: $bcc\r\n";
			}

	        $emailContent = '<table border="0" cellpadding="0" cellspacing="0" class="body" style="background-color:#f6f6f6;width:100%">';
			$emailContent .= '<tbody>';
			$emailContent .= '<tr>';
			$emailContent .= '<td>&nbsp;</td>';
			$emailContent .= '<td class="container" style="display:block;margin:0 auto!important;max-width:580px;padding:10px;width:580px">';
			$emailContent .= '<div class="content" style="box-sizing:border-box;display:block;margin:0 auto;max-width:580px;padding:10px">';
			$emailContent .= '<table class="main" style="background:#fff;border-radius:3px;width:100%">';
			$emailContent .= '<tbody>';
			$emailContent .= '<tr>';
			$emailContent .= '<td class="wrapper" style="box-sizing:border-box;padding:20px">';
			$emailContent .= '<table border="0" cellpadding="0" cellspacing="0">';
			$emailContent .= '<tbody>';
			$emailContent .= '<tr>';
			$emailContent .= '<td>';
			$emailContent .= '<p style="margin-bottom: 25px;">Hi '.$customerName.', good day!</p>';
			$emailContent .= '<p style="margin-bottom: 25px;">We hope you are doing well!</p>';
			$emailContent .= '<p>We would like to share with you that your purchase with Order #'.$orderNumber.' has been endorsed to the warehouse for the dispatch process, however, shipping will be a bit delayed as we’re waiting for one of your products to arrive and we expect them within 2-3 business days at the latest.</p>';
			$emailContent .= '<p>We apologize for any trouble this has caused, rest assured your order is on priority to leave once stocks turn up, and we\'ll send you an email confirmation with the tracking details once your item is dispatched.
			We appreciate you for allowing us to sort this out.</p>';
			$emailContent .= '<p>Keep safe and have a lovely day!</p>';
			$emailContent .= '<br>';
			$emailContent .= '<p style="margin-bottom: 0px;">Many Thanks,</p>';
			$emailContent .= '<p style="margin-top: 0px;">Frankies Auto Electrics and Car Audio</p>';
			$emailContent .= '</td>';
			$emailContent .= '</tr>';
			$emailContent .= '</tbody>';
			$emailContent .= '</table>';
			$emailContent .= '</td>';
			$emailContent .= '</tr>';
			$emailContent .= '</tbody>';
			$emailContent .= '</table>';
			$emailContent .= '</div>';
			$emailContent .= '</td>';
			$emailContent .= '<td>&nbsp;</td>';
			$emailContent .= '</tr>';
			$emailContent .= '</tbody>';
			$emailContent .= '</table>';


	        $boundary      = uniqid(rand(), true);
	        $strRawMessage = "From: $userNameEmail\r\n";
	        $strRawMessage .= "To: $emailTo\r\n";
	        $strRawMessage .= $bcc;
	        $strRawMessage .= "Subject: $subject\r\n";
	        $strRawMessage .= "In-Reply-To: $userEmail\r\n";
	        $strRawMessage .= "MIME-Version: 1.0\r\n";
	        // $strRawMessage .= 'Content-type: Multipart/Mixed; boundary="' . $boundary . '"' . "\r\n";
	        $strRawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
	        $strRawMessage .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
	        $strRawMessage .= $emailContent;

	        $this->send($strRawMessage, $emailTo, TicketType::TYPE_AWAITING_SHIPMENT, $orderNumber);

	        logger('sending awaiting shipment to: ' . $emailTo);

	    }
	    else
        {
        	logger('awaiting shipment email exists..');
        }

    }

    public function send($strRawMessage, $emailTo, $ticketType, $orderNumber)
    {
    	GmailApi::sendComposedMessage($strRawMessage, $emailTo, null, $ticketType, $orderNumber);
    }
    /**
     * End Emailer using GmailAPI
     *
     */

    public function getUsernameEmail()
    {
    	$emailSupportAddresses = EmailSupportAddress::active()->first();
        $userName              = $emailSupportAddresses->name;
        $userEmail             = '<'.$emailSupportAddresses->email.'>';
        $userNameEmail         = $userName.' '.$userEmail;

        return $userNameEmail;
    }

    public function getUserEmail()
    {
    	$emailSupportAddresses = EmailSupportAddress::active()->first();

        return '<'.$emailSupportAddresses->email.'>';
    }

}
