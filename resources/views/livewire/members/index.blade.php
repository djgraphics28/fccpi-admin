<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $gender = '';
    public $church = '';
    public $group = '';

    public $churches;
    public $groups;

    // Modal properties
    public $showModal = false;
    public $editingId = null;

    // Form properties
    #[Validate('required|string|max:255')]
    public $first_name = '';

    #[Validate('required|string|max:255')]
    public $last_name = '';

    #[Validate('required|in:Male,Female')]
    public $gender_form = '';

    #[Validate('required|string')]
    public $church_form = '';

    #[Validate('required|string')]
    public $group_form = '';

    #[Validate('required|string')]
    public $color = '#3B82F6';

    public function mount()
    {
        $this->churches = ['FCC Alcala', 'FCC Balangobong', 'FCC Basista', 'FCC Bugayong', 'FCC Mabini', 'FCC Labrador', 'FCC San Bonifacio', 'FCC Rosales'];
        $this->groups = \App\Models\Youth::distinct('`group`')->pluck('group');
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->gender = '';
        $this->church = '';
        $this->group = '';
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function getMembers()
    {
        return \App\Models\Youth::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->gender, function ($query) {
                $query->where('gender', $this->gender);
            })
            ->when($this->church, function ($query) {
                $query->where('church', $this->church);
            })
            ->when($this->group, function ($query) {
                $query->where('`group`', $this->group);
            })
            ->latest()
            ->paginate(10);
    }

    public function getStats()
    {
        $total = \App\Models\Youth::count();
        $male = \App\Models\Youth::where('gender', 'Male')->count();
        $female = \App\Models\Youth::where('gender', 'Female')->count();

        $churchCounts = \App\Models\Youth::selectRaw('church, COUNT(*) as count')
            ->groupBy('church')
            ->get()
            ->pluck('count', 'church');

        // $groupCounts = \App\Models\Youth::selectRaw('`group`, COUNT(*) as count')
        //     ->groupBy('`group`')
        //     ->get()
        //     ->pluck('count', 'group');
        $groupCounts = 0;

        return compact('total', 'male', 'female', 'churchCounts', 'groupCounts');
    }

    public function showCreateModal()
    {
        $this->resetForm();
        $this->showModal = true;
        $this->editingId = null;
    }

    public function showEditModal($id)
    {
        $this->resetForm();
        $this->editingId = $id;

        $member = \App\Models\Youth::find($id);
        if ($member) {
            $this->first_name = $member->first_name;
            $this->last_name = $member->last_name;
            $this->gender_form = $member->gender;
            $this->church_form = $member->church;
            $this->group_form = $member->group;
            $this->color = $member->color;
        }

        $this->showModal = true;
    }

    public function saveMember()
    {
        $this->validate();

        $data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender_form,
            'church' => $this->church_form,
            'group' => $this->group_form,
            'color' => $this->color,
        ];

        if ($this->editingId) {
            \App\Models\Youth::find($this->editingId)->update($data);
            session()->flash('message', 'Member updated successfully!');
        } else {
            \App\Models\Youth::create($data);
            session()->flash('message', 'Member created successfully!');
        }

        $this->closeModal();
        $this->resetPage();
    }

    public function deleteMember($id)
    {
        \App\Models\Youth::find($id)->delete();
        session()->flash('message', 'Member deleted successfully!');
        $this->resetPage();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->editingId = null;
    }

    private function resetForm()
    {
        $this->reset(['first_name', 'last_name', 'gender_form', 'church_form', 'group_form', 'color']);
        $this->resetErrorBag();
    }
}; ?>

<div>
    <!-- Header with Stats -->
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Youth Members</h1>
            <button wire:click="showCreateModal" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                Add New Member
            </button>
        </div>

        @php $stats = $this->getStats(); @endphp
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow border dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Members</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow border dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400">Male</div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['male'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow border dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400">Female</div>
                <div class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ $stats['female'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow border dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400">Active Filters</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ ($search ? 1 : 0) + ($gender ? 1 : 0) + ($church ? 1 : 0) + ($group ? 1 : 0) }}
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow border dark:border-gray-700">
        <div class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Members</label>
                <input wire:model.live="search" type="text" placeholder="Search by name..."
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gender</label>
                <select wire:model.live="gender" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Genders</option>
                    <option value="Male">Male ({{ $stats['male'] }})</option>
                    <option value="Female">Female ({{ $stats['female'] }})</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Church</label>
                <select wire:model.live="church" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Churches</option>
                    @foreach ($churches as $churchOption)
                        <option value="{{ $churchOption }}">
                            {{ $churchOption }} ({{ $stats['churchCounts'][$churchOption] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Group</label>
                <select wire:model.live="group" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Groups</option>
                    @foreach ($groups as $groupOption)
                        <option value="{{ $groupOption }}">
                            {{ $groupOption }} ({{ $stats['groupCounts'][$groupOption] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>

            <button wire:click="resetFilters" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors">
                Reset
            </button>
        </div>
    </div>

    <!-- Flash Message -->
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gender</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Church</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Group</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Color</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->getMembers() as $member)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $member->first_name }} {{ $member->last_name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $member->gender === 'Male' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300' }}">
                                    {{ $member->gender }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-300">{{ $member->church }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-300">{{ $member->group }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded border dark:border-gray-600" style="background-color: {{ $member->color }}"></div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $member->color }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button wire:click="showEditModal({{ $member->id }})" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3">Edit</button>
                                <button wire:click="deleteMember({{ $member->id }})" wire:confirm="Are you sure you want to delete this member?" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No members found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $this->getMembers()->links() }}
    </div>

    <!-- Sidebar Modal -->
    <div x-data="{ open: @entangle('showModal') }" x-show="open" class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50" x-show="open" x-transition.opacity></div>

        <div class="absolute inset-y-0 right-0 max-w-full flex">
            <div x-show="open" x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300"
                 x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                 class="relative w-screen max-w-md">
                <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-xl">
                    <!-- Header -->
                    <div class="px-4 py-6 bg-gray-50 dark:bg-gray-700 sm:px-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $editingId ? 'Edit Member' : 'Add New Member' }}
                            </h2>
                            <button wire:click="closeModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="flex-1 overflow-y-auto">
                        <div class="px-4 sm:px-6 py-6 space-y-6">
                            <!-- First Name -->
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">First Name</label>
                                <input type="text" id="first_name" wire:model="first_name"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('first_name') <span class="text-red-500 dark:text-red-400 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Name</label>
                                <input type="text" id="last_name" wire:model="last_name"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('last_name') <span class="text-red-500 dark:text-red-400 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Gender -->
                            <div>
                                <label for="gender_form" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gender</label>
                                <select id="gender_form" wire:model="gender_form"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                @error('gender_form') <span class="text-red-500 dark:text-red-400 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Church -->
                            <div>
                                <label for="church_form" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Church</label>
                                <select id="church_form" wire:model="church_form"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select Church</option>
                                    @foreach ($churches as $churchOption)
                                        <option value="{{ $churchOption }}">{{ $churchOption }}</option>
                                    @endforeach
                                </select</div>
