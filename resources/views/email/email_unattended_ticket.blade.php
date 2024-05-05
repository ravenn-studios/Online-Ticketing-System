<table border="0" cellpadding="0" cellspacing="0" class="body" style="background-color:#eff4fa;width:100%">
  <tbody>
    <tr>
      <td>&nbsp;</td>'
      <td class="container" style="display:block;margin:0 auto!important;max-width:580px;padding:10px;width:580px">
        <div class="content" style="box-sizing:border-box;display:block;margin:0 auto;max-width:580px;padding:10px;min-height: 350px;">
          <table class="main" style="background:#fff;border-radius:3px;width:100%">
            <tbody>
              <tr>
                <td class="wrapper" style="box-sizing:border-box;padding:20px">
                  <table border="0" cellpadding="0" cellspacing="0">
                    <tbody>
                      <tr>
                        <td>
                          <p style="color: #7a7a7a;"> {{ $last_message_created_at }}</p>
                          <p style="margin-bottom: 40px;">
                            <a href="{{ $ticket_view_url }}" target="_blank" style="text-decoration: none;">Ticket #{{ $ticket_id }}</a>
                          </p>
                          <p>{!! $content !!}</p>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </td>
    </tr>
  </tbody>
</table>