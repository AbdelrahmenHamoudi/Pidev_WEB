document.addEventListener('DOMContentLoaded', function() {
    console.log('Reservation UI Loaded');

    // FAVORITES HEART TOGGLE
    const favoriteIcons = document.querySelectorAll('.badge-favorite i');
    favoriteIcons.forEach(icon => {
        icon.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (this.classList.contains('far')) {
                this.classList.remove('far', 'fa-heart');
                this.classList.add('fas', 'fa-heart');
                this.style.color = '#e74c3c';
            } else {
                this.classList.remove('fas', 'fa-heart');
                this.classList.add('far', 'fa-heart');
                this.style.color = '#e74c3c';
            }
        });
    });

    // DINAMIC TAB FILTERING
    const tabs = document.querySelectorAll('.nav-tabs-modern .nav-link');
    const cards = document.querySelectorAll('.hebergement-card-horizontal');

    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const targetType = this.getAttribute('data-type');
            
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            if (targetType === 'all') {
                cards.forEach(card => card.style.display = 'flex');
            } else {
                cards.forEach(card => {
                    if (card.getAttribute('data-type') === targetType) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
        });
    });

    // SEARCH BUTTON FEEDBACK
    const mainSearchBtn = document.querySelector('.search-btn');
    if (mainSearchBtn) {
        mainSearchBtn.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1.05)';
            }, 100);
        });
    }
});
