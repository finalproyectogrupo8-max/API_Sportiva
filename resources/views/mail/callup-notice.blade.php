<x-mail::message>
# ¡Hola!

@if ($mode === \App\Club\Callups\CallupNoticeMode::Updated)
**Hubo cambios en el partido.** Revisá los datos nuevos.

@elseif ($mode === \App\Club\Callups\CallupNoticeMode::Reminder)
Todavía no nos confirmaste si va. Te pedimos que respondas así armamos el equipo.

@endif
@if (count($players) === 1)
{{ $players[0]['name'] }} está convocado/a para el partido de {{ $categoryName }}.
@else
{{ implode(', ', array_slice(array_column($players, 'name'), 0, -1)) }} y {{ $players[count($players) - 1]['name'] }} están convocados/as para el partido de {{ $categoryName }}.
@endif

@foreach ($summary as $line)
- {{ $line }}
@endforeach

@foreach ($players as $player)
<x-mail::button :url="$player['url']">
Confirmar si va {{ $player['name'] }}
</x-mail::button>
@endforeach

{{ $clubName }}
</x-mail::message>
