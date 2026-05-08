// initialisation de la page profil
document.addEventListener('DOMContentLoaded', async function () {
    await initializeProfile();
    setupEventListeners();
});

// memoire centrale profile
const profileState = {
    orders: [],
    reorderSourceOrderId: null,
    reorderModal: null,
    defaultAvatarPath: './img/profile.jpg'
};
// affichage donnees du profile
async function initializeProfile() {
    try {
        const profileData = await requestJson('../Backend/api/profile/get_profile.php');

        if (!profileData.success) {
            // Not logged in -> sign-in
            window.location.href = 'signin.html';
            return;
        }

        populateProfileInfo(profileData);
        fetchOrders();
        fetchReservations();

        if (!profileState.reorderModal && typeof bootstrap !== 'undefined') {
            const reorderEl = document.getElementById('reorderModal');
            if (reorderEl) profileState.reorderModal = new bootstrap.Modal(reorderEl);
        }
    } catch (err) {
        console.error('Error initializing profile:', err);
    }
}

/**
 * Fonction pour simplifier et centraliser les appels API.
 * - Inclut les cookies (credentials: 'include') pour l'authentification de session
 * - Lance une erreur sur les réponses non 2xx pour que les appelants puissent utiliser try/catch
 * @param {string} url   - L'adresse de l'API.
 * @param {object} options - Options de la requête (méthode, body, etc.).
 * @returns {Promise<object>} Les données de la réponse au format JSON.
 */
async function requestJson(url, options = {}) {
    const res = await fetch(url, { credentials: 'include', ...options });
    if (!res.ok) throw new Error(`HTTP ${res.status} on ${url}`);
    return res.json();
}

// remplissage user info dans UI
function populateProfileInfo(data) {
    const navUsername = document.getElementById('username');
    if (navUsername) navUsername.textContent = data.username;

    // profile card username / email
    document.querySelectorAll('.profile-username').forEach(el => el.textContent = data.username);
    document.querySelectorAll('.profile-email').forEach(el => el.textContent = data.email);

    const memberSince = document.getElementById('member-since');
    if (memberSince) {
        const parsed = data.created_at ? new Date(data.created_at) : null;
        const year = parsed && !Number.isNaN(parsed.getTime())
            ? parsed.getFullYear()
            : new Date().getFullYear();
        memberSince.textContent = String(year);
    }

    const setUsername = document.getElementById('settingUsername');
    const setEmail = document.getElementById('setting-email');
    if (setUsername) setUsername.value = data.username;
    if (setEmail) setEmail.value = data.email;

    const avatar = buildAvatarUrl(data.avatar || profileState.defaultAvatarPath);
    const avatarImg = document.getElementById('profile-avatar');
    const navAvatarImg = document.getElementById('nav-profile-avatar');

    if (avatarImg) {
        avatarImg.onerror = () => {
            avatarImg.onerror = null;
            avatarImg.src = profileState.defaultAvatarPath;
        };
        avatarImg.src = avatar;
    }

    if (navAvatarImg) {
        navAvatarImg.onerror = () => {
            navAvatarImg.onerror = null;
            navAvatarImg.src = profileState.defaultAvatarPath;
        };
        navAvatarImg.src = avatar;
    }
}

