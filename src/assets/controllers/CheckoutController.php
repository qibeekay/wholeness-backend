<?php

namespace App\Controllers;

use App\Config\Database;
use App\Helpers\AuthGuard;
use App\Helpers\Utils;
use App\Models\Store;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class CheckoutController
{
    private Database $db;
    private Store $store;

    public function __construct(Database $db, Store $store)
    {
        $this->db = $db;
        $this->store = $store;
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    }

    /* ----------------------------------------------------------
       STEP 1 – create PaymentIntent
    ---------------------------------------------------------- */
    public function createIntent(): string
    {
        // AuthGuard::guardBearer();
        AuthGuard::guardUser();



        // if (!isset($_SESSION['user'])) {
        //     return Utils::sendErrorResponse('User not logged in', 401);
        // }

        $input = (array) json_decode(file_get_contents('php://input'), true);
        $items = $input['items'] ?? [];

        if (!$items) {
            return Utils::sendErrorResponse('Cart is empty', 422);
        }

        [$products, $total] = Utils::validateCart($items, $this->store);

        // 1. persist pending order
        $orderId = $this->db->query(
            "INSERT INTO orders (user_id, total_amount, currency, status, created_at)
             VALUES (:uid, :total, 'eur', 'PENDING', NOW())",
            ['uid' => $_SESSION['user']['id'], 'total' => $total]
        )->lastInsertId();

        foreach ($products as $p) {
            $this->db->query(
                "INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                 VALUES (:oid, :pid, :qty, :price)",
                ['oid' => $orderId, 'pid' => $p['id'], 'qty' => $p['qty'], 'price' => $p['price']]
            );
        }

        // 2. create Stripe PaymentIntent
        $intent = PaymentIntent::create([
            'amount' => $total * 100, // in cents
            'currency' => 'eur',
            'metadata' => ['order_id' => $orderId],
        ]);

        // 3. store Stripe payment_intent_id
        $this->db->query(
            "UPDATE orders SET stripe_payment_intent_id = :pi WHERE id = :id",
            ['pi' => $intent->id, 'id' => $orderId]
        );

        return Utils::sendSuccessResponse('Intent created', [
            'clientSecret' => $intent->client_secret,
            'publishableKey' => $_ENV['STRIPE_PUBLISHABLE_KEY']
        ]);
    }

    /* ----------------------------------------------------------
       STEP 2 – confirm & capture
    ---------------------------------------------------------- */
    public function confirmIntent(): string
    {
        // AuthGuard::guardBearer();
        AuthGuard::guardUser();

        $body = (array) json_decode(file_get_contents('php://input'), true);
        $paymentIntentId = $body['paymentIntentId'] ?? '';

        if (!$paymentIntentId) {
            return Utils::sendErrorResponse('Missing paymentIntentId', 422);
        }

        $intent = PaymentIntent::retrieve($paymentIntentId);

        if ($intent->status !== 'succeeded') {
            return Utils::sendErrorResponse('Payment not succeeded', 400);
        }

        $orderId = (int) $intent->metadata->order_id;
        $order = $this->db->query(
            "SELECT * FROM orders WHERE id = :id AND status = 'PENDING'",
            ['id' => $orderId]
        )->find();
        if (!$order) {
            return Utils::sendErrorResponse('Order mismatch', 400);
        }

        // stock check again
        $items = $this->db->query(
            "SELECT product_id, quantity FROM order_items WHERE order_id = :id",
            ['id' => $orderId]
        )->getAll();
        foreach ($items as $it) {
            $prod = $this->store->find($it['product_id']);
            if ($prod['quantity'] < $it['quantity']) {
                return Utils::sendErrorResponse('Product out of stock', 409);
            }
        }

        // atomic update
        $this->db->beginTransaction();
        try {
            $this->db->query(
                "UPDATE orders SET status = 'PAID' WHERE id = :id",
                ['id' => $orderId]
            );

            foreach ($items as $it) {
                $this->db->query(
                    "UPDATE products SET quantity = quantity - :qty WHERE id = :pid",
                    ['qty' => $it['quantity'], 'pid' => $it['product_id']]
                );
            }

            $this->db->query(
                "INSERT INTO payments
                 (order_id, provider, provider_txn_id, amount, currency, status, raw_response, created_at)
                 VALUES
                 (:oid, 'stripe', :txnid, :amt, 'eur', 'CAPTURED', :raw, NOW())",
                [
                    'oid' => $orderId,
                    'txnid' => $intent->id,
                    'amt' => $intent->amount / 100,
                    'raw' => json_encode($intent)
                ]
            );

            $this->db->commit();
            return Utils::sendSuccessResponse('Payment confirmed', ['orderId' => $orderId]);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return Utils::sendErrorResponse('Capture failed', 500);
        }
    }

}