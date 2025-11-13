// Custom JavaScript for Filament sidebar behavior

document.addEventListener('DOMContentLoaded', function() {
    // Ensure sidebar starts collapsed on desktop
    if (window.innerWidth >= 1024) {
        // Check if Alpine.js store exists and set sidebar to closed
        if (window.Alpine && window.Alpine.store('sidebar')) {
            window.Alpine.store('sidebar').close();
        }
    }
    
    // Listen for sidebar state changes to adjust main content
    document.addEventListener('alpine:init', () => {
        Alpine.store('sidebar', {
            isOpen: false,
            
            open() {
                this.isOpen = true;
                document.body.classList.add('sidebar-open');
            },
            
            close() {
                this.isOpen = false;
                document.body.classList.remove('sidebar-open');
            },
            
            toggle() {
                this.isOpen ? this.close() : this.open();
            }
        });
    });
});

// Handle window resize to auto-close sidebar on mobile
window.addEventListener('resize', function() {
    if (window.innerWidth < 1024 && window.Alpine && window.Alpine.store('sidebar')) {
        // On mobile, let the default behavior handle it
        return;
    }
});