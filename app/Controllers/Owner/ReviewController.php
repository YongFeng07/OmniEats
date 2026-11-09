<?php

namespace App\Controllers\Owner;

use App\Models\Review;
use App\Services\AuthService;

/**
 * Review management — restaurant owner/staff (Payment & Review
 * module, Student 3). Staff can VIEW reviews of their own restaurant
 * and REPLY to them; hiding is an admin moderation action.
 */
class ReviewController
{
    private function tenantId(): ?int
    {
        $restaurantId = AuthService::user()['restaurant_id'] ?? null;
        return $restaurantId !== null ? (int) $restaurantId : null;
    }

    public function index(): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        view('owner/reviews/index', [
            'title'   => 'Customer Reviews',
            'reviews' => Review::allForRestaurant($restaurantId),
        ]);
    }

    /** Reply to a review of the OWN restaurant (update allowed). */
    public function reply(string $id): void
    {
        $restaurantId = $this->tenantId();
        if ($restaurantId === null) {
            redirect('/owner');
        }

        $review = Review::find((int) $id);
        if ($review === null || (int) $review['restaurant_id'] !== $restaurantId) {
            flash('error', 'Review not found.');
            redirect('/owner/reviews');
        }

        $reply = trim((string) ($_POST['reply'] ?? ''));
        if ($reply === '') {
            flash('error', 'Please write a reply before submitting.');
            redirect('/owner/reviews');
        }
        if (mb_strlen($reply) > 500) {
            flash('error', 'The reply must not exceed 500 characters.');
            redirect('/owner/reviews');
        }

        Review::setReply((int) $id, $reply, (int) AuthService::user()['id']);
        activity('review_reply', 'review', (int) $id);

        flash('success', 'Reply published — the customer sees it on the order page.');
        redirect('/owner/reviews');
    }
}
