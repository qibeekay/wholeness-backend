<?php

namespace App\Controllers;

use App\Helpers\FileUpload;
use App\Models\Blog;
use App\Helpers\Utils;
use App\Helpers\AuthGuard;
use Google\Service\MyBusinessQA\Author;

class BlogController
{
    private Blog $blog;

    public function __construct(Blog $blog)
    {
        $this->blog = $blog;
    }

    // get all blogs
    public function fetchAll(): string
    {
        AuthGuard::guardBearer();
        return Utils::sendSuccessResponse('Blogs fetched successfully', $this->blog->all());
    }

    // fetch blog by excerpts
    public function fetchByExcept(string $excerpt): string
    {
        AuthGuard::guardBearer();
        $detail = $this->blog->findByExcerpt($excerpt);

        return $detail ? Utils::sendSuccessResponse('Blog fetched successfully', $detail)
            : Utils::sendErrorResponse('Blog not found', 404);
    }

    // create a new blog
    public function blog(array $input, array $files = []): string
    {
        AuthGuard::guardBearer();

        [$data, $errors] = Utils::validate($input, [
            'title' => 'required|string|max:255',
            'excerpt' => 'required|string|max:255',
            'content' => 'required|string',
            'author' => 'string|max:255',
        ]);

        if ($errors) {
            return Utils::sendErrorResponse('validation failed: ' . implode(', ', $errors), 422, );
        }

        // file upload
        $data['image'] = '';
        if (!empty($files['image']['tmp_name'])) {
            $data['image'] = FileUpload::store($files['image']);
        }

        $id = $this->blog->create($data);
        return Utils::sendSuccessResponse('Product created', ['id' => $id], 201);
    }

    // update a blog post
    public function update(int $id, array $input, array $files = []): string
    {
        AuthGuard::guardBearer();

        if (!$this->blog->find($id)) {
            return Utils::sendErrorResponse('Blog not found', 404);
        }

        [$data, $errors] = Utils::validate($input, [
            'title' => 'sometimes|string|max:255',
            'excerpt' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'author' => 'sometimes|string|max:255',
        ]);

        if ($errors) {
            return Utils::sendErrorResponse('Validation failed: ' . implode(', ', $errors), 422, );
        }

        if (!empty($files['image']['tmp_name'])) {
            $data['image'] = FileUpload::store($files['image']);
        }

        if (!$this->blog->find($id)) {
            return Utils::sendErrorResponse('Product not found', 404);
        }

        $updated = $this->blog->update($id, $data);

        if (!$updated) {
            // decide: empty payload vs. identical values
            return Utils::sendErrorResponse('No fields provided or values unchanged', 422);
        }

        return Utils::sendSuccessResponse('Blog updated');
    }

    // delete a blog
    public function destroy(int $id): string
    {
        AuthGuard::guardBearer();
        if (!$this->blog->find($id)) {
            return Utils::sendErrorResponse('Blog not found', 404);
        }

        $deleted = $this->blog->delete($id);
        return $deleted
            ? Utils::sendSuccessResponse('Blog deleted')
            : Utils::sendErrorResponse('Delete failed', 500);
    }
}