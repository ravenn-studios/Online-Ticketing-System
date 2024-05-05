
<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes(['verify' => true]);

// Route::get('/', 'HomeController@index')->name('home');

// Route::get('/home', 'HomeController@index')->name('home');

Route::get('/test_query', 'TestController@test_query');

//access through web and logged in
Route::group(['middleware' => ['web','auth']], function() {

    Route::get('/', 'UserController@index');
    
    Route::get('/channels/email', 'EmailSupportAddressController@index');
    Route::get('/channels/facebook', 'FacebookController@index');
    Route::post('createSignature', 'UserController@createSignature');
    Route::post('getSignatureDetails', 'UserController@getSignatureDetails');
    Route::post('updateSignature', 'UserController@updateSignature');
    Route::post('deleteSignature', 'UserController@deleteSignature');

    Route::get('/test_syncFacebookConversations', 'TestController@test_syncFacebookConversations');
    // Route::get('/ebay/oauth', 'TestController@oauth');

    Route::get('/users', 'UserController@index');
    Route::get('/user/settings', 'UserController@settings');
    Route::get('/user/reminders', 'UserController@reminders');
    Route::get('/users/schedules', 'UserController@schedules');
    Route::get('/tickets/{status?}', 'TicketController@index');
    Route::get('/tickets/unassigned/{status?}', 'TicketController@index');
    Route::get('/tickets/custom/{slug?}', 'TicketController@customPage');
    Route::get('/tickets/tag/{slug}', 'TicketController@pageTag');
    Route::get('/tickets/category/{slug}', 'TicketController@pageCategory');
    Route::get('/my-agent-tickets', 'TicketController@agentTickets');
    // Route::get('/tickets/custom/{page?}',['as'=>'custom-view','uses'=>'TicketController@customPage']);
    Route::get('/email-templates', 'EmailTemplateController@index');
    // Route::get('/channels/email', 'EmailSupportAddressController@index')->middleware('auth');
    Route::get('/tickets/my-tickets', 'TicketController@myTickets')->name('tickets.myTickets');
    Route::get('/tickets/sent', 'TicketController@sent')->name('tickets.mySentTickets');
    Route::get('/chat/facebook', 'FacebookController@chat');
    Route::get('/chats', 'ChatController@index');
    Route::get('/users/ratings', 'UserController@ratings');
    Route::get('/dashboard', 'DashboardController@index');
    Route::get('/facebook/sync-conversations', 'FacebookController@syncConversations');
    Route::get('/test_sync_tickets', 'TestController@test_sync_tickets');
    Route::get('/dropzone', 'DropzoneController@index');
    Route::post('/dropzone/upload', 'DropzoneController@upload')->name('dropzone.upload');
    Route::get('/activity/logs', 'ActivityLogController@index');
    Route::get('users/export/', 'UserController@export');
    // Route::get('export/agent_performance/{userId?}', 'ExportController@agent_performance');
    Route::get('export/agent_performance', 'ExportController@agent_performance');
    Route::get('/export', 'ExportController@export_agents_view');
    Route::post('ajaxGetUserAgents', 'AjaxController@ajaxGetUserAgents');
    Route::post('view_agent_performance', 'ExportController@view_agent_performance');
    Route::post('view_agents_performance', 'ExportController@view_agents_performance');
    Route::post('view_categorized_report', 'ExportController@view_categorized_report');
    Route::get('agents_performance_summary', 'ExportController@agents_performance_summary');
    Route::post('ajaxExportViewReport', 'ExportController@exportViewReport');
    Route::get('export_categorized_report', 'ExportController@export_categorized_report');
    Route::get('agentTicketsPaginated', 'ExportController@agentTicketsPaginated');
    Route::get('ajaxDownloadReportByFilename', 'ExportController@download_report_by_filename');
    Route::get('/ebay/oauth', 'EbayController@oauth');
    Route::get('/ebay/connect', 'EbayController@connect');
    Route::get('/ebay/refreshTokens', 'EbayController@refreshTokens');
    Route::get('/spam/filters', 'SpamFilterController@index');
    
});

