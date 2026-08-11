<x-filament-panels::page>
    <x-filament::card>
        <div class="flex flex-col gap-4">
            <h2 class="text-lg font-bold">Rapports</h2>
            <p>Générez des rapports sur les propriétés et leurs documents.</p>
        </div>

        <div class="mt-6 flex flex-col gap-4">
            <x-filament::button wire:click="exportAllExcel" color="primary">
                Télécharger le rapport Excel
            </x-filament::button>

            <x-filament::button wire:click="exportPdf" color="secondary">
                Télécharger le rapport PDF
            </x-filament::button>
        </div>
    </x-filament::card>
</x-filament-panels::page>
