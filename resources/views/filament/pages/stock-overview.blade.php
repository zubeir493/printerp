<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
    </form>

    @if($warehouse_id)
    <div class="mt-6 mb-6">
        <x-filament::card>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">
                        Total Warehouse Value
                    </div>
                    <div class="text-3xl font-bold">
                        {{ number_format($this->totalValue, 2) }} ETB
                    </div>
                </div>
            </div>
        </x-filament::card>
    </div>
    @endif

    <div class="mt-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>