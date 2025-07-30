<?php

namespace App\Controllers;

use App\Helpers\FileUpload;
use App\Models\Store;
use App\Helpers\Utils;
use App\Helpers\AuthGuard;

class ProductController
{
    private Store $store;

    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    // Get all products
    public function fetchAll(): string
    {
        AuthGuard::guard();
        return Utils::sendSuccessResponse('Product fetched successfully', $this->store->all());
    }


    // get single product by id
    public function fetchById(int $id): string
    {
        AuthGuard::guard();
        $product = $this->store->find($id);

        return $product ? Utils::sendSuccessResponse('Product fetched successfully', $product)
            : Utils::sendErrorResponse('Product not found', 404);
    }

    // create a new product
    public function store(array $input, array $files = []): string
    {
        AuthGuard::guard();

        [$data, $errors] = Utils::validate($input, [
            'name' => 'required|string|max:255',
            'description' => 'string',
            'price' => 'required|numeric|min:0',
            'inStock' => 'boolean',
            'quantity' => 'integer|min:0',
        ]);


        if ($errors) {
            return Utils::sendErrorResponse('Validation failed: ' . implode(', ', $errors), 422, );
        }

        // File upload
        $data['image'] = '';
        if (!empty($files['image']['tmp_name'])) {
            $data['image'] = FileUpload::store($files['image']);
        }

        $id = $this->store->create($data);
        return Utils::sendSuccessResponse('Product created', ['id' => $id], 201);
    }

    /* ----------  UPDATE  ---------- */
    public function update(int $id, array $input, array $files = []): string
    {
        AuthGuard::guard();

        if (!$this->store->find($id)) {
            return Utils::sendErrorResponse('Product not found', 404);
        }

        [$data, $errors] = Utils::validate($input, [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'inStock' => 'sometimes|boolean',
            'quantity' => 'sometimes|integer|min:0',
        ]);


        if ($errors) {
            return Utils::sendErrorResponse('Validation failed: ' . implode(', ', $errors), 422, );
        }

        if (!empty($files['image']['tmp_name'])) {
            $data['image'] = FileUpload::store($files['image']);
        }

        if (!$this->store->find($id)) {
            return Utils::sendErrorResponse('Product not found', 404);
        }

        $updated = $this->store->update($id, $data);

        if (!$updated) {
            // decide: empty payload vs. identical values
            return Utils::sendErrorResponse('No fields provided or values unchanged', 422);
        }

        return Utils::sendSuccessResponse('Product updated');
    }

    /* ----------  DELETE  ---------- */
    public function destroy(int $id): string
    {
        AuthGuard::guard();
        if (!$this->store->find($id)) {
            return Utils::sendErrorResponse('Product not found', 404);
        }

        $deleted = $this->store->delete($id);
        return $deleted
            ? Utils::sendSuccessResponse('Product deleted')
            : Utils::sendErrorResponse('Delete failed', 500);
    }
}