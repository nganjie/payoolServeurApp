<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Response;
use App\Models\Admin\Admin;
use App\Models\User;
use App\Models\UserNotice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserNoticeController extends Controller
{
    public function index()
    {
        $user_noticet = UserNotice::auth()->orderByDesc("id")->get();
        
        $total=count($user_noticet);
        $admin=Admin::first();
        $page_title = __("User Notice")." : ".$total."/".$admin->limit_notice;
        $user_notices = UserNotice::auth()->orderByDesc("id")->paginate(10);
        return view('user.sections.notice.index', compact('page_title','user_notices'));
    }
    public function indexAdmin()
    {
        $page_title = __("User Notice");
        $user_notices = UserNotice::orderByDesc("id")->paginate(10);
        return view('admin.sections.user-notice.index', compact('page_title','user_notices'));
    }

    public function showCreate(){
        $user_noticet = UserNotice::auth()->orderByDesc("id")->get();
        $total=count($user_noticet);
        $admin=Admin::first();
        $page_title = __("Add New Notice")." : ".$total."/".$admin->limit_notice;
        //$page_title = __("Add New Notice");
        return view('user.sections.notice.create', compact('page_title'));
    }
    public function showUpdate(UserNotice $user_notice){
        $page_title = __("Update  User Notice");
        //dd($user_notice);
        return view('user.sections.notice.create', compact('page_title','user_notice'));
    }
    public function showUpdateAdmin(UserNotice $user_notice){
        $page_title = __("Update  User Notice");
        //dd($user_notice);
        return view('admin.sections.user-notice.edit', compact('page_title','user_notice'));
    }
    public function updateLimitNotice(Request $request){
        $validator = Validator::make($request->all(),[
            'limit_notice'=>"required|integer"
        ]);
        $validated = $validator->validate();
        //dd($validated);
        
        try{
            $admin =Admin::find(auth()->user()->id);
            $admin->update($validated);
            $admin->save();
            //return redirect()->route('user.notice.update',$user_notice)->with(['success' => [__('User Notice updated successfully!')]]);
           return back()->with(['success' => [__('User Limit Notice updated successfully!')]]);
        }catch(Exception $e){
            dd($e);
                    return back()->with(['error' => [__("Something Went Wrong! Please Try Again")]]);
                }
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
        $user_notices = UserNotice::auth()->orderByDesc("id")->get();
        
        $total=count($user_notices);
        $admin=Admin::first();
        
        if($total>=$admin->limit_notice){
            //$error = ['error' => [__('Something Is Wrong In Your Card')]];
            return back()->with(['error' => [__('Please follow the notice limit')]]);
        }
        //dump($total);
        //dd($admin);
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
