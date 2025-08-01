<?php

namespace App\Helpers;

use App\Models\Store;

class Utils
{
    /**
     * Send JSON success response
     *
     * @param string $message The success message
     * @param array $data Additional data to include in the response
     * @param int $statusCode HTTP status code (default: 200)
     * @return string JSON-encoded response
     */
    public static function sendSuccessResponse(string $message, object|array $data = [], int $statusCode = 200): string
    {
        http_response_code($statusCode);
        return json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Send JSON error response
     *
     * @param string $message The error message
     * @param int $statusCode HTTP status code
     * @return string JSON-encoded response
     */
    public static function sendErrorResponse(string $message, int $statusCode): string
    {
        http_response_code($statusCode);
        return json_encode([
            'status' => 'error',
            'message' => $message
        ]);
    }

    /* … existing sendSuccess / sendError methods … */

    /**
     * Sanitize & validate an associative array against simple rules.
     *
     * @param array $input
     * @param array $rules   ['name' => 'required|string|max:255', …]
     * @return array [sanitizedData, errors]
     */

    public static function validate(array $input, array $rules): array
    {
        $errors = [];
        $clean = [];

        foreach ($rules as $field => $ruleString) {
            $value = $input[$field] ?? null;

            // rule parts
            $parts = explode('|', $ruleString);
            $first = array_shift($parts);

            // required check
            if ($first === 'required' && empty($value)) {
                $errors[$field] = "$field is required";
                continue;
            }
            if ($first === 'sometimes' && ($value === null || $value === '')) {
                continue; // skip optional empty fields
            }

            // cast / sanitize
            switch (true) {
                case in_array('string', $parts):
                    $value = trim((string) $value);
                    break;
                case in_array('numeric', $parts):
                    $value = (float) $value;
                    break;
                case in_array('integer', $parts):
                    $value = (int) $value;
                    break;
                case in_array('boolean', $parts):
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
            }

            // constraints
            foreach ($parts as $p) {
                if (preg_match('/^max:(\d+)$/', $p, $m) && strlen($value) > $m[1]) {
                    $errors[$field] = "$field must be ≤ {$m[1]} chars";
                }
                if (preg_match('/^min:(\d+(?:\.\d+)?)$/', $p, $m) && $value < $m[1]) {
                    $errors[$field] = "$field must be ≥ {$m[1]}";
                }
            }

            $clean[$field] = $value;
        }

        return [$clean, $errors];
    }

    /**
     * Validate cart items and calculate total.
     *
     * @param array $items Array of cart items, each with 'id' and 'qty'
     * @param Store $store Store model instance to fetch product data
     * @return array [products, total] Array of validated products and total price
     * @throws \Exception If validation fails, exits with error response
     */
    public static function validateCart(array $items, Store $store): array
    {
        $products = [];
        $total = 0.0;

        foreach ($items as $it) {
            $id = (int) ($it['id'] ?? 0);
            $qty = (int) ($it['qty'] ?? 0);

            /** @var array|false $prod */
            $prod = $store->find($id);
            if (!$prod) {
                echo self::sendErrorResponse("Product $id not found", 404);
                exit;
            }
            if ($qty <= 0 || $qty > $prod['quantity']) {
                echo self::sendErrorResponse("Invalid quantity for {$prod['name']}", 422);
                exit;
            }

            $products[] = [
                'id' => $prod['id'],
                'name' => $prod['name'],
                'price' => $prod['price'],
                'quantity' => $prod['quantity'],
                'qty' => $qty,
            ];
            $total += $prod['price'] * $qty;
        }

        return [$products, $total];
    }
}
