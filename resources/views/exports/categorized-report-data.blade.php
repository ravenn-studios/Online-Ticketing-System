<div class="accordion-1">

  <div> {{-- class="container" --}}
    <div class="row">

      @php
        $tmpUsersCategorizedReportData = $usersCategorizedReportData;
        $tmpUsers = array_column($tmpUsersCategorizedReportData, 'name');
        // dump($tmpUsersCategorizedReportData);

        array_shift($tmpUsersCategorizedReportData);
        // dd($tmpUsersCategorizedReportData);
        // dump($tmpUsersCategorizedReportData[0]['categories'][0][1]);
      @endphp

      @foreach($usersCategorizedReportData as $key => $data)

        @if ($loop->first)
        <div class="col-md-10 mx-auto mb-4">

          {{-- <h5 class="float-left">{{ $data['name'] }}</h5> --}}

          <div class="row">
            <div class="col excluded-categories-wrapper"></div>
            <div class="col">
              <span class="btn btn-sm btn-primary float-right reset-report-sub-categories display-none">Reset</span>
            </div>
          </div>

          {{-- <br class="clearfix"> --}}
          <input type="hidden" class="categorized-report-user-id" value="{{ $data['id'] }}">

          <div class="accordion my-3" id="accordionExample">

            @foreach($data['categories'] as $_key => $_data)

              @php
                $parentCategory = (isset($_data[0])) ? $_data[0] : '';
                $collapseId = $data['id'] . $_key;
                // dump($key);
                // dump($_key);
              @endphp

              <div class="card" style="position: relative;">
                <span class="btn-exclude" title="exclude in report" data-action="exclude">EXCLUDE</span>
                <div class="card-header p-0" id="headingOne">
                  <h5 class="mb-0">
                    <button class="btn btn-link w-100 text-primary text-left" type="button" data-toggle="collapse" data-target="#collapse{{ $collapseId }}" aria-expanded="true" aria-controls="collapseOne">
                      {{ $parentCategory['category_name'] }}
                      <i class="ni ni-bold-down float-right"></i>
                    </button>
                    <input type="hidden" class="categorized_report_parent_category_id" value="{{ $parentCategory['category_id'] }}">
                  </h5>
                </div>

                {{-- <div id="collapse{{ $collapseId }}" class="collapse {{ ($loop->index == 0) ? 'show' : '' }}" aria-labelledby="headingOne" data-parent="#accordionExample"> --}}
                <div id="collapse{{ $collapseId }}" class="collapse multi-collapse show" aria-labelledby="headingOne" data-parent="#accordionExample">
                  <div class="card-body opacity-8">
                    
                    @foreach($_data as $__key => $category)

                      {{-- default key = 0 is the parent category which should be displayed as collapse header only --}}
                      @if($__key != 0) 

                        <div class="row">
                          <div class="col"></div>
                          @if($__key == 1)
                            @foreach($tmpUsers as $user)
                              <div class="col">
                                <p class="text-right small" style="font-weight: 600;">{{ $user }}</p>
                              </div>
                            @endforeach 
                          @endif 
                        </div>

                        <div class="row">

                          <div class="col">
                            <p class="small">
                              {{ $category['category_name'] }}
                            </p>
                          </div>

                          <div class="col">

                            {{-- @if($__key == 1 )
                              <p class="text-right small" style="font-weight: 600;">{{ $data['name'] }}</p>
                            @endif --}}

                            <p class="text-right small" style="font-weight: 400;">
                              @php
                              // dd($__key);
                                // $percentageFromParent = ($category['category_tickets_count'] != 0) ? number_format( ($category['category_tickets_count'] / $parentCategory['category_tickets_count']) * 100, 2) .'%' : '0%';

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
                            </p>
                          </div>


                          {{-- loop here the other users data... and make it display as table --}}
                          @foreach($tmpUsersCategorizedReportData as $tmpKey => $val)

                            @php
                              // dump($data['name']);
                              // dump($val['name']);
                              // dump($key);
                              // dump($_key);
                              // dump($__key);
                              // dd($tmpUsersCategorizedReportData[$key]['categories'][$_key][0]['category_tickets_count']);
                              $tmpParentCategory = [];
                              $tmpParentCategory = (isset($tmpUsersCategorizedReportData[$key]['categories'][$_key][0])) ? $tmpUsersCategorizedReportData[$key]['categories'][$_key][0] : '';
                              // dump($tmpParentCategory);
                              // dump($tmpParentCategory['category_tickets_count']);
                            @endphp

                            <div class="col">

                              {{-- @if($__key == 1 )
                                <p class="text-right small" style="font-weight: 600;">{{ $val['name'] }}</p>
                              @endif --}}

                              <p class="text-right small" style="font-weight: 400;">
                                @php

                                  if ( $tmpParentCategory['category_tickets_count'] != 0 )
                                  {
                                    // dd($tmpParentCategory);
                                    $percentageFromParent = floatval(number_format( ($tmpUsersCategorizedReportData[$key]['categories'][$_key][$__key]['category_tickets_count'] / $tmpParentCategory['category_tickets_count']) * 100, 2)) .'%';
                                  }
                                  else
                                  {
                                    // dd($tmpUsersCategorizedReportData);
                                    // dd($tmpUsersCategorizedReportData[$key]['categories'][$_key][$__key]['category_tickets_count'] = 0);
                                    $tmpUsersCategorizedReportData[$key]['categories'][$_key][$__key]['category_tickets_count'] = 0;
                                    $percentageFromParent               = '0%';
                                  }

                                @endphp

                                {{ $tmpUsersCategorizedReportData[$key]['categories'][$_key][$__key]['category_tickets_count'] . ' of ' . $tmpParentCategory['category_tickets_count'] .' tickets (' . $percentageFromParent . ')' }}
                              </p>
                            </div>

                          @endforeach


                        </div>
                      @endif
                    @endforeach

                  </div>
                </div>
              </div>

            @endforeach

          </div>

        </div>
        @endif

      @endforeach

    </div>
  </div>
</div>