/* Modern Reservation UI Logic */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize filter state
    const filterRadios = document.querySelectorAll('.filter-option input');
    
    filterRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            const form = radio.closest('form');
            if (form) {
                // Auto-submit if user changes a top category
                if (radio.name === 'type' || radio.name === 'disponible') {
                    // (Optional) Add a debounce if needed
                }
            }
        });
    });

    // Handle scroll for navbar glassmorphism
    window.addEventListener('scroll', () => {
        const navbar = document.querySelector('.navbar-re7la');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Initialize Tooltips/Popovers if any (optional)
});
