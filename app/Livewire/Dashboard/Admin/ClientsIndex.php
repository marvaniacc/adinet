<?php

namespace App\Livewire\Dashboard\Admin;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class ClientsIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $clients = User::query()
            ->where('role', User::ROLE_CLIENT)
            ->withCount(['consultationRequests', 'appointments'])
            ->when($this->search !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($qq) use ($term) {
                    $qq->where('mobile', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term);
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.dashboard.admin.clients-index', [
            'clients' => $clients,
        ]);
    }
}
