<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Signature;
use App\ActivityLog;
use App\TicketStatus;
use App\TicketPriority;
use App\TicketType;
use App\TicketOrigin;

class ActivityLogController extends Controller
{
    
    public function index()
    {

        $user  = Auth::user();
        // $signatures = Signature::where('user_id', $user->id)->orderBy('created_at', 'DESC')->paginate(10);

        $logs = ActivityLog::where('properties', '<>', '{"attributes":[],"old":[]}')->orderBy('created_at', 'DESC')->paginate(20);


        foreach($logs as $log)
        {

            $originalProperties = json_decode($log->properties);

            //current / new
            if ( isset($originalProperties->attributes->status_id) )
            {
                $originalProperties->attributes->status_id = TicketStatus::find($originalProperties->attributes->status_id)->name;
            }

            if ( isset($originalProperties->attributes->priority_id) )
            {
                $originalProperties->attributes->priority_id = TicketPriority::find($originalProperties->attributes->priority_id)->name;
            }

            if ( isset($originalProperties->attributes->type_id) )
            {
                $originalProperties->attributes->type_id = TicketType::find($originalProperties->attributes->type_id)->name;
            }

            if ( isset($originalProperties->attributes->origin_id) )
            {
                $originalProperties->attributes->origin_id = TicketOrigin::find($originalProperties->attributes->origin_id)->name;
            }

            if ( isset($originalProperties->attributes->read) )
            {

                if( $originalProperties->attributes->read )
                {
                    $originalProperties->attributes->read = 'Read';
                }
                else
                {
                    $originalProperties->attributes->read = 'Unread';
                }
                
            }

            //old

            if ( isset($originalProperties->old->status_id) )
            {
                $originalProperties->old->status_id = TicketStatus::find($originalProperties->old->status_id)->name;
            }

            if ( isset($originalProperties->old->priority_id) )
            {
                $originalProperties->old->priority_id = TicketPriority::find($originalProperties->old->priority_id)->name;
            }

            if ( isset($originalProperties->old->type_id) )
            {
                $originalProperties->old->type_id = TicketType::find($originalProperties->old->type_id)->name;
            }

            if ( isset($originalProperties->old->origin_id) )
            {
                $originalProperties->old->origin_id = TicketOrigin::find($originalProperties->old->origin_id)->name;
            }

            if ( isset($originalProperties->old->read) )
            {

                if( $originalProperties->old->read )
                {
                    $originalProperties->old->read = 'Read';
                }
                else
                {
                    $originalProperties->old->read = 'Unread';
                }
                
            }


            $log->properties = json_encode($originalProperties);

            // dump( json_decode($log->properties) );

        }

        return view('activity.logs', compact(['logs']));

    }

}
