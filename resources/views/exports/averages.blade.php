
@if(!$tickets->count())

    <table class="table-responsive">
        <tr>
            <td>No Records Found.</td>
        </tr>
    </table>

@else

<table class="table-responsive">

    <tr>
        <td></td>
        <td style="text-align: center;"><i>COUNTA of STATUS</i></td>
        <td></td>
        <td style="text-align: center;" colspan="2"><i>STATUS</i></td>
        <td></td>
        <td></td>
        <td style="text-align: center;" colspan="2"><i>SOLVED WITHIN/OVER A DAY</i></td>
        <td></td>
        <td style="text-align: center;" colspan="2"><i>CLOSED WITHIN/OVER A DAY</i></td>
        <td></td>
    </tr>

    <tr>
        <td></td>
        <td style="text-align: center;"><i>ASSIGNED TO</i></td>
        <td></td>
        <td style="text-align: center;">Solved</td>
        <td style="text-align: center;">Closed</td>
        <td style="text-align: center;">Total</td>
        <td></td>
        <td style="text-align: center;">&lt; 24H</td>
        <td style="text-align: center;">&gt; 24H</td>
        <td style="text-align: center;">&lt; 24H</td>
        <td style="text-align: center;">&gt; 24H</td>
    </tr>

    <tr>
        <td></td>
        <td style="text-align: center;">{{ $user->name }}</td>
        <td></td>
        <td style="text-align: center;">{{ $agentTicketsCountByStatus['solved'] }}</td>
        <td style="text-align: center;">{{ $agentTicketsCountByStatus['closed'] }}</td>
        <td style="text-align: center;">{{ $agentTicketsCountByStatus['solved'] + $agentTicketsCountByStatus['closed'] }}</td>
        <td></td>
        <td style="text-align: center;">@if(!empty($tickets->last()->count_tickets_solved_in_a_day)){{ $tickets->last()->count_tickets_solved_in_a_day }} @endif</td>
        <td style="text-align: center;">@if(!empty($tickets->last()->count_tickets_solved_over_a_day)){{ $tickets->last()->count_tickets_solved_over_a_day }} @endif</td>
        <td style="text-align: center;">@if(!empty($tickets->last()->count_tickets_closed_in_a_day)){{ $tickets->last()->count_tickets_closed_in_a_day }} @endif</td>
        <td style="text-align: center;">@if(!empty($tickets->last()->count_tickets_closed_over_a_day)){{ $tickets->last()->count_tickets_closed_over_a_day }} @endif</td>
    </tr>

    <tr></tr>

    <tr>
        <td></td>
        <td style="text-align: center;" colspan="2"><i>Average Time Ticket was</i></td>
        <td></td>
    </tr>

    <tr>
        <td></td>
        <td style="text-align: center;">Pending to Solved</td>
        <td></td>
        <td style="text-align: center;">Solved to Closed</td>
        <td></td>
    </tr>

    <tr>
        <td></td>
        <td style="text-align: center;" @if(!empty($tickets->last()->average_time_ticket_opened_to_solved)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $tickets->last()->average_time_ticket_opened_to_solved_detailed }}" @endif>{{ $tickets->last()->average_time_ticket_opened_to_solved }}</td>
        <td></td>
        <td style="text-align: center;" @if(!empty($tickets->last()->average_time_ticket_solved_to_closed)) data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $tickets->last()->average_time_ticket_solved_to_closed_detailed }}" @endif>{{ $tickets->last()->average_time_ticket_solved_to_closed }}</td>
        <td></td>
    </tr>
</table>

@endif