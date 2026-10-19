/* =====================================================================
   NMA Sanders - comportements d'interface (JavaScript vanille, leger)
   ===================================================================== */
(function () {
    'use strict';

    /** Confirmation avant toute action sensible (formulaire marque data-confirmer). */
    document.addEventListener('submit', function (evenement) {
        var formulaire = evenement.target;
        var message = formulaire.getAttribute('data-confirmer');

        if (message && !window.confirm(message)) {
            evenement.preventDefault();
            return;
        }

        // Anti double-soumission.
        var bouton = formulaire.querySelector('button[type="submit"]');
        if (bouton && !formulaire.hasAttribute('data-sans-attente')) {
            bouton.disabled = true;
            var icone = bouton.querySelector('i');
            if (icone) {
                icone.className = 'bi bi-arrow-repeat nma-spin';
            }
            window.setTimeout(function () { bouton.disabled = false; }, 8000);
        }
    }, true);

    /** Quantite +/- sur les fiches produit et le panier. */
    document.addEventListener('click', function (evenement) {
        var cible = evenement.target.closest('[data-quantite]');
        if (!cible) {
            return;
        }

        var direction = cible.getAttribute('data-quantite');
        var champ = document.getElementById(cible.getAttribute('data-cible'));
        if (!champ) {
            return;
        }

        var valeur = parseInt(champ.value, 10);
        var min = parseInt(champ.getAttribute('min') || '1', 10);
        var max = parseInt(champ.getAttribute('max') || '9999', 10);

        if (isNaN(valeur)) {
            valeur = min;
        }
        valeur += direction === 'plus' ? 1 : -1;

        champ.value = Math.max(min, Math.min(max, valeur));

        // Soumission automatique du panier si demande.
        if (champ.hasAttribute('data-auto-submit') && champ.form) {
            champ.form.submit();
        }
    });

    /** Filtres de liste : soumission des que la valeur change. */
    document.addEventListener('change', function (evenement) {
        var champ = evenement.target;
        if (champ.matches('[data-filtre-auto]') && champ.form) {
            champ.form.submit();
        }
    });

    /** Recherche locale dans un tableau (sans rechargement). */
    var recherche = document.querySelector('[data-recherche-table]');
    if (recherche) {
        recherche.addEventListener('input', function () {
            var motif = recherche.value.toLowerCase().trim();
            var table = document.querySelector(recherche.getAttribute('data-recherche-table'));
            if (!table) {
                return;
            }
            table.querySelectorAll('tbody tr').forEach(function (ligne) {
                ligne.style.display = motif === '' || ligne.textContent.toLowerCase().indexOf(motif) !== -1 ? '' : 'none';
            });
        });
    }

    /** Impression d'une zone (bon de commande, planning). */
    document.querySelectorAll('[data-imprimer]').forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            window.print();
        });
    });

    /** Comptes de demonstration : remplissage automatique du formulaire. */
    document.querySelectorAll('.nma-demo-chip').forEach(function (puce) {
        puce.addEventListener('click', function () {
            var email = document.getElementById('email');
            var mdp = document.getElementById('mot_de_passe');
            if (!email || !mdp) {
                return;
            }
            email.value = this.getAttribute('data-email') || '';
            mdp.value = this.getAttribute('data-mdp') || '';
            mdp.focus();
        });
    });

    /** Compteurs animes sur les indicateurs chiffres. */
    document.querySelectorAll('.nma-stat-value').forEach(function (bloc) {
        var texte = bloc.textContent.trim();
        var trouve = texte.match(/^([0-9][0-9\s.,]*)(.*)$/);
        if (!trouve) {
            return;
        }
        var brut = trouve[1].replace(/\s/g, '');
        var decimales = /[.,]/.test(brut) ? 1 : 0;
        var cible = parseFloat(brut.replace(',', '.'));
        if (isNaN(cible) || cible <= 0 || cible > 10000000) {
            return;
        }
        var suffixe = trouve[2] || '';
        var debut = null;
        var duree = 850;

        bloc.classList.add('nma-compteur');
        bloc.textContent = (decimales ? (0).toFixed(1) : '0') + suffixe;

        function etape(instant) {
            if (debut === null) {
                debut = instant;
            }
            var avancement = Math.min(1, (instant - debut) / duree);
            var valeur = cible * (1 - Math.pow(1 - avancement, 3));
            bloc.textContent = (decimales ? valeur.toFixed(1) : String(Math.round(valeur))) + suffixe;
            if (avancement < 1) {
                window.requestAnimationFrame(etape);
            } else {
                bloc.textContent = texte;
            }
        }

        window.requestAnimationFrame(etape);
    });

    /** Barres de progression : remplissage anime au chargement. */
    document.querySelectorAll('.nma-progress .progress-bar, .progress .progress-bar').forEach(function (barre) {
        var largeur = barre.style.width;
        if (!largeur || largeur === '0%' || largeur === '100%') {
            return;
        }
        barre.style.width = '0%';
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                barre.style.width = largeur;
            });
        });
    });

    /** Onde visuelle au clic sur les boutons principaux. */
    document.addEventListener('click', function (evenement) {
        var bouton = evenement.target.closest('.btn-nma');
        if (!bouton) {
            return;
        }
        var zone = bouton.getBoundingClientRect();
        var taille = Math.max(zone.width, zone.height);
        var onde = document.createElement('span');
        onde.className = 'nma-ripple';
        onde.style.width = taille + 'px';
        onde.style.height = taille + 'px';
        onde.style.left = (evenement.clientX - zone.left - taille / 2) + 'px';
        onde.style.top = (evenement.clientY - zone.top - taille / 2) + 'px';
        bouton.appendChild(onde);
        window.setTimeout(function () { onde.remove(); }, 650);
    });

    /** Disparition automatique des messages de succes. */
    document.querySelectorAll('.alert-success.alert-dismissible').forEach(function (alerte) {
        window.setTimeout(function () {
            alerte.classList.add('nma-part');
            window.setTimeout(function () { alerte.remove(); }, 420);
        }, 6000);
    });

    /** v3 : scene animee - parallaxe douce pilotee par la souris. */
    (function () {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        if (!document.querySelector('.nma-public') || !window.matchMedia('(hover: hover)').matches) {
            return;
        }

        var racine = document.documentElement;
        var planifie = false;

        document.addEventListener('mousemove', function (evenement) {
            if (planifie) {
                return;
            }
            planifie = true;
            window.requestAnimationFrame(function () {
                planifie = false;
                racine.style.setProperty('--nma-mx', ((evenement.clientX / window.innerWidth) * 2 - 1).toFixed(3));
                racine.style.setProperty('--nma-my', ((evenement.clientY / window.innerHeight) * 2 - 1).toFixed(3));
            });
        });
    }());

    /** v3 : cartes inclinables (perspective douce au survol). */
    (function () {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        if (!window.matchMedia('(hover: hover)').matches) {
            return;
        }

        document.querySelectorAll('.nma-tilt').forEach(function (carte) {
            carte.addEventListener('pointermove', function (evenement) {
                var zone = carte.getBoundingClientRect();
                var x = (evenement.clientX - zone.left) / zone.width - 0.5;
                var y = (evenement.clientY - zone.top) / zone.height - 0.5;
                carte.style.transform = 'perspective(760px) rotateX(' + (-y * 6).toFixed(2)
                    + 'deg) rotateY(' + (x * 8).toFixed(2) + 'deg) translateY(-3px)';
            });
            carte.addEventListener('pointerleave', function () {
                carte.style.transform = '';
            });
        });
    }());
}());
