<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    // Use effectiveUser() to support "view as" feature
    $effectiveUser = effectiveUser();

    return $effectiveUser && (int) $effectiveUser->id === (int) $id;
});

Broadcast::channel('session.{userId}', function ($user, $userId) {
    // Use effectiveUser() to support "view as" feature
    $effectiveUser = effectiveUser();

    return $effectiveUser && (int) $effectiveUser->id === (int) $userId;
});
