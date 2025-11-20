<?php

namespace App\Controllers;

use App\Models\Events;
use App\Helpers\Utils;
use App\Helpers\FileUpload;
use App\Helpers\AuthGuard;

class EventController
{
    private Events $event;

    public function __construct(Events $event)
    {
        $this->event = $event;
    }

    /* ---------- admin CRUD ---------- */
    public function fetchAll(): string
    {
        AuthGuard::guardBearer();           // admin only
        return Utils::sendSuccessResponse('Events fetched', $this->event->all());
    }

    public function fetchSingle(int $id): string
    {
        AuthGuard::guardBearer();
        $e = $this->event->find($id);
        return $e ? Utils::sendSuccessResponse('Event fetched', $e)
            : Utils::sendErrorResponse('Event not found', 404);
    }

    public function create(array $input, array $files = []): string
    {
        AuthGuard::guardBearer();

        [$data, $err] = Utils::validate($input, [
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'event_date'  => 'required|date',
            'event_time'  => 'required|date_format:H:i',
            'venue'       => 'required|string|max:255',
            'capacity'    => 'required|integer|min:1',
            'category'    => 'required|in:workshop,support,training,social',
            'price'       => 'string|max:50',
        ]);
        if ($err) return Utils::sendErrorResponse('Validation: ' . implode(', ', $err), 422);

        $data['image'] = '';
        if (!empty($files['image']['tmp_name'])) {
            $data['image'] = FileUpload::store($files['image']);
        }

        $id = $this->event->create($data);
        return Utils::sendSuccessResponse('Event created', ['id' => $id], 201);
    }

    public function update(int $id, array $input, array $files = []): string
    {
        AuthGuard::guardBearer();
        if (!$this->event->find($id)) {
            return Utils::sendErrorResponse('Event not found', 404);
        }

        [$data, $err] = Utils::validate($input, [
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'event_date'  => 'sometimes|date',
            'event_time'  => 'sometimes|date_format:H:i',
            'venue'       => 'sometimes|string|max:255',
            'capacity'    => 'sometimes|integer|min:1',
            'category'    => 'sometimes|in:workshop,support,training,social',
            'price'       => 'sometimes|string|max:50',
        ]);
        if ($err) return Utils::sendErrorResponse('Validation: ' . implode(', ', $err), 422);

        if (!empty($files['image']['tmp_name'])) {
            $data['image'] = FileUpload::store($files['image']);
        }

        $ok = $this->event->update($id, $data);
        return $ok ? Utils::sendSuccessResponse('Event updated')
            : Utils::sendErrorResponse('Nothing changed', 422);
    }

    public function delete(int $id): string
    {
        AuthGuard::guardBearer();
        if (!$this->event->find($id)) {
            return Utils::sendErrorResponse('Event not found', 404);
        }
        $this->event->delete($id);
        return Utils::sendSuccessResponse('Event deleted');
    }

    /* ---------- user facing ---------- */
    public function listPublic(): string
    {
        // no auth required
        return Utils::sendSuccessResponse('Events', $this->event->all());
    }

    public function showPublic(int $id): string
    {
        $e = $this->event->find($id);
        return $e ? Utils::sendSuccessResponse('Event', $e)
            : Utils::sendErrorResponse('Not found', 404);
    }

    /* ---------- booking ---------- */
    /* ---------- booking ---------- */
    public function book(int $id, array $input): string
    {
        AuthGuard::guardBearer();          // still require a valid token
        // $uid = AuthGuard::userId();     // OLD
        $uid = (int) ($input['user_id'] ?? 0);
        if ($uid <= 0) {
            return Utils::sendErrorResponse('user_id is required and must be positive', 422);
        }

        $ok = $this->event->book($id, $uid);
        return $ok ? Utils::sendSuccessResponse('Booking confirmed')
            : Utils::sendErrorResponse('Fully booked or already booked', 422);
    }

    public function cancel(int $id, array $input): string  // <── added 2-nd param
    {
        AuthGuard::guardBearer();
        // $uid = AuthGuard::userId();     // OLD
        $uid = (int) ($input['user_id'] ?? 0);
        if ($uid <= 0) {
            return Utils::sendErrorResponse('user_id is required and must be positive', 422);
        }

        $ok = $this->event->cancelBooking($id, $uid);
        return $ok ? Utils::sendSuccessResponse('Booking cancelled')
            : Utils::sendErrorResponse('Not found', 404);
    }

    /* ---------- admin attendee management ---------- */
    public function removeAttendee(int $id, int $userId): string
    {
        AuthGuard::guardBearer();
        $ok = $this->event->removeAttendee($id, $userId);
        return $ok ? Utils::sendSuccessResponse('Attendee removed')
            : Utils::sendErrorResponse('Attendee not found', 404);
    }
}
