<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Review;

/**
 * Customer ratings (Grab/Foodpanda-style): the owner of a COMPLETED
 * order rates the restaurant 1–5 stars with an optional comment. One
 * review per order (UNIQUE). restaurants.rating is recomputed from the
 * reviews table after every submission, so the rating shown on cards
 * is a real aggregate once reviews exist.
 */
class ReviewService
{
    public static function submit(int $orderId, int $customerId, int $rating, string $comment): bool
    {
        $order = Order::find($orderId);
        if ($order === null || (int) $order['customer_id'] !== $customerId) {
            flash('error', 'Order not found.');
            return false;
        }

        if ($order['status'] !== 'completed') {
            flash('error', 'You can only review an order after it is completed.');
            return false;
        }

        if (Review::findByOrder($orderId) !== null) {
            flash('error', 'You have already reviewed this order.');
            return false;
        }

        if ($rating < 1 || $rating > 5) {
            flash('error', 'Please choose a rating between 1 and 5 stars.');
            return false;
        }

        if (mb_strlen($comment) > 500) {
            flash('error', 'The comment must not exceed 500 characters.');
            return false;
        }

        $reviewId = Review::create(
            $orderId,
            $customerId,
            (int) $order['restaurant_id'],
            $rating,
            $comment !== '' ? $comment : null
        );

        // Recompute the restaurant's displayed rating from real reviews.
        Restaurant::setRating(
            (int) $order['restaurant_id'],
            Review::averageForRestaurant((int) $order['restaurant_id'])
        );

        activity('review_submit', 'review', $reviewId, 'rating=' . $rating);

        return true;
    }
}