Route::get('ebay-verification', function(Request $request) {

    if ( isset( $_GET['challenge_code'] ) )
    {
        $hash = hash_init('sha256');

        hash_update($hash, $_GET['challenge_code']);
        hash_update($hash, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        hash_update($hash, 'https://ots.blackedgedigital.com/ebay-verification');

        $responseHash = hash_final($hash);
        return response()->json(['challengeResponse' => $responseHash])
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET')->setStatusCode(200);
    }

});


Route::get('/test', 'TestController@test_query')->name('change.password');

Route::get('/channels/facebook/oauth', 'FacebookController@oauth');
Route::get('/webhook/verify', 'WebhookController@verify');
Route::post('ajaxUpdateRequesterEmail', 'AjaxController@ajaxUpdateRequesterEmail');
Route::post('ajaxLinkOrderNumber', 'AjaxController@ajaxLinkOrderNumber');
Route::post('ajaxUnlinkOrderNumber', 'AjaxController@ajaxUnlinkOrderNumber');
Route::post('ajaxGetAgentTicketsByView', 'AjaxController@ajaxGetAgentTicketsByView');
Route::post('ajaxGetAgentTickets', 'AjaxController@ajaxGetAgentTickets');
Route::post('ajaxSearchTickets', 'AjaxController@ajaxSearchTickets');
Route::post('ajaxSearchByTags', 'AjaxController@ajaxSearchByTags');
Route::post('ajaxSearchByCategories', 'AjaxController@ajaxSearchByCategories');
Route::post('ajaxSetTicketToRead', 'AjaxController@ajaxSetTicketToRead');
Route::post('ajaxSetDistributeTickets', 'AjaxController@ajaxSetDistributeTickets');
Route::post('ajaxFetchData', 'AjaxController@ajaxFetchData');
Route::post('ajaxBulkDeleteTemplates', 'AjaxController@ajaxBulkDeleteTemplates');
Route::post('ajaxBulkUpdateTickets', 'AjaxController@ajaxBulkUpdateTickets');
Route::post('ajaxGetFacebookPageInfo', 'AjaxController@ajaxGetFacebookPageInfo');
Route::post('ajaxSearchFacebookConversations', 'AjaxController@ajaxSearchFacebookConversations');
Route::post('ajaxUpdateFacebookConversationTicketStatus', 'AjaxController@ajaxUpdateFacebookConversationTicketStatus');
Route::post('ajaxSyncFacebookConversation', 'AjaxController@ajaxSyncFacebookConversation');
Route::post('ajaxSendFacebookMessage', 'AjaxController@ajaxSendFacebookMessage');
Route::post('ajaxGetFacebookMessages', 'AjaxController@ajaxGetFacebookMessages');
Route::post('ajaxUpdateUserInfo', 'AjaxController@ajaxUpdateUserInfo');
Route::post('ajaxRefreshSignatureListing', 'AjaxController@ajaxRefreshSignatureListing');
Route::post('ajaxRefreshCustomPagesListing', 'AjaxController@ajaxRefreshCustomPagesListing');
Route::post('ajaxDeleteCustomPage', 'AjaxController@ajaxDeleteCustomPage');
Route::post('ajaxUpdatePageConditions', 'AjaxController@ajaxUpdatePageConditions');
Route::post('ajaxGetCustomPageData', 'AjaxController@ajaxGetCustomPageData');
Route::post('ajaxPostPageConditions', 'AjaxController@ajaxPostPageConditions');
Route::post('ajaxGetColumnDetails', 'AjaxController@ajaxGetColumnDetails');
Route::post('ajaxGetMessages', 'AjaxController@ajaxGetMessages');
Route::post('ajaxSendMessage', 'AjaxController@ajaxSendMessage');
Route::post('ajaxUpdateTicket', 'AjaxController@ajaxUpdateTicket');
Route::post('ajaxGetTicketStatus', 'AjaxController@ajaxGetTicketStatus');
Route::post('ajaxRefreshTicketListing', 'AjaxController@ajaxRefreshTicketListing');
Route::post('ajaxRefreshTemplatesListing', 'AjaxController@ajaxRefreshTemplatesListing');
Route::post('ajaxFilterTicket', 'AjaxController@ajaxFilterTicket');
Route::post('ajaxCreateEmailTemplate', 'AjaxController@ajaxCreateEmailTemplate');
Route::post('ajaxGetEmailTemplate', 'AjaxController@ajaxGetEmailTemplate');
Route::post('ajaxSubmitUpdateEmailTemplate', 'AjaxController@ajaxSubmitUpdateEmailTemplate');
Route::post('ajaxDeleteEmailTemplate', 'AjaxController@ajaxDeleteEmailTemplate');
Route::post('ajaxGetTemplateData', 'AjaxController@ajaxGetTemplateData');
Route::post('ajaxAssignTicket', 'AjaxController@ajaxAssignTicket');
Route::post('ajaxRegister', 'AjaxController@ajaxRegister');
Route::post('ajaxRefreshUsertable', 'AjaxController@ajaxRefreshUsertable');
Route::post('ajaxGetUserDetails', 'AjaxController@ajaxGetUserDetails');
Route::post('ajaxUpdateUser', 'AjaxController@ajaxUpdateUser');
Route::post('ajaxDeleteUser', 'AjaxController@ajaxDeleteUser');
Route::post('ajaxAssignTicketTo', 'AjaxController@ajaxAssignTicketTo');
Route::post('ajaxGetCustomVariables', 'AjaxController@ajaxGetCustomVariables');
Route::post('ajaxAddEmailSupport', 'AjaxController@ajaxAddEmailSupport')->name('ajaxAddEmailSupport');
Route::post('ajaxDeleteEmailSupport', 'AjaxController@ajaxDeleteEmailSupport');
Route::post('ajaxRefreshEmailSupportListing', 'AjaxController@ajaxRefreshEmailSupportListing');
Route::post('ajaxStoreSessionEmailSupportId', 'AjaxController@ajaxStoreSessionEmailSupportId');
Route::post('ajaxSendComposedMessage', 'AjaxController@ajaxSendComposedMessage');

// ChatController create ticket
// Route::get('chats/generateTicketFromChat', 'ChatController@generateTicketFromChat')->name('chats.generateTicketFromChat')->middleware('cors');

Route::post('ajaxAuthUserAvailableToChat', 'AjaxController@ajaxAuthUserAvailableToChat');
Route::post('ajaxGetAgentChatLogs', 'AjaxController@ajaxGetAgentChatLogs');
Route::post('ajaxEndChat', 'AjaxController@ajaxEndChat');
Route::post('ajaxUpdateUserChatAvailability', 'AjaxController@ajaxUpdateUserChatAvailability');
Route::post('ajaxGetAuthuser', 'AjaxController@ajaxGetAuthuser');
Route::post('ajaxStartChat', 'AjaxController@ajaxStartChat');
Route::post('ajaxGetUnreadMessagesCount', 'AjaxController@ajaxGetUnreadMessagesCount');
Route::post('ajaxSeenMessages', 'AjaxController@ajaxSeenMessages');
Route::post('ajaxFilterChats', 'AjaxController@ajaxFilterChats');
Route::post('ajaxUpdateChatStatus', 'AjaxController@ajaxUpdateChatStatus');
Route::post('ajaxSearchChats', 'AjaxController@ajaxSearchChats');
Route::post('ajaxGetChatStatus', 'AjaxController@ajaxGetChatStatus');
Route::post('ajaxSendChatMessage', 'AjaxController@ajaxSendChatMessage');
Route::post('ajaxGetChatConversations', 'AjaxController@ajaxGetChatConversations');
Route::post('ajaxGetChatMessages', 'AjaxController@ajaxGetChatMessages');
Route::post('ajaxSaveTags', 'AjaxController@ajaxSaveTags');
Route::post('ajaxSaveCategories', 'AjaxController@ajaxSaveCategories');
Route::post('ajaxRequestTicket', 'AjaxController@ajaxRequestTicket');
Route::post('ajaxRenderNotifications', 'AjaxController@ajaxRenderNotifications');
Route::post('ajaxGetTicketRequestData', 'AjaxController@ajaxGetTicketRequestData');
Route::post('ajaxActionTicketRequest', 'AjaxController@ajaxActionTicketRequest');
Route::post('ajaxUpdateUserTicketLimit', 'AjaxController@ajaxUpdateUserTicketLimit');
Route::post('ajaxRefreshUsersTicketLimitListing', 'AjaxController@ajaxRefreshUsersTicketLimitListing');
Route::post('ajaxCheckReminders', 'AjaxController@ajaxCheckReminders');
Route::post('ajaxCreateReminder', 'AjaxController@ajaxCreateReminder');
Route::post('ajaxDeleteReminder', 'AjaxController@ajaxDeleteReminder');
Route::post('ajaxGetReminderDetails', 'AjaxController@ajaxGetReminderDetails');
Route::post('ajaxUpdateReminder', 'AjaxController@ajaxUpdateReminder');
Route::post('getAuthUserUnreadReminders', 'AjaxController@getAuthUserUnreadReminders');
Route::post('ajaxReadReminder', 'AjaxController@ajaxReadReminder');
Route::post('ajaxCheckTicketsCounts', 'AjaxController@ajaxCheckTicketsCounts');
Route::post('ajaxUpdatePassword', 'AjaxController@ajaxUpdatePassword');
Route::post('updateUserSchedule', 'AjaxController@updateUserSchedule');
Route::get('refreshCsrfToken', 'AjaxController@refreshCsrfToken');
Route::post('ajaxPreviewTicket', 'AjaxController@ajaxPreviewTicket');
Route::post('ajaxMarkSpamTickets', 'AjaxController@ajaxMarkSpamTickets');
Route::post('ajaxRemoveAsSpam', 'AjaxController@ajaxRemoveAsSpam');
Route::post('ajaxGetTicketsStatistics', 'AjaxController@ajaxGetTicketsStatistics');
Route::post('ajaxGetTicketDetails', 'AjaxController@ajaxGetTicketDetails');