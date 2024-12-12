<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserNotice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserNoticeController extends Controller
{
    public function index()
    {
        $page_title = __("User Notice");
        $user_notices = UserNotice::auth()->orderByDesc("id")->paginate(10);
        return view('user.sections.notice.index', compact('page_title','user_notices'));
    }

    public function showCreate(){
        $page_title = __("Add New Notice");
        return view('user.sections.notice.create', compact('page_title'));
    }
    public function showUpdate(UserNotice $user_notice){
        $page_title = __("Update  User Notice");
        //dd($user_notice);
        return view('user.sections.notice.create', compact('page_title','user_notice'));
    }
    public function update(Request $request){
        $validator = Validator::make($request->all(),[
            'name'     => "required|string|max:100",
            'rating'     => "required|string|min:0|max:5",
            'details'   => "required|string",
            'user_notice_id'=>"required|integer"
        ]);
        $validated = $validator->validate();
        try{
            $user_notice=UserNotice::find($request->user_notice_id);
            //dd($user_notice);
            $user_notice->update($request->except(['user_notice_id']));
            $user_notice->save();
            $page_title = __("Update  User Notice");
            //return redirect()->route('user.notice.update',$user_notice)->with(['success' => [__('User Notice updated successfully!')]]);
           return back()->with(['success' => [__('User Notice updated successfully!')]]);
        }catch(Exception $e){
            dd($e);
                    return back()->with(['error' => [__("Something Went Wrong! Please Try Again")]]);
                }
    }
    public function store(Request $request){
        $validator = Validator::make($request->all(),[
            'name'     => "required|string|max:100",
            'rating'     => "required|string|min:0|max:5",
            'details'   => "required|string",
        ]);
        $validated = $validator->validate();
        $validated['designation']="User";
        $validated['image']=auth()->user()->userImage;
        //if(!auth()->user()->image)
        //dd($validated);
        $user =User::Where('id',auth()->user()->id)->first();
        $notice= new UserNotice($validated);
        $user->userNotice()->save($notice);
        return redirect()->route('user.notice.index')->with(['success' => [__('User Notice created successfully!')]]);

    }

}
