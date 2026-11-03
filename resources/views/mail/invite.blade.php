<x-mail::message>
# Te invitaron a Sportiva

@if ($clubName)
Te invitaron a formar parte de **{{ $clubName }}** en Sportiva. Para activar tu cuenta, hacé clic en el botón y elegí tu contraseña:
@else
Te invitaron a formar parte de Sportiva. Para activar tu cuenta, hacé clic en el botón y elegí tu contraseña:
@endif

<x-mail::button :url="$acceptUrl">
Elegir contraseña
</x-mail::button>

Este enlace vence en 72 horas. Si no esperabas esta invitación, podés ignorar este correo.

El equipo de Sportiva
</x-mail::message>

