<?php
   
namespace App\Http\Controllers;
   
use App\Http\Requests;
use Illuminate\Http\Request;
use App\File;
   
class DropzoneController extends Controller
{
   
    /**
     * Generate Image upload View
     *
     * @return void
     */
    public function index()
    {
        return view('dropzone.index');
    }
    
    /**
     * Image Upload Code
     *
     * @return void
     */
    public function upload(Request $request)
    {
        $file = $request->file('file');
   
        $randString = random_bytes(6);
        $randString = bin2hex($randString);
        $fileName   = $randString.'.'.$file->extension();
        // $file->move(public_path('images'),$fileName);

        $createFile = File::create([
                    'name'      => $fileName,
                    'extension' => $file->extension(),
                    'path'      => storage_path('public/attachments/'.$fileName),
                ]);

        $file->storeAs('public/attachments/', $fileName);
   
        return response()->json(['success' => true, 'filename' => $fileName]);
    }
   
}