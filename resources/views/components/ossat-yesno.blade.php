{{-- Composant x-ossat-yesno : select Oui/Non pour les champs booléens --}}
@props(['name', 'label', 'value' => null])

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    <select name="{{ $name }}"
        {{ $attributes->merge(['class' => 'w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500']) }}>
        <option value="">--</option>
        <option value="1" {{ old($name, $value) == '1' || old($name, $value) === true ? 'selected' : '' }}>Oui</option>
        <option value="0" {{ old($name, $value) == '0' && old($name, $value) !== null ? 'selected' : '' }}>Non</option>
    </select>
</div>
