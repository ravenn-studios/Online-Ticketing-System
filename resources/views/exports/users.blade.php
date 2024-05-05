
@if(!$tickets->count())

    <table class="table-responsive">
        <tr>
            <td>No Records Found.</td>
        </tr>
    </table>

@else

<table>
    @foreach($agentsTicketsCountByStatus as $agentTicketCount)
        
            
            @if( $loop->index == 0)

                <thead>
                    <tr>
                        <th style="text-align: center;"></th>
                        <th style="text-align: center;" colspan="16">Tickets/Email/Chat Handled (Podium)</th>
                        <th style="text-align: center;" colspan="3">IP Calls Handled</th>
                        <th style="text-align: center;">Total Target</th>
                        <th style="text-align: center;">Actual</th>
                        <th style="text-align: center;">Total Calls</th>
                    </tr>
                    <tr>
                        <th style="text-align: center;"></th>
                        <th style="text-align: center;">ASSIGNED TO</th>
                        <th style="text-align: center;">Pending</th>
                        <th style="text-align: center;">Solved</th>
                        <th style="text-align: center;">Closed</th>
                        <th style="text-align: center;"># Handled</th>
                        <th style="text-align: center;">Target</th>
                        <th style="text-align: center;">Total Percentage</th>
                        <th style="text-align: center;">Percentage to Goal</th>
                        <th style="text-align: center;">Tickets left to Goal</th>
                        <th style="text-align: center;">Solved &lt; 24H</th>
                        <th style="text-align: center;">Solved  &gt; 24H</th>
                        <th style="text-align: center;">Closed &lt; 24H</th>
                        <th style="text-align: center;">Closed &gt; 24H</th>
                        <th style="text-align: center;">Pending to Solved</th>
                        <th style="text-align: center;">Solved to Closed</th>
                        <th style="text-align: center;">Pending to Closed</th>
                        <th style="text-align: center;"># Handled</th>
                        <th style="text-align: center;">Target</th>
                        <th style="text-align: center;">Total Percentage</th>
                        <th style="text-align: center;">{{ isset($viewReportData['totalTarget']) ? $viewReportData['totalTarget'] : 0 }}</th>
                        <th style="text-align: center;">{{ isset($viewReportData['totalActual']) ? $viewReportData['totalActual'] : 0 }}</th>
                        <th style="text-align: center;">{{ isset($viewReportData['totalCalls']) ? $viewReportData['totalCalls'] : 0 }}</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td></td>
                        <td style="text-align: center;">{{ $agentTicketCount['name'] }}</td>

                        @if( empty($viewReportData['rowDataAverages']) )

                            <td style="text-align: center;">{{ $agentTicketCount['tickets_pending'] }}</td>
                            <td style="text-align: center;">{{ $agentTicketCount['tickets_solved'] }}</td>
                            <td style="text-align: center;">{{ $agentTicketCount['tickets_closed'] }}</td>
                            <td style="text-align: center;">{{ $agentTicketCount['tickets_pending'] + $agentTicketCount['tickets_solved'] + $agentTicketCount['tickets_closed'] }}</td>
                            <td style="text-align: center;">0</td>
                            <td style="text-align: center;">0</td>
                            <td style="text-align: center;">0%</td>
                            <td style="text-align: center;">0%</td>
                            <td style="text-align: center;">
                                @if( isset( $agentTicketCount['count_tickets_solved_in_a_day'] ) ) {{ $agentTicketCount['count_tickets_solved_in_a_day'] }} @endif
                            </td>

                            <td style="text-align: center;">
                                @if( isset( $agentTicketCount['count_tickets_solved_over_a_day'] ) ) {{ $agentTicketCount['count_tickets_solved_over_a_day'] }} @endif
                            </td>

                            <td style="text-align: center;">
                                @if( isset( $agentTicketCount['count_tickets_closed_in_a_day'] ) ) {{ $agentTicketCount['count_tickets_closed_in_a_day'] }} @endif
                            </td>

                            <td style="text-align: center;">
                                @if( isset( $agentTicketCount['count_tickets_closed_over_a_day'] ) ) {{ $agentTicketCount['count_tickets_closed_over_a_day'] }} @endif
                            </td>

                            <td style="text-align: center;" @if(isset($agentTicketCount['average_time_ticket_opened_to_solved'])) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $agentTicketCount['average_time_ticket_opened_to_solved_detailed'] }}" @endif>
                                @if(isset($agentTicketCount['average_time_ticket_opened_to_solved']) && !empty($agentTicketCount['average_time_ticket_opened_to_solved']))
                                    {{ $agentTicketCount['average_time_ticket_opened_to_solved'] }}
                                @else
                                    -    
                                @endif
                            </td>
                            <td style="text-align: center;" @if(isset($agentTicketCount['average_time_ticket_solved_to_closed'])) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $agentTicketCount['average_time_ticket_solved_to_closed_detailed'] }}" @endif>
                                @if(isset($agentTicketCount['average_time_ticket_solved_to_closed']) && !empty($agentTicketCount['average_time_ticket_solved_to_closed']))
                                    {{ $agentTicketCount['average_time_ticket_solved_to_closed'] }}
                                @else
                                    -    
                                @endif
                            </td>
                            <td style="text-align: center;" @if(isset($agentTicketCount['average_time_ticket_opened_to_closed'])) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $agentTicketCount['average_time_ticket_opened_to_closed_detailed'] }}" @endif>
                                @if(isset($agentTicketCount['average_time_ticket_opened_to_closed']) && !empty($agentTicketCount['average_time_ticket_opened_to_closed']))
                                    {{ $agentTicketCount['average_time_ticket_opened_to_closed'] }}
                                @else
                                    -    
                                @endif
                            </td>
                            <td style="text-align: center;">0</td>
                            <td style="text-align: center;">0</td>
                            <td style="text-align: center;">0%</td>
                            <td style="text-align: center;">0</td>
                            <td style="text-align: center;">{{ $agentTicketCount['tickets_pending'] + $agentTicketCount['tickets_solved'] + $agentTicketCount['tickets_closed'] }}</td>
                            <td style="text-align: center;">0%</td>

                        @else

                            @if( !empty($viewReportData['rowDataAverages']) )

                                @foreach($viewReportData['rowDataAverages'][$agentTicketCount['id']] as $key => $rowData)

                                    @if( $loop->index != 0)

                                        <td style="text-align: center;">{{ $rowData }}</td>

                                    @endif

                                @endforeach

                            @endif

                        @endif

                    </tr>
                </tbody>

            @endif

    @endforeach

