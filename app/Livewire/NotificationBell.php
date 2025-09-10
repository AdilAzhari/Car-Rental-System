<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationBell extends Component
{
    public $unreadCount = 0;
    public $notifications = [];

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        $user = auth()->user();
        if ($user) {
            $this->notifications = $user->unreadNotifications()->limit(10)->get()->toArray();
            $this->unreadCount = $user->unreadNotifications()->count();
        }
    }

    public function markAllAsRead()
    {
        $user = auth()->user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
            $this->loadNotifications();
            
            session()->flash('success', __('notifications.all_marked_as_read'));
        }
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}