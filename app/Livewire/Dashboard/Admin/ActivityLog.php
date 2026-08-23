<?php

namespace App\Livewire\Dashboard\Admin;

use App\Models\AdminAction;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class ActivityLog extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.dashboard.admin.activity-log', [
            'actions' => AdminAction::query()
                ->with('admin:id,first_name,last_name')
                ->latest()
                ->paginate(30),
        ]);
    }
}
