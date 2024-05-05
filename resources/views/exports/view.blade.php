
<div class="col-md-2">

    <div class="nav-wrapper">

        <ul class="nav nav-pills nav-fill flex-column flex-md-row" id="tabs-icons-text" role="tablist">
            <li class="nav-item">
                {{-- <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-2-tab" data-toggle="tab" href="#tabs-icons-text-2" role="tab" aria-controls="tabs-icons-text-2" aria-selected="false"><i class="ni ni-bell-55 mr-2"></i>Average</a> --}}
                <a class="nav-link mb-sm-3 mb-md-0 active" id="tabs-icons-text-2-tab" data-toggle="tab" href="#tabs-icons-text-2" role="tab" aria-controls="tabs-icons-text-2" aria-selected="false">Average</a>
            </li>
            <li class="nav-item">
                {{-- <a class="nav-link mb-sm-3 mb-md-0 active" id="tabs-icons-text-1-tab" data-toggle="tab" href="#tabs-icons-text-1" role="tab" aria-controls="tabs-icons-text-1" aria-selected="true"><i class="ni ni-cloud-upload-96 mr-2"></i>Data</a> --}}
                <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-1-tab" data-toggle="tab" href="#tabs-icons-text-1" role="tab" aria-controls="tabs-icons-text-1" aria-selected="true">Data</a>
            </li>
        </ul>
    </div>
</div>


<div class="card shadow">
    <div class="card-body">
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade" id="tabs-icons-text-1" role="tabpanel" aria-labelledby="tabs-icons-text-1-tab">
                
                <div class="table-responsive table-view-report">

                    <div>
                        
                        <table class="table align-items-center table-bordered">
                            <thead class="thead-light" style="position: sticky; top: -1px;">
                                <tr>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px; width: 60px!important;">#</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px; width: 300px!important; word-break: break-all;">SUBJECT</th>
                                    {{-- <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">ASSIGNED TO</th> --}}
                                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">REQUESTER</th>
                                    {{-- <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">ORIGIN</th> --}}
                                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">AGENT REPLIES</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">AVG.<br>RESPONSE TIME</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">RESPONDED<br>AFTER</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">SOLVED<br>AFTER</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">CLOSED<br>AFTER</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">STATUS</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px; width: 150px!important;">DATE</th>
                                    {{-- <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">TIME</th> --}}
                                </tr>
                            </thead>
                            <tbody>

                            @foreach($tickets as $key => $ticket)

                                <tr>
                                    {{-- <td colspan="1" height="25" style="vertical-align: center; text-align: center; word-break: break-word; min-width: 250px;">{{ $ticket->id }}: {{ $ticket->subject }}</td> --}}
                                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ $loop->iteration }}</td>
                                    {{-- <td colspan="1" height="25" style="vertical-align: center; text-align: center; word-break: break-word; min-width: 250px;">
                                        <a target="_blank" href="{{ URL('tickets/my-tickets?ticket_ids=') . $ticket->id }}" data-ticket-id="{{ $ticket->id }}">{{ $ticket->subject }}</a>
                                    </td> --}}
                                    <td colspan="1" height="25" style="vertical-align: center;word-break: break-word; min-width: 250px;">
                                        {{-- <a target="_blank" href="{{ URL('tickets/my-tickets?ticket_ids=') . $ticket->id }}" class="preview-ticket">{{ $ticket->subject }}</a> --}}
                                        <a target="_blank" href="{{ URL('tickets/my-tickets?ticket_ids=') . $ticket->id }}">{{ $ticket->subject }}</a>
                                        <p class="text-muted text-xs m-0">{{ $ticket->requester }}</p>
                                    </td>
                                    {{-- <td colspan="1" height="25" style="vertical-align: center; text-align: center;" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $user->name }}">
                                        <i class="far fa-user-circle" style="font-size: 25px;"></i>
                                    </td> --}}
                                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $ticket->requester }}">
                                        <i class="far fa-user-circle" style="font-size: 25px;"></i>
                                    </td>
                                    {{-- <td colspan="1" height="25" style="vertical-align: center; text-align: center;" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ strtoupper( $ticket->origin->name ) }}">
                                        <i class="far fa-envelope" style="font-size: 16px;"></i>
                                    </td> --}}
                                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ (!empty($ticket->agent_reply_count) ? $ticket->agent_reply_count : '-') }}</td>
                                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;" @if(!empty($ticket->agent_average_response_time_detailed)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $ticket->agent_average_response_time_detailed }}" @endif >{{ (!empty($ticket->agent_average_response_time) ? $ticket->agent_average_response_time : '-') }}</td>
                                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;" @if(!empty($ticket->agent_first_response_duration_detailed)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $ticket->agent_first_response_duration_detailed }}" @endif >{{ (!empty($ticket->agent_first_response_duration) ? $ticket->agent_first_response_duration : '-') }}</td>
                                    <td @if( isset($ticket->solved_over_a_day) && $ticket->solved_over_a_day ) style="background: #ffdede;" @endif class="text-center" colspan="1" height="25" style="vertical-align: center; text-align: center;" @if(!empty($ticket->solved_duration)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $ticket->detailed_solved_duration }}" @endif >{{ (!empty($ticket->solved_duration) ? $ticket->solved_duration : '-') }}</td>
                                    <td @if( isset($ticket->closed_over_a_day) && $ticket->closed_over_a_day ) style="background: #ffdede;" @endif class="text-center" colspan="1" height="25" style="vertical-align: center; text-align: center;" @if(!empty($ticket->closed_duration)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $ticket->detailed_closed_duration }}" @endif >{{ (!empty($ticket->closed_duration) ? $ticket->closed_duration : '-') }}</td>
                                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ $ticket->status->name }}</td>
                                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ $ticket->created_at->format('M d, Y h:ia') }}</td>
                                    {{-- <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ $ticket->created_at->format('h:ia') }}</td> --}}
                                </tr>

                            @endforeach

                            </tbody>  
                        </table>

                    </div>

                </div>

            </div>
            <div class="tab-pane fade show active" id="tabs-icons-text-2" role="tabpanel" aria-labelledby="tabs-icons-text-2-tab">
                
                <div class="table-responsive table-view-report">

                    <input type="hidden" class="userId" value="{{ $user->id }}">

                    <div>
                        
                        <table class="table align-items-center table-bordered">

                            <tr>
                                <td style="text-align: center;"><i>COUNTA of STATUS</i></td>
                                <td style="text-align: center;" colspan="4"><i>STATUS</i></td>
                                {{-- <td></td>
                                <td></td> --}}
                                <td style="text-align: center;" colspan="2"><i>SOLVED WITHIN/OVER</i></td>
                                <td style="text-align: center;" colspan="2"><i>CLOSED WITHIN/OVER</i></td>
                                <td style="text-align: center;" colspan="3"><i>Average Time Ticket was</i></td>
                                {{-- <td></td>
                                <td></td>
                                <td></td> --}}
                            </tr>

                            <tr>
                                <td style="text-align: center;"><i>ASSIGNED TO</i></td>
                                <td style="text-align: center;">Pending</td>
                                <td style="text-align: center;">Solved</td>
                                <td style="text-align: center;">Closed</td>
                                <td style="text-align: center;">Total</td>
                                {{-- <td></td> --}}
                                <td style="text-align: center;">< 24H</td>
                                <td style="text-align: center;">> 24H</td>
                                <td style="text-align: center;">< 24H</td>
                                <td style="text-align: center;">> 24H</td>
                                <td style="text-align: center;">Pending to Solved</td>
                                <td style="text-align: center;">Solved to Closed</td>
                                <td style="text-align: center;">Pending to Closed</td>
                                {{-- <td></td>
                                <td></td>
                                <td></td> --}}
                            </tr>

                            <tr>
                                <td style="text-align: center;">{{ $user->name }}</td>
                                <td style="text-align: center;">{{ $agentTicketsCountByStatus['pending'] }}</td>
                                <td style="text-align: center;">{{ $agentTicketsCountByStatus['solved'] }}</td>
                                <td style="text-align: center;">{{ $agentTicketsCountByStatus['closed'] }}</td>
                                <td style="text-align: center;">{{ $agentTicketsCountByStatus['pending'] + $agentTicketsCountByStatus['solved'] + $agentTicketsCountByStatus['closed'] }}</td>
                                {{-- <td></td> --}}
                                <td style="text-align: center;">{{ $tickets->last()->count_tickets_solved_in_a_day }}</td>
                                <td style="text-align: center;">{{ $tickets->last()->count_tickets_solved_over_a_day }}</td>
                                <td style="text-align: center;">{{ $tickets->last()->count_tickets_closed_in_a_day }}</td>
                                <td style="text-align: center;">{{ $tickets->last()->count_tickets_closed_over_a_day }}</td>
                                <td style="text-align: center;" @if(!empty($tickets->last()->average_time_ticket_opened_to_solved)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $tickets->last()->average_time_ticket_opened_to_solved_detailed }}" @endif>{{ $tickets->last()->average_time_ticket_opened_to_solved }}</td>
                                <td style="text-align: center;" @if(!empty($tickets->last()->average_time_ticket_solved_to_closed)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $tickets->last()->average_time_ticket_solved_to_closed_detailed }}" @endif>{{ $tickets->last()->average_time_ticket_solved_to_closed }}</td>
                                <td style="text-align: center;" @if(!empty($tickets->last()->average_time_ticket_opened_to_closed)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $tickets->last()->average_time_ticket_opened_to_closed_detailed }}" @endif>{{ $tickets->last()->average_time_ticket_opened_to_closed }}</td>
                                {{-- <td></td>
                                <td></td>
                                <td></td> --}}
                            </tr>

                        </table>

                        {{-- <br>

                        <table class="table align-items-center table-bordered" style="width: 50%;">

                            <tr>
                                <td style="text-align: center;" colspan="2"><i>Average Time Ticket was</i></td>
                            </tr>

                            <tr>
                                <td style="text-align: center;">Pending to Solved</td>
                                <td style="text-align: center;">Solved to Closed</td>
                            </tr>

                            <tr>
                                <td style="text-align: center;" @if(!empty($tickets->last()->average_time_ticket_opened_to_solved)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $tickets->last()->average_time_ticket_opened_to_solved_detailed }}" @endif>{{ $tickets->last()->average_time_ticket_opened_to_solved }}</td>
                                <td style="text-align: center;" @if(!empty($tickets->last()->average_time_ticket_solved_to_closed)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $tickets->last()->average_time_ticket_solved_to_closed_detailed }}" @endif>{{ $tickets->last()->average_time_ticket_solved_to_closed }}</td>
                            </tr>

                        </table> --}}

                    </div>

                </div>

            </div>
        </div>
    </div>
