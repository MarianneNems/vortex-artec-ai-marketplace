// VortexArtec Demo - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('VORTEX AI Platform Demo loaded');
    
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const headerNav = document.querySelector('.header-nav');
    
    if (mobileMenuToggle && headerNav) {
        mobileMenuToggle.addEventListener('click', function() {
            headerNav.classList.toggle('active');
        });
    }
});