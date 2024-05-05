@php
    $arrAgentResponseTimeInMinutes = $arrResponseTimePercentage = [];
@endphp

@foreach($tickets as $ticket)

    <tr>
        <td colspan="1" height="25" style="vertical-align: middle; text-align: center;">{{ $loop->iteration }}</td>
        <td colspan="1" height="25" style="vertical-align: middle;word-break: break-word; min-width: 250px;">
            <a target="_blank" class="preview-ticket" data-ticket-id="{{ $ticket->id }}" href="{{ URL('tickets/my-tickets?ticket_ids=') . $ticket->id }}">{{ $ticket->subject }}</a>
            <p class="text-muted text-xs m-0">{{ $ticket->requester }}</p>

            {{-- for js search --}}
            <p class="text-hidden">{{ $ticket->assignedTo->user->name }}</p>
            <p class="text-hidden">{{ $ticket->status->name }}</p>
        </td>
        <td colspan="1" height="25" style="vertical-align: middle; text-align: center;" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $ticket->assignedTo->user->name }}">
            {!! $ticket->assignedTo->user->avatarNav() !!}
        </td>
        <td colspan="1" height="25" style="vertical-align: middle; text-align: center;">{{ (!empty($ticket->agent_reply_count) ? $ticket->agent_reply_count : '-') }}</td>
        <td colspan="1" height="25" style="vertical-align: middle; text-align: center;" @if(!empty($ticket->agent_average_response_time_detailed)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $ticket->agent_average_response_time_detailed }}" @endif >{{ (!empty($ticket->agent_average_response_time) ? $ticket->agent_average_response_time : '-') }}</td>

        @php

            $tmpResponseTimePercentage = 0;

            if ( !empty($ticket->agent_average_response_time_in_minutes) )
            {
                array_push($arrAgentResponseTimeInMinutes, $ticket->agent_average_response_time_in_minutes);

                $tmpResponseTimePercentage = number_format($ticket->agent_average_response_time_in_minutes / 15, 2) * 100;
                array_push($arrResponseTimePercentage, $tmpResponseTimePercentage);
            }

        @endphp

        <input type="hidden" class="response-time-in-minutes" value="{{ !empty($ticket->agent_average_response_time_in_minutes) ? $ticket->agent_average_response_time_in_minutes : '' }}">
        {{-- <td colspan="1" height="25" style="vertical-align: middle; text-align: center;" class="response-time-percentage">{{ !empty($ticket->agent_average_response_time_in_minutes) ? number_format($ticket->agent_average_response_time_in_minutes / $responseTimeTarget, 2) * 100 . '%' : '-' }}</td> --}}
        <td colspan="1" height="25" style="vertical-align: middle; text-align: center;" class="response-time-percentage">
            @if( !empty($ticket->agent_average_response_time_in_minutes) )

                @php
                    $tmpAgent_average_response_time_in_minutes = ($ticket->agent_average_response_time_in_minutes / $responseTimeTarget) * 100;
                @endphp

                @if( $tmpAgent_average_response_time_in_minutes > 100 )

                    100%<i class="ni ni-fat-add"></i>

                @else
                    {{ number_format($ticket->agent_average_response_time_in_minutes / $responseTimeTarget, 2) * 100 . '%' }}
                @endif

            @else
                -
            @endif
        </td>
        <td colspan="1" height="25" style="vertical-align: middle; text-align: center;" @if(!empty($ticket->agent_first_response_duration_detailed)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $ticket->agent_first_response_duration_detailed }}" @endif >{{ (!empty($ticket->agent_first_response_duration) ? $ticket->agent_first_response_duration : '-') }}</td>
        <td @if( isset($ticket->solved_over_a_day) && $ticket->solved_over_a_day ) style="background: #ffdede;" @endif colspan="1" height="25" style="vertical-align: middle; text-align: center;" @if(!empty($ticket->solved_duration)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $ticket->detailed_solved_duration }}" @endif >{{ (!empty($ticket->solved_duration) ? $ticket->solved_duration : '-') }}</td>
        <td @if( isset($ticket->closed_over_a_day) && $ticket->closed_over_a_day ) style="background: #ffdede;" @endif class="text-center" colspan="1" height="25" style="vertical-align: middle; text-align: center;" @if(!empty($ticket->closed_duration)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $ticket->detailed_closed_duration }}" @endif >{{ (!empty($ticket->closed_duration) ? $ticket->closed_duration : '-') }}</td>
        <td colspan="1" height="25" style="vertical-align: middle; text-align: center;">{{ $ticket->status->name }}</td>
        <td colspan="1" height="25" style="vertical-align: middle; text-align: center;">{{ $ticket->created_at->format('M d, Y h:ia') }}</td>
    </tr>

    @if ($loop->last)

        @php
            $sumAgentResponseTimeInMinutes = array_sum($arrAgentResponseTimeInMinutes);
            $sumResponseTimePercentage     = array_sum($arrResponseTimePercentage);
        @endphp

        <tr class="report-efficiency">
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td style="text-align: center;">{{ ( $sumAgentResponseTimeInMinutes > 0 ) ? number_format( $sumAgentResponseTimeInMinutes / count($arrAgentResponseTimeInMinutes), 2) . ' minutes' : '-' }}</td>
            <td style="text-align: center;">{{ ( $sumResponseTimePercentage > 0 ) ? number_format($sumResponseTimePercentage / count($arrResponseTimePercentage), 2) .'%' : '-' }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    @endif

@endforeach