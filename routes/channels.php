<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Retailer-specific private channels
Broadcast::channel('retailer.{id}', function ($user, $id) {
 return (int) $user->id === (int) $id && $user->role === 'retailer';
});

// Admin-only channel
Broadcast::channel('admin', function ($user) {
 return $user->role === 'admin';
});

// Admin broadcast channel (for global notifications)
Broadcast::channel('admin.{id}', function ($user, $id) {
 return (int) $user->id === (int) $id && $user->role === 'admin';
});
