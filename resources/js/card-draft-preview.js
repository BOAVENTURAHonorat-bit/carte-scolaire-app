/**
 * Aperçu de carte "en direct" à côté du formulaire d'ajout/modification d'élève :
 * à chaque modification (débouncée), envoie l'état actuel du formulaire au serveur
 * qui rend le VRAI template de carte (celui du PDF final) et renvoie le HTML, affiché
 * dans un <iframe> isolé. Fidèle plutôt qu'une approximation, au prix d'un léger délai.
 */
window.cardServerPreview = function (previewUrl) {
    return {
        html: '',
        loading: false,
        timer: null,

        schedule() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.refresh(), 500);
        },

        refresh() {
            const form = this.$root.querySelector('form[data-card-form]') || document.querySelector('form[data-card-form]');
            if (!form) return;

            this.loading = true;

            fetch(previewUrl, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'text/html',
                },
            })
                .then((response) => (response.ok ? response.text() : Promise.reject()))
                .then((html) => {
                    this.html = html;
                    this.loading = false;
                })
                .catch(() => {
                    this.loading = false;
                });
        },
    };
};
