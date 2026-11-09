<?php

namespace App\Controllers\Admin;

use App\Models\Review;
use App\Services\AuthService;

/**
 * Review moderation — Super Admin (Payment & Review module,
 * Student 3). Hide/unhide inappropriate reviews; the moderation
 * action itself is recorded on the review row.
 */
class ReviewController
{
    public function index(): void
    {
        view('admin/reviews/index', [
            'title'   => 'Review Moderation',
            'reviews' => Review::allWithRestaurants(),
        ]);
    }

    /** Hide / unhide (review DELETE = soft hide, records preserved). */
    public function toggle(string $id): void
    {
        $review = Review::find((int) $id);
        if ($review === null) {
            abort(404);
        }

        $hidden = (int) $review['is_hidden'] !== 1;
        Review::setHidden((int) $id, $hidden, (int) AuthService::user()['id']);
        activity($hidden ? 'review_hide' : 'review_unhide', 'review', (int) $id);

        flash('success', $hidden
            ? 'Review hidden — it no longer appears on the public restaurant page.'
            : 'Review restored to public view.');
        redirect('/admin/reviews');
    }
}
