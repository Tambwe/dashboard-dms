@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Diagnostic SDK Empreintes</h1>
            <p class="text-gray-600 mt-1">Captures AJAX avec rapport en temps réel.</p>
        </div>
        <a href="{{ route('households.index') }}"
           class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
            &larr; Retour
        </a>
    </div>

    {{-- Parametres --}}
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Parametres du test</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de captures</label>
                <input type="number" id="cfg-attempts" min="1" max="30" value="5"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pose / Object ID</label>
                <select id="cfg-objectId" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="22" selected>22 - Flat Left Four Fingers</option>
                    <option value="21">21 - Flat Two Thumbs</option>
                    <option value="23">23 - Flat Right Four Fingers</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Timeout capture (sec)</label>
                <input type="number" id="cfg-timeout" min="10" max="90" value="30"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button id="btn-start"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                    &#9654; Lancer le test
                </button>
                <button id="btn-stop" disabled
                        class="flex-1 bg-red-500 hover:bg-red-600 disabled:opacity-40 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                    &#9632; Arreter
                </button>
            </div>
        </div>
    </div>

    {{-- Status --}}
    <div id="status-box" class="hidden bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <span id="status-text" class="text-blue-800 font-medium">Initialisation...</span>
    </div>

    {{-- Compteurs --}}
    <div id="summary-box" class="hidden grid grid-cols-2 md:grid-cols-6 gap-3 mb-6">
        <div class="bg-blue-50 rounded-lg p-3 text-center">
            <p class="text-xs text-blue-600">Progression</p>
            <p class="text-xl font-bold text-blue-800"><span id="cnt-done">0</span>/<span id="cnt-total">0</span></p>
        </div>
        <div class="bg-green-50 rounded-lg p-3 text-center">
            <p class="text-xs text-green-600">Avec template</p>
            <p class="text-xl font-bold text-green-800" id="cnt-template">0</p>
        </div>
        <div class="bg-red-50 rounded-lg p-3 text-center">
            <p class="text-xs text-red-600">Low quality</p>
            <p class="text-xl font-bold text-red-800" id="cnt-lowq">0</p>
        </div>
        <div class="bg-yellow-50 rounded-lg p-3 text-center">
            <p class="text-xs text-yellow-600">Timeout</p>
            <p class="text-xl font-bold text-yellow-800" id="cnt-timeout">0</p>
        </div>
        <div class="bg-orange-50 rounded-lg p-3 text-center">
            <p class="text-xs text-orange-600">FetchedValue</p>
            <p class="text-xl font-bold text-orange-800" id="cnt-fetched">0</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-3 text-center">
            <p class="text-xs text-purple-600">Latence moy.</p>
            <p class="text-xl font-bold text-purple-800"><span id="cnt-avg">0</span> ms</p>
        </div>
    </div>

    {{-- Verdict --}}
    <div id="verdict-box" class="hidden rounded-xl p-4 mb-6 text-center">
        <p id="verdict-text" class="text-2xl font-bold"></p>
        <p id="verdict-sub" class="text-sm mt-1"></p>
    </div>

    {{-- Tableau --}}
    <div id="table-box" class="hidden bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Rapport detaille</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">#</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Activation</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">HTTP</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Templates</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Low Q.</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">FetchedValue</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Timeout</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Duree</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Erreur</th>
                    </tr>
                </thead>
                <tbody id="result-tbody" class="divide-y divide-gray-100"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    var btnStart  = document.getElementById('btn-start');
    var btnStop   = document.getElementById('btn-stop');
    var statusBox = document.getElementById('status-box');
    var statusTxt = document.getElementById('status-text');
    var summaryBox = document.getElementById('summary-box');
    var verdictBox = document.getElementById('verdict-box');
    var tableBox  = document.getElementById('table-box');
    var tbody     = document.getElementById('result-tbody');
    function el(id) { return document.getElementById(id); }

    var running = false;
    var stopRequested = false;
    var stats = {};

    function resetStats(total) {
        stats = { done: 0, total: total, template: 0, lowq: 0, timeout: 0, fetched: 0, msTotal: 0 };
        el('cnt-total').textContent = total;
        ['done','template','lowq','timeout','fetched'].forEach(function(k){ el('cnt-'+k).textContent = 0; });
        el('cnt-avg').textContent = '0';
        tbody.innerHTML = '';
    }

    function updateSummary(r) {
        stats.done++;
        if (r.templateCount > 0)   stats.template++;
        if (r.lowQualityCount > 0) stats.lowq++;
        if (r.timeout)             stats.timeout++;
        if (r.fetchedValue)        stats.fetched++;
        stats.msTotal += (r.captureMs || 0);
        el('cnt-done').textContent     = stats.done;
        el('cnt-template').textContent = stats.template;
        el('cnt-lowq').textContent     = stats.lowq;
        el('cnt-timeout').textContent  = stats.timeout;
        el('cnt-fetched').textContent  = stats.fetched;
        el('cnt-avg').textContent      = Math.round(stats.msTotal / stats.done);
    }

    function appendRow(n, r) {
        var ok = function(v) { return v ? '<span class="text-green-700 font-medium">OK</span>' : '<span class="text-red-700 font-medium">KO</span>'; };
        var yn = function(v, bad) { return v ? '<span class="'+(bad?'text-red-700':'text-green-700')+' font-medium">Oui</span>' : '<span class="text-gray-400">Non</span>'; };
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td class="px-4 py-2 text-gray-700">'+n+'</td>'+
            '<td class="px-4 py-2">'+ok(r.activateOk)+'</td>'+
            '<td class="px-4 py-2">'+ok(r.httpOk)+'</td>'+
            '<td class="px-4 py-2 font-medium '+(r.templateCount>0?'text-green-700':'text-gray-500')+'">'+r.templateCount+'/'+r.fingerCount+'</td>'+
            '<td class="px-4 py-2 '+(r.lowQualityCount>0?'text-red-700 font-medium':'text-gray-400')+'">'+(r.lowQualityCount||'&mdash;')+'</td>'+
            '<td class="px-4 py-2">'+yn(r.fetchedValue,true)+'</td>'+
            '<td class="px-4 py-2">'+yn(r.timeout,true)+'</td>'+
            '<td class="px-4 py-2 text-gray-600">'+(r.captureMs||0)+' ms</td>'+
            '<td class="px-4 py-2 text-red-600 text-xs">'+(r.error||'&mdash;')+'</td>';
        tbody.appendChild(tr);
    }

    function showVerdict() {
        var rate = stats.total > 0 ? Math.round(stats.template * 100 / stats.total) : 0;
        var label, cls;
        if (rate >= 80)      { label = 'BON';      cls = 'bg-green-50 text-green-800 border border-green-200'; }
        else if (rate >= 30) { label = 'INSTABLE'; cls = 'bg-yellow-50 text-yellow-800 border border-yellow-200'; }
        else                 { label = 'CRITIQUE'; cls = 'bg-red-50 text-red-800 border border-red-200'; }
        verdictBox.className = 'rounded-xl p-4 mb-6 text-center ' + cls;
        el('verdict-text').textContent = label + ' - ' + rate + '% captures avec template';
        el('verdict-sub').textContent  = stats.template+'/'+stats.total+' reussies, '+stats.lowq+' low quality, '+stats.timeout+' timeout, latence moy '+ Math.round(stats.msTotal/Math.max(1,stats.total))+' ms';
        verdictBox.classList.remove('hidden');
    }

    async function runTest() {
        var attempts = Math.max(1, Math.min(30, parseInt(el('cfg-attempts').value) || 5));
        var objectId = parseInt(el('cfg-objectId').value) || 22;
        var timeout  = Math.max(10, Math.min(90, parseInt(el('cfg-timeout').value) || 30));
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var captureUrl = '{{ route("households.fingerprint-sdk-test.capture") }}';

        running = true;
        stopRequested = false;
        btnStart.disabled = true;
        btnStop.disabled  = false;
        statusBox.classList.remove('hidden');
        summaryBox.classList.remove('hidden');
        tableBox.classList.remove('hidden');
        verdictBox.classList.add('hidden');
        resetStats(attempts);

        for (var i = 1; i <= attempts; i++) {
            if (stopRequested) {
                statusTxt.textContent = 'Test arrete apres ' + (i-1) + ' captures.';
                break;
            }
            statusTxt.textContent = 'Capture ' + i + '/' + attempts + ' - Posez la main sur le scanner...';

            var r;
            try {
                var controller = new AbortController();
                var tid = setTimeout(function(){ controller.abort(); }, (timeout + 15) * 1000);
                var resp = await fetch(captureUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ objectId: objectId, timeout: timeout }),
                    signal: controller.signal
                });
                clearTimeout(tid);
                if (!resp.ok) {
                    r = { activateOk:false, httpOk:false, captureMs:0, fingerCount:0, templateCount:0, lowQualityCount:0, fetchedValue:false, timeout:false, error:'HTTP '+resp.status };
                } else {
                    r = await resp.json();
                    if (r.fatal) { statusTxt.textContent = 'Erreur fatale : ' + r.error; break; }
                }
            } catch(e) {
                r = { activateOk:false, httpOk:false, captureMs:0, fingerCount:0, templateCount:0, lowQualityCount:0, fetchedValue:false, timeout:true, error:String(e.message||'Timeout reseau') };
            }

            appendRow(i, r);
            updateSummary(r);
        }

        statusBox.classList.add('hidden');
        showVerdict();
        btnStart.disabled = false;
        btnStop.disabled  = true;
        running = false;
    }

    btnStart.addEventListener('click', function() { if (!running) runTest(); });
    btnStop.addEventListener('click',  function() { stopRequested = true; });
})();
</script>
@endsection
