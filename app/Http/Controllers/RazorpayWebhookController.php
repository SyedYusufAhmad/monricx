<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\PaymentWebhookEvent;
use App\Services\OrderPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RazorpayWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaymentGateway $gateway,
        OrderPaymentService $paymentService,
    ): JsonResponse {
        $rawPayload = $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature');
        $eventId = (string) $request->header('X-Razorpay-Event-Id');
        $secret = (string) config('services.razorpay.webhook_secret');

        if ($rawPayload === '' || $signature === '' || $eventId === '' || $secret === '') {
            return response()->json(['accepted' => false], 400);
        }

        try {
            $gateway->verifyWebhookSignature($rawPayload, $signature, $secret);
            $payload = json_decode($rawPayload, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            Log::warning('Rejected Razorpay webhook.', ['exception' => $exception]);

            return response()->json(['accepted' => false], 400);
        }

        if (! is_array($payload) || ! is_string($payload['event'] ?? null)) {
            return response()->json(['accepted' => false], 400);
        }

        $event = PaymentWebhookEvent::query()->firstOrCreate(
            ['event_id' => $eventId],
            [
                'provider' => 'razorpay',
                'event_type' => $payload['event'],
                'signature' => $signature,
                'payload' => $payload,
            ]
        );

        if ($event->processed_at !== null) {
            return response()->json(['accepted' => true]);
        }

        try {
            if (in_array($payload['event'], [
                'payment.authorized',
                'payment.captured',
                'payment.failed',
                'order.paid',
            ], true)) {
                $providerPayment = data_get($payload, 'payload.payment.entity');

                if (! is_array($providerPayment)) {
                    throw new \UnexpectedValueException('Webhook does not contain a payment entity.');
                }

                $paymentService->processWebhookPayment($providerPayment);
            }

            $event->forceFill(['processed_at' => now(), 'processing_error' => null])->save();
        } catch (Throwable $exception) {
            $event->forceFill(['processing_error' => $exception->getMessage()])->save();
            Log::error('Razorpay webhook processing failed.', [
                'webhook_event_id' => $event->id,
                'exception' => $exception,
            ]);

            return response()->json(['accepted' => false], 500);
        }

        return response()->json(['accepted' => true]);
    }
}
