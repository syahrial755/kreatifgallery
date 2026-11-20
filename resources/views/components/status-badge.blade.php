@props(['status'])
@php
    $map = [
        'menunggu' => ['Menunggu', 'bg-warning/15 text-[#a8680c]'],
        'disetujui' => ['Disetujui', 'bg-success/15 text-success'],
        'ditolak' => ['Ditolak', 'bg-danger/15 text-danger'],
        'baru' => ['Baru', 'bg-warning/15 text-[#a8680c]'],
        'selesai' => ['Selesai', 'bg-success/15 text-success'],
    ];
    [$label, $cls] = $map[$status] ?? [ucfirst($status), 'bg-gray-100 text-muted'];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold $cls"]) }}>{{ $label }}</span>