</div>


{{-- <div class="table-responsive table-view-report">

    <div>
        
        <table class="table align-items-center table-bordered">
            <thead class="thead-light">
                <tr>
                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px; width: 300px!important; word-break: break-all;">SUBJECT</th>
                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">ASSIGNED TO</th>
                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">REQUESTER</th>
                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">ORIGIN</th>
                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">AGENT REPLIES</th>
                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">AVG.<br>RESPONSE TIME</th>
                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">RESPONDED<br>AFTER</th>
                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">SOLVED<br>AFTER</th>
                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">CLOSED<br>AFTER</th>
                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">STATUS</th>
                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px; width: 150px!important;">DATE</th>
                    <th colspan="1" scope="col" height="35" style="vertical-align: center; text-align: center; font-family: 'Open Sans'; font-size: 10px;">TIME</th>
                </tr>
            </thead>
            <tbody>

            @foreach($tickets as $key => $ticket)

                <tr>
                    <td colspan="1" height="25" style="vertical-align: center; text-align: center; word-break: break-word;">{{ $ticket->subject }}</td>
                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ $user->name }}</td>
                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ $ticket->requester }}</td>
                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ strtoupper( $ticket->origin->name ) }}</td>
                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ (!empty($ticket->agent_reply_count) ? $ticket->agent_reply_count : '-') }}</td>
                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ (!empty($ticket->agent_average_response_time) ? $ticket->agent_average_response_time : '-') }}</td>
                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ (!empty($ticket->agent_first_response_duration) ? $ticket->agent_first_response_duration : '-') }}</td>
                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ (!empty($ticket->solved_duration) ? $ticket->solved_duration : '-') }}</td>
                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ (!empty($ticket->closed_duration) ? $ticket->closed_duration : '-') }}</td>
                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ $ticket->status->name }}</td>
                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ $ticket->created_at->format('M d, Y') }}</td>
                    <td colspan="1" height="25" style="vertical-align: center; text-align: center;">{{ $ticket->created_at->format('h:ia') }}</td>
                </tr>

            @endforeach

                <tr></tr>

                <tr>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;"><i>COUNTA of STATUS</i></td>
                    <td colspan="2" style="text-align: center;" colspan="2"><i>STATUS</i></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>

                <tr>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;"><i>ASSIGNED TO</i></td>
                    <td style="text-align: center;">Solved</td>
                    <td style="text-align: center;">Closed</td>
                    <td style="text-align: center;">Total</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>

                <tr>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;">{{ $user->name }}</td>
                    <td style="text-align: center;">{{ $agentTicketsCountByStatus['solved'] }}</td>
                    <td style="text-align: center;">{{ $agentTicketsCountByStatus['closed'] }}</td>
                    <td style="text-align: center;">{{ $agentTicketsCountByStatus['solved'] + $agentTicketsCountByStatus['closed'] }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>

            </tbody>
        </table>

    </div>

</div> --}}
