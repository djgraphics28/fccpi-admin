<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Youth;
use Illuminate\Support\Facades\Artisan;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $gender = '';
    public $church = '';
    public $color_filter = '';
    public $is_facilitator_filter = '';

    public $churches;
    public $colors;

    // Modal properties
    public $showModal = false;
    public $editingId = null;

    // Bulk action properties
    public $selectedMembers = [];
    public $selectAll = false;
    public $showBulkModal = false;
    public $bulkAction = '';
    public $numberOfGroups = 2;

    // Print properties
    public $showPrintModal = false;
    public $printLayout = '3x8'; // 3 columns x 8 rows
    public $includeChurch = true;
    public $includeColorGroup = true;
    public $fontSize = 'medium';

    // Form properties
    #[Validate('required|string|max:255')]
    public $first_name = '';

    #[Validate('required|string|max:255')]
    public $last_name = '';

    #[Validate('required|in:Male,Female')]
    public $gender_form = '';

    #[Validate('required|string')]
    public $church_form = '';

    public $color = '#3B82F6';

    public $is_facilitator = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->churches = ['FCC Alcala', 'FCC Balangobong', 'FCC Basista', 'FCC Bugayong', 'FCC Mabini', 'FCC Labrador', 'FCC San Bonifacio', 'FCC Rosales'];
        $this->colors = Youth::distinct('color')->whereNotNull('color')->pluck('color');
    }

    /**
     * Reset all filters
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->gender = '';
        $this->church = '';
        $this->color_filter = '';
        $this->resetPage();
        $this->selectedMembers = [];
        $this->selectAll = false;
        $this->is_facilitator_filter = '';
    }

    /**
     * Reset pagination when search is updated
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Get filtered members with pagination
     */
    public function getMembers()
    {
        return Youth::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')->orWhere('last_name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->gender, function ($query) {
                $query->where('gender', $this->gender);
            })
            ->when($this->church, function ($query) {
                $query->where('church', $this->church);
            })
            ->when($this->color_filter, function ($query) {
                $query->where('color', $this->color_filter);
            })
            ->when($this->is_facilitator_filter, function ($query) {
                if ($this->is_facilitator_filter === 'yes') {
                    $query->where('is_facilitator', true);
                } elseif ($this->is_facilitator_filter === 'no') {
                    $query->where('is_facilitator', false);
                }
            })
            ->latest()
            ->paginate(10);
    }

    /**
     * Get all filtered members IDs for bulk actions (without pagination)
     */
    public function getAllFilteredMemberIds()
    {
        return Youth::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')->orWhere('last_name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->gender, function ($query) {
                $query->where('gender', $this->gender);
            })
            ->when($this->church, function ($query) {
                $query->where('church', $this->church);
            })
            ->when($this->color_filter, function ($query) {
                $query->where('color', $this->color_filter);
            })
            ->when($this->is_facilitator_filter, function ($query) {
                if ($this->is_facilitator_filter === 'yes') {
                    $query->where('is_facilitator', true);
                } elseif ($this->is_facilitator_filter === 'no') {
                    $query->where('is_facilitator', false);
                }
            })
            ->orderBy('first_name')
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get members for printing
     */
    public function getMembersForPrint()
    {
        if (empty($this->selectedMembers)) {
            // If no members selected, use filtered members
            return Youth::query()
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('first_name', 'like', '%' . $this->search . '%')->orWhere('last_name', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->gender, function ($query) {
                    $query->where('gender', $this->gender);
                })
                ->when($this->church, function ($query) {
                    $query->where('church', $this->church);
                })
                ->when($this->color_filter, function ($query) {
                    $query->where('color', $this->color_filter);
                })
                ->orderBy('first_name')
                ->get();
        }

        // Use selected members
        return Youth::whereIn('id', $this->selectedMembers)->orderBy('first_name')->get();
    }

    /**
     * Get statistics for dashboard
     */
    public function getStats(): array
    {
        $total = Youth::count();
        $male = Youth::where('gender', 'Male')->count();
        $female = Youth::where('gender', 'Female')->count();

        $churchCounts = Youth::selectRaw('church, COUNT(*) as count')->groupBy('church')->get()->pluck('count', 'church');

        $colorCounts = Youth::selectRaw('color, COUNT(*) as count')->groupBy('color')->get()->pluck('count', 'color');

        $totalFacilitators = Youth::where('is_facilitator', true)->count();

        return compact('total', 'male', 'female', 'churchCounts', 'colorCounts', 'totalFacilitators');
    }

    /**
     * Toggle select all members on current page
     */
    public function updatedSelectAll($value): void
    {
        if ($value) {
            // Select all members on current page
            $currentPageMembers = $this->getMembers()->pluck('id')->toArray();
            $this->selectedMembers = array_unique(array_merge($this->selectedMembers, $currentPageMembers));
        } else {
            // Deselect all members on current page
            $currentPageMembers = $this->getMembers()->pluck('id')->toArray();
            $this->selectedMembers = array_diff($this->selectedMembers, $currentPageMembers);
        }
    }

    /**
     * Select all members across all pages
     */
    public function selectAllMembers(): void
    {
        $this->selectedMembers = $this->getAllFilteredMemberIds();
        $this->selectAll = true;
    }

    /**
     * Clear all selections
     */
    public function clearSelection(): void
    {
        $this->selectedMembers = [];
        $this->selectAll = false;
    }

    /**
     * Updated selected members array
     */
    public function updatedSelectedMembers(): void
    {
        // When manually selecting/deselecting members, update selectAll state
        $currentPageMembers = $this->getMembers()->pluck('id')->toArray();
        $selectedOnPage = array_intersect($this->selectedMembers, $currentPageMembers);

        // If all members on current page are selected, check selectAll
        // If not all are selected, uncheck selectAll
        $this->selectAll = count($selectedOnPage) === count($currentPageMembers) && count($currentPageMembers) > 0;
    }

    /**
     * Show create member modal
     */
    public function showCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
        $this->editingId = null;
    }

    /**
     * Show edit member modal
     */
    public function showEditModal($id): void
    {
        $this->resetForm();
        $this->editingId = $id;

        $member = Youth::find($id);
        if ($member) {
            $this->first_name = $member->first_name;
            $this->last_name = $member->last_name;
            $this->gender_form = $member->gender;
            $this->church_form = $member->church;
            $this->color = $member->color ?? '#3B82F6';
            $this->is_facilitator = $member->is_facilitator;
        }

        $this->showModal = true;
    }

    /**
     * Save or update member
     */
    public function saveMember(): void
    {
        $this->validate();

        $data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender_form,
            'church' => $this->church_form,
            'color' => $this->color,
            'is_facilitator' => $this->is_facilitator,
        ];

        if ($this->editingId) {
            Youth::find($this->editingId)->update($data);
            session()->flash('message', 'Member updated successfully!');
        } else {
            Youth::create($data);
            session()->flash('message', 'Member created successfully!');
        }

        $this->closeModal();
        $this->resetPage();
    }

    /**
     * Delete member
     */
    public function deleteMember($id): void
    {
        Youth::find($id)->delete();
        session()->flash('message', 'Member deleted successfully!');
        $this->resetPage();
    }

    /**
     * Show bulk action modal
     */
    public function showBulkActionModal($action): void
    {
        $this->bulkAction = $action;
        $this->showBulkModal = true;

        // Set default number of groups based on current data
        if ($action === 'randomize') {
            $uniqueColors = Youth::distinct('color')->whereNotNull('color')->count();
            $this->numberOfGroups = max(2, $uniqueColors);
        }

        // Emit event to Alpine.js
        $this->dispatch('bulk-modal-opened');
    }

    /**
     * Show print modal
     */
    public function printModal(): void
    {
        // dd('print modal');
        $this->showPrintModal = true;
    }

    /**
     * Execute bulk action
     */
    public function executeBulkAction(): void
    {
        if (empty($this->selectedMembers)) {
            session()->flash('error', 'Please select at least one member.');
            $this->closeBulkModal();
            return;
        }

        try {
            switch ($this->bulkAction) {
                case 'delete':
                    Youth::whereIn('id', $this->selectedMembers)->delete();
                    session()->flash('message', count($this->selectedMembers) . ' members deleted successfully!');
                    break;

                case 'randomize':
                    $this->randomizeColorsUsingCommand();
                    session()->flash('message', count($this->selectedMembers) . ' members randomized successfully!');
                    break;
            }

            // Close modal and reset selections
            $this->closeBulkModal();
            $this->selectedMembers = [];
            $this->selectAll = false;
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while processing the bulk action: ' . $e->getMessage());
            $this->closeBulkModal();
        }
    }

    /**
     * Randomize colors using Artisan command
     */
    private function randomizeColorsUsingCommand(): void
    {
        // Prepare member IDs for the command
        $memberIds = $this->selectedMembers;

        // Call the Artisan command
        $exitCode = Artisan::call('youth:randomize-colors', [
            '--colorNumber' => $this->numberOfGroups,
            '--memberIds' => $memberIds,
        ]);

        if ($exitCode !== 0) {
            throw new \Exception('Color randomization failed. Please try again.');
        }
    }

    public function printNameTags()
    {
        $selectedIds = $this->selectedMembers; // array of IDs
        return redirect()->route('print.name.tags', ['ids' => $selectedIds]);
    }

    /**
     * Get color name from hex code for display
     */
    public function getColorName(?string $hex): string
    {
        if (empty($hex)) {
            return 'No Color';
        }

        $colorNames = [
            '#3B82F6' => 'Blue Group',
            '#EF4444' => 'Red Group',
            '#10B981' => 'Green Group',
            '#F59E0B' => 'Yellow Group',
            '#8B5CF6' => 'Purple Group',
            '#EC4899' => 'Pink Group',
            '#06B6D4' => 'Cyan Group',
            '#84CC16' => 'Lime Group',
            '#F97316' => 'Orange Group',
            '#6366F1' => 'Indigo Group',
            '#14B8A6' => 'Teal Group',
            '#EAB308' => 'Amber Group',
            '#A855F7' => 'Violet Group',
            '#D946EF' => 'Fuchsia Group',
            '#0EA5E9' => 'Sky Group',
            '#22C55E' => 'Emerald Group',
            '#FACC15' => 'Gold Group',
            '#FB923C' => 'Coral Group',
            '#C084FC' => 'Lavender Group',
            '#F472B6' => 'Rose Group',
        ];

        return $colorNames[$hex] ?? $this->generateColorName($hex);
    }

    /**
     * Generate a friendly name for custom colors
     */
    private function generateColorName(string $hex): string
    {
        // Remove # from hex code
        $hex = ltrim($hex, '#');

        // Convert hex to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Simple color detection based on RGB values
        if ($r > 200 && $g < 100 && $b < 100) {
            return 'Custom Red';
        }
        if ($g > 200 && $r < 100 && $b < 100) {
            return 'Custom Green';
        }
        if ($b > 200 && $r < 100 && $g < 100) {
            return 'Custom Blue';
        }
        if ($r > 200 && $g > 200 && $b < 100) {
            return 'Custom Yellow';
        }
        if ($r > 200 && $b > 200 && $g < 100) {
            return 'Custom Magenta';
        }
        if ($g > 200 && $b > 200 && $r < 100) {
            return 'Custom Cyan';
        }
        if ($r > 150 && $g > 150 && $b > 150) {
            return 'Custom Light';
        }
        if ($r < 100 && $g < 100 && $b < 100) {
            return 'Custom Dark';
        }

        return 'Custom Color';
    }

    /**
     * Close bulk modal
     */
    public function closeBulkModal(): void
    {
        $this->showBulkModal = false;
        $this->bulkAction = '';
        $this->numberOfGroups = 2;
    }

    /**
     * Close print modal
     */
    public function closePrintModal(): void
    {
        $this->showPrintModal = false;
    }

    /**
     * Close modal and reset form
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
        $this->editingId = null;
    }

    /**
     * Reset form fields
     */
    private function resetForm(): void
    {
        $this->reset(['first_name', 'last_name', 'gender_form', 'church_form', 'color']);
        $this->resetErrorBag();
    }
}; ?>

