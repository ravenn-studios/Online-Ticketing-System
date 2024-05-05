<div class="row">
  <div class="col">
    <!-- Fullcalendar -->
    <div class="card card-calendar">
      <!-- Card header -->
      <div class="card-header">
        <!-- Title -->
        <h5 class="h3 mb-0">Work Week</h5> </div>
      <!-- Card body -->
      <div class="card-body p-0">
        <div class="calendar fc fc-unthemed fc-ltr" data-toggle="calendar" id="calendar">
          <div class="fc-toolbar fc-header-toolbar">
            <div class="fc-left"></div>
            <div class="fc-right"></div>
            <div class="fc-center"></div>
            <div class="fc-clear"></div>
          </div>
          <div class="fc-view-container" style="">
            <div class="fc-view fc-month-view fc-basic-view" style="">
              <table class="" style="width: 100%;">
                <thead class="fc-head">
                  <tr style="border-bottom: 1px solid #DDD; border-top: 1px solid #DDD;">
                    <td class="fc-head-container fc-widget-header">
                      <div class="fc-row fc-widget-header">
                        <table class="" style="width: 100%;">
                          <thead>
                            <tr>
                              {{-- <th class="fc-day-header fc-widget-header fc-sun"><span>Sun</span></th>
                              <th class="fc-day-header fc-widget-header fc-mon"><span>Mon</span></th>
                              <th class="fc-day-header fc-widget-header fc-tue"><span>Tue</span></th>
                              <th class="fc-day-header fc-widget-header fc-wed"><span>Wed</span></th>
                              <th class="fc-day-header fc-widget-header fc-thu"><span>Thu</span></th>
                              <th class="fc-day-header fc-widget-header fc-fri"><span>Fri</span></th>
                              <th class="fc-day-header fc-widget-header fc-sat"><span>Sat</span></th> --}}
                              @foreach( $arrDateDay as $date)

                                <th class="fc-day-header fc-widget-header" style="width: 240px;"><span>{{ $date['day'] }}</span></th>

                                {{-- <span class="badge badge-default">Default</span> --}}

                              @endforeach

                            </tr>
                          </thead>
                        </table>
                      </div>
                    </td>
                  </tr>
                </thead>
                <tbody class="fc-body">
                  <tr>
                    <td class="fc-widget-content">
                      <div class="fc-scroller fc-day-grid-container" style="overflow: hidden; height: 1287px;">
                        <div class="fc-day-grid fc-unselectable">
                          <div class="fc-row fc-week fc-widget-content" style="height: 214px;">
                            {{-- <div class="fc-bg">
                              <table class="">
                                <tbody>
                                  <tr>
                                    <td class="fc-day fc-widget-content fc-sun fc-other-month fc-past" data-date="2022-03-27"></td>
                                    <td class="fc-day fc-widget-content fc-mon fc-other-month fc-past" data-date="2022-03-28"></td>
                                    <td class="fc-day fc-widget-content fc-tue fc-other-month fc-past" data-date="2022-03-29"></td>
                                    <td class="fc-day fc-widget-content fc-wed fc-other-month fc-past" data-date="2022-03-30"></td>
                                    <td class="fc-day fc-widget-content fc-thu fc-other-month fc-past" data-date="2022-03-31"></td>
                                    <td class="fc-day fc-widget-content fc-fri fc-past" data-date="2022-04-01"></td>
                                    <td class="fc-day fc-widget-content fc-sat fc-past" data-date="2022-04-02"></td>
                                  </tr>
                                </tbody>
                              </table>
                            </div> --}}
                            <div class="fc-content-skeleton">
                              <table style="width: 100%;height: 180px;">
                                <thead style="height: 180px;">
                                  <tr>
                                    {{-- loop through reach user's schedule, then will loop through each days/date --}}
                                    
                                      @foreach( $arrDateDay as $key => $date)

                                        <td class="fc-day-top fc-sat fc-past" data-date="2022-04-02"  style="vertical-align: top; width: 240px; @if( $key <= 5 ) border-right: 1px solid #DDD; @endif">
                                          <span class="fc-day-number float-left">

                                          @foreach( $usersSchedule as $userSchedule )

                                                @if ( $userSchedule->{strtolower($date['day'])} )

                                                  <span class="badge badge-primary user-schedule-badge" data-day="{{ strtolower($date['day']) }}" data-user-schedule-id="{{ $userSchedule->id }}" data-work-day="true" title="{{ $userSchedule->user->name }}" style="cursor: pointer;">{{ $userSchedule->user->name }}</span></br>
                                                  
                                                @else
                                                
                                                  <span class="badge badge-secondary user-schedule-badge" data-day="{{ strtolower($date['day']) }}" data-user-schedule-id="{{ $userSchedule->id }}" data-work-day="false" title="{{ $userSchedule->user->name }}" style="cursor: pointer;">{{ $userSchedule->user->name }} - Rest Day</span></br>

                                                @endif

                                          @endforeach

                                          </span>
                                        </td>

                                    @endforeach

                                  </tr>
                                </thead>
                              </table>
                            </div>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal - Edit event -->
    <!--* Modal body *-->
    <!--* Modal footer *-->
    <!--* Modal init *-->
    <div class="modal fade" id="update-user-schedule-modal" tabindex="-1" role="dialog" aria-labelledby="edit-event-label" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-secondary" role="document">
        <div class="modal-content">
          <!-- Modal body -->
          <div class="modal-body">
              <div class="form-group">
                <label class="form-control-label">User</label>
                <input type="text" class="form-control form-control-alternative name" placeholder="Agent" readonly>
              </div>

              <div class="custom-control custom-radio mb-3 work-day-toggle">
                <input type="radio" id="customRadio1" name="customRadio" class="custom-control-input work-day">
                <label class="custom-control-label" for="customRadio1">Regular Day</label>
              </div>
              <div class="custom-control custom-radio">
                <input type="radio" id="customRadio2" name="customRadio" class="custom-control-input rest-day">
                <label class="custom-control-label" for="customRadio2">Rest Day</label>
              </div>

          </div>
          <!-- Modal footer -->
          <div class="modal-footer">
            <button class="btn btn-primary btn-update-user-schedule" data-calendar="update">Update</button>
            {{-- <button class="btn btn-danger" data-calendar="delete">Delete</button> --}}
            <button class="btn btn-link ml-auto" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>