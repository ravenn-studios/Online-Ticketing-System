<?php

// header('Access-Control-Allow-Origin: *');

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/chat/send-message', function(Request $request) {
    
    return event(new \App\Events\CustomerMessaged($request->name, $request->email, $request->message, $request->type));
    // event(new App\Events\CustomerMessaged('john doe', 'john_doe@johndoe.com', 'Hello World123'));
    // return "Event has been sent!";  

});

Route::post('/chat/receive-unread-message-count', function(Request $request) {

    return event(new \App\Events\ReceiveUnreadMessageCount($request->unreadMessageCount));
    // event(new App\Events\CustomerMessaged('john doe', 'john_doe@johndoe.com', 'Hello World123'));
    // return "Event has been sent!";  

});

Route::post('/chat/customer-updated-information', function(Request $request) {

    return event(new \App\Events\CustomerUpdatedInformation($request->customer_id, $request->name, $request->email));

});

Route::post('/chat/customer-ended-chat', function(Request $request) {

    return event(new \App\Events\CustomerEndedChat($request->chat_id));

});

Route::post('/chat/agent-no-response', function(Request $request) {

    return event(new \App\Events\AgentNoResponse($request->chat_id));

});

Route::get('/get-available-agents', function(Request $request) {

    $users = \App\User::where('is_online', true)->get();
    
    // return !empty( $users->count() );
    return response()->json(['status' => !empty( $users->count()) ]);

});

Route::post('/chat/rate-chat', function(Request $request) {

    return event(new \App\Events\RateChat($request->chat_id, $request->rating, $request->remarks));

});

Route::post('/chat/send-file', function(Request $request) {

    // return $request->all();
    
    $imageUrl = $request->file('upload');

    //get image contents
    $randAlphanumeric = random_bytes(8);
    $randAlphanumeric = bin2hex($randAlphanumeric);
    //get image file ext.
    $_imageUrl = explode('?', $imageUrl);
    $ext = '.'.$imageUrl->getClientOriginalExtension();
    //set new image filename and store to storage images
    $name = 'chat'.$randAlphanumeric;
    $fileName = $name.$ext;
    
    \Storage::put('public/images/'.$fileName, $imageUrl->get());
    
    $file = \App\File::create([
        'name'      => $name,
        'extension' => $ext,
        'path'      => storage_path('public/images/'.$fileName),
        // 'path'      => $image,
    ]);

    
    $_image   = base64_encode('<img src="data:image/jpeg;charset=utf-8;base64,'.base64_encode(\Storage::get('public/images/'.$fileName)).'" />');

    return event(new \App\Events\CustomerMessaged($request->name, $request->email, $_image, $request->type, $file->id));

    // return response()->json($_image);


})->name('api.chat.send-file');