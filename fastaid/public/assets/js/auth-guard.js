/* JS Auth Guard to redirect if not logged in or wrong role */
document.addEventListener('DOMContentLoaded', () => {
    // Only run on protected pages
    const path = window.location.pathname;
    const protectedPages = ['dashboard', 'request', 'tracking', 'focus', 'admin'];
    const isProtected = protectedPages.some(p => path.includes(p));

    if (isProtected && api) {
        api.get_me().then(r => {
            if (!r.success) {
                window.location.href = 'login.html';
                return;
            }

            const role = r.data.user.role;

            // Enforce role access
            const requiredRole = path.includes('patient-dashboard') ? 'patient'
                : path.includes('volunteer-dashboard') ? 'volunteer'
                : path.includes('admin') ? 'admin'
                : null;
            if (requiredRole && role !== requiredRole) {
                const dashboards = {
                    patient: 'patient-dashboard.html',
                    volunteer: 'volunteer-dashboard.html',
                    admin: 'admin.html'
                };
                window.location.href = dashboards[role] || 'login.html';
            }
        }).catch(() => {
            window.location.href = 'login.html';
        });
    }
});
