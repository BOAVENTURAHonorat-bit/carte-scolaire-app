@php
    $guardians = $student->guardians;
   $penaltyHtml = preg_replace(
    '/(\d[\d\s]*\s?F\s?CFA)/i',
    '<strong style="color: #ED1C24;">$1</strong>',
    e($school->late_penalty_note)
);
    $isPreview = $preview ?? false;
    $isDraftPreview = $draftPreview ?? false;
    // Identifiant unique par objet PHP plutôt que par id en base : un élève pas encore
    // enregistré (aperçu en direct) a 3 accompagnateurs non persistés dont $guardian->id
    // vaut systématiquement null — en s'appuyant dessus, les 3 cases CSS ciblaient le même
    // sélecteur #guardian-photo- et la dernière photo ajoutée écrasait visuellement les
    // précédentes. spl_object_id reste unique même sans id, et sans collision entre élèves
    // différents dans un aperçu multi-cartes.
@endphp
@if ($isPreview)
    @foreach ($guardians as $guardian)
        @if ($guardian->photo_uri)
            <style>
                #guardian-photo-{{ spl_object_id($guardian) }} {
                    background-image: url('{{ $guardian->photo_uri }}');
                    background-position: {{ $guardian->photo_position_x ?? 50 }}% {{ $guardian->photo_position_y ?? 50 }}%;
                }
            </style>
        @endif
    @endforeach
@endif
<div class="card verso" style="{{ $pageBreak ? 'page-break-after: always;' : '' }}">
    <div class="verso-border"></div>
    <div class="verso-inner">
    <div class="verso-table">
        @for ($i = 0; $i < 3; $i++)
            @php
                $guardian = $guardians[$i] ?? null;
                $guardianPosX = $guardian->photo_position_x ?? 50;
                $guardianPosY = $guardian->photo_position_y ?? 50;
            @endphp
            <div class="guardian-cell">
                @if ($isPreview)
                    <div class="frame" @if ($guardian && $guardian->photo_uri) id="guardian-photo-{{ spl_object_id($guardian) }}" @endif
                        @if ($guardian && $guardian->photo_uri && ! $isDraftPreview)
                            x-data="cardPhotoDrag('guardian', {{ $guardian->id }}, {{ $guardianPosX }}, {{ $guardianPosY }})"
                            @mousedown="startDrag($event)" @mousemove.window="onDrag($event)" @mouseup.window="endDrag()"
                            @touchstart="startDrag($event)" @touchmove.window="onDrag($event)" @touchend.window="endDrag()"
                            :style="`background-position: ${posX}% ${posY}%;`"
                        @endif
                    ></div>
                @else
                    <div class="frame">
                        @if ($guardian && $guardian->photo_uri)
                            <img src="{{ $guardian->photo_uri }}" alt="photo accompagnateur">
                        @endif
                    </div>
                @endif
                @if ($guardian)
                    <div class="guardian-name">{{ $guardian->first_name }} {{ $guardian->last_name }}</div>
                @endif
            </div>
        @endfor
    </div>

    <div class="verso-footer">
        <div class="verso-footer-inner">
            <div class="note">
                <span class="nb">NB</span> : Tout retrait d'enfants est subordonné de la carte de sécurité portant la photo de l'accompagnateur.
            </div>
            <div class="exit-hours">
                <span class="lbl">Heures de Sortie</span> : {{ $school->exit_hours }}
            </div>
            <div class="penalty">
                {!! $penaltyHtml !!}
            </div>
        </div>
    </div>
    </div>
</div>
