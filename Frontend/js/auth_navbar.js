document.addEventListener('DOMContentLoaded', async () => {
    if (window.location.pathname.includes('dashboard.html') || window.location.pathname.includes('profile.html')) {
        return;
    }

    try {
        // user connecte?
        const sessionRes = await fetch('../Backend/api/auth/session_check.php', { credentials: 'include' });
        if (!sessionRes.ok) return;

        // verifier que la reponse est bien du JSON avant de parser
        const contentType = sessionRes.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) return;

        const sessionData = await sessionRes.json();

        // si connecte remplacer login par dropdown
        if (sessionData.logged_in) {

            // chercher "signin.html" pour trouver bouton Log In
            const loginLink = document.querySelector('a[href$="signin.html"]');
            if (!loginLink) return;

            // remonter au parent <li>
            const navItem = loginLink.closest('.nav-item');
            if (!navItem) return;

            // recup photo de profil (avatar)
            const profileRes = await fetch('../Backend/api/profile/get_profile.php', { credentials: 'include' });
            let avatarUrl = './img/profile.jpg'; // avatar par defaut

            if (profileRes.ok) {
                const profileData = await profileRes.json();
                if (profileData.success && profileData.avatar) {
                    avatarUrl = buildDashboardAvatarUrl(profileData.avatar);
                }
            }

            // inserer dropdown
            navItem.classList.add('dropdown');

            // lien vers Dashboard si admin
            const adminLink = (sessionData.role && sessionData.role.toLowerCase() === 'admin')
                ? `<li><a class="dropdown-item" href="./dashboard.html"><i class="fa-solid fa-tachometer-alt me-2 icon-green"></i>Dashboard</a></li>`
                : '';

            navItem.innerHTML = `
                <a href="#" class="link-body-emphasis text-decoration-none dropdown-toggle d-flex align-items-center fw-semibold" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="me-2">${sessionData.username || 'User'}</span>
                    <img src="${avatarUrl}" alt="Profile" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;" onerror="this.src='./img/profile.jpg'">
                    
                </a>
                <ul class="dropdown-menu dropdown-menu-end text-small" style="background: var(--background-color);">
                    <li><a class="dropdown-item" href="./profile.html">
                            <i class="fa-solid fa-user me-2 icon-green"></i>Profile</a></li>
                    ${adminLink}
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger" href="../Backend/api/auth/logout.php">
                            <i class="fa-solid fa-right-from-bracket me-2"></i>Sign out</a></li>
                </ul>
            `;
        }
    } catch (err) {
        console.error("Erreur lors de la vérification de la session pour la navbar :", err);
    }
});


function buildDashboardAvatarUrl(path) {
    const defaultAvatar = './img/profile.jpg';
    if (!path) return defaultAvatar;
    const normalized = String(path).trim();
    if (!normalized) return defaultAvatar;
    // Si c'est un lien web complet (http/https) ou un chemin absolu, on ne touche à rien
    if (/^https?:\/\//i.test(normalized) || normalized.startsWith('/')) return normalized;
    // Si le chemin commence par ./ ou ../, on le laisse tel quel
    if (normalized.startsWith('./') || normalized.startsWith('../')) return normalized;
    // Si le chemin commence par 'Frontend/', on le retire pour obtenir un chemin relatif
    if (normalized.startsWith('Frontend/')) return `./${normalized.slice('Frontend/'.length)}`;
    // Si le chemin commence par 'Backend/', on le retire pour obtenir un chemin relatif
    if (normalized.startsWith('Backend/')) return `../${normalized}`;
    // Si c'est dans un dossier uploads, on le fait pointer vers le backend
    if (normalized.startsWith('uploads/')) return `../Backend/${normalized}`;
    // Si le nom de fichier ne contient pas de slash (c'est un nom de fichier seul)
    if (/^[^/]+\.(png|jpe?g|webp|gif)$/i.test(normalized))
        return `../Backend/uploads/avatars/${normalized}`;
    // Cas par défaut : on suppose que c'est un chemin relatif vers le dossier d'uploads du backend
    return `../Backend/${normalized}`;
}
