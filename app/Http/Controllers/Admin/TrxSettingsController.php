<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Admin;
use App\Models\Admin\TransactionSetting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TrxSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = __("Fees & Charges");
        $admin =Admin::where('id',auth()->user()->id)->first();
        $transaction_charges = TransactionSetting::where('slug','virtual_card_'.auth()->user()->name_api)->first();
        if(!($transaction_charges))
        {
            //echo "un monde de fou";
            $transaction_charges = TransactionSetting::whereIn('slug',['transfer-money','withdraw_card_'.auth()->user()->name_api,'reload_card_'.auth()->user()->name_api,'gift_card','virtual_card'])->get();
        }else{
            
            $transaction_charges = TransactionSetting::where('slug','virtual_card_'.$admin->name_api)->orWhereIn('slug',['transfer-money','withdraw_card_'.auth()->user()->name_api,'reload_card_'.auth()->user()->name_api,'gift_card'])->get();
        }
        //dd($transaction_charges);
        
        $slug ='virtual_card_'.$admin->name_api;
        //dd($transaction_charges);
        return view('admin.sections.trx-settings.index',compact(
            'page_title',
            'transaction_charges'
        ));
    }

    /**
     * Update transaction charges
     * @param Request closer
     * @return back view
     */
    public function trxChargeUpdate(Request $request) {
        $admin =Admin::where('id',auth()->user()->id)->first();
        $slug =$request->slug;
        
        if(Str::contains($request->slug,"virtual_card")||$request->slug==='reload_card')
        {
            $transaction_setting = TransactionSetting::where('slug',$request->slug.'_'.$admin->name_api)->first();
            if(!$transaction_setting&&($slug=="virtual_card"||$slug=="reload_card")){
                $slug=$request->slug.'_'.$admin->name_api;
            }
            
        }
       //dd($slug);
        $validator = Validator::make($request->all(),[
            'slug'                              => 'required|string',
            $request->slug.'_fixed_charge'      => 'required|numeric',
            $request->slug.'_percent_charge'    => 'required|numeric',
            $request->slug.'_fixed_month_charge'      => 'numeric',
            $request->slug.'_fixed_final_charge'    => 'numeric',
            $request->slug.'_min_limit'         => 'required|numeric',
            $request->slug.'_max_limit'         => 'required|numeric',
            $request->slug.'_daily_limit'       => 'sometimes|required|numeric',
            $request->slug.'_monthly_limit'     => 'sometimes|required|numeric',
        ]);
        $validated = $validator->validate();
        //dd($validated);
        $transaction_setting = TransactionSetting::where('slug',$slug)->first();
        $validated = replace_array_key($validated,$request->slug."_");
        if(!$transaction_setting){
           dd($slug);
           $title='';
           if($validated['slug']=="virtual_card")
           {
            $title='Virtual Card';
           }else if($validated['slug']=="reload_card")
           {
            $title='Card Reload';
           }else{
            $title='Gift Card';
           }
           $title.=' Charges';
            $transactions =new TransactionSetting();
            $transactions->slug=$slug;
            $transactions->title=$title;
            $transactions->fixed_charge=$validated['fixed_charge'];
            $transactions->percent_charge=$validated['percent_charge'];
            if(isset($validated['fixed_month_charge']))
            $transactions->fixed_month_charge=$validated['fixed_month_charge'];
            if(isset($validated['fixed_final_charge']))
            $transactions->fixed_final_charge=$validated['fixed_final_charge'];
            $transactions->min_limit=$validated['min_limit'];
            $transactions->max_limit=$validated['max_limit'];
            if(isset($validated['daily_limit']))
            $transactions->daily_limit=$validated['daily_limit'];
            if(isset($validated['monthly_limit']))
            $transactions->monthly_limit=$validated['monthly_limit'];
            $transactions->admin_id=$admin->id;
            //dd($transactions);
            $transactions->save();
            $transaction_setting = TransactionSetting::where('slug',$slug)->first();

        }else{
            try{
                $transaction_setting->update($validated);
            }catch(Exception $e) {
                return back()->with(['error' => [__('Something went wrong! Please try again')]]);
            }
    
            
        }
        return back()->with(['success' => [__('Charge Updated Successfully!')]]);
        //else return back()->with(['error' => ['Transaction charge not found!']]);
        

        

    }
    public function trxChargeEversendUpdate(Request $request) {
        $validator = Validator::make($request->all(),[
            'slug'                              => 'required|string',
            $request->slug.'_fixed_charge'      => 'required|numeric',
            $request->slug.'_percent_charge'    => 'required|numeric',
            $request->slug.'_fixed_month_charge'      => 'required|numeric',
            $request->slug.'_percent_month_charge'    => 'required|numeric',
            $request->slug.'_min_limit'         => 'required|numeric',
            $request->slug.'_max_limit'         => 'required|numeric',
            $request->slug.'_daily_limit'       => 'sometimes|required|numeric',
            $request->slug.'_monthly_limit'     => 'sometimes|required|numeric',
            'month_charge'=> 'required|numeric',
        ]);
        $validated = $validator->validate();

        $transaction_setting = TransactionSetting::where('slug',$request->slug)->first();

        if(!$transaction_setting) return back()->with(['error' => ['Transaction charge not found!']]);
        $validated = replace_array_key($validated,$request->slug."_");

        try{
            $transaction_setting->update($validated);
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }

        return back()->with(['success' => [__('Charge Updated Successfully!')]]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
