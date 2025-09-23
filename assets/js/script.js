let lastScrollY = window.scrollY;
const navbar = document.querySelector('.navbar-wrapper');

window.addEventListener('scroll', () => {
    if (window.scrollY > lastScrollY) {
        // Scrolling down
        navbar.classList.add('hidden');
    } else {
        // Scrolling up
        navbar.classList.remove('hidden');
    }
    lastScrollY = window.scrollY;
});
