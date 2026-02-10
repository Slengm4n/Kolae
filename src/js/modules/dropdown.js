// src/js/modules/dropdown.js

export function initNavbar() {
    const userMenuButton = document.getElementById('user-menu-button');
    const profileDropdown = document.getElementById('profile-dropdown');

    if (userMenuButton) {
        userMenuButton.addEventListener('click', (event) => {
            event.stopPropagation();
            profileDropdown.classList.toggle('opacity-0');
            profileDropdown.classList.toggle('invisible');
            profileDropdown.classList.toggle('transform');
            profileDropdown.classList.toggle('-translate-y-2');
        });
    }

    window.addEventListener('click', (event) => {
        if (profileDropdown && !profileDropdown.classList.contains('invisible')) {
            if (!profileDropdown.contains(event.target) && !userMenuButton.contains(event.target)) {
                profileDropdown.classList.add('opacity-0', 'invisible', '-translate-y-2');
            }
        }
    });
}
