<x-filament-panels::page>
    @php($report = $this->report())

    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-4">
        <div class="md:col-span-2">
            <x-filament::section>
                <form wire:submit="save">
                    {{ $this->form }}
                </form>
            </x-filament::section>
        </div>
    </div>

    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>
