@php
    $schoolClass = $student->schoolClass;
    $isMaternelle = str_starts_with(strtolower($schoolClass->level ?? ''), 'maternelle');
    $studentPosX = $student->photo_position_x ?? 50;
    $studentPosY = $student->photo_position_y ?? 50;
    $studentPhotoId = 'student-photo-'.$student->id;
    $isPreview = $preview ?? false;
@endphp
@if ($isPreview && $student->photo_uri)
    <style>
        #{{ $studentPhotoId }} {
            background-image: url('{{ $student->photo_uri }}');
            background-position: {{ $studentPosX }}% {{ $studentPosY }}%;
        }
    </style>
@endif
@if ($isPreview && $school->representative_photo_uri)
    <style>
        #representative-photo-{{ $student->id }} {
            background-image: url('{{ $school->representative_photo_uri }}');
        }
    </style>
@endif
@if ($school->logo_uri)
    <style>
        #logo-ecole-{{ $student->id }} {
            background-image: url('{{ $school->logo_uri }}');
        }
    </style>
@endif
@if ($school->watermark_uri)
    {{-- background-image doit passer par un bloc <style>, pas par un attribut style="" en ligne :
         dompdf ne rend pas les data-URI (base64) trop longues quand elles sont dans un attribut HTML. --}}
    <style>
        .filigrane-{{ $student->id }}::before {
            background-image: url('{{ $school->watermark_uri }}');
        }
    </style>
@endif
<div class="card recto @if ($school->watermark_uri) filigrane-{{ $student->id }} @endif" style="{{ $pageBreak ? 'page-break-after: always;' : '' }}">
    <div class="bandeau-ecole" style="background: {{ $school->primary_color }};">
        <div class="nom-ecole">CSCB {{ strtoupper($school->name) }}</div>
    </div>

    @if ($isPreview)
        <div class="medaillon-directeur" @if ($school->representative_photo_uri) id="representative-photo-{{ $student->id }}" @endif></div>
    @else
        <div class="medaillon-directeur">
            @if ($school->representative_photo_uri)
                <img src="{{ $school->representative_photo_uri }}" alt="responsable">
            @endif
        </div>
    @endif

    @if ($isPreview)
        <div class="photo-eleve" id="{{ $studentPhotoId }}"
            @if ($student->photo_uri)
                x-data="cardPhotoDrag('student', null, {{ $studentPosX }}, {{ $studentPosY }})"
                @mousedown="startDrag($event)" @mousemove.window="onDrag($event)" @mouseup.window="endDrag()"
                @touchstart="startDrag($event)" @touchmove.window="onDrag($event)" @touchend.window="endDrag()"
                :style="`background-position: ${posX}% ${posY}%;`"
            @endif
        ></div>
    @else
        <div class="photo-eleve">
            @if ($student->photo_uri)
                <img src="{{ $student->photo_uri }}" alt="photo élève">
            @endif
        </div>
    @endif

    <div class="bandeau-titre">
        <span>Carte de sécurité</span>
    </div>
    <div class="bandeau-niveau">
        <span>{{ $isMaternelle ? 'MATERNEL' : 'PRIMAIRE' }}</span>
    </div>

    @php
        // Champs à largeur fixe (dompdf ne supporte pas de "shrink-to-fit" en CSS) :
        // on réduit la police au lieu de laisser le texte coupé par overflow:hidden,
        // pour les noms composés ou les élèves ayant 3 prénoms ou plus.
        $classeLabel = trim(($schoolClass->level ?? '').' '.($schoolClass->section ?? ''));
        $nomFontSize = \App\Support\CardText::fitFontSize($student->last_name, 6.3, 16, 4.0);
        $prenomFontSize = \App\Support\CardText::fitFontSize($student->first_name, 6.3, 16, 4.0);
        $classeFontSize = \App\Support\CardText::fitFontSize($classeLabel, 6.3, 10, 4.0);
        $telephoneFontSize = \App\Support\CardText::fitFontSize($student->parent_phone, 6.3, 14, 4.5);
    @endphp
    <div class="champs">
        <div class="champ nom">
            <span class="label">Nom (s) :</span>
            <span class="valeur"><span class="valeur-inner" style="font-size: {{ $nomFontSize }}pt;">{{ $student->last_name }}</span></span>
        </div>
        <div class="champ prenom">
            <span class="label">Prénom :</span>
            <span class="valeur"><span class="valeur-inner" style="font-size: {{ $prenomFontSize }}pt;">{{ $student->first_name }}</span></span>
        </div>
        <div class="champ age">
            <span class="label">Age :</span>
            <span class="valeur"><span class="valeur-inner">{{ $student->age !== null ? $student->age.' ans' : '' }}</span></span>
        </div>
        <div class="champ classe">
            <span class="label">Classe :</span>
            <span class="valeur"><span class="valeur-inner" style="font-size: {{ $classeFontSize }}pt;">{{ $classeLabel }}</span></span>
        </div>
        <div class="champ telephone">
            <span class="label">Tél :</span>
            <span class="valeur"><span class="valeur-inner" style="font-size: {{ $telephoneFontSize }}pt;">{{ $student->parent_phone }}</span></span>
        </div>
    </div>

    @if ($school->stamp_uri)
        <div class="cachet"><img src="{{ $school->stamp_uri }}" alt="cachet, signature et nom de la directrice"></div>
    @endif

    @if ($isPreview)
        <div class="logo-ecole" @if ($school->logo_uri) id="logo-ecole-{{ $student->id }}" @endif></div>
    @else
        <div class="logo-ecole">
            @if ($school->logo_uri)
                <img src="{{ $school->logo_uri }}" alt="logo">
            @endif
        </div>
    @endif

    <div class="separateur"></div>

    <div class="footer">
        @if ($school->email || $school->address)
            <p>
                @if ($school->email)Email : {{ $school->email }}@endif
                @if ($school->address)&nbsp;&nbsp;{{ $school->address }}@endif
            </p>
        @endif
        @if ($school->phone)
            <p>Tél : {{ $school->phone }}</p>
        @endif
    </div>
</div>