</table>

<table class="table-responsive">

    <tr></tr>
    <thead>
        <tr>
            <th></th>
            <th style="vertical-align: center; text-align: center; font-size: 12px;font-weight: 700;">
                {{ $dateRange }}
            </th>
        </tr>
        <tr>
            <th height="35" style="vertical-align: center; text-align: center; font-size: 10px;">#</th>
            <th height="35" style="vertical-align: center; text-align: center; font-size: 10px; width: 40px;">SUBJECT</th>
            <th height="35" style="vertical-align: center; text-align: center; font-size: 10px;">ASSIGNED TO</th>
            <th height="35" style="vertical-align: center; text-align: center; font-size: 10px;">REQUESTER</th>
            <th height="35" style="vertical-align: center; text-align: center; font-size: 10px;">ORIGIN</th>
            <th height="35" style="vertical-align: center; text-align: center; font-size: 10px; width: 18px;">AGENT REPLIES</th>
            <th height="35" style="vertical-align: center; text-align: center; font-size: 10px; width: 18px;">AVG.<br>RESPONSE TIME</th>
            <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-size: 10px; width: 18px;">RESPONSE TIME %</th>
            <th height="35" style="vertical-align: center; text-align: center; font-size: 10px; width: 15px;">RESPONDED<br>AFTER</th>
            <th height="35" style="vertical-align: center; text-align: center; font-size: 10px; width: 15px;">SOLVED<br>AFTER</th>
            <th height="35" style="vertical-align: center; text-align: center; font-size: 10px; width: 15px;">CLOSED<br>AFTER</th>
            <th height="35" style="vertical-align: center; text-align: center; font-size: 10px; width: 12px;">STATUS</th>
            <th height="35" style="vertical-align: center; text-align: center; font-size: 10px;">DATE</th>
        </tr>
    </thead>
    <tbody>

        @php
            $arrAgentResponseTimeInMinutes = $arrResponseTimePercentage = [];
        @endphp

        @foreach($tickets as $key => $ticket)

            <tr>
                <td height="25" style="vertical-align: center; text-align: center;">{{ $loop->iteration }}</td>
                <td height="25" style="vertical-align: center; text-align: left; word-break: break-word;">{{ $ticket->subject }}</td>
                <td height="25" style="vertical-align: center; text-align: left;">{{ $user->name }}</td>
                <td height="25" style="vertical-align: center; text-align: left;">{{ $ticket->requester }}</td>
                <td height="25" style="vertical-align: center; text-align: left;">@if(isset($ticket->origin->name)) {{ strtoupper( $ticket->origin->name ) }} @endif</td>
                <td height="25" style="vertical-align: center; text-align: center;">{{ (!empty($ticket->agent_reply_count) ? $ticket->agent_reply_count : '-') }}</td>
                <td height="25" style="vertical-align: center; text-align: center;">{{ (!empty($ticket->agent_average_response_time) ? $ticket->agent_average_response_time : '-') }}</td>

                 @php

                    $tmpResponseTimePercentage = 0;

                    if ( !empty($ticket->agent_average_response_time_in_minutes) )
                    {
                        array_push($arrAgentResponseTimeInMinutes, $ticket->agent_average_response_time_in_minutes);

                        $tmpResponseTimePercentage = number_format(($ticket->agent_average_response_time_in_minutes / 15) * 100, 2);
                        array_push($arrResponseTimePercentage, $tmpResponseTimePercentage);
                    }

                @endphp

                <td colspan="1" height="25" style="vertical-align: center; text-align: center;" class="response-time-percentage">
                    {{-- {{ !empty($ticket->agent_average_response_time_in_minutes) ? number_format(($ticket->agent_average_response_time_in_minutes / 15) * 100, 2) . '%' : '-' }} --}}

                    @if( !empty($ticket->agent_average_response_time_in_minutes) )

                        @php
                            $tmpAgent_average_response_time_in_minutes = $ticket->agent_average_response_time_in_minutes / 15 * 100;
                        @endphp

                        @if( $tmpAgent_average_response_time_in_minutes > 100 )

                            100%+

                        @else
                            {{ number_format($ticket->agent_average_response_time_in_minutes / 15, 2) * 100 . '%' }}
                        @endif

                    @else
                        -
                    @endif
                    
                </td>

                <td height="25" style="vertical-align: center; text-align: center;">{{ (!empty($ticket->agent_first_response_duration) ? $ticket->agent_first_response_duration : '-') }}</td>
                <td height="25" style="vertical-align: center; text-align: center;">{{ (!empty($ticket->solved_duration) ? $ticket->solved_duration : '-') }}</td>
                <td height="25" style="vertical-align: center; text-align: center;">{{ (!empty($ticket->closed_duration) ? $ticket->closed_duration : '-') }}</td>
                <td height="25" style="vertical-align: center; text-align: center;">{{ $ticket->status->name }}</td>
                <td height="25" style="vertical-align: center; text-align: center;">{{ $ticket->created_at->format('M d, Y h:ia') }}</td>
                {{-- <td height="25" style="vertical-align: center; text-align: center;">{{ $ticket->created_at->format('h:ia') }}</td> --}}

                {{-- <td height="25">{{ $ticket['name'] }}</td>
                <td height="25">{{ $ticket['email'] }}</td>
                <td height="25">{{ $ticket['id'] }}</td> --}}
            </tr>

            @if ( $loop->last )

                <tr class="report-efficiency">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;">{{ ( array_sum($arrAgentResponseTimeInMinutes) > 0 ) ? number_format( array_sum($arrAgentResponseTimeInMinutes) / count($arrAgentResponseTimeInMinutes), 2) . ' minutes' : '-' }}</td>
                    <td style="text-align: center;">{{ ( array_sum($arrResponseTimePercentage) > 0 ) ? number_format(array_sum($arrResponseTimePercentage) / count($arrResponseTimePercentage), 2) .'%' : '-' }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>

            @endif

        @endforeach

    </tbody>
</table>

<br>

@endif