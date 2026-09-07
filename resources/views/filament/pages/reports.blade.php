<x-filament-panels::page>

    <div class="flex gap-2 mb-6">
        <x-filament::button wire:click="exportAllExcel" icon="heroicon-o-table-cells">
            Exporter tout le patrimoine (Excel)
        </x-filament::button>
        <x-filament::button wire:click="exportPdf" icon="heroicon-o-document" color="gray">
            Exporter le rapport complet (PDF)
        </x-filament::button>
    </div>

    {{-- statistique general --}}

    <x-filament::card class="mb-6">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold">
                Statistiques générales
            </h3>
            <x-filament::button size="sm" wire:click="exportStatsExcel" icon="heroicon-o-arrow-down-tray">
                Exporter (Excel)
            </x-filament::button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-gray-500">Nombre total de propriétés</p>
                <p class="text-lg font-semibold">{{ $totalProperties }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Nombre de propriétés avec titre foncier</p>
                <p class="text-lg font-semibold">{{ $totalPropertiesWithTitle }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Nombre de propriétés sans titre foncier</p>
                <p class="text-lg font-semibold">{{ $totalPropertiesWithoutTitle }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Nombre de propriétés valorisées</p>
                <p class="text-lg font-semibold">{{ $totalValuedProperties }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Valeur estimée totale</p>
                <p class="text-lg font-semibold">{{ number_format($totalValues, 0, ',', ' ') }}</p>
            </div>
        </div>
    </x-filament::card>

</x-filament-panels::page>
