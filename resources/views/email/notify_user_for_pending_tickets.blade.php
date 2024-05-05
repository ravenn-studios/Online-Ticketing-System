<!DOCTYPE html>
<html>
<head>
	<title>{{ $reminder->title }}</title>
</head>
<body>
   
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <!-- START HEADER/BANNER -->
  <tbody>
    <tr>
      <td align="center">
        <table class="col-600" width="600" border="0" align="center" cellpadding="0" cellspacing="0" style="border-left: 1px solid #dbd9d9; border-right: 1px solid #dbd9d9; border-top: 1px solid #dbd9d9;">
          <tbody>
            <tr>
              <td align="center">
                <!-- <img src="https://phplaravel-370483-1521810.cloudwaysapps.com/images/black-edge-logo.png"> -->
                <img src="{{ asset('/images/black-edge-logo.png') }}" width="250px">
              </td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
    <!-- END HEADER/BANNER -->
    <!-- START WHAT WE DO -->
    <tr>
      <td align="center">
        <table class="col-600" width="600" border="0" align="center" cellpadding="0" cellspacing="0" style="margin-left:20px; margin-right:20px;">
          <tbody>
            <tr>
              <td align="center">
                <table class="col-600" width="600" border="0" align="center" cellpadding="0" cellspacing="0" style=" border-left: 1px solid #dbd9d9; border-right: 1px solid #dbd9d9; border-bottom: 1px solid #dbd9d9; padding-bottom: 40px;">
                  <tbody>
                    <tr>
                      <td height="50"></td>
                    </tr>
                    <tr>
                      <td align="center">
                          <table class="insider" style="width: 100%; padding: 0px 20px;">
                            <tbody>
                              <tr align="left">
                                <td style="font-family: 'Raleway', sans-serif; font-size:23px; color:#2a3b4c; line-height:30px; font-weight: bold;">{{ $reminder->title }}</td>
                              </tr>
                              <tr>
                                <td height="5"></td>
                              </tr>
                              <tr>
                                <td style="font-family: 'Lato', sans-serif; font-size:14px; color:#7f8c8d; line-height:24px; font-weight: 300;"> {{ $reminder->description }} <a href="{{ $ticketsViewUrl }}" target="_blank" style="text-decoration: none;">View Tickets</a> </td>
                              </tr>
                            </tbody>
                          </table>
                        </td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
            <!-- END WHAT WE DO -->
            <!-- START FOOTER -->
          </tbody>
        </table>
      </td>
    </tr>
    <!-- END FOOTER -->
  </tbody>
</table>

</body>
</html>