function buildAvatarUrl(path) {
    if (!path) return profileState.defaultAvatarPath;
    const normalized = String(path).trim();
    if (!normalized) return profileState.defaultAvatarPath;
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

async function fetchOrders() {
    const orderContainer = document.querySelector('#order');
    if (!orderContainer) return;

    try {
        const data = await requestJson('../Backend/api/profile/fetch_user_orders.php');

        if (data.success && data.data && data.data.length > 0) {
            profileState.orders = data.data;

            let html = `<h3 class="mb-3"><i class="fas fa-shopping-cart me-2 icon-green"></i>My Orders</h3>`;

            data.data.forEach((order) => {
                const itemsHtml = (order.items || []).map((item) => `
                    <li class="d-flex align-items-center mb-1">
                        <img src="${item.img}" alt="${item.name}"> ${item.name} &times;${item.qty}
                        <span class="text-muted ms-1 small">($${Number(item.price || 0).toFixed(2)} per item)</span>
                    </li>
                `).join('');

                const statusColors = {
                    'Completed': 'bg-success',
                    'Delivered': 'bg-success',
                    'Pending': 'bg-warning text-dark',
                    'Preparing': 'bg-info text-dark',
                    'Cancelled': 'bg-danger',
                };
                const badgeClass = statusColors[order.status] || 'bg-secondary';
                const dateStr = order.date ? new Date(order.date).toLocaleDateString() : '—';

                html += `
                    <div class="bg-white border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1">Order #${order.id}</h5>
                                <p class="mb-0 text-muted small">Placed on: ${dateStr}</p>
                            </div>
                            <div class="text-end">
                                <span class="badge ${badgeClass}">${order.status}</span>
                                <p class="mb-0 mt-1 fw-semibold">$${Number(order.total).toFixed(2)}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="order-items">
                            <p class="mb-2 fw-semibold small">Items:</p>
                            <ul class="mb-0 list-unstyled" style="padding-left:10px;">${itemsHtml}</ul>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button class="btn btn-sm btn-green" onclick="openReorderModal(${order.id})">
                                <i class="fas fa-redo me-1"></i>Reorder
                            </button>
                        </div>
                    </div>
                `;
            });

            orderContainer.innerHTML = html;
            return;
        }

        profileState.orders = [];
        orderContainer.innerHTML = `
            <h3 class="mb-3"><i class="fas fa-shopping-cart me-2 icon-green"></i>My Orders</h3>
            <div class="bg-white border rounded p-4 text-center text-muted mb-3">
                <i class="fas fa-box-open mb-2" style="font-size:2rem; color:#ddd;"></i>
                <p class="mb-0">You have no orders yet.</p>
            </div>
        `;
    } catch (err) {
        console.error('Error fetching orders:', err);
    }
}

function openReorderModal(orderId) {
    const order = profileState.orders.find(o => Number(o.id) === Number(orderId));
    if (!order || !Array.isArray(order.items) || order.items.length === 0) {
        alert('This order cannot be reordered.');
        return;
    }

    profileState.reorderSourceOrderId = Number(order.id);

    const sourceText = document.getElementById('reorder-source-order-text');
    const itemsContainer = document.getElementById('reorder-items-container');
    if (!sourceText || !itemsContainer) return;

    sourceText.textContent = `Edit quantities before placing a new order from #${order.id}.`;

    itemsContainer.innerHTML = order.items.map((item, index) => `
        <div class="border rounded p-2 mb-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-0 fw-semibold">${item.name}</p>
                    <p class="mb-0 text-muted small">$${Number(item.price || 0).toFixed(2)} each</p>
                </div>
                <div style="max-width:110px;">
                    <label class="form-label small mb-1">Qty</label>
                    <input type="number" min="0" class="form-control reorder-qty-input"
                        data-menu-item-id="${item.menu_item_id}"
                        data-price="${Number(item.price || 0)}"
                        value="${Number(item.qty || 0)}"
                        id="reorder-qty-${index}">
                </div>
            </div>
        </div>
    `).join('');

    updateReorderEstimatedTotal();
    itemsContainer.querySelectorAll('.reorder-qty-input').forEach(input =>
        input.addEventListener('input', updateReorderEstimatedTotal)
    );

    profileState.reorderModal?.show();
}

function updateReorderEstimatedTotal() {
    const totalEl = document.getElementById('reorder-estimated-total');
    if (!totalEl) return;

    const inputs = document.querySelectorAll('#reorder-items-container .reorder-qty-input');
    let total = 0;

    inputs.forEach((input) => {
        const qty = Number(input.value || 0);
        const price = Number(input.getAttribute('data-price') || 0);
        if (Number.isFinite(qty) && qty > 0) total += qty * price;
    });

    totalEl.textContent = `$${total.toFixed(2)}`;
}

async function fetchReservations() {
    const resContainer = document.querySelector('#reservation');
    if (!resContainer) return;

    try {
        const data = await requestJson('../Backend/api/profile/fetch_user_reservations.php');

        if (data.success && data.data && data.data.length > 0) {
            let html = `<h3 class="mb-3"><i class="fas fa-calendar me-2 icon-green"></i>My Reservations</h3>`;

            data.data.forEach((r) => {
                const dateStr = r.date ? new Date(r.date).toLocaleDateString() : '—';
                const timeStr = r.time ? r.time.substring(0, 5) : '—';

                html += `
                    <div class="bg-white border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1">Reservation #${r.id}</h5>
                                <p class="mb-0 text-muted small">Booked on: ${r.created_at ? new Date(r.created_at).toLocaleDateString() : '—'}</p>
                            </div>
                        </div>
                        <hr>
                        <ul class="mb-0 small list-unstyled" style="padding-left:10px;">
                            <li class="mb-1"><i class="fas fa-chair me-2 icon-green"></i>Table Number: ${r.table_number ?? 'N/A'}</li>
                            <li class="mb-1"><i class="fas fa-users me-2 icon-green"></i>Number of Guests: ${r.guests}</li>
                            <li class="mb-1"><i class="fas fa-calendar me-2 icon-green"></i>Date &amp; Time: ${dateStr} at ${timeStr}</li>
                            ${r.special_notes ? `<li class="mb-1"><i class="fas fa-sticky-note me-2 icon-green"></i>Notes: ${r.special_notes}</li>` : ''}
                        </ul>
                    </div>
                `;
            });

            resContainer.innerHTML = html;
            return;
        }

        resContainer.innerHTML = `
            <h3 class="mb-3"><i class="fas fa-calendar me-2 icon-green"></i>My Reservations</h3>
            <div class="bg-white border rounded p-4 text-center text-muted mb-3">
                <i class="fas fa-calendar-times mb-2" style="font-size:2rem; color:#ddd;"></i>
                <p class="mb-0">You have no reservations yet.</p>
            </div>
        `;
    } catch (err) {
        console.error('Error fetching reservations:', err);
    }
}

//  message feedback upload avatar
function showAvatarFeedback(message, ok) {
    const feedback = document.getElementById('avatar-feedback');
    if (!feedback) return;
    feedback.style.display = 'block';
    feedback.className = `small mt-2 ${ok ? 'text-success' : 'text-danger'}`;
    feedback.textContent = message;
    setTimeout(() => { feedback.style.display = 'none'; }, 3500);
}

function setupEventListeners() {
    // Avatar upload 
    const avatarBtn = document.getElementById('change-avatar-btn');
    const avatarInput = document.getElementById('avatar-input');

    if (avatarBtn && avatarInput) {
        avatarBtn.addEventListener('click', () => avatarInput.click());

        avatarInput.addEventListener('change', async () => {
            const [file] = avatarInput.files || [];
            if (!file) return;

            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showAvatarFeedback('Please upload JPG, PNG or WEBP images only.', false);
                avatarInput.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                showAvatarFeedback('Image size must be 2MB or less.', false);
                avatarInput.value = '';
                return;
            }

            const form = new FormData();
            form.append('avatar', file);

            try {
                const result = await requestJson('../Backend/api/profile/upload_profile_avatar.php', {
                    method: 'POST',
                    body: form,
                });

                if (!result.success) {
                    showAvatarFeedback(result.message || 'Failed to update profile picture.', false);
                    avatarInput.value = '';
                    return;
                }

                const newUrl = buildAvatarUrl(result.avatar);
                const avatarImg = document.getElementById('profile-avatar');
                const navAvatarImg = document.getElementById('nav-profile-avatar');
                if (avatarImg) avatarImg.src = newUrl;
                if (navAvatarImg) navAvatarImg.src = newUrl;

                showAvatarFeedback('Profile picture updated successfully.', true);
            } catch (err) {
                console.error(err);
                showAvatarFeedback('Failed to upload image. Please try again.', false);
            } finally {
                avatarInput.value = '';
            }
        });
    }

    // reorder confirm button
    const reorderBtn = document.getElementById('confirm-reorder-btn');
    if (reorderBtn) {
        reorderBtn.addEventListener('click', async () => {
            const sourceOrderId = profileState.reorderSourceOrderId;
            if (!sourceOrderId) { alert('No order selected for reorder.'); return; }

            const inputs = Array.from(document.querySelectorAll('#reorder-items-container .reorder-qty-input'));
            const items = inputs
                .map(input => ({
                    menu_item_id: Number(input.getAttribute('data-menu-item-id') || 0),
                    qty: Number(input.value || 0),
                }))
                .filter(item => item.menu_item_id > 0 && item.qty > 0);

            if (items.length === 0) { alert('Please keep at least one item with quantity > 0.'); return; }

            try {
                const result = await requestJson('../Backend/api/profile/reorder_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ source_order_id: sourceOrderId, items }),
                });

                if (!result.success) { alert(result.message || 'Failed to reorder.'); return; }

                profileState.reorderModal?.hide();
                alert(`New order #${result.new_order_id} created successfully!`);
                fetchOrders();
            } catch (err) {
                console.error(err);
                alert('Failed to reorder. Please try again.');
            }
        });
    }

    /* Edit / Save / Cancel profile */
    const editBtn = document.getElementById('edit-profile-btn');
    const saveBtn = document.getElementById('save-profile-btn');
    const cancelBtn = document.getElementById('cancel-edit-btn');
    const usernameInput = document.getElementById('settingUsername');

    if (editBtn) {
        editBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (usernameInput) usernameInput.disabled = false;
            if (saveBtn) saveBtn.style.display = 'block';
            if (cancelBtn) cancelBtn.style.display = 'block';
            editBtn.style.display = 'none';
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (usernameInput) usernameInput.disabled = true;
            if (saveBtn) saveBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
            if (editBtn) editBtn.style.display = 'block';
            initializeProfile(); // re-fetch to restore values
        });
    }

    // profile update
    const profileForm = document.getElementById('update-profile-form');
    if (profileForm) {
        const submitProfileUpdate = async (e) => {
            e.preventDefault();
            if (!saveBtn || saveBtn.style.display === 'none') return;

            const payload = {
                action: 'update_profile',
                username: usernameInput ? usernameInput.value : '',
            };

            try {
                const data = await requestJson('../Backend/api/profile/update_profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });

                const feedback = document.getElementById('setting-feedback');
                if (!feedback) return;

                feedback.style.display = 'block';
                if (data.success) {
                    feedback.className = 'mt-2 text-success small';
                    feedback.textContent = data.message;

                    if (usernameInput) usernameInput.disabled = true;
                    if (editBtn) editBtn.style.display = 'block';
                    if (saveBtn) saveBtn.style.display = 'none';
                    if (cancelBtn) cancelBtn.style.display = 'none';

                    // update displayed name immediately
                    document.querySelectorAll('.profile-username').forEach(el => el.textContent = payload.username);
                    const navUsername = document.getElementById('username');
                    if (navUsername) navUsername.textContent = payload.username;
                } else {
                    feedback.className = 'mt-2 text-danger small';
                    feedback.textContent = data.message;
                }

                setTimeout(() => { feedback.style.display = 'none'; }, 3000);
            } catch (err) {
                console.error(err);
            }
        };

        profileForm.addEventListener('submit', submitProfileUpdate);
    }

    // change password
    const pwForm = document.getElementById('change-password-form');
    if (pwForm) {
        pwForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const currPw = document.getElementById('current-password')?.value || '';
            const newPw = document.getElementById('new-password')?.value || '';
            const confPw = document.getElementById('confirm-password')?.value || '';

            try {
                const data = await requestJson('../Backend/api/profile/update_profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'change_password',
                        current_password: currPw,
                        new_password: newPw,
                        confirm_password: confPw,
                    }),
                });

                alert(data.message);
                if (data.success) pwForm.reset();
            } catch (err) {
                console.error(err);
                alert('Request failed. Please try again.');
            }
        });
    }

    // delete account
    const delBtn = document.getElementById('delete-account-btn');
    if (delBtn) {
        delBtn.addEventListener('click', () => {
            const deleteModal = document.getElementById('deleteConfirmModal');
            const deleteMsg = document.getElementById('deleteConfirmMessage');
            const deleteBtn = document.getElementById('deleteConfirmBtn');

            if (!deleteModal || !deleteMsg || !deleteBtn || typeof bootstrap === 'undefined') {
                if (confirm('Are you sure you want to permanently delete your account? This cannot be undone!')) {
                    performAccountDeletion();
                }
                return;
            }

            deleteMsg.innerHTML = 'Are you sure you want to <strong>permanently delete your account</strong>? All your orders and reservations will be lost forever.';

            const bsModal = new bootstrap.Modal(deleteModal);
            const newBtn = deleteBtn.cloneNode(true);
            deleteBtn.parentNode.replaceChild(newBtn, deleteBtn);
            newBtn.id = 'deleteConfirmBtn';

            newBtn.addEventListener('click', async () => {
                bsModal.hide();
                await performAccountDeletion();
            }, { once: true });

            bsModal.show();
        });
    }
}

async function performAccountDeletion() {
    try {
        const data = await requestJson('../Backend/api/profile/update_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_account' }),
        });

        if (data.success) {
            alert('Account deleted.');
            window.location.href = 'index.php';
            return;
        }
        alert(`Error: ${data.message}`);
    } catch (err) {
        console.error(err);
    }
}
