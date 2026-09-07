<x-filament-widgets::widget>
    <x-filament::card>
        <div class="flex items-end gap-4">
            <div class="flex-1">
                <form wire:submit.prevent>
                    {{ $this->form }}
                </form>
            </div>
            <x-filament::button color="gray" wire:click="resetFilter" icon="heroicon-o-x-mark">
                Réinitialiser
            </x-filament::button>
        </div>
    </x-filament::card>
</x-filament-widgets::widget>