@if( empty($usersTicketsSummaryData) )

    <table class="table-responsive">
        <tr>
            <td>No Records Found.</td>
        </tr>
    </table>

@else

@php
	$dateColumns = array_keys($usersTicketsSummaryData[0]['tickets_count']);
	$colspan     = count($dateColumns) + 1;
@endphp
<table class="table-responsive">
    <thead>

    	<tr>
    		<td colspan="{{ $colspan }}" style="text-align: center; font-weight: bold; font-family: 'Calibri';">For the month of {{ \Carbon\Carbon::parse($dateRange[0])->format('F') }}</td>
    	</tr>

    	<tr>
    		<td colspan="{{ $colspan }}" style="text-align: center; font-weight: bold; font-family: 'Calibri';">{{ \Carbon\Carbon::parse($dateRange[0])->format('F d') }} - {{ \Carbon\Carbon::parse($dateRange[1])->format('F d') }}</td>
    	</tr>
    	<tr></tr>

    </thead>

    <tbody>

    	<tr>
    		<td style="text-align: center; font-weight: bold; text-transform: uppercase; font-family: 'Calibri';">ASSIGNED TO</td>

    		@foreach($dateColumns as $dateColumn)

    			<td style="text-align: center; font-weight: bold; font-family: 'Calibri';">{{ $dateColumn }}</td>

    		@endforeach

    	</tr>

        @php
            $ticketsCountByDateIndex = $ticketsCountByDateIndexAndStatusPending = $ticketsCountByDateIndexAndStatusSolved = $ticketsCountByDateIndexAndStatusClosed = [];
            $totalPending            = $totalSolved = $totalClosed = 0;
        @endphp

    	@foreach($usersTicketsSummaryData as $key => $data)

	    	<tr>
	    		<td colspan="{{ $colspan }}" style="text-align: left; background: orange; font-family: 'Arial'; font-size: 10px;">{{ $data['name'] }}</td>
	    	</tr>

    		@php
                $ticketsCountData['pending'] = array_column($data['tickets_count'], 'tickets_pending');
                $ticketsCountData['solved']  = array_column($data['tickets_count'], 'tickets_solved');
                $ticketsCountData['closed']  = array_column($data['tickets_count'], 'tickets_closed');
    		@endphp

    		@foreach($ticketsCountData as $_key => $_data)
    			<tr>
    				<td style="text-align: left; font-family: 'Arial'; font-size: 10px;">{{ ucfirst($_key) }}</td>

    				@foreach($_data as $__key => $ticketCount)

    					<td style="text-align: center; font-family: 'Arial'; font-size: 10px;">{{ empty($ticketCount) ? '-' : $ticketCount }}</td>

                        @php
                            $ticketsCountByDateIndex[$__key][] = $ticketCount;

                            if( !empty($ticketCount) && $_key == 'pending' )
                            {
                                $totalPending += $ticketCount;
                                $ticketsCountByDateIndexAndStatusPending[$__key][] = $ticketCount;
                            }
                            elseif( $_key == 'pending' )
                            {
                                $ticketsCountByDateIndexAndStatusPending[$__key][] = $ticketCount;
                            }

                            if( !empty($ticketCount) && $_key == 'solved' )
                            {
                                $totalSolved += $ticketCount;
                                $ticketsCountByDateIndexAndStatusSolved[$__key][] = $ticketCount;
                            }
                            elseif( $_key == 'solved' )
                            {
                                $ticketsCountByDateIndexAndStatusSolved[$__key][] = $ticketCount;
                            }

                            if( !empty($ticketCount) && $_key == 'closed' )
                            {
                                $totalClosed += $ticketCount;
                                $ticketsCountByDateIndexAndStatusClosed[$__key][] = $ticketCount;
                            }
                            elseif( $_key == 'closed' )
                            {
                                $ticketsCountByDateIndexAndStatusClosed[$__key][] = $ticketCount;
                            }

                        @endphp

		    		@endforeach

    			</tr>
    		@endforeach

    	@endforeach

            {{-- <tr></tr>

            <tr>
                
                <td style="text-align: left; font-family: 'Arial'; font-size: 10px;">Total</td>

                @foreach($ticketsCountByDateIndex as $subTotal)

                    @php
                        $sum = array_sum($subTotal);
                    @endphp

                    <td style="text-align: center; font-family: 'Arial'; font-size: 10px;">{{ empty($sum) ? '-' : $sum }}</td>

                @endforeach

            </tr> --}}

            <tr></tr>

            <tr>
                
                <td style="text-align: left; font-family: 'Arial'; font-size: 10px;">Pending</td>

                @foreach($ticketsCountByDateIndexAndStatusPending as $_subTotal)

                    @php
                        $sum = array_sum($_subTotal);
                    @endphp

                    <td style="text-align: center; font-family: 'Arial'; font-size: 10px;">{{ empty($sum) ? '-' : $sum }}</td>

                @endforeach

            </tr>

            <tr>
                
                <td style="text-align: left; font-family: 'Arial'; font-size: 10px;">Solved</td>

                @foreach($ticketsCountByDateIndexAndStatusSolved as $_subTotal)

                    @php
                        $sum = array_sum($_subTotal);
                    @endphp

                    <td style="text-align: center; font-family: 'Arial'; font-size: 10px;">{{ empty($sum) ? '-' : $sum }}</td>

                @endforeach

            </tr>

            <tr>
                
                <td style="text-align: left; font-family: 'Arial'; font-size: 10px;">Closed</td>

                @foreach($ticketsCountByDateIndexAndStatusClosed as $_subTotal)

                    @php
                        $sum = array_sum($_subTotal);
                    @endphp

                    <td style="text-align: center; font-family: 'Arial'; font-size: 10px;">{{ empty($sum) ? '-' : $sum }}</td>

                @endforeach

            </tr>

            <tr></tr>

            <tr>
                <td style="text-align: left; font-family: 'Arial'; font-size: 10px;">TOTAL PENDING</td>

                <td style="text-align: center; font-family: 'Arial'; font-size: 10px;">{{ $totalPending }}</td>
            </tr>

            <tr>
                <td style="text-align: left; font-family: 'Arial'; font-size: 10px;">TOTAL SOLVED</td>

                <td style="text-align: center; font-family: 'Arial'; font-size: 10px;">{{ $totalSolved }}</td>
            </tr>

            <tr>
                <td style="text-align: left; font-family: 'Arial'; font-size: 10px;">TOTAL CLOSED</td>

                <td style="text-align: center; font-family: 'Arial'; font-size: 10px;">{{ $totalClosed }}</td>
            </tr>

            <tr></tr>

            <tr>
                <td style="text-align: left; font-family: 'Arial'; font-size: 10px;">GRAND TOTAL</td>

                <td style="text-align: center; font-family: 'Arial'; font-size: 10px;">{{ $totalPending + $totalSolved +$totalClosed }}</td>
            </tr>

    </tbody>
</table>

@endif