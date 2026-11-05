<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\User;

/**
 * Restaurant business logic (Module 2): tenant registration, profile
 * management, admin approval lifecycle and the availability control
 * unique feature (OPEN/CLOSED).
 *
 * Tenant isolation: every method that touches tenant data receives the
 * restaurant id from the authenticated session (controllers pass it) —
 * never from the browser.
 */
class RestaurantService
{
    /**
     * Owner registers their restaurant. Creates the tenant with
     * status=pending, links the owner account to it, stores the images.
     */
    public static function registerForOwner(array $data, array $files, int $ownerId): bool
    {
        if (Restaurant::findByOwner($ownerId) !== null) {
            flash('error', 'You already have a restaurant.');
            return false;
        }

        $logo = UploadService::store($files['logo'] ?? [], 'restaurants');
        if (!$logo['ok']) {
            return false;
        }

        $cover = UploadService::store($files['cover_image'] ?? [], 'restaurants');
        if (!$cover['ok']) {
            return false;
        }

        $id = Restaurant::create(
            $data + [
                'slug'        => self::uniqueSlug($data['name']),
                'logo'        => $logo['path'],
                'cover_image' => $cover['path'],
            ],
            $ownerId
        );

        // Tenant link: the owner now belongs to this restaurant.
        User::setTenant($ownerId, $id);

        activity('register_restaurant', 'restaurant', $id);

        return true;
    }

    /** Owner updates their restaurant profile (optional new images). */
    public static function updateProfile(int $restaurantId, array $data, array $files): bool
    {
        $current = Restaurant::find($restaurantId);
        if ($current === null) {
            flash('error', 'Restaurant not found.');
            return false;
        }

        $logo = UploadService::store($files['logo'] ?? [], 'restaurants');
        if (!$logo['ok']) {
            return false;
        }

        $cover = UploadService::store($files['cover_image'] ?? [], 'restaurants');
        if (!$cover['ok']) {
            return false;
        }

        // No new file submitted? Keep the current image.
        Restaurant::update(
            $restaurantId,
            $data,
            $logo['path'] ?? $current['logo'],
            $cover['path'] ?? $current['cover_image']
        );

        activity('update_restaurant', 'restaurant', $restaurantId);

        return true;
    }

    /**
     * ★ UNIQUE FEATURE — Restaurant Availability Control.
     * Flips the restaurant between OPEN and CLOSED. While CLOSED the
     * public profile stays visible, but new orders are blocked (Module 5
     * re-checks is_open during checkout).
     */
    public static function toggleOpen(int $restaurantId): bool
    {
        $restaurant = Restaurant::find($restaurantId);
        if ($restaurant === null) {
            flash('error', 'Restaurant not found.');
            return false;
        }

        $open = (int) $restaurant['is_open'] !== 1;
        Restaurant::setOpen($restaurantId, $open);

        activity($open ? 'restaurant_open' : 'restaurant_closed', 'restaurant', $restaurantId);

        return true;
    }

    /**
     * Admin approval lifecycle. Deactivated restaurants are archived
     * (soft state), never physically deleted — their order history and
     * subscription records must survive.
     */
    public static function setApproval(int $restaurantId, string $status): bool
    {
        if (!in_array($status, ['approved', 'deactivated'], true)) {
            flash('error', 'Invalid status.');
            return false;
        }

        $restaurant = Restaurant::find($restaurantId);
        if ($restaurant === null) {
            flash('error', 'Restaurant not found.');
            return false;
        }

        Restaurant::setStatus($restaurantId, $status);

        activity(
            $status === 'approved' ? 'approve_restaurant' : 'deactivate_restaurant',
            'restaurant',
            $restaurantId
        );

        return true;
    }

    /** URL-friendly, unique slug generated once at registration. */
    private static function uniqueSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
        if ($slug === '') {
            $slug = 'restaurant';
        }

        $base = $slug;
        $i = 2;
        while (Restaurant::findBySlug($slug) !== null) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
