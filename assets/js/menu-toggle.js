document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const toggleBtn = document.getElementById('sidebarCollapse');
    const body = document.body;
    const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
    
    if (isCollapsed) {
        applyCollapse();
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (body.classList.contains('sidebar-toggled')) {
                removeCollapse();
            } else {
                applyCollapse();
            }
        });
    }

    function applyCollapse() {
        body.classList.add('sidebar-toggled');
        localStorage.setItem('sidebar-collapsed', 'true');
        if (window.innerWidth < 768) {
            sidebar.classList.add('mobile-show');
        }
    }

    function removeCollapse() {
        body.classList.remove('sidebar-toggled');
        localStorage.setItem('sidebar-collapsed', 'false');
        if (window.innerWidth < 768) {
            sidebar.classList.remove('mobile-show');
        }
    }

    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('mobile-show');
        }
    });

    const currentPath = window.location.pathname.split("/").pop();
    const navLinks = document.querySelectorAll('.sidebar ul li a');
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.parentElement.classList.add('active');
        }
    });
});
