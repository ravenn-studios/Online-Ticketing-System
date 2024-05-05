<div class="row">
    
    <div class="col-md-3">
        <div class="nav-wrapper">

            {{-- <input type="text" class="form-control-sm mr-3" id="daterange-export-modal" placeholder="Date Range" autocomplete="off">

            <br>
            <br> --}}

            <ul class="nav nav-pills nav-fill flex-column flex-md-row" id="tabs-icons-text" role="tablist">
                <li class="nav-item">
                    {{-- <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-2-tab" data-toggle="tab" href="#tabs-icons-text-2" role="tab" aria-controls="tabs-icons-text-2" aria-selected="false"><i class="ni ni-bell-55 mr-2"></i>Average</a> --}}
                    <a class="nav-link mb-sm-3 mb-md-0 active" id="tabs-icons-text-2-tab" data-toggle="tab" href="#tabs-icons-text-2" role="tab" aria-controls="tabs-icons-text-2" aria-selected="false">Average</a>
                </li>
                <li class="nav-item">
                    {{-- <a class="nav-link mb-sm-3 mb-md-0 active" id="tabs-icons-text-1-tab" data-toggle="tab" href="#tabs-icons-text-1" role="tab" aria-controls="tabs-icons-text-1" aria-selected="true"><i class="ni ni-cloud-upload-96 mr-2"></i>Data</a> --}}
                    <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-1-tab" data-toggle="tab" href="#tabs-icons-text-1" role="tab" aria-controls="tabs-icons-text-1" aria-selected="true">Data</a>
                </li>

                @if(Auth::id() == 1)
                    <li class="nav-item">
                        {{-- <a class="nav-link mb-sm-3 mb-md-0 active" id="tabs-icons-text-1-tab" data-toggle="tab" href="#tabs-icons-text-1" role="tab" aria-controls="tabs-icons-text-1" aria-selected="true"><i class="ni ni-cloud-upload-96 mr-2"></i>Data</a> --}}
                        <a class="nav-link mb-sm-3 mb-md-0 categorized-report" id="tabs-icons-text-3-tab" data-toggle="tab" href="#tabs-icons-text-3" role="tab" aria-controls="tabs-icons-text-3" aria-selected="true">Categorized</a>
                    </li>
                @endif
            </ul>
        </div>
    </div>


    <div class="col-md-2 offset-md-7 text-right my-auto export-report-wrapper">
        <div class="loadOverlay2">
            <img src="{{ asset('images/circles-menu-3.gif') }}" style="width: 42px; margin-top: -10px;">
        </div>
        <a href="#" target="_blank" data-user-id="1" class="btn btn-icon btn-primary btn-sm export-view-report text-white">
          <span class="btn-inner--icon"><i class="fas fa-file-download"></i></span>
          <span class="btn-inner--text ml-0">Export</span>
        </a>
    </div>

</div>


<div class="card shadow">
    <div class="card-body">

        <div class="row">
            <div class="col-md-12 text-right mb-3">
                <label class="form-control-sm">Response Time Target(minutes) : </label>
                <input type="text" class="form-control-sm target-response-time" placeholder="Target Response Time" style="border: 1px solid #e9e9e9;" value="15">
            </div>
        </div>

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade" id="tabs-icons-text-1" role="tabpanel" aria-labelledby="tabs-icons-text-1-tab">
                
                <div class="table-responsive table-view-report">

                    <div>
                        
                        <table class="table align-items-center table-bordered view-report-table" id="view-report-table">
                            <thead class="thead-light" style="position: sticky; top: -1px;">
                                <tr>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: middle; text-align: center; font-family: 'Open Sans'; font-size: 10px; width: 60px!important;">#</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: middle; text-align: center; font-family: 'Open Sans'; font-size: 10px; width: 300px!important; word-break: break-all;">SUBJECT</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: middle; text-align: center; font-family: 'Open Sans'; font-size: 10px;">ASSIGNED TO</th>
                                    {{-- <th colspan="1" scope="col" height="35" style="vertical-align: middle; text-align: center; font-family: 'Open Sans'; font-size: 10px;">REQUESTER</th> --}}
                                    {{-- <th colspan="1" scope="col" height="35" style="vertical-align: middle; text-align: center; font-family: 'Open Sans'; font-size: 10px;">ORIGIN</th> --}}
                                    <th colspan="1" scope="col" height="35" style="vertical-align: middle; text-align: center; font-family: 'Open Sans'; font-size: 10px;">AGENT REPLIES</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: middle; text-align: center; font-family: 'Open Sans'; font-size: 10px;">AVG.<br>RESPONSE TIME</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: middle; text-align: center; font-family: 'Open Sans'; font-size: 10px;">RESPONSE TIME %</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: middle; text-align: center; font-family: 'Open Sans'; font-size: 10px;">RESPONDED<br>AFTER</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: middle; text-align: center; font-family: 'Open Sans'; font-size: 10px;">SOLVED<br>AFTER</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: middle; text-align: center; font-family: 'Open Sans'; font-size: 10px;">CLOSED<br>AFTER</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: middle; text-align: center; font-family: 'Open Sans'; font-size: 10px;">STATUS</th>
                                    <th colspan="1" scope="col" height="35" style="vertical-align: middle; text-align: center; font-family: 'Open Sans'; font-size: 10px; width: 150px!important;">DATE</th>
                                    {{-- <th colspan="1" scope="col" height="35" style="vertical-align: middle; text-align: center; font-family: 'Open Sans'; font-size: 10px;">TIME</th> --}}
                                </tr>
                            </thead>
                            <tbody>

                            @php
                                $arrAgentResponseTimeInMinutes = $arrResponseTimePercentage = [];
                            @endphp

                            @foreach($tickets as $key => $ticket)

                                {{-- @if($key < 20) --}}

                                <tr>
                                    {{-- <td colspan="1" height="25" style="vertical-align: middle; text-align: center; word-break: break-word; min-width: 250px;">{{ $ticket->id }}: {{ $ticket->subject }}</td> --}}
                                    <td colspan="1" height="25" style="vertical-align: middle; text-align: center;">{{ $loop->iteration }}</td>
                                    <td colspan="1" height="25" style="vertical-align: middle;word-break: break-word; min-width: 250px;">
                                        {{-- <a target="_blank" href="{{ URL('tickets/my-tickets?ticket_ids=') . $ticket->id }}" class="preview-ticket">{{ $ticket->subject }}</a> --}}
                                        <a target="_blank" class="preview-ticket" data-ticket-id="{{ $ticket->id }}" href="{{ URL('tickets/my-tickets?ticket_ids=') . $ticket->id }}">{{ $ticket->subject }}</a>
                                        <p class="text-muted text-xs m-0">{{ $ticket->requester }}</p>

                                        {{-- for js search --}}
                                        <p class="text-hidden">{{ $ticket->assignedTo->user->name }}</p>
                                        <p class="text-hidden">{{ $ticket->status->name }}</p>
                                    </td>
                                    {{-- <td colspan="1" height="25" style="vertical-align: middle; text-align: center;" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $ticket->assignedTo->user->name }}">
                                        <i class="far fa-user-circle" style="font-size: 25px;"></i>
                                    </td> --}}
                                    <td colspan="1" height="25" style="vertical-align: middle; text-align: center;" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $ticket->assignedTo->user->name }}">
                                        {!! $ticket->assignedTo->user->avatarNav() !!}
                                    </td>
                                    {{-- <td colspan="1" height="25" style="vertical-align: middle; text-align: center;" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $ticket->requester }}">
                                        <i class="far fa-user-circle" style="font-size: 25px;"></i>
                                    </td> --}}
                                    {{-- <td colspan="1" height="25" style="vertical-align: middle; text-align: center;" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ strtoupper( $ticket->origin->name ) }}">
                                        <i class="far fa-envelope" style="font-size: 16px;"></i>
                                    </td> --}}
                                    <td colspan="1" height="25" style="vertical-align: middle; text-align: center;">{{ (!empty($ticket->agent_reply_count) ? $ticket->agent_reply_count : '-') }}</td>
                                    <td colspan="1" height="25" style="vertical-align: middle; text-align: center;" @if(!empty($ticket->agent_average_response_time_detailed)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $ticket->agent_average_response_time_detailed }}" @endif >{{ (!empty($ticket->agent_average_response_time) ? $ticket->agent_average_response_time : '-') }}</td>

                                    @php

                                        $tmpResponseTimePercentage = 0;

                                        if ( !empty($ticket->agent_average_response_time_in_minutes) )
                                        {
                                            array_push($arrAgentResponseTimeInMinutes, $ticket->agent_average_response_time_in_minutes);

                                            $tmpResponseTimePercentage = number_format(($ticket->agent_average_response_time_in_minutes / 15) * 100, 2);
                                            array_push($arrResponseTimePercentage, $tmpResponseTimePercentage);
                                        }

                                    @endphp

                                    <input type="hidden" class="response-time-in-minutes" value="{{ !empty($ticket->agent_average_response_time_in_minutes) ? $ticket->agent_average_response_time_in_minutes : '' }}">
                                    {{-- <td colspan="1" height="25" style="vertical-align: middle; text-align: center;" class="response-time-percentage">{{ !empty($ticket->agent_average_response_time_in_minutes) ? number_format($ticket->agent_average_response_time_in_minutes / 15, 2) * 100 . '%' : '-' }}</td> --}}
                                    <td colspan="1" height="25" style="vertical-align: middle; text-align: center;" class="response-time-percentage">
                                        @if( !empty($ticket->agent_average_response_time_in_minutes) )

                                            @php
                                                $tmpAgent_average_response_time_in_minutes = ($ticket->agent_average_response_time_in_minutes / 15) * 100;
                                            @endphp

                                            @if( $tmpAgent_average_response_time_in_minutes > 100 )

                                                100%<i class="ni ni-fat-add"></i>

                                            @else
                                                {{ number_format(($ticket->agent_average_response_time_in_minutes / 15) * 100, 2) . '%' }}
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
                                    {{-- <td colspan="1" height="25" style="vertical-align: middle; text-align: center;">{{ $ticket->created_at->format('h:ia') }}</td> --}}
                                </tr>

                                {{-- @endif --}}

                                @if ( $loop->last )

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

                            </tbody>  
                        </table>

                        <div class="load-more-block text-center">
                            <button class="btn btn-sm btn-primary mt-4 btn-load-more-report-tickets">Load More..</button>
                        </div>

                    </div>

                </div>

            </div>
            <div class="tab-pane fade show active" id="tabs-icons-text-2" role="tabpanel" aria-labelledby="tabs-icons-text-2-tab">
                
                <div class="table-responsive table-view-report table-view-report-average">

                    <input type="hidden" class="userIds" value="{{ $_userIds }}">

                    <div>
                            
                        <table class="table align-items-center table-bordered">

                            @if( !empty($agentsTicketsCountByStatus) )

                                @foreach($agentsTicketsCountByStatus as $agentTicketCount)
                                    
                                        
                                        @if( $loop->index == 0)
                                            <thead class="thead-light">
                                                <tr>
                                                    {{-- <th style="text-align: center; width: 230px;"><i>COUNTA of STATUS</i></th> --}}
                                                    {{-- <th style="text-align: center; width: 230px;"></th> --}}
                                                    {{-- <th style="text-align: center;" colspan="16"><i>Handled Contacts</i></th> --}}
                                                    <th style="text-align: center;" colspan="16">Tickets/Email/Chat Handled (Podium)</th>
                                                    <th style="text-align: center;" colspan="3">IP Calls Handled</th>
                                                    <th style="text-align: center;">Total Target</th>
                                                    <th style="text-align: center;">Actual</th>
                                                    <th style="text-align: center;">Total Calls</th>
                                                    {{-- <th style="text-align: center;" colspan="2"><i>SOLVED WITHIN/OVER</i></th>
                                                    <th style="text-align: center;" colspan="2"><i>CLOSED WITHIN/OVER</i></th>
                                                    <th style="text-align: center;" colspan="3"><i>Average Time Ticket was</i></th> --}}
                                                </tr>

                                                <tr>
                                                    <th style="text-align: center;" class="fixed-column"><i>ASSIGNED TO</i></th>
                                                    <th style="text-align: center;">Pending</th>
                                                    <th style="text-align: center;">Solved</th>
                                                    <th style="text-align: center;">Closed</th>
                                                    <th style="text-align: center;"># Handled</th>
                                                    <th style="text-align: center;">Target</th>
                                                    <th style="text-align: center;">Total Percentage</th>
                                                    <th style="text-align: center;">Percentage to Goal</th>
                                                    <th style="text-align: center;">Tickets left to Goal</th>
                                                    <th style="text-align: center;">Solved < 24H</th>
                                                    <th style="text-align: center;">Solved > 24H</th>
                                                    <th style="text-align: center;">Closed < 24H</th>
                                                    <th style="text-align: center;">Closed > 24H</th>
                                                    <th style="text-align: center;">Pending to Solved</th>
                                                    <th style="text-align: center;">Solved to Closed</th>   
                                                    <th style="text-align: center;">Pending to Closed</th>   
                                                    <th style="text-align: center;"># Handled</th>   
                                                    <th style="text-align: center;">Target</th>   
                                                    <th style="text-align: center;">Total Percentage</th>
                                                    <th style="text-align: center;background-color: inherit; color: inherit;font-size: .8125rem;color: #525f7f; font-weight: 500;" class="total-target">0</th>
                                                    <th style="text-align: center;background-color: inherit; color: inherit;font-size: .8125rem;color: #525f7f; font-weight: 500;" class="total-actual">0</th>
                                                    <th style="text-align: center;background-color: inherit; color: inherit;font-size: .8125rem;color: #525f7f; font-weight: 500;" class="total-calls">0</th>
                                                </tr>
                                            </thead>
                                        @endif
                                        

                                        <tbody>
                                            <tr>
                                                {{-- <td style="text-align: center;">{{ $user->name }}</td> --}}
                                                {{-- <td style="text-align: center;">{{ $agentTicketCount['name'] }}</td> --}}
                                                <td style="text-align: center;" class="column-user" data-user-id="{{ $agentTicketCount['id'] }}" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $agentTicketCount['name'] }}">{!! \App\User::find($agentTicketCount['id'])->avatarNav() !!}</td>
                                                <td style="text-align: center;">{{ $agentTicketCount['tickets_pending'] }}</td>
                                                <td style="text-align: center;">{{ $agentTicketCount['tickets_solved'] }}</td>
                                                <td style="text-align: center;">{{ $agentTicketCount['tickets_closed'] }}</td>
                                                <td style="text-align: center;" class="tickets-emails-chats-handled">{{ $agentTicketCount['tickets_pending'] + $agentTicketCount['tickets_solved'] + $agentTicketCount['tickets_closed'] }}</td>
                                                <td style="text-align: center;"><div class="handled-contacts-target" contenteditable="true" data-handled-tickets="{{ $agentTicketCount['tickets_pending'] + $agentTicketCount['tickets_solved'] + $agentTicketCount['tickets_closed'] }}">0</div></td>
                                                <td style="text-align: center;" class="handled-contacts-total-percentage">0%</td>
                                                <td style="text-align: center;" class="percentage-to-goal">0%</td>
                                                <td style="text-align: center;" class="tickets-left-to-goal">0</td>
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

                                                <td style="text-align: center;" class="ip-calls-handled editable" contenteditable="true">0</td>
                                                <td style="text-align: center;" class="ip-calls-target editable" contenteditable="true">0</td>
                                                <td style="text-align: center;" class="ip-calls-total-percentage">0%</td>

                                                <td style="text-align: center;" class="row-total-target">0</td>
                                                <td style="text-align: center;" class="row-total-actual">{{ $agentTicketCount['tickets_pending'] + $agentTicketCount['tickets_solved'] + $agentTicketCount['tickets_closed'] }}</td>
                                                <td style="text-align: center;" class="row-total-calls" data-row-total-calls="0.00">0.00%</td>
                                            </tr>
                                        </tbody>

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

                                @endforeach

                            @endif

                        </table>

                    </div>

                </div>

            </div>
            <div class="tab-pane fade" id="tabs-icons-text-3" role="tabpanel" aria-labelledby="tabs-icons-text-3-tab">
                
                <div class="table-responsive table-view-report table-view-report-average">

                    <input type="hidden" class="userIds" value="{{ $_userIds }}">

                    <div class="categorized-report-wrapper px-3"></div>

                </div>

            </div>
        </div>
    </div>
</div>