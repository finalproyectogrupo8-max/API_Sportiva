<x-mail::message>
# {{ $subjectLine }}

Hola, {{ $recipientName }}:

{{-- El cuerpo es Markdown del club. Blade escapa el HTML antes de que se convierta el Markdown del
     mail: el formato funciona y un <script> se ve como texto. Los links javascript: los bloquea el parser. --}}
{{ $body }}

{{ $clubName }}

<x-mail::subcopy>
Recibís este mail porque estás registrado en {{ $clubName }}. Si no querés recibir más comunicados, pedíselo al club.
</x-mail::subcopy>
</x-mail::message>
