/**
 * ══════════════════════════════════════════════════════════
 *  RE7LA — Accessibilité Avancée pour Malvoyants
 *  APIs : Web Speech Synthesis + Web Speech Recognition
 *  Parcours complet : arrivée → recherche → réservation → confirmation
 * ══════════════════════════════════════════════════════════
 */
(function () {
    'use strict';

    /* ── CONFIG ─────────────────────────────────────────── */
    const C = {
        lang: 'fr-FR', rate: .92, pitch: 1.05,
        welcomeDelay: 700, welcomeDur: 4800,
        toastMs: 3500, hoverMs: 550,
    };

    /* ── STATE ──────────────────────────────────────────── */
    const S = {
        hc: false, lg: false, voice: false, ey: false,
        panelOpen: false, hoverTmr: null, speaking: false,
        reserved: { hotel: null, car: null, activity: null },
    };

    /* ═══════════════════════════════════════════════════
       HELPERS
       ═══════════════════════════════════════════════════ */
    function speak(txt, cb) {
        if (!('speechSynthesis' in window)) return;
        speechSynthesis.cancel();
        const u = new SpeechSynthesisUtterance(txt);
        u.lang = C.lang; u.rate = C.rate; u.pitch = C.pitch;
        const v = speechSynthesis.getVoices().find(v => v.lang.startsWith('fr'));
        if (v) u.voice = v;
        S.speaking = true;
        u.onend = () => { S.speaking = false; cb && cb() };
        u.onerror = () => { S.speaking = false };
        speechSynthesis.speak(u);
    }
    function stop() { speechSynthesis?.cancel(); S.speaking = false }

    function toast(msg, ico = 'fa-check-circle') {
        const t = document.getElementById('re7la-toast');
        if (!t) return;
        t.innerHTML = `<i class="fas ${ico}"></i> ${msg}`;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), C.toastMs);
    }

    function showTip(txt) {
        const el = document.getElementById('re7la-hover-tip');
        if (!el) return;
        el.innerHTML = `<i class="fas fa-volume-up"></i> ${txt}`;
        el.classList.add('show');
    }
    function hideTip() {
        const el = document.getElementById('re7la-hover-tip');
        if (el) el.classList.remove('show');
    }

    /* ═══════════════════════════════════════════════════
       1. BIENVENUE VOCALE
       ═══════════════════════════════════════════════════ */
    function initWelcome() {
        const ov = document.getElementById('re7la-welcome');
        if (!ov) return;

        // Vérifier si l'utilisateur a déjà vu le message durant cette session
        if (sessionStorage.getItem('re7la_welcome_done')) {
            ov.style.display = 'none';
            return;
        }

        // Au lieu de démarrer automatiquement (ce qui est bloqué par les navigateurs), on attend le clic
        ov.addEventListener('click', () => {
            sessionStorage.setItem('re7la_welcome_done', 'true');
            if ('speechSynthesis' in window) { speechSynthesis.getVoices(); speechSynthesis.onvoiceschanged = () => speechSynthesis.getVoices(); }

            speak("Bienvenue sur Re7la, votre plateforme de voyage en Tunisie. Utilisez le bouton Accessibilité pour activer l'aide visuelle et vocale.");

            ov.classList.add('hide');
            setTimeout(() => ov.remove(), 900);
        });
    }

    /* ═══════════════════════════════════════════════════
       2. BOUTON ACCESSIBILITÉ — HOVER & CLIC
       ═══════════════════════════════════════════════════ */
    function initBtn() {
        const btn = document.getElementById('re7la-a11y-btn');
        if (!btn) return;
        let spoke = false;
        btn.addEventListener('mouseenter', () => {
            if (!spoke) { speak("Activer les options d'accessibilité"); spoke = true; setTimeout(() => spoke = false, 8000); }
        });
        btn.addEventListener('click', () => {
            openPanel(true);
            speak("Panneau d'accessibilité ouvert. Choisissez vos options.");
        });
    }

    /* ═══════════════════════════════════════════════════
       3. PANNEAU D'OPTIONS
       ═══════════════════════════════════════════════════ */
    function openPanel(v) {
        const ov = document.getElementById('re7la-a11y-overlay');
        const pn = document.getElementById('re7la-a11y-panel');
        if (!ov || !pn) return;
        S.panelOpen = v;
        if (v) { ov.classList.add('open'); setTimeout(() => pn.classList.add('open'), 30); }
        else { pn.classList.remove('open'); setTimeout(() => ov.classList.remove('open'), 300); }
    }

    function initPanel() {
        document.getElementById('re7la-a11y-close')?.addEventListener('click', () => { openPanel(false); speak("Panneau fermé."); });
        document.getElementById('re7la-a11y-overlay')?.addEventListener('click', () => openPanel(false));
        loadPrefs();
        wire('sw-hc', 'hc', 'hc', 'Contraste élevé activé. Fond noir, texte blanc, boutons jaune vif.', 'Contraste normal rétabli.');
        wire('sw-lg', 'lg', 'lg-txt', 'Texte agrandi pour une lecture plus facile.', 'Taille de texte normale.');
        wire('sw-vo', 'voice', null, 'Lecture vocale activée. Les contenus seront lus au survol de la souris.', 'Lecture vocale désactivée.');
        wire('sw-ey', 'ey', 'eye-track', 'Navigation par les yeux activée. L\'interface est optimisée pour le suivi du regard.', 'Navigation par les yeux désactivée.');
    }

    function wire(id, key, cls, onMsg, offMsg) {
        const sw = document.getElementById(id);
        if (!sw) return;
        const opt = sw.closest('.a11y-opt');
        sw.addEventListener('change', () => {
            S[key] = sw.checked; savePrefs();
            if (cls) document.documentElement.classList.toggle(cls, sw.checked);
            if (opt) opt.classList.toggle('on', sw.checked);
            speak(sw.checked ? onMsg : offMsg);
            toast(sw.checked ? onMsg : offMsg, sw.checked ? 'fa-check-circle' : 'fa-times-circle');
        });
        if (opt) opt.addEventListener('click', e => {
            if (e.target.closest('.a11y-sw')) return;
            sw.checked = !sw.checked; sw.dispatchEvent(new Event('change'));
        });
    }

    function savePrefs() { localStorage.setItem('re7la_a11y', JSON.stringify(S)); }
    function loadPrefs() {
        try {
            const d = JSON.parse(localStorage.getItem('re7la_a11y'));
            if (!d) return;
            [{ k: 'hc', id: 'sw-hc', c: 'hc' }, { k: 'lg', id: 'sw-lg', c: 'lg-txt' }, { k: 'voice', id: 'sw-vo', c: null }, { k: 'ey', id: 'sw-ey', c: 'eye-track' }]
                .forEach(m => {
                    if (d[m.k]) {
                        S[m.k] = true; const el = document.getElementById(m.id);
                        if (el) { el.checked = true; el.closest('.a11y-opt')?.classList.add('on'); }
                        if (m.c) document.documentElement.classList.add(m.c);
                    }
                });
        } catch (e) { }
    }

    /* ═══════════════════════════════════════════════════
       4. LECTURE VOCALE GLOBALE (AU SURVOL)
       ═══════════════════════════════════════════════════ */
    function initGlobalVoiceReader() {
        let currentEl = null;

        document.addEventListener('mouseover', e => {
            if (!S.voice) return;

            // Trouver l'élément lisible le plus proche
            const el = e.target.closest('a, button, h1, h2, h3, h4, h5, h6, p, label, input, select, textarea, img, .hotel-card-horiz, .feature-card, .recap-card');
            if (!el || el === currentEl) return;

            currentEl = el;
            clearTimeout(S.hoverTmr);

            S.hoverTmr = setTimeout(() => {
                if (!S.voice || currentEl !== el) return;

                let textToRead = "";
                let tooltipText = "";

                if (el.tagName === 'IMG') {
                    textToRead = el.alt || "Image";
                } else if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                    textToRead = "Champ de saisie : " + (el.placeholder || el.value || el.name || "");
                } else if (el.tagName === 'SELECT') {
                    textToRead = "Menu déroulant : " + (el.options[el.selectedIndex]?.text || "");
                } else {
                    // Pour les cartes complexes, on regarde s'il y a un attribut personnalisé ou on lit le contenu texte formaté
                    if (el.hasAttribute('data-vdesc')) {
                        textToRead = el.getAttribute('data-vdesc');
                    } else {
                        // Nettoyer le texte
                        textToRead = (el.getAttribute('aria-label') || el.innerText || el.textContent || "")
                            .replace(/\s+/g, ' ')
                            .trim();
                    }
                }

                // On limite la taille pour ne pas lire toute la page d'un coup si on survole un conteneur
                if (textToRead && textToRead.length > 0 && textToRead.length < 500) {
                    speak(textToRead);
                    showTip(textToRead.length > 60 ? textToRead.substring(0, 57) + "..." : textToRead);

                    // Ajouter le badge visuel si c'est une carte
                    if (el.classList.contains('hotel-card-horiz')) {
                        let b = el.querySelector('.spk-badge');
                        if (!b) {
                            b = document.createElement('div');
                            b.className = 'spk-badge';
                            b.innerHTML = '<i class="fas fa-volume-up"></i> Lecture en cours';
                            const imgBox = el.querySelector('.card-img-side');
                            if (imgBox) {
                                imgBox.style.position = 'relative';
                                imgBox.appendChild(b);
                            }
                        }
                        b?.classList.add('show');
                    }
                }
            }, C.hoverMs);
        }, true);

        document.addEventListener('mouseout', e => {
            if (!S.voice) return;
            const el = e.target.closest('a, button, h1, h2, h3, h4, h5, h6, p, label, input, select, textarea, img, .hotel-card-horiz, .feature-card, .recap-card');

            // Si on quitte l'élément actuel
            if (el === currentEl) {
                clearTimeout(S.hoverTmr);
                hideTip();
                const badge = currentEl?.querySelector('.spk-badge');
                if (badge) badge.classList.remove('show');
                currentEl = null;
                if (S.speaking) stop();
            }
        }, true);
    }

    /* ═══════════════════════════════════════════════════
       6. PARCOURS DE RÉSERVATION GUIDÉ
       Boutons « Réserver » sur les cartes → récap + confirmation
       ═══════════════════════════════════════════════════ */
    function initReservationFlow() {
        // La navigation vers l'ancienne page ("Voir les disponibilités") est rétablie.
        // L'interception qui bloquait le lien a été retirée à votre demande.

        // (Optionnel) on peut lire le bouton au clic avant que la page ne change
        document.addEventListener('click', e => {
            const btn = e.target.closest('.btn-availability');
            if (!btn) return;

            if (S.voice) {
                speak("Redirection vers la page de l'hébergement.");
            }

            // On ne met PLUS e.preventDefault(); pour que le lien normal s'ouvre !
        });
    }

    function showRecap() {
        let sec = document.getElementById('re7la-recap');
        if (sec) {
            sec.classList.add('show');
            updateRecap();
            setTimeout(() => sec.scrollIntoView({ behavior: 'smooth', block: 'center' }), 100);
            return;
        }

        sec = document.createElement('div');
        sec.id = 're7la-recap';
        sec.className = 'container show';
        sec.setAttribute('data-aos', 'fade-up');
        updateRecapHTML(sec);

        const proSec = document.querySelector('.mb-5.mt-5[data-aos="fade-up"]');
        if (proSec) {
            proSec.parentElement.insertBefore(sec, proSec);
        } else {
            const fallback = document.getElementById('results-wrapper');
            if (fallback) fallback.appendChild(sec);
        }

        setTimeout(() => {
            if (sec) sec.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }

    function updateRecapHTML(sec) {
        const h = S.reserved.hotel || {};
        const c = S.reserved.car || {};
        const a = S.reserved.activity || {};

        sec.innerHTML = `
        <h3 style="font-weight:800;color:#1e293b;font-family:'Outfit','Poppins',sans-serif;margin-bottom:24px;margin-top:40px">
            <i class="fas fa-clipboard-check me-2" style="color:#1ABC9C"></i>
            Votre Réservation
        </h3>
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="recap-card rc-hotel">
                    <span class="rc-badge rc-badge-hotel"><i class="fas fa-hotel me-1"></i> Hébergement</span>
                    <div class="rc-title">${h.name || '-'}</div>
                    <div class="rc-sub">${h.loc || 'Tunisie'} · Check-in/out à définir</div>
                    <div class="rc-price">${h.price || '-'} <small style="font-size:.7rem;font-weight:500;color:#94a3b8">/ nuit</small></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="recap-card rc-car">
                    <span class="rc-badge rc-badge-car"><i class="fas fa-car me-1"></i> Voiture</span>
                    <div class="rc-title">${c.name || '-'}</div>
                    <div class="rc-sub">${c.details || ''}</div>
                    <div class="rc-price">${c.price || '-'}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="recap-card rc-act">
                    <span class="rc-badge rc-badge-act"><i class="fas fa-umbrella-beach me-1"></i> Activité</span>
                    <div class="rc-title">${a.name || '-'}</div>
                    <div class="rc-sub">${a.details || ''}</div>
                    <div class="rc-price">${a.price || '-'}</div>
                </div>
            </div>
        </div>
        <div class="text-center">
            <button id="re7la-confirm-btn" style="background:linear-gradient(135deg,#1ABC9C,#16a085);color:#fff;border:none;border-radius:50px;padding:16px 50px;font-weight:700;font-size:1.05rem;cursor:pointer;box-shadow:0 8px 28px rgba(26,188,156,.35);transition:.3s">
                <i class="fas fa-check-circle me-2"></i> Confirmer la Réservation
            </button>
        </div>
    `;

        /* Confirm button */
        sec.querySelector('#re7la-confirm-btn')?.addEventListener('click', () => {
            showConfirmation();
        });
    }

    function updateRecap() {
        const sec = document.getElementById('re7la-recap');
        if (sec) updateRecapHTML(sec);
    }

    /* ═══════════════════════════════════════════════════
       7. CONFIRMATION FINALE (voix + visuel)
       ═══════════════════════════════════════════════════ */
    function showConfirmation() {
        const el = document.getElementById('re7la-confirm');
        if (el) { el.classList.add('show'); }

        speak("Félicitations ! Votre réservation a été confirmée avec succès. Un email de confirmation a été envoyé à votre adresse. Merci d'avoir choisi Re7la. Bon voyage !", () => {
            // After speech ends, user can close
        });

        toast("Réservation confirmée avec succès !", "fa-check-circle");

        document.getElementById('re7la-confirm-close')?.addEventListener('click', () => {
            document.getElementById('re7la-confirm')?.classList.remove('show');
        });
    }

    /* ═══════════════════════════════════════════════════
       8. RACCOURCIS CLAVIER
       ═══════════════════════════════════════════════════ */
    function initKeys() {
        document.addEventListener('keydown', e => {
            if (e.altKey && e.key === 'a') { e.preventDefault(); openPanel(!S.panelOpen); }
            if (e.key === 'Escape' && S.panelOpen) openPanel(false);
        });
    }

    /* ═══════════════════════════════════════════════════
       INIT
       ═══════════════════════════════════════════════════ */
    function init() {
        if ('speechSynthesis' in window) speechSynthesis.getVoices();
        initWelcome();
        initBtn();
        initPanel();
        initGlobalVoiceReader();
        initReservationFlow();
        initKeys();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();

})();