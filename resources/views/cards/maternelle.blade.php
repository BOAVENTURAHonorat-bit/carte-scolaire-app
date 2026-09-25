<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    @if ($preview ?? false)
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @endif
    <title>Cartes de sécurité - Maternelle</title>
    <style>
        @page {
            margin: 0;
            size: 85.6mm 54mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            background: #fff;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1a1a1a;
        }

        .card {
            width: 85.6mm;
            height: 54mm;
            position: relative;
            overflow: hidden;
            background: #fff;
            page-break-inside: avoid;
        }


        /* ---------- RECTO — converti au mm depuis le gabarit de référence CSCB (canvas 1063x705px) ---------- */
        /* absolute positioning uniquement : évite le bug de pagination dompdf sur les tables/float */

        .bandeau-ecole {
            position: absolute;
            top: 0;
            left: 0;
            width: 85.6mm;
            height: 7.66mm;
            color: #fff;
            display: table;
        }

        .bandeau-ecole .nom-ecole {
            /* vertical-align:middle est fiable pour les petites hauteurs (bandeau-titre/niveau,
           5.21mm) mais casse au-delà (testé : dès 7.66mm le texte remonte tout en haut dans
           dompdf) — ici on force donc un alignement haut + un padding-top calculé à la main
           pour centrer visuellement le texte dans les 7.66mm de hauteur. */
            display: table-cell;
            vertical-align: top;
            padding-top: 1.18mm;
            padding-left: 2mm;
            padding-right: 14.5mm;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Filigrane : pseudo-élément ::before qui couvre toute la carte, image
       très agrandie et pré-floutée (côté serveur, cf. ImageDataUri::blurredDataUri
       — dompdf ne supporte pas `filter`) pour une texture douce plutôt qu'un
       logo net. Premier enfant en ordre de peinture : les autres éléments de
       la carte sont en position:absolute sans z-index, ils s'affichent donc
       naturellement par-dessus. */
        .card.recto::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-repeat: no-repeat;
            background-size: 201.4mm 285.7mm;
            background-position: -96.5mm -123.9mm;
            opacity: 0.2;
            pointer-events: none;
        }

        .medaillon-directeur {
            position: absolute;
            top: 1.3mm;
            right: 1.77mm;
            width: 11.78mm;
            height: 11.78mm;
            /* Le sceau (logo-dagnon-cropped.png) est déjà un cercle complet avec sa propre
           bordure bleue dessinée dedans — pas de bordure CSS ajoutée ici pour éviter un
           double anneau. */
            background-size: cover;
            background-position: 50% 50%;
            background-repeat: no-repeat;
            border-radius: 50%;
            overflow: hidden;
            z-index: 5;
        }

        .medaillon-directeur img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
        }

        .photo-eleve {
            position: absolute;
            top: 11.87mm;
            left: 4.83mm;
            width: 21.34mm;
            height: 26.04mm;
            border: 0.25mm solid #ccc;
            background-color: #ddd;
            background-size: cover;
            background-position: 50% 50%;
            background-repeat: no-repeat;
            overflow: hidden;
        }

        .photo-eleve img {
            width: 100%;
            height: 100%;
        }

        .bandeau-titre {
            position: absolute;
            top: 12.03mm;
            left: 29.96mm;
            width: 43.48mm;
            height: 5.21mm;
            background: #ED1C24;
            border-radius: 1.18mm;
            display: table;
            white-space: nowrap;
            overflow: hidden;
        }

        .bandeau-titre span {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            color: #fff;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08mm;
        }

        .bandeau-niveau {
            position: absolute;
            top: 17.24mm;
            left: 34.79mm;
            width: 30.6mm;
            height: 3.83mm;
            background: #000;
            border-radius: 0.94mm;
            display: table;
            white-space: nowrap;
            overflow: hidden;
        }

        .bandeau-niveau span {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            color: #fff;
            font-size: 6.2pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.24mm;
        }

        .champs {
            position: absolute;
            top: 21.85mm;
            left: 31.41mm;
            width: 48.32mm;
            z-index: 7;
        }

        .champ {
            display: table;
            width: 100%;
            margin-bottom: 0.85mm;
        }

        .champ .label {
            display: table-cell;
            white-space: nowrap;
            font-size: 5.8pt;
            font-weight: bold;
            color: #000;
        }

        .champ .valeur {
            display: table-cell;
            width: 100%;
            font-family: 'DejaVu Sans Mono', monospace;
            font-weight: bold;
            font-size: 6.3pt;
            color: #000;
            text-transform: uppercase;
            padding: 0 0.5mm 0.25mm 0.8mm;
        }

        .champ .valeur-inner {
            display: inline-block;
            width: 100%;
            border-bottom: 0.2mm dotted #000;
            padding-bottom: 0.25mm;
            white-space: nowrap;
            overflow: hidden;
        }

        .champ.nom .valeur-inner,
        .champ.prenom .valeur-inner {
            width: 26.57mm;
        }

        .champ.age .valeur-inner,
        .champ.classe .valeur-inner {
            width: 13mm;
        }

        .champ.telephone .valeur-inner {
            width: 24mm;
        }


        .cachet {
            /* Image unique regroupant cachet + signature + nom de la directrice
               (assets/cachet-signature-nom.png, ratio ~1.57:1). */
            position: absolute;
            top: 33.5mm;
            left: 55mm;
            width: 27mm;
            height: 17.16mm;
            z-index: 6;
        }

        .cachet img {
            width: 80%;
            height: 80%;
        }

        
        .logo-ecole {
            position: absolute;
            top: 40.38mm;
            left: 3mm;
            width: 11.69mm;
            height: 11.69mm;
            background-size: contain;
            background-position: 50% 50%;
            background-repeat: no-repeat;
            border-radius: 50%;
            overflow: hidden;
            z-index: 5;
        }

        .logo-ecole img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .separateur {
            position: absolute;
            top: 45.96mm;
            left: 13.69mm;
            right: 3.62mm;
            height: 0.31mm;
            background: #1E4FCB;
        }

        .footer {
            position: absolute;
            top: 46.88mm;
            left: 1.5mm;
            right: 1.5mm;
            text-align: center;
        }

        .footer p {
            margin-bottom: 0.46mm;
            font-size: 4.5pt;
            font-weight: bold;
            color: #000;
        }

        .footer p:last-child {
            margin-bottom: 0;
        }

        /* ---------- VERSO ---------- */

        .verso-border {
            position: absolute;
            top: 2.6mm;
            left: 2.6mm;
            right: 2.6mm;
            bottom: 2.6mm;
            
        }

        .verso-inner {
            padding-top: 3.6mm;
            padding-bottom: 3.6mm;
        }

        .verso-table {
            display: table;
            width: 78.4mm;
            margin-left: 3.6mm;
            margin-right: 3.6mm;
            table-layout: fixed;
            height: 30mm;
            border-collapse: separate;
            border-spacing: 1.2mm 0;
        }

        .guardian-cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            border: 0.25mm solid #333;
        }

        .guardian-cell .frame {
            width: 100%;
            height: 26.4mm;
            background-size: cover;
            background-position: 50% 50%;
            background-repeat: no-repeat;
            overflow: hidden;
        }

        .guardian-cell .frame img {
            width: 100%;
            height: 100%;
        }

        .guardian-cell .guardian-name {
            font-size: 5pt;
            margin-top: 0.5mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .verso-footer {
            margin-left: 3.6mm;
            margin-right: 3.6mm;

            padding-top: 1.8mm;
        }

        .verso-footer-inner {
            width: 88%;
            margin: 0 auto;
            padding-left: 3mm;
            text-align: left;
        }

        .note {
            font-size: 5pt;
            font-style: italic;
            line-height: 2.3mm;
        }

        .note .nb {
            font-weight: bold;
            text-decoration: underline;
            font-style: normal;
        }

        .verso-footer .exit-hours,
        .verso-footer .penalty {
            font-size: 5.6pt;
            font-style: italic;
        }

        .verso-footer .exit-hours {
            padding-top: 0.6mm;
            margin-top: 0.6mm;
        }

        .verso-footer .penalty {
            text-align: center;
            color: #000;
            margin-top: 1mm;
        }

        .exit-hours .lbl {
            font-weight: bold;
            text-decoration: underline;
            font-style: normal;
        }
    </style>
    @if ($preview ?? false)
    <style>
        html,
        body {
            background: #e5e7eb;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 24px 0;
        }

        .preview-zoom {
            zoom: 2.4;
        }

        .preview-zoom .card {
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.18);
            border-radius: 1.5mm;
            margin-bottom: 6mm;
        }

        .preview-zoom .card:last-child {
            margin-bottom: 0;
        }

        .preview-back {
            position: fixed;
            top: 16px;
            left: 16px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff;
            color: #374151;
            font-family: sans-serif;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            text-decoration: none;
        }

        .preview-back:hover {
            background: #f3f4f6;
        }

        .preview-modify,
        .preview-save {
            position: fixed;
            top: 16px;
            right: 16px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #4f46e5;
            color: #fff;
            font-family: sans-serif;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .preview-modify:hover,
        .preview-save:hover {
            background: #4338ca;
        }

        .preview-modify.is-active {
            background: #374151;
        }

        .preview-save {
            right: 140px;
            background: #16a34a;
        }

        .preview-save:hover {
            background: #15803d;
        }

        .preview-save:disabled {
            opacity: 0.6;
            cursor: default;
        }

        .preview-hint {
            position: fixed;
            top: 60px;
            right: 16px;
            max-width: 220px;
            background: #111827;
            color: #fff;
            font-family: sans-serif;
            font-size: 12px;
            line-height: 1.4;
            padding: 8px 12px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .edit-mode-on .photo-frame,
        .edit-mode-on .guardian-cell .frame {
            cursor: move;
            outline: 2px dashed #4f46e5;
            outline-offset: 1px;
        }
    </style>
    @endif
    @if ($draftPreview ?? false)
    <style>
        html, body {
            background: transparent;
        }

        body {
            min-height: 0;
            padding: 0;
            gap: 12px;
        }

        .preview-zoom {
            zoom: 1;
        }

        .preview-zoom .card {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
            margin-bottom: 12px;
        }
    </style>
    @endif
</head>

<body @if ($preview ?? false) x-data :class="$store.cardEdit.editMode ? 'edit-mode-on' : ''" @endif>
    @if ($preview ?? false)
    @vite(['resources/js/card-preview.js'])
    @unless ($draftPreview ?? false)
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="preview-back" onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }">
        &larr; Retour
    </a>
    @if ($students->count() === 1)
    <button type="button" class="preview-modify" :class="{ 'is-active': $store.cardEdit.editMode }"
        @click="$store.cardEdit.editMode = !$store.cardEdit.editMode"
        x-text="$store.cardEdit.editMode ? 'Terminer' : 'Modifier'">
        Modifier
    </button>
    <button type="button" class="preview-save" x-show="$store.cardEdit.editMode" x-cloak
        :disabled="$store.cardEdit.saving"
        @click="saveCardPhotoPositions({{ $students->first()->id }}, '{{ route('students.photo-position', $students->first()) }}')"
        x-text="$store.cardEdit.saving ? 'Enregistrement...' : ($store.cardEdit.saved ? 'Enregistré ✓' : 'Enregistrer')">
        Enregistrer
    </button>
    <div class="preview-hint" x-show="$store.cardEdit.editMode" x-cloak>
        Glissez une photo directement sur la carte pour la recadrer, puis cliquez sur Enregistrer.
    </div>
    @endif
    @endunless
    <div class="preview-zoom">
        @endif
        @php $side = $side ?? null; @endphp
        @if ($side === 'recto')
        {{-- Export "recto uniquement" --}}
        @foreach ($students as $student)
        @include('cards.partials._recto', ['student' => $student, 'school' => $school, 'pageBreak' => ! $loop->last])
        @endforeach
        @elseif ($side === 'verso')
        {{-- Export "verso uniquement" --}}
        @foreach ($students as $student)
        @include('cards.partials._verso', ['student' => $student, 'school' => $school, 'pageBreak' => ! $loop->last])
        @endforeach
        @elseif ($groupBySide ?? false)
        {{-- Export par classe : tous les rectos d'abord, puis tous les versos --}}
        {{-- (pratique pour l'impression recto-verso : on imprime le premier lot, --}}
        {{-- on retourne la pile, puis on imprime le second lot). --}}
        @foreach ($students as $student)
        @include('cards.partials._recto', ['student' => $student, 'school' => $school, 'pageBreak' => true])
        @endforeach
        @foreach ($students as $student)
        @include('cards.partials._verso', ['student' => $student, 'school' => $school, 'pageBreak' => ! $loop->last])
        @endforeach
        @else
        @foreach ($students as $student)
        @include('cards.partials._recto', ['student' => $student, 'school' => $school, 'pageBreak' => ! ($preview ?? false), 'preview' => $preview ?? false])
        @include('cards.partials._verso', ['student' => $student, 'school' => $school, 'pageBreak' => ! $loop->last && ! ($preview ?? false), 'preview' => $preview ?? false])
        @endforeach
        @endif
        @if ($preview ?? false)
    </div>
    @endif
</body>

</html>