<section class="w-full">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Youth Members Management
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Manage and organize youth members across different churches and color groups
                </p>
            </div>
            <button wire:click="showCreateModal"
                class="w-full sm:w-auto flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors"
                data-test="add-member-button">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add New Member
            </button>
        </div>

        <!-- Statistics Cards -->
        @php $stats = $this->getStats(); @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Members Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
                <p class="text-gray-600 dark:text-gray-400 mb-2">Total Members</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $stats['total'] }}
                </p>
            </div>

            <!-- Male Members Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
                <p class="text-gray-600 dark:text-gray-400 mb-2">Male Members</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ $stats['male'] }}
                </p>
            </div>

            <!-- Female Members Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
                <p class="text-gray-600 dark:text-gray-400 mb-2">Female Members</p>
                <p class="text-2xl font-bold text-pink-600 dark:text-pink-400">
                    {{ $stats['female'] }}
                </p>
            </div>

            <!-- Color Groups Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
                <p class="text-gray-600 dark:text-gray-400 mb-2">Color Groups</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ count($stats['colorCounts']) }}
                </p>
            </div>
        </div>
    </div>

    <!-- Bulk Actions & Filters Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
        <!-- Bulk Actions -->
        @if (count($selectedMembers) > 0)
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-blue-800 dark:text-blue-300 font-medium">
                            {{ count($selectedMembers) }} member(s) selected
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="printNameTags()"
                            class="flex items-center px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print Name Tags
                        </button>
                        <button wire:click="showBulkActionModal('randomize')"
                            class="flex items-center px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Randomize Colors
                        </button>
                        @if(Auth::user()->id === 1)
                        <button wire:click="showBulkActionModal('delete')"
                            class="flex items-center px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete Selected
                        </button>
                        @endif
                        <button wire:click="clearSelection"
                            class="flex items-center px-3 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Clear Selection
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Selection Actions -->
        <div class="mb-4 flex flex-wrap gap-2 items-center">
            <span class="text-sm text-gray-600 dark:text-gray-400">Select:</span>
            <button wire:click="selectAllMembers"
                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline">
                All {{ count($this->getAllFilteredMemberIds()) }} members
            </button>
            <span class="text-gray-400">•</span>
            <button wire:click="clearSelection"
                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300 underline">
                Clear selection
            </button>
        </div>

        <!-- Filters -->
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Filter Members
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Search Members
                </label>
                <input id="search" wire:model.live="search" type="text"
                    placeholder="Search by first or last name..."
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
            </div>

            <!-- Gender Filter -->
            <div>
                <label for="gender" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Gender
                </label>
                <select id="gender" wire:model.live="gender"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    <option value="">All Genders</option>
                    <option value="Male">Male ({{ $stats['male'] }})</option>
                    <option value="Female">Female ({{ $stats['female'] }})</option>
                </select>
            </div>

            <!-- Church Filter -->
            <div>
                <label for="church" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Church
                </label>
                <select id="church" wire:model.live="church"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    <option value="">All Churches</option>
                    @foreach ($churches as $churchOption)
                        <option value="{{ $churchOption }}">
                            {{ $churchOption }} ({{ $stats['churchCounts'][$churchOption] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Color Filter -->
            <div>
                <label for="color_filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Color Group
                </label>
                <select id="color_filter" wire:model.live="color_filter"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    <option value="">All Colors</option>
                    @foreach ($colors as $colorOption)
                        <option value="{{ $colorOption }}">
                            {{ $this->getColorName($colorOption) }} ({{ $stats['colorCounts'][$colorOption] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="is_facilitator_filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Facilitator Status
                </label>
                <select id="is_facilitator_filter" wire:model.live="is_facilitator_filter"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    <option value="">All Members</option>
                    <option value="yes">Facilitators ({{ $stats['totalFacilitators'] }})</option>
                    <option value="no">Non-Facilitators ({{ $stats['total'] - $stats['totalFacilitators'] }})</option>
                </select>
            </div>

            <!-- Reset Button -->
            <div class="flex justify-end">
                <button wire:click="resetFilters"
                    class="w-full flex items-center justify-center px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    @if (session()->has('message'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-lg border border-green-200 dark:border-green-800"
            data-test="success-message">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div
            class="mb-6 p-4 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-lg border border-red-200 dark:border-red-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- Members Table -->
    <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <div class="flex items-center">
                                <input type="checkbox" wire:model.live="selectAll"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <span class="ml-2">Select</span>
                            </div>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Name</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Gender</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Church</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Color Group</th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->getMembers() as $member)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" wire:model="selectedMembers" value="{{ $member->id }}"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $member->first_name }} {{ $member->last_name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $member->gender === 'Male' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300' }}">
                                    {{ $member->gender }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-300">
                                {{ $member->church }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600 shadow-sm"
                                        style="background-color: {{ $member->color }}"></div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $this->getColorName($member->color) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="showEditModal({{ $member->id }})"
                                        class="inline-flex items-center px-3 py-1.5 text-sm bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 transition-colors"
                                        data-test="edit-member-button">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </button>
                                    @if(Auth::user()->id === 1 )
                                    <button wire:click="deleteMember({{ $member->id }})"
                                        wire:confirm="Are you sure you want to delete this member?"
                                        class="inline-flex items-center px-3 py-1.5 text-sm bg-white dark:bg-gray-700 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 transition-colors"
                                        data-test="delete-member-button">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Delete
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center">
                                <div
                                    class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mb-4 opacity-50" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="mb-2 text-lg">No members found</p>
                                    <p class="text-sm">Try adjusting your search filters or add a new member</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($this->getMembers()->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $this->getMembers()->links() }}
            </div>
        @endif
    </div>

    <!-- Create/Edit Member Modal -->
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
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $editingId ? 'Edit Member' : 'Add New Member' }}
                            </h2>
                            <button wire:click="closeModal"
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="flex-1 overflow-y-auto">
                        <form wire:submit="saveMember" class="p-6 space-y-6">
                            <!-- First Name -->
                            <div>
                                <label for="first_name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    First Name
                                </label>
                                <input id="first_name" wire:model="first_name" type="text" required autofocus
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    data-test="first-name-input">
                                @error('first_name')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label for="last_name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Last Name
                                </label>
                                <input id="last_name" wire:model="last_name" type="text" required
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    data-test="last-name-input">
                                @error('last_name')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Gender -->
                            <div>
                                <label for="gender_form"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Gender
                                </label>
                                <select id="gender_form" wire:model="gender_form" required
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    data-test="gender-select">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                @error('gender_form')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <!-- Facilitator Checkbox -->
                                <label class="inline-flex items-center">
                                    <input type="checkbox" wire:model="is_facilitator"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <span class="ml-2 text-gray-700 dark:text-gray-300">Is Facilitator</span>
                                </label>
                            </div>

                            <!-- Church -->
                            <div>
                                <label for="church_form"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Church
                                </label>
                                <select id="church_form" wire:model="church_form" required
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    data-test="church-select">
                                    <option value="">Select Church</option>
                                    @foreach ($churches as $churchOption)
                                        <option value="{{ $churchOption }}">{{ $churchOption }}</option>
                                    @endforeach
                                </select>
                                @error('church_form')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Color Picker -->
                            <div>
                                <label for="color"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Color Group
                                </label>
                                <div class="grid grid-cols-5 gap-2 mb-3">
                                    @foreach ([
        '#3B82F6' => 'Blue',
        '#EF4444' => 'Red',
        '#10B981' => 'Green',
        '#F59E0B' => 'Yellow',
        '#8B5CF6' => 'Purple',
        '#EC4899' => 'Pink',
        '#06B6D4' => 'Cyan',
        '#84CC16' => 'Lime',
        '#F97316' => 'Orange',
        '#6366F1' => 'Indigo',
    ] as $colorValue => $colorName)
                                        <button type="button" wire:click="$set('color', '{{ $colorValue }}')"
                                            class="h-8 rounded border-2 {{ $color === $colorValue ? 'border-gray-800 dark:border-white' : 'border-gray-300 dark:border-gray-600' }}"
                                            style="background-color: {{ $colorValue }}"
                                            title="{{ $colorName }} Group"></button>
                                    @endforeach
                                </div>
                                <div class="flex items-center gap-4">
                                    <input type="color" id="color" wire:model="color"
                                        class="w-12 h-12 rounded border border-gray-300 dark:border-gray-600 cursor-pointer"
                                        data-test="color-input">
                                    <input wire:model="color" type="text"
                                        class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        placeholder="#3B82F6">
                                </div>
                                @error('color')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button type="button" wire:click="closeModal"
                                    class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                                    data-test="save-member-button">
                                    {{ $editingId ? 'Update Member' : 'Create Member' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Replace the existing Bulk Action Modal for Randomize Colors with this side drawer version -->

    <!-- Randomize Colors Side Drawer Modal -->
    <div x-data="{ open: @entangle('showBulkModal') }" x-show="open" class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50" x-show="open" x-transition.opacity></div>

        <div class="absolute inset-y-0 right-0 max-w-full flex">
            <div x-show="open" x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                class="relative w-screen max-w-md">
                <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-xl">
                    <!-- Header -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Randomize Color Groups
                            </h2>
                            <button wire:click="closeBulkModal"
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 overflow-y-auto">
                        <div class="p-6 space-y-6">
                            <!-- Info Box -->
                            <div
                                class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700 dark:text-blue-300">
                                            @if (count($selectedMembers) > 0)
                                                <strong>{{ count($selectedMembers) }} selected members</strong> will be
                                                randomized.
                                            @else
                                                <strong>All filtered members</strong> will be randomized.
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Number of Color Groups -->
                            <div>
                                <label for="numberOfGroups"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Number of Color Groups
                                </label>
                                <input type="number" id="numberOfGroups" wire:model="numberOfGroups" min="2"
                                    max="20"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Members will be evenly distributed across <span
                                        class="font-semibold">{{ $numberOfGroups }}</span> color groups.
                                </p>
                            </div>

                            <!-- How it works -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">How it works</h3>
                                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                    <p>• Members are randomly assigned to color groups</p>
                                    <p>• Groups are sized as evenly as possible</p>
                                    <p>• If member count isn't divisible by group count, some groups will have one more
                                        member</p>
                                    <p>• Existing color assignments will be replaced</p>
                                </div>
                            </div>

                            <!-- Color Preview -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Color Groups
                                    Preview</h3>
                                <div class="grid grid-cols-5 gap-2">
                                    @foreach ([
        '#3B82F6' => 'Blue',
        '#EF4444' => 'Red',
        '#10B981' => 'Green',
        '#F59E0B' => 'Yellow',
        '#8B5CF6' => 'Purple',
        '#EC4899' => 'Pink',
        '#06B6D4' => 'Cyan',
        '#84CC16' => 'Lime',
        '#F97316' => 'Orange',
        '#6366F1' => 'Indigo',
        '#14B8A6' => 'Teal',
        '#EAB308' => 'Amber',
        '#A855F7' => 'Violet',
        '#D946EF' => 'Fuchsia',
        '#0EA5E9' => 'Sky',
        '#22C55E' => 'Emerald',
        '#FACC15' => 'Gold',
        '#FB923C' => 'Coral',
        '#C084FC' => 'Lavender',
        '#F472B6' => 'Rose',
    ] as $colorValue => $colorName)
                                        @if ($loop->index < $numberOfGroups)
                                            <div class="text-center">
                                                <div class="w-8 h-8 rounded border border-gray-300 dark:border-gray-600 mx-auto mb-1"
                                                    style="background-color: {{ $colorValue }}"></div>
                                                <span
                                                    class="text-xs text-gray-600 dark:text-gray-400">{{ $loop->iteration }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex gap-3">
                            <button type="button" wire:click="closeBulkModal"
                                class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                                Cancel
                            </button>
                            <button type="button" wire:click="executeBulkAction"
                                class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                                <div class="flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Randomize Colors
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Keep the Delete confirmation as a centered modal (since it's a destructive action) -->
    <div x-data="{ open: @entangle('showBulkModal') && $wire.bulkAction === 'delete' }" x-show="open" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="open"
                x-transition.opacity @click="$wire.closeBulkModal()"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
                x-show="open" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" @click.stop>

                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                            Delete Selected Members
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Are you sure you want to delete {{ count($selectedMembers) }} selected member(s)?
                                This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="button" wire:click="executeBulkAction"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                    <button type="button" wire:click="closeBulkModal"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update the Print Modal section -->
    <div x-data="{ open: @entangle('showPrintModal') }" x-show="open" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="open"
                x-transition.opacity></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
                x-show="open" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 dark:bg-purple-900 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                            Print Name Tags
                        </h3>
                        <div class="mt-4 space-y-4">
                            <!-- Print Count -->
                            <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                                <p class="text-sm text-blue-800 dark:text-blue-300">
                                    @if (count($selectedMembers) > 0)
                                        Printing {{ count($selectedMembers) }} selected member(s)
                                    @else
                                        Printing all {{ count($this->getMembersForPrint()) }} filtered member(s)
                                    @endif
                                </p>
                            </div>

                            <!-- Layout Info -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Layout (A4 - 2 columns)
                                </label>
                                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <strong>A4 Size:</strong> 2 columns per page<br>
                                        <strong>Content:</strong> First name + Color group<br>
                                        <strong>Orientation:</strong> Portrait
                                    </p>
                                </div>
                            </div>

                            <!-- Font Size -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Font Size
                                </label>
                                <div class="grid grid-cols-3 gap-2">
                                    <label
                                        class="flex items-center justify-center p-2 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <input type="radio" wire:model="fontSize" value="small"
                                            class="text-purple-600 focus:ring-purple-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Small</span>
                                    </label>
                                    <label
                                        class="flex items-center justify-center p-2 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <input type="radio" wire:model="fontSize" value="medium"
                                            class="text-purple-600 focus:ring-purple-500" checked>
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Medium</span>
                                    </label>
                                    <label
                                        class="flex items-center justify-center p-2 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <input type="radio" wire:model="fontSize" value="large"
                                            class="text-purple-600 focus:ring-purple-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Large</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Options -->
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="includeColorGroup"
                                        class="rounded border-gray-300 text-purple-600 focus:ring-purple-500" checked>
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include Color
                                        Group</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="button" wire:click="printNameTags"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Now
                    </button>
                    <button type="button" wire:click="closePrintModal"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update the Print Preview section -->
    <div id="print-preview" class="hidden">
        <div class="name-tags-container">
            @foreach ($this->getMembersForPrint()->chunk(16) as $pageIndex => $pageMembers)
                <div class="name-tags-page"
                    style="
                width: 210mm;
                height: 297mm;
                padding: 15mm;
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(8, 1fr);
                gap: 8mm;
                page-break-after: always;
                font-family: Arial, sans-serif;
                background: white;
            ">
                    @foreach ($pageMembers as $member)
                        <div class="name-tag"
                            style="
                        border: 2px solid {{ $member->color }};
                        border-radius: 12px;
                        padding: 6mm;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        text-align: center;
                        background: white;
                        break-inside: avoid;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    ">
                            <!-- First Name Only -->
                            <div
                                style="
                            font-weight: bold;
                            margin-bottom: 3mm;
                            font-size: {{ $fontSize === 'small' ? '20pt' : ($fontSize === 'medium' ? '24pt' : '28pt') }};
                            color: #1f2937;
                            line-height: 1.2;
                        ">
                                {{ $member->first_name }}
                            </div>

                            <!-- Color Group (if enabled) -->
                            @if ($includeColorGroup)
                                <div
                                    style="
                                display: flex;
                                align-items: center;
                                gap: 2mm;
                                margin-top: 2mm;
                            ">
                                    <div class="w-4 h-4 rounded border"
                                        style="background-color: {{ $member->color }}; border: 1px solid #d1d5db;">
                                    </div>
                                    <span
                                        style="
                                    font-size: {{ $fontSize === 'small' ? '10pt' : ($fontSize === 'medium' ? '12pt' : '14pt') }};
                                    color: #374151;
                                    font-weight: 500;
                                ">
                                        {{ $this->getColorName($member->color) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <!-- Fill empty slots to maintain grid layout -->
                    @for ($i = count($pageMembers); $i < 16; $i++)
                        <div class="empty-slot"
                            style="
                        border: 2px dashed #d1d5db;
                        border-radius: 12px;
                        background: transparent;
                    ">
                        </div>
                    @endfor
                </div>
            @endforeach
        </div>
    </div>

    <!-- Update the JavaScript for Print Functionality -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-print-preview', (options) => {
                // Show print preview
                const printPreview = document.getElementById('print-preview');
                printPreview.classList.remove('hidden');

                // Wait for rendering then print
                setTimeout(() => {
                    window.print();

                    // Hide print preview after printing
                    setTimeout(() => {
                        printPreview.classList.add('hidden');
                    }, 500);
                }, 500);
            });
        });

        // Print styles
        const printStyles = `
        <style>
            @media print {
                body * {
                    visibility: hidden;
                }
                #print-preview,
                #print-preview * {
                    visibility: visible;
                }
                #print-preview {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background: white;
                }
                .name-tags-page {
                    page-break-after: always;
                    margin: 0;
                    padding: 15mm;
                    width: 210mm;
                    height: 297mm;
                }
                .name-tag {
                    break-inside: avoid;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .empty-slot {
                    visibility: hidden !important;
                }
                @page {
                    size: A4 portrait;
                    margin: 0;
                }
            }

            /* Screen preview styles */
            @media screen {
                .name-tags-page {
                    margin: 20px auto;
                    border: 1px solid #e5e7eb;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                }
            }
        </style>
    `;

        // Add print styles to document
        if (!document.querySelector('#print-styles')) {
            const styleSheet = document.createElement('style');
            styleSheet.id = 'print-styles';
            styleSheet.innerText = printStyles;
            document.head.appendChild(styleSheet);
        }
    </script>
</section>
