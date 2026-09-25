{{-- Aperçu en direct de la vraie carte (recto + verso) : rendu côté serveur avec
     le même template que le PDF final, rafraîchi automatiquement pendant la saisie
     (débouncé). Doit être rendu à l'intérieur d'un ancêtre portant x-data="cardServerPreview(...)"
     qui englobe aussi le formulaire (cf students/create.blade.php) — les écouteurs y sont posés
     pour capter les événements du formulaire par remontée (bubbling). --}}
<div class="order-first lg:order-none lg:sticky lg:top-6 space-y-2">
    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide flex items-center gap-2">
        Aperçu de la carte
        <span x-show="loading" x-cloak class="inline-block h-3 w-3 rounded-full border-2 border-indigo-200 border-t-indigo-600 animate-spin"></span>
    </p>

    <div class="rounded-xl border border-gray-200 bg-gray-50 overflow-hidden">
        <iframe :srcdoc="html" class="w-full block" style="height: 470px; border: 0;" title="Aperçu de la carte" sandbox=""></iframe>
    </div>

    <p class="text-[11px] text-gray-400">
        Aperçu fidèle au vrai gabarit — la carte PDF imprimable reste accessible après enregistrement via "Carte PDF".
    </p>
</div>
