/**
 * Fluidité globale de l'application : barre de progression sur toute navigation
 * (lien classique ou formulaire), feedback visuel "en cours d'envoi" sur les
 * boutons, et auto-soumission débouncée des formulaires de filtre/recherche
 * (marqués avec data-autosubmit) pour éviter d'avoir à cliquer "Filtrer".
 *
 * Pas de dépendance externe : ce fichier remplace un besoin type NProgress/htmx
 * pour les navigations classiques Blade (rechargement de page), en gardant le
 * feedback instantané attendu d'une app plus "fluide".
 */

function startProgressBar() {
    const bar = document.getElementById('nprogress-bar');
    if (!bar) return;

    bar.style.transition = 'none';
    bar.style.width = '0%';
    bar.classList.add('is-active');

    // Force reflow pour que la transition suivante s'applique bien.
    void bar.offsetWidth;
    bar.style.transition = '';

    requestAnimationFrame(() => {
        bar.style.width = '80%';
    });
}

function finishProgressBar() {
    const bar = document.getElementById('nprogress-bar');
    if (!bar) return;

    bar.style.width = '100%';
    window.setTimeout(() => {
        bar.classList.remove('is-active');
        window.setTimeout(() => {
            bar.style.width = '0%';
        }, 300);
    }, 200);
}

document.addEventListener('DOMContentLoaded', () => {
    finishProgressBar();

    // Navigation via liens internes classiques (pas les ancres, pas les liens externes/nouveaux onglets).
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link) return;
        if (link.target === '_blank' || link.hasAttribute('download')) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return;

        const url = new URL(link.href, window.location.href);
        const isSamePageAnchor = url.pathname === window.location.pathname && url.hash;
        if (isSamePageAnchor || url.origin !== window.location.origin) return;

        startProgressBar();
    });

    // Envoi de formulaire : barre de progression + état "chargement" sur le bouton cliqué.
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (form.dataset.noProgress === 'true') return;

        startProgressBar();

        const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitter) {
            submitter.dataset.loading = 'true';
            submitter.disabled = true;
        }
    });

    window.addEventListener('pageshow', (event) => {
        // Revenir en arrière avec le bouton précédent restaure un formulaire figé (bfcache) : on réactive tout.
        if (event.persisted) {
            document.querySelectorAll('[data-loading="true"]').forEach((el) => {
                el.dataset.loading = 'false';
                el.disabled = false;
            });
            finishProgressBar();
        }
    });

    // Auto-soumission débouncée des formulaires de filtre/recherche.
    document.querySelectorAll('form[data-autosubmit]').forEach((form) => {
        const delay = parseInt(form.dataset.autosubmit, 10) || 500;
        let timer = null;

        const submit = () => {
            form.classList.remove('filter-pending');
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        };

        form.querySelectorAll('input[type="text"], input[type="search"]').forEach((input) => {
            input.addEventListener('input', () => {
                form.classList.add('filter-pending');
                clearTimeout(timer);
                timer = setTimeout(submit, delay);
            });
        });

        form.querySelectorAll('select').forEach((select) => {
            select.addEventListener('change', () => {
                clearTimeout(timer);
                submit();
            });
        });
    });
});
