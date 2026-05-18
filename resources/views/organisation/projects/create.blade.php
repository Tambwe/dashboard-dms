@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <div class="flex items-center mb-4">
                <a href="{{ route('organisation.projects.index') }}"
                   class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mr-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Nouveau Projet</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Créer un projet pour votre organisation</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <form action="{{ route('organisation.projects.store') }}" method="POST" class="p-6 space-y-6">
                @csrf
                @include('organisation.projects.partials.form', ['submitLabel' => 'Créer le projet'])
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    async function fetchJson(url) {
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) {
            throw new Error('Erreur de chargement');
        }
        return response.json();
    }

    async function loadTerritoiresByIndex(idx, provinceId) {
        const tSel = document.getElementById('t-' + idx);
        const cSel = document.getElementById('c-' + idx);
        if (!tSel) return;

        tSel.innerHTML = '<option value="">-- Territoire --</option>';
        if (cSel) cSel.innerHTML = '<option value="">-- Commune --</option>';
        if (!provinceId) return;

        const data = await fetchJson('/api/geographic/territoires?province_id=' + provinceId);
        (data.data ?? data).forEach((t) => {
            const o = document.createElement('option');
            o.value = t.id;
            o.textContent = t.name;
            tSel.appendChild(o);
        });
    }

    async function loadCommunesByIndex(idx, territoireId) {
        const cSel = document.getElementById('c-' + idx);
        if (!cSel) return;

        cSel.innerHTML = '<option value="">-- Commune --</option>';
        if (!territoireId) return;

        const data = await fetchJson('/api/geographic/communes?territoire_id=' + territoireId);
        (data.data ?? data).forEach((c) => {
            const o = document.createElement('option');
            o.value = c.id;
            o.textContent = c.name;
            cSel.appendChild(o);
        });
    }

    window.dmsZoneProvince = async function (sel) {
        const idx = sel.dataset.idx;
        await loadTerritoiresByIndex(idx, sel.value);
    };

    window.dmsZoneTerritoire = async function (sel) {
        const idx = sel.dataset.idx;
        await loadCommunesByIndex(idx, sel.value);
    };

    function nextZoneIndex() {
        const rows = Array.from(document.querySelectorAll('#zones-list .zone-row'));
        let max = -1;
        rows.forEach((row) => {
            const province = row.querySelector('.zone-province');
            const name = province ? province.getAttribute('name') || '' : '';
            const match = name.match(/execution_zones\[(\d+)\]/);
            if (match) {
                max = Math.max(max, Number(match[1]));
            }
        });
        return max + 1;
    }

    window.dmsZoneAdd = function () {
        const list = document.getElementById('zones-list');
        if (!list) return;

        const idx = nextZoneIndex();
        const firstProvince = document.querySelector('#zones-list .zone-province');
        const provinceOptions = firstProvince
            ? firstProvince.innerHTML
            : '<option value="">-- Province --</option>';

        const row = document.createElement('div');
        row.className = 'zone-row grid grid-cols-3 gap-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600';
        row.innerHTML = `
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Province</label>
                <select name="execution_zones[${idx}][province_id]" class="zone-province w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm" data-idx="${idx}" onchange="dmsZoneProvince(this)">
                    ${provinceOptions}
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Territoire</label>
                <select name="execution_zones[${idx}][territoire_id]" id="t-${idx}" class="zone-territoire w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm" data-idx="${idx}" onchange="dmsZoneTerritoire(this)">
                    <option value="">-- Territoire --</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Commune</label>
                <div class="flex gap-1">
                    <select name="execution_zones[${idx}][commune_id]" id="c-${idx}" class="zone-commune flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                        <option value="">-- Commune --</option>
                    </select>
                    <button type="button" onclick="dmsZoneRemove(this)" class="px-2 text-red-500 hover:text-red-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>`;

        list.appendChild(row);
    };

    window.dmsZoneRemove = function (btn) {
        const list = document.getElementById('zones-list');
        if (!list) return;

        const row = btn.closest('.zone-row');
        const rows = list.querySelectorAll('.zone-row');
        if (rows.length > 1 && row) {
            row.remove();
            return;
        }

        if (row) {
            const province = row.querySelector('.zone-province');
            const territoire = row.querySelector('.zone-territoire');
            const commune = row.querySelector('.zone-commune');
            if (province) province.value = '';
            if (territoire) territoire.innerHTML = '<option value="">-- Territoire --</option>';
            if (commune) commune.innerHTML = '<option value="">-- Commune --</option>';
        }
    };

    document.addEventListener('change', function (event) {
        const province = event.target.closest('.zone-province');
        if (province) {
            window.dmsZoneProvince(province).catch(() => {});
            return;
        }

        const territoire = event.target.closest('.zone-territoire');
        if (territoire) {
            window.dmsZoneTerritoire(territoire).catch(() => {});
        }
    });
})();
</script>
@endpush
