<?php

namespace App\Helpers;

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
}
