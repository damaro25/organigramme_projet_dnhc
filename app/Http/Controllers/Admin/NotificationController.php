<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Notifications de la plateforme (étapes cochées sur mes projets). Elles sont marquées comme lues à l'affichage. */
class NotificationController extends Controller
{
    public function __invoke(Request $request): View
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20);
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return view('admin.notifications', compact('notifications'));
    }
}
