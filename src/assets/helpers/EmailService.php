<?php

namespace App\Helpers;

class EmailService
{
    private $apiUrl;
    private $bearerToken;

    public function __construct($apiUrl, $bearerToken)
    {
        $this->apiUrl = $apiUrl;
        $this->bearerToken = $bearerToken;
    }

    /**
     * Send a payment-receipt e-mail.
     *
     * @param string $recipientEmail
     * @param array  $order           Order row from DB (contains total_amount, currency, status…)
     * @param array  $items           Array of line items (each with name, qty, unit_price)
     * @param bool   $success         true = payment succeeded, false = payment failed/cancelled
     * @return array                  ['success' => bool, 'message' => string]
     */
    public function sendPaymentReceipt(string $recipientEmail, array $order, array $items, bool $success): array
    {
        $subject = $success
            ? 'Payment confirmed – Quantum Leap Sports'
            : 'Payment failed – Quantum Leap Sports';

        $bodyLines = [];
        $bodyLines[] = $success
            ? 'Thank you for your purchase!'
            : 'We could not process your payment.';

        $bodyLines[] = '';
        $bodyLines[] = 'Order #' . $order['id'] . ' – ' . strtoupper($order['currency']) . ' ' . number_format($order['total_amount'], 2);
        $bodyLines[] = str_repeat('-', 40);

        foreach ($items as $i) {
            $bodyLines[] = sprintf('%s  x%d  @ %.2f', $i['name'], $i['quantity'], $i['unit_price']);
        }

        $bodyLines[] = str_repeat('-', 40);
        $bodyLines[] = 'Total: ' . strtoupper($order['currency']) . ' ' . number_format($order['total_amount'], 2);
        $bodyLines[] = '';
        $bodyLines[] = $success
            ? 'Your card has been charged. You will receive a shipping confirmation shortly.'
            : 'No money has been charged. Please try again or use a different payment method.';

        // Convert body lines to a single string
        $body = implode("\n", $bodyLines);

        $data = [
            'email'   => $recipientEmail,
            'subject' => $subject, // Use the dynamic subject
            'body'    => $body,    // Use the dynamic body
            'html'    => false     // Changed to false since we're using plain text formatting
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->bearerToken
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $resp = curl_exec($ch);
        if (curl_errno($ch)) {
            return ['success' => false, 'message' => 'cURL: ' . curl_error($ch)];
        }

        // $payload = json_encode($data);
        // error_log('[MAIL] Payload: ' . $payload);

        // $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // error_log('[MAIL] HTTP status: ' . $httpStatus);
        // error_log('[MAIL] Raw response: ' . $resp);
        // curl_close($ch);

        $decoded = json_decode($resp, true);
        if (($decoded['success'] ?? false) === true) {
            return ['success' => true,  'message' => 'Receipt e-mail queued'];
        }

        return [
            'success' => false,
            'message' => $decoded['message'] ?? $resp ?: 'Empty response from mail service'
        ];
    }
}
