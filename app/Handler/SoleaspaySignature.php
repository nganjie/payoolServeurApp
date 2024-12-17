<?php

namespace App\Handler;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Exceptions\InvalidWebhookSignature;
use Spatie\WebhookClient\Exceptions\WebhookFailed;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;

class SoleaspaySignature implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        //Log::info(['data'=>'receive']);
       //if(isset(response(en)))
        return true;
        $signature = $request->header($config->signatureHeaderName);
        /*if (!$signature) {
            return false;
        }*/
        $signingSecret = $config->signingSecret;
        /*if (empty($signingSecret)) {
            throw new InvalidWebhookSignature();
        }*/
        $computedSignature = hash_hmac('sha512', $request->getContent(), $signingSecret);
        /*$dat=['signame'=>$config->signatureHeaderName,'content'=>$request->getContent(),'signature'=>$signature,'computeSignature'=>$computedSignature];
        $d=json_encode($dat);
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/eversend.log'),
          ])->info($d);*/
        return hash_equals($signature, $computedSignature);
    }
}