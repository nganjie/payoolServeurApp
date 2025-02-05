<?php

namespace App\Http\Controllers\Admin;

use App\Constants\LanguageConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use App\Imports\LanguageImport;
use App\Models\Admin\Language;
use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class LanguageController extends Controller
{
    public $prod=[
        "Dashboard"=>"Tableau de bord",
        "change card type"=>"changer le type de carte",
        "Rejection Reason"=>"Motif du rejet",
        "Your KYC verification request is rejected by admin"=>"Votre demande de vérification KYC est rejetée par l'administrateur",
        "KYC rejected"=>"KYC rejeté",
        "Approved At"=>"Approuvé à",
        "Thank you for using our application!"=>"Merci d'utiliser notre application !",
        "KYC Approved"=>"KYC Approuvé",
        "Your KYC verification request is approved by admin"=>"Votre demande de vérification KYC est approuvée par l'administrateur",
    "When you switch from one card type to another, only cards matching the selected type will be visible in your account"=>"Lorsque vous passez d’un type de carte à un autre, seules les cartes correspondant au type sélectionné seront visibles dans votre compte",
    "is rechargeable"=>"est rechargeable",
    "card top-up is temporarily disabled for this card type"=>"la recharge de carte est temporairement désactivé pour ce type de carte",
    "Add Card To User"=>"Ajouter une carte à un utilisateur",
    "Send Card To User"=>"Envoyer la carte à l'utilisateur",
    "Card Code"=>"Code De la Carte",
    "Api Type"=>"Type D'api",
    "Card Withdraw"=>"Retrait D'argent De La Carte",
    "Withdraw successful Money of card"=>"Retrait D'argent de la carte",
    "Withdrawed Money Successfully of the card"=>"Argent retiré avec succès de la carte",
    "Total Withdraw"=>"Total Prélever",
    " Withdraw Money card"=>"Retirer de l'argent de la carte",
    "Virtual Card Money withdraw Charges"=>"Frais de retrait d'argent de carte virtuelle",
    "Virtual Card (Withdraw Amount)"=>"Carte virtuelle (montant du retrait)",
    "Account Amount"=>"Montant du compte",
    "amount to withdraw"=>"montant à retirer",
    "New Ticket User"=>"Nouveau Ticket Utilisateur",
    "you have new ticket for User"=>"vous avez un nouveau ticket utilisateur","User Support Ticket Message"=>"Message du ticket d'assistance utilisateur",
    "Support Ticket Solved"=>"Ticket d'assistance résolu",
    "withdraw  money card"=>"retirer de l'argent par carte",
    "withdrawal of money from the card is temporarily disabled for this type of card"=>"les retrait d'argent  de la carte est temporairement désactivé pour ce type de carte",
    "User Notice updated successfully!"=>"Avis utilisateur mis à jour avec succès !",
    "Add New Notice"=>"Ajouter un nouvel avis",
    "User Notice created successfully!"=>"Avis utilisateur créé avec succès !",
    "Update  User Notice"=>"Mettre à jour l'avis utilisateur",
    "User Limit Notice updated successfully!"=>"Avis de limite d'utilisateur mis à jour avec succès !",
    "Please follow the notice limit"=>"Veuillez respecter la limit des avis",
    "leave a review"=>"Laisser Un Avis",
    "User Notice"=>"Avis Utilisateur",
    "Carte - Suspendue"=>"Carte - Suspendue",
    "Card - Inactive"=>"Carte - Inactive",
    "Virtual Card (Adjusted)"=>"Carte virtuelle (ajustée)",
    "your virtual card has been deleted"=>"votre carte virtuelle a été supprimée",
    "Virtual Card (Terminated)"=>"Carte virtuelle (terminée)",
    "Card - Deleted"=>"Carte - Supprimée",
    "Virtual Card Transaction (Payement)"=>"Transaction par carte virtuelle (paiement)","you have made a payment"=>"vous avez effectué un paiement","card Acceptor Name"=>"Nom de l'accepteur de carte","card Acceptor City"=>"Ville accepteur de cartes",
    "Virtual Card Transaction ( Payement Failed)"=>"Transaction par carte virtuelle (échec du paiement)",
    "Payment failure, insufficient balance"=>"Échec de paiement, solde insuffisant","Subscription Renewal"=>"Renouvellement d'abonnement","Virtual Card (Card Maintenance Fee Successful)"=>"Carte virtuelle (frais de maintenance de la carte réussis)","Card - Active"=>"Carte - Active","Webhook Url"=>"URL du webhook","number of failed transaction attempts"=>"nombre de tentatives de transaction ayant échoué","Price of The Penalty"=>"Prix ​​de la pénalité","Activate The Penalty"=>"Activer la pénalité",
    "number of failures"=>"Nombre D'échecs","maximum number of failures"=>"Nombre Maximum D'échecs ",
    "warning"=>"Avertissement","if you make successive attempts to make a payment error, your card will be blocked and you will have to pay a fine of"=>"si vous faite =>nbtrx tentative successive d'erreur de payement, votre carte sera bloquer et vous devrier payer une amande de =>amount USD","card blocking"=>"Blocage De Carte","Your card has been blocked, you must go to your payool account to pay a fine of to be able to unblock it"=>"Votre carte a été bloquée, vous devez vous rendre sur votre compte payool pour payer une amende de =>amount USD pour pouvoir la débloquer","Unlocking your card"=>"Déblocage de votre carte","will be charged from your wallet as a penalty to unblock your card"=>"=>amount USD sera prélevé dans votre portefeuille en guise de pénalités pour débloquer votre carte"
    ,"Please note that after unblocking, it is imperative to top up your card in order to make your payments. Without topping up, your card may be permanently deleted."=>"Veuillez noter qu’après le déblocage, il est impératif de recharger votre carte afin de pouvoir effectuer vos paiements. Sans recharge, votre carte risque d’être définitivement supprimée.","Pay the penalty"=>"Payer la pénalité","PAY-PENALITY"=>"PAIEMENT-PENALITÉ","PAY-PENALITY-VIRTUAL-CARD"=>"PAIEMENT-PENALITÉ-CARTE-VIRTUELLE","The penalty on your virtual card has been successfully paid."=>"La pénalité sur votre carte virtuelle a été payée avec succès.","Virtual Card (Card Unbloking)"=>"Carte virtuelle (Déblocage de carte)","Card Unbloking Success"=>"Succès du déblocage de la carte","narrative"=>"narratif","reason"=>"raison",
    "your card has been successfully deleted"=>"votre carte à ete supprimer avec succès","Remove card"=>"Supprimer la carte","Confirm Deletion"=>"Confirmer la Suppression","This card is already deleted from the bank. Are you sure you want to permanently remove it from your account? This action is irreversible and all data associated with this card will be deleted from your PayOol™ space."=>"Cette carte est déjà supprimée auprès de la banque. Êtes-vous sûr(e) de vouloir la retirer définitivement de votre compte ? Cette action est irréversible et toutes les données associées à cette carte seront supprimées de votre espace PayOol™.","Response Message"=>"Message de réponse","Reference"=>"Référence","Gateway Reference"=>"Référence de la passerelle","unblock"=>"Débloquer","KYC Rejected"=>"KYC Rejeté","Copy mail Users"=>"Copier le courrier des utilisateurs","Create a card for your personal needs"=>"Créez une carte pour vos besoins personnels","Personal Card"=>"Carte Personnelle","Business Card"=>"Carte Business","Create a card for your business"=>"Créer une carte pour votre activité professionnelle",
    "Previous cards will no longer be accessible unless you revert to the other type of card"=>"Les cartes précédentes ne seront plus accessibles, sauf si vous revenez à l’autre type de carte","This shift implies that"=>"Ce basculement implique que","the card purchase is temporary deactivate for this type of card"=>"l'achat de carte est temporaire desactiver pour se type de carte","Carte Premium"=>"Carte Premium","Carte Basique"=>"Carte Basique","Subscription Type"=>"Mode De Subscription","One-time Payment"=>"Paiement unique","Monthly Fee"=>"Frais mensuels","Pay a one-time fee for unlimited card use with no monthly charges"=>"Payez des frais uniques pour une utilisation illimitée de la carte sans frais mensuels","Pay a small 1 USD fee each month for ongoing card management"=>"Payez des frais minimes de 1 USD chaque mois pour la gestion continue de la carte",    
];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = __("Language Manager");
        $languages = Language::paginate(10);
        return view('admin.sections.language.index',compact(
            'page_title',
            'languages',
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name'      => 'required|string|max:80|unique:languages,name',
            'code'      => 'required|string|max:20|unique:languages,code',
            'dir'       => 'required|string|max:20|in:ltr,rtl',
        ]);

        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with("modal","language-add");
        }

        $validated = $validator->validate();

        $default = false;
        if(!Language::default()->exists()) {
            $default = true;
        }

        $validated['status']            = $default;
        $validated['last_edit_by']      = auth()->user()->id;

        try{
            Language::create($validated);
        }catch(Exception $e) {
            return back()->with(['error' => [__("Something went wrong! Please try again")]]);
        }

        return back()->with(['success' => [__("Language created successfully!")]]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'target'        => 'required|numeric|exists:languages,id',
            'edit_name'     => ["required","string","max:80",Rule::unique("languages","name")->ignore($request->target)],
            'edit_code'     => ["required","string","max:80",Rule::unique("languages","code")->ignore($request->target)],
            'edit_dir'      => ["required","string","max:20","in:ltr,rtl"],
        ]);

        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with("modal","language-edit");
        }

        $validated = $validator->validate();
        $validated = replace_array_key($validated,"edit_");
        $validated = Arr::except($validated,['target']);

        $language = Language::find($request->target);

        try{
            $language->update($validated);
        }catch(Exception $e) {
            return back()->with(['error' => [__("Something went wrong! Please try again")]]);
        }

        return back()->with(['success' => [__("Language updated successfully!")]]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $request->validate([
            'target'    => 'required|numeric|exists:languages,id',
        ]);

        $language = Language::find($request->target);
        if($language->code == LanguageConst::NOT_REMOVABLE) {
            return back()->with(['error' => ['Language ('.$language->name.') is not removable.']]);
        }

        try{
           $language->delete();
        }catch(Exception $e) {
            return back()->with(['error' => [__("Something went wrong! Please try again")]]);
        }

        // Delete File
        try{
            $file = lang_path($language->code.".json");
            delete_file($file);
        }catch(Exception $e) {
            return back()->with(['warning' => [__("File remove failed!")]]);
        }

        return back()->with(['success' => [__("Language deleted successfully!")]]);
    }


    public function statusUpdate(Request $request) {
        $validator = Validator::make($request->all(),[
            'data_target'       => 'required|numeric|exists:languages,id',
            'status'            => 'required|boolean',
        ]);

        if($validator->fails()) {
            $errors = ['error' => $validator->errors() ];
            return Response::error($errors);
        }

        $validated = $validator->validate();

        if(Language::whereNot("id",$validated['data_target'])->default()->exists()) {
            $warning = ['warning' => [__("Please deselect your default language first.")]];
            return Response::warning($warning);
        }

        $language = Language::find($validated['data_target']);

        try{
            $language->update([
                'status'        => ($validated['status']) ? false : true,
            ]);
        }catch(Exception $e) {
            $errors = ['error' => [__("Something went wrong! Please try again")] ];
            return Response::error($errors,null,500);
        }

        $success = ['success' => [__("Language status updated successfully!")]];
        return Response::success($success);
    }


    public function info($code) {

        $language = Language::where("code",$code);

        if(!$language->exists()) {
            return back()->with(['error' => [__("Sorry! Language not found!")]]);
        }

        $file = lang_path($code.".json");
        if(!is_file($file)) {
            return back()->with(['error' => [__("Something went wrong! Please try again")]]);
        }

        $data = file_get_contents($file);
        try{
            $key_value = json_decode($data,true);
        }catch(Exception $e) {
            return back()->with(['error' => [__("Something went wrong! Please try again")]]);
        }

        $language = $language->first();

        $page_title = "Language Information";
        return view('admin.sections.language.info',compact(
            'page_title',
            'key_value',
            'language',
        ));
    }


    public function import(Request $request) {

        $validator = Validator::make($request->all(),[
            'language'      => 'required|string|exists:languages,code',
            'file'          => 'required|file|mimes:csv,xlsx',
        ]);

        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with("modal","language-import");
        }

        $validated = $validator->validate();

        try{
            $sheets = (new LanguageImport)->toArray($validated['file'])->columnData()->keyValue();
        }catch(Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        //  dd(json_encode((new LanguageImport)->toArray($validated['file'])->columnData()->getArray()['Key']));
        $filter_with_database_lang = array_intersect_key($sheets,[$validated['language'] => "value"]);

        $get_predefine_keys = LanguageImport::getKeys();

        foreach($filter_with_database_lang as $code => $item) {

            $item = array_intersect_key($item,array_flip($get_predefine_keys));

            $json_format = json_encode($item);
            if($code=='fr'){
                $file_path = get_files_path('prod');
            
                
                $file_name = get_first_file_from_dir($file_path);
                $mergedArray = array_merge($item, $this->prod);
                $json_format=json_encode($mergedArray);
            }

            $file = lang_path($code.".json");
            if(is_file($file)) {
                file_put_contents($file,$json_format);
            }else {
                create_file($file);
                file_put_contents($file,$json_format);
            }
        }

        try{
            if($request->hasFile('file')) {
                $file_name = 'language-'.Carbon::parse(now())->format("Y-m-d") . "." .$validated['file']->getClientOriginalExtension();
                $file_link = get_files_path('language-file') . '/' . $file_name;
                (new Filesystem)->cleanDirectory(get_files_path('language-file'));
                File::move($validated['file'],$file_link);
            }
        }catch(Exception $e) {
            return back()->with(['warning' => [__("Failed to store new file.")]]);
        }

        return back()->with(['success' => [__("Language updated successfully!")]]);
    }


    public function switch(Request $request) {
        $code = $request->target;
        $language = Language::where("code",$code);
        if(!$language->exists()) {
            return back()->with(['error' => [__("Oops! Language not found!")]]);
        }

        Session::put('local',$code);

        return back()->with(['success' => [__("Language switch successfully!")]]);
    }


    public function download() {
        $file_path = get_files_path('language-file');
        dd($file_path);
        $file_name = get_first_file_from_dir($file_path);
        if($file_name == false) {
            return back()->with(['error' => [__("File does not exists.")]]);
        }
        $file_link = $file_path . '/' . $file_name;
        return response()->download($file_link);
    }
}
