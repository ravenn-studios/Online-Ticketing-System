@php
$tmpUsersCategorizedReportData = $usersCategorizedReportData;
$tmpUsers                      = array_column($tmpUsersCategorizedReportData, 'name');

array_shift($tmpUsersCategorizedReportData);
// dd($usersCategorizedReportData);
@endphp


<table class="table-responsive">

	<thead>
		<tr>
			<th>
				{{ \Carbon\Carbon::parse($dateRange[0])->format('F d, y') }} - {{ \Carbon\Carbon::parse($dateRange[1])->format('F d, y') }}
			</th>
		</tr>
		<tr>
			<th></th>
		</tr>
	</thead>
	
	<tbody>

		@foreach($usersCategorizedReportData as $key => $data)

	        @if ($loop->first)

	        	<tr>
        			<td></td>
        			<td></td>
	        		@foreach($tmpUsers as $tmpKey => $user)
                        <td style="font-weight: 600;">{{ $user }}</td>
                    @endforeach 
	        	</tr>

	        	@foreach($data['categories'] as $_key => $_data)

					@php
						$parentCategory = (isset($_data[0])) ? $_data[0] : '';
					@endphp

					@if(in_array($parentCategory['category_id'], $parentCategoriesId))

						<tr>
							<td style="font-weight: 600;">{{ $parentCategory['category_name'] }}</td>
							<td></td>
						</tr>

						@foreach($_data as $__key => $category)

							{{-- default key = 0 is the parent category which should be displayed as collapse header only --}}
							@if($__key != 0)

	                         	<tr>
	                         		<td>{{ $category['category_name'] }}</td>
	                         		<td></td>

		                         	<td>

		                              @php
		                                if ( $parentCategory['category_tickets_count'] != 0 )
		                                {
		                                  $percentageFromParent = floatval(number_format( ($category['category_tickets_count'] / $parentCategory['category_tickets_count']) * 100, 2)) .'%';
		                                }
		                                else
		                                {
		                                  $category['category_tickets_count'] = 0;
		                                  $percentageFromParent               = '0%';
		                                }
		                              @endphp

		                              {{ $category['category_tickets_count'] . ' of ' . $parentCategory['category_tickets_count'] .' tickets (' . $percentageFromParent . ')' }}

		                            </td>

		                            	{{-- loop here the other users data... and make it display as table --}}
										@foreach($tmpUsersCategorizedReportData as $tmpKey => $val)

										@php
											$tmpParentCategory = [];
											$tmpParentCategory = (isset($tmpUsersCategorizedReportData[$key]['categories'][$_key][0])) ? $tmpUsersCategorizedReportData[$key]['categories'][$_key][0] : '';
										@endphp

										<td>

											@php

											if ( $tmpParentCategory['category_tickets_count'] != 0 )
											{
												$percentageFromParent = floatval(number_format( ($tmpUsersCategorizedReportData[$key]['categories'][$_key][$__key]['category_tickets_count'] / $tmpParentCategory['category_tickets_count']) * 100, 2)) .'%';
											}
											else
											{
												$tmpUsersCategorizedReportData[$key]['categories'][$_key][$__key]['category_tickets_count'] = 0;
												$percentageFromParent               = '0%';
											}

											@endphp

											{{ $tmpUsersCategorizedReportData[$key]['categories'][$_key][$__key]['category_tickets_count'] . ' of ' . $tmpParentCategory['category_tickets_count'] .' tickets (' . $percentageFromParent . ')' }}

										</td>

										@endforeach

	                         	</tr>

							@endif

						@endforeach

						<tr></tr>

					@endif

				@endforeach

	        @endif

	    @endforeach
		
	</tbody>

</table>


{{-- @if( empty($usersCategorizedReportData) )

    <table class="table-responsive">
        <tr>
            <td>No Records Found.</td>
        </tr>
    </table>

@else

	<table class="table-responsive">

		<tbody>

			<tr>
				<td>
					{{ \Carbon\Carbon::parse($dateRange[0])->format('F d, y') }} - {{ \Carbon\Carbon::parse($dateRange[1])->format('F d, y') }}
				</td>
			</tr>

			@foreach($usersCategorizedReportData as $key => $data)

				@php
					$parentCategory = (isset($data[0])) ? $data[0] : '';
				@endphp

				@foreach( $data as $_key => $category)

					@if(in_array($parentCategory['category_id'], $parentCategoryIds))

						@if($_key != 0)

							<tr>

								<td>
									{{ $category['category_name'] }}
								</td>

								<td style="text-align: center;">
									@php

								        if ( $parentCategory['category_tickets_count'] != 0 )
								        {
								          $percentageFromParent = number_format( ($category['category_tickets_count'] / $parentCategory['category_tickets_count']) * 100, 2) .'%';
								        }
								        else
								        {
								          $category['category_tickets_count'] = 0;
								          $percentageFromParent               = '0%';
								        }
								    @endphp

							      	{{ $category['category_tickets_count'] . ' of ' . $parentCategory['category_tickets_count'] .' tickets (' . $percentageFromParent . ')' }}
								</td>

							</tr>

						@else
							<tr></tr>
							<tr>
								<td style="font-weight: bold;">
									{{ $category['category_name'] }}
								</td>
							</tr>

						@endif

					@endif

				@endforeach

			@endforeach

		</tbody>

	</table>

@endif --}}