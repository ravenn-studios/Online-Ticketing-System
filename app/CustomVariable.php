<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\TicketStatus;
use App\TicketPriority;
use App\TicketType;
use App\User;

class CustomVariable extends Model
{
    public $table = 'custom_variables';

    public static function buildCustomVariables($ticket)
    {
        $customVariables = self::all();
        $variables       = Array();
        $user            = Auth::user();
        $_ticket         = $ticket->toArray();

        foreach ($customVariables as $customVariable)
        {
            if ($customVariable->name == 'CUSTOMER_NAME')
            {
                $variables[$customVariable->name] = rtrim(explode("<", $_ticket[$customVariable->column])[0]);
            }
            else if ($customVariable->name == 'CUSTOMER_EMAIL')
            {
                $variables[$customVariable->name] = (preg_match('/<(.*?)>/', $_ticket[$customVariable->column], $match) == 1) ? $match[1] : rtrim(explode("@", $_ticket[$customVariable->column])[0]);
            }
            else if ($customVariable->name == 'TICKET_STARTED_AT')
            {
                $variables[$customVariable->name] = date('M d', strtotime($_ticket[$customVariable->column]));
            }
            else if ($customVariable->name == 'TICKET_STATUS')
            {
                $variables[$customVariable->name] = TicketStatus::find($_ticket[$customVariable->column])->name;
            }
            else if ($customVariable->name == 'TICKET_PRIORITY')
            {
                $variables[$customVariable->name] = TicketPriority::find($_ticket[$customVariable->column])->name;
            }
            else if ($customVariable->name == 'TICKET_TYPE')
            {
                $variables[$customVariable->name] = TicketType::find($_ticket[$customVariable->column])->name;
            }
            else
            {
                $variables[$customVariable->name] = $_ticket[$customVariable->column];
            }
        }

        $variables['AGENT_NAME']      = $user->name;
        $variables['AGENT_EMAIL']     = $user->email;

        return $variables;

    }

    public static function getVariableResponse($ticket, $emailContent)
    {

        $variables = self::buildCustomVariables($ticket);


        $response = preg_replace_callback('/{{(.+?)}}/ix',function($match)use($variables){
            return !empty($variables[$match[1]]) ? $variables[$match[1]] : $match[0];
       },$emailContent);

       return $response;

    }
}
