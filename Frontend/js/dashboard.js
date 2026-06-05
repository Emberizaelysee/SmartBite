// eviter le retour en arriere du user apres logout
window.addEventListener('pageshow', function (e) {
    if (e.persisted) {
        fetch('../Backend/api/auth/session_check.php', { credentials: 'include' })
            .then(res => res.json())
            .then(data => {
                if (!data.logged_in) {
                    window.location.replace('signin.html');
                }
            })
            .catch(() => {
                window.location.replace('signin.html');
            });
    }
});

// initialisation de la page dashboard
document.addEventListener('DOMContentLoaded', async () => {
    const canLoadDashboard = await checkAdminSession();
    if (!canLoadDashboard) return;
    setupDashboardInteractions();
    fetchMenuItems();
    fetchOrders();
    fetchReservations();
    fetchRestaurantTables();
    fetchReviews();
    fetchUsers();
});

const dashboardDefaultAvatarPath = './img/profile.jpg';

async function checkAdminSession() {
    try {
        const data = await requestJSON('../Backend/api/auth/session_check.php');
        const role = (data.role || '').toLowerCase();

        if (!data.logged_in || role !== 'admin') {
            window.location.href = 'signin.html';
            return false;
        }

        const navUsername = document.getElementById('username');
        if (navUsername) {
            navUsername.textContent = data.username || 'Admin';
        }

        const profileData = await requestJSON('../Backend/api/profile/get_profile.php');
        if (profileData.success) {
            const navAvatar = document.getElementById('nav-dashboard-avatar');
            if (navAvatar) {
                navAvatar.onerror = () => {
                    navAvatar.onerror = null;
                    navAvatar.src = dashboardDefaultAvatarPath;
                };
                navAvatar.src = buildDashboardAvatarUrl(profileData.avatar);
            }
        }
        return true;
    } catch (e) {
        console.error("Session check failed", e);
        window.location.href = 'signin.html';
        return false;
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

async function requestJSON(url, options = {}) {
    const res = await fetch(url, {
        credentials: 'include',
        ...options
    });

    if (!res.ok) {
        throw new Error(`HTTP ${res.status} on ${url}`);
    }

    return await res.json();
}

function buildDashboardAvatarUrl(path) {
    if (!path) return dashboardDefaultAvatarPath;
    const normalized = String(path).trim();
    if (!normalized) return dashboardDefaultAvatarPath;
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

/* Initialisation de la chart */
document.addEventListener('DOMContentLoaded', async function () {
    const ctx = document.getElementById('salesChart');
    if (!ctx) return;

    const primaryColor = '#16c451';
    const primaryColorFill = 'rgba(22, 196, 81, 0.15)';
    const textColor = '#333';
    const gridColor = 'rgba(22, 196, 81, 0.1)';

    let labels = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    let revenueData = [0, 0, 0, 0, 0, 0, 0];

    try {
        const result = await dashboardAction({ action: 'get_weekly_revenue' });
        if (result.success && result.data.length > 0) {
            labels = result.data.map(r => r.day);
            revenueData = result.data.map(r => r.revenue);
        }
    } catch (e) {
        console.warn('Could not load chart data from DB, using defaults.');
    }

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Weekly Revenue ($)',
                data: revenueData,
                lineTension: 0.4,
                backgroundColor: primaryColorFill,
                borderColor: primaryColor,
                borderWidth: 3,
                pointBackgroundColor: primaryColor,
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: primaryColor,
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: textColor,
                        font: { size: 13, family: "'Poppins', sans-serif" }
                    }
                },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: textColor,
                    bodyColor: textColor,
                    borderColor: primaryColor,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    bodyFont: { family: "'Poppins', sans-serif" },
                    titleFont: { family: "'Poppins', sans-serif", weight: '600' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: textColor,
                        font: { family: "'Poppins', sans-serif" },
                        callback: (value) => '$' + value
                    },
                    grid: { color: gridColor, drawBorder: false }
                },
                x: {
                    ticks: {
                        color: textColor,
                        font: { family: "'Poppins', sans-serif" }
                    },
                    grid: { color: gridColor, drawBorder: false }
                }
            }
        }
    });
});
// memoire centrale pour eviter les appels API inutiles
const RESERVATION_DURATION_MINUTES = 90;

function reservationTimeToMinutes(timeStr) {
    const key = (timeStr || '').substring(0, 8);
    const parts = key.split(':');
    return (parseInt(parts[0], 10) || 0) * 60 + (parseInt(parts[1], 10) || 0);
}

function reservationTimesOverlap(timeA, timeB) {
    const a = reservationTimeToMinutes(timeA);
    const b = reservationTimeToMinutes(timeB);
    return a < (b + RESERVATION_DURATION_MINUTES) && b < (a + RESERVATION_DURATION_MINUTES);
}

function normalizeDashboardTimeValue(time) {
    const t = (time || '').trim();
    if (/^\d{2}:\d{2}$/.test(t)) return `${t}:00`;
    return t;
}

const dashboardState = {
    // donne de la database
    menuItems: [],
    orders: [],
    reservations: [],
    restaurantTables: [],
    reviews: [],
    users: [],
    orderCart: [],
    // memoire des search bar
    filters: {
        menu: '',
        order: '',
        reservation: '',
        review: '',
        user: ''
    },
    // memoire des tris
    sort: {
        menu: { key: null, direction: 0 },
        order: { key: null, direction: 0 },
        reservation: { key: null, direction: 0 },
        user: { key: null, direction: 0 }
    },
    // memoire des ids en edition
    editingMenuId: null,
    editingOrderId: null,
    editingReservationId: null,
    editingTableId: null,
    // memoire des modals
    modals: {}
};

// envoie des donnees au dashboard_actions.php de maniere securise (payload)
async function dashboardAction(payload) {
    return requestJSON('../Backend/api/dashboard/dashboard_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });
}

// recuperation du menu si pas d'erreur alors memoire centrale sinon array vide
async function fetchMenuItems() {
    try {
        const items = await requestJSON('../Backend/api/dashboard/fetch_Menu_Items.php');
        dashboardState.menuItems = Array.isArray(items) ? items : [];
    } catch (e) {
        console.error('Error fetching menu items:', e);
        dashboardState.menuItems = [];
    }
    await fetchCategories();
    renderMenuItems();
}

// recuperation des categories pour le dropdown
async function fetchCategories() {
    try {
        const res = await dashboardAction({ action: 'get_categories' });
        const cats = res.success ? (res.data || []) : [];
        const sel = document.getElementById('menu-form-category');
        if (!sel) return;
        // transform les donnes recues en html
        const all = cats.length > 0 ? cats : [...new Map(dashboardState.menuItems.filter(m => m.category_id).map(m => [m.category_id, { id: m.category_id, name: m.category }])).values()];
        sel.innerHTML = all.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    } catch (e) {
        console.error('Error fetching categories:', e);
    }
}

function renderMenuItems() {
    const tbody = document.querySelector('#menu tbody');
    if (!tbody) return;

    // recuperation valeur de search bar
    const term = dashboardState.filters.menu.trim().toLowerCase();
    const rows = dashboardState.menuItems.filter((item) => {
        if (!term) return true;
        return `${item.name} ${item.description || ''} ${item.category || ''} ${item.ingredients || ''}`
            .toLowerCase()
            .includes(term);
    });

    // application du tri d'apres sortRows
    const sortedRows = sortRows(rows, 'menu');

    if (sortedRows.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="fas fa-utensils mb-2 d-block" style="font-size:2rem; color:#ddd;"></i>
                    No menu items found.
                </td>
            </tr>
        `;
        return;
    }

    // affichage des donnees
    tbody.innerHTML = sortedRows.map((item) => `
        <tr>
            <td>${item.name}</td>
            <td class="text-truncate" style="max-width: 150px;">${item.description || '-'}</td>
            <td>${item.category || '-'}</td>
            <td>$${Number(item.price).toFixed(2)}</td>
            <td>
                <img src="${item.image || './img/placeholder.jpg'}" alt="Dish" style="max-width:40px; border-radius:5px;">
            </td>
            <td class="text-truncate" style="max-width: 150px;">${item.ingredients || '-'}</td>
            <td>
                <div style="display: flex; flex-directon: row; flex-wrap: nowrap;">
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editMenu(${item.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline-secondary me-1" onclick="viewMenuRow(${item.id})"><i class="fas fa-eye"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-delete-action="menu" data-id="${item.id}"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
    `).join('');
}

/*
function getStockSearchTokens(quantityRaw) {
    const quantity = Number(quantityRaw ?? 0);
    if (!Number.isFinite(quantity) || quantity <= 0) return 'out of stock 0 left';
    if (quantity <= 5) return `low stock ${quantity} left`;
    return `in stock ${quantity} left`;
}

function renderStockStatus(quantityRaw) {
    const quantity = Number(quantityRaw ?? 0);
    if (!Number.isFinite(quantity) || quantity <= 0) {
        return `<span class="text-danger fw-semibold">Out of stock</span> <small class="text-muted">(0 left)</small>`;
    }

    if (quantity <= 5) {
        return `<span class="text-warning fw-semibold">Low stock</span> <small class="text-muted">(${quantity} left)</small>`;
    }

    return `<span class="text-success fw-semibold">In stock</span> <small class="text-muted">(${quantity} left)</small>`;
}*/

function deleteMenu(id) {
    showDeleteConfirm('Are you sure you want to <strong>delete this menu item</strong>? This cannot be undone.', () => {
        // si confirm envoie a dashboardAction delete_menu et id
        dashboardAction({ action: 'delete_menu', id }).then(res => {
            // si reponse positive recharge fetchMenuItems sinon alerte erreur
            if (res.success) fetchMenuItems();
            else alert(res.message);
        });
    });
}

function editMenu(id) {
    // recherche dans la memoire le menuTtem d'apres id
    const item = dashboardState.menuItems.find(m => m.id === id);
    if (!item) return;
    openMenuModal('edit', item);
}

// recuperation des commandes
async function fetchOrders() {
    try {
        const res = await requestJSON('../Backend/api/dashboard/fetch_all_orders.php');
        dashboardState.orders = res.success ? (res.data || []) : [];
    } catch (e) {
        console.error('Error fetching order : ', e);
        dashboardState.orders = [];
    }
    renderOrders();
    updateDashboardStats();
}

// affichage des commandes
function renderOrders() {
    const tbody = document.querySelector('#order tbody');
    if (!tbody) return;

    // recuperation valeur de search bar
    const term = dashboardState.filters.order.trim().toLowerCase();
    const rows = dashboardState.orders.filter((o) => {
        if (!term) return true;
        return `${o.id} ${o.username || ''} ${o.status} ${o.order_date || ''} ${o.special_instructions || ''}`
            .toLowerCase()
            .includes(term);
    });

    // application du tri d'apres sortRows
    const sortedRows = sortRows(rows, 'order');

    if (sortedRows.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="fas fa-receipt mb-2 d-block" style="font-size:2rem; color:#ddd;"></i>
                    No orders found.
                </td>
            </tr>
        `;
        return;
    }

    // affichage des donnees
    tbody.innerHTML = sortedRows.map((o) => `
        <tr>
            <td>${o.order_date || '-'}</td>
            <td>#${o.id}</td>
            <td>${o.username || `User ${o.user_id}`}</td>
            <td>$${Number(o.total_amount || 0).toFixed(2)}</td>
            <td>
                <select class="form-select form-select-sm status-select" onchange="updateOrderStatus(${o.id}, this.value)">
                    <option value="Pending" ${o.status === 'Pending' ? 'selected' : ''}>Pending</option>
                    <option value="Confirmed" ${o.status === 'Confirmed' ? 'selected' : ''}>Confirmed</option>
                    <option value="Preparing" ${o.status === 'Preparing' ? 'selected' : ''}>Preparing</option>
                    <option value="Completed" ${o.status === 'Completed' ? 'selected' : ''}>Completed</option>
                    <option value="Delivered" ${o.status === 'Delivered' ? 'selected' : ''}>Delivered</option>
                    <option value="Cancelled" ${o.status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                </select>
            </td>
            <td>${o.special_instructions || '-'}</td>
            <td class="text-nowrap">
                <div class="d-inline-flex align-items-center flex-nowrap">
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editOrder(${o.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline-secondary me-1" onclick="viewOrderRow(${o.id})"><i class="fas fa-eye"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-delete-action="order" data-id="${o.id}"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
    `).join('');
}

function deleteOrder(id) {
    showDeleteConfirm('Are you sure you want to <strong>delete this order</strong>? This cannot be undone.', () => {
        // si confirm envoie a dashboardAction delete_order et id 
        dashboardAction({ action: 'delete_order', id }).then((res) => {
            // si reponse positive recharge fetchOrders sinon alerte erreur
            if (res.success) fetchOrders();
            else alert(res.message);
        });
    });
}

function updateOrderStatus(id, newStatus) {
    // si confirm envoie a dashboardAction update_order_status id et newStatus
    dashboardAction({ action: 'update_order_status', id, status: newStatus }).then((res) => {
        // si reponse positive recharge fetchOrders sinon alerte erreur
        if (res.success) fetchOrders();
        else alert(res.message);
    });
}

function editOrder(id) {
    // recherche dans la memoire le order d'apres id
    const order = dashboardState.orders.find((o) => o.id === id);
    if (!order) return;

    // mise a jour des info du modal
    dashboardState.editingOrderId = order.id;
    document.getElementById('orderFormModalLabel').innerHTML =
        '<span class="modal-title-icon"><i class="fas fa-receipt"></i></span> Edit Order';
    document.getElementById('order-form-submit-btn').innerHTML = '<i class="fas fa-check me-1"></i>Save';
    document.getElementById('order-form-id').value = String(order.id);
    document.getElementById('order-form-status').value = order.status || 'Pending';
    document.getElementById('order-form-notes').value = order.special_instructions || '';

    // discount a zero
    document.getElementById('order-discount-value').value = '';
    const pctRadio = document.querySelector('input[name="discount-type"][value="percent"]');
    if (pctRadio) pctRadio.checked = true;

    // afficher user selectionner
    selectOrderUser(order.user_id, order.username, false);

    // afficher item dans dropdown
    populateOrderItemSelect();

    // reconstruction panier d'apres item stocker
    dashboardState.orderCart = (order.items || []).map(item => ({
        menu_id: item.menu_id,
        name: item.name,
        price: item.price,
        quantity: item.quantity,
    }));
    renderOrderCart();
    recalcOrderTotal();

    dashboardState.modals.order?.show();
}

async function fetchReservations() {
    try {
        const res = await requestJSON('../Backend/api/dashboard/fetch_all_reservations.php');
        dashboardState.reservations = res.success ? (res.data || []) : [];
    } catch (e) {
        console.error('fetchReservations failed:', e);
        dashboardState.reservations = [];
    }
    renderReservations();
    updateDashboardStats();
}

function renderReservations() {
    const tbody = document.querySelector('#reservations-table tbody');
    if (!tbody) return;

    // recuperation valeur de search bar
    const term = dashboardState.filters.reservation.trim().toLowerCase();
    const rows = dashboardState.reservations.filter((r) => {
        if (!term) return true;
        return `${r.date} ${r.time} ${r.customer_name || ''} ${r.table_id || ''}`
            .toLowerCase()
            .includes(term);
    });

    // application du tri d'apres sortRows
    const sortedRows = sortRows(rows, 'reservation');

    if (sortedRows.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="fas fa-calendar mb-2 d-block" style="font-size:2rem; color:#ddd;"></i>
                    No reservations found.
                </td>
            </tr>
        `;
        return;
    }

    // affichage des donnees
    tbody.innerHTML = sortedRows.map((r) => `
        <tr>
            <td>${r.date}</td>
            <td>${r.time}</td>
            <td>${r.guests}</td>
            <td>${r.table_id || 'N/A'}</td>
            <td>${r.customer_name}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1" onclick="editReservation(${r.id})"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-outline-secondary me-1" onclick="viewReservationRow(${r.id})"><i class="fas fa-eye"></i></button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-delete-action="reservation" data-id="${r.id}"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

async function fetchRestaurantTables() {
    try {
        const res = await requestJSON('../Backend/api/dashboard/fetch_all_tables.php');
        dashboardState.restaurantTables = res.success ? (res.data || []) : [];
    } catch (e) {
        console.error('fetchRestaurantTables failed:', e);
        dashboardState.restaurantTables = [];
    }
    renderRestaurantTables();
}

function renderRestaurantTables() {
    const tbody = document.querySelector('#restaurant-tables-table tbody');
    if (!tbody) return;

    const rows = dashboardState.restaurantTables;
    if (rows.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                    <i class="fas fa-chair mb-2 d-block" style="font-size:2rem; color:#ddd;"></i>
                    No tables found.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = rows.map((t) => `
        <tr>
            <td>${t.id}</td>
            <td>${t.number}</td>
            <td>${t.capacity}</td>
            <td>${t.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td>
            <td class="text-nowrap">
                <div class="d-inline-flex align-items-center flex-nowrap gap-1">
                    <button class="btn btn-sm btn-outline-primary" onclick="editRestaurantTable(${t.id})" title="Edit"><i class="fas fa-edit"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-delete-action="table" data-id="${t.id}" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
    `).join('');
}

function addRestaurantTable() {
    dashboardState.editingTableId = null;
    document.getElementById('tableFormModalLabel').innerHTML =
        '<span class="modal-title-icon"><i class="fas fa-chair"></i></span> Add Table';
    document.getElementById('table-form')?.reset();
    document.getElementById('table-form-id').value = '';
    document.getElementById('table-form-active').value = '1';
    dashboardState.modals.table?.show();
}

function editRestaurantTable(id) {
    const table = dashboardState.restaurantTables.find((t) => t.id === id);
    if (!table) return;

    dashboardState.editingTableId = table.id;
    document.getElementById('tableFormModalLabel').innerHTML =
        '<span class="modal-title-icon"><i class="fas fa-chair"></i></span> Edit Table';
    document.getElementById('table-form-id').value = String(table.id);
    document.getElementById('table-form-number').value = String(table.number);
    document.getElementById('table-form-capacity').value = String(table.capacity);
    document.getElementById('table-form-active').value = table.is_active ? '1' : '0';
    dashboardState.modals.table?.show();
}

function deleteRestaurantTable(id) {
    showDeleteConfirm('Are you sure you want to <strong>delete this table</strong>?', () => {
        dashboardAction({ action: 'delete_table', id }).then((res) => {
            if (res.success) {
                fetchRestaurantTables();
                fetchAndPopulateTableSelect(null);
            } else {
                alert(res.message || 'Failed to delete table.');
            }
        });
    });
}

async function submitTableForm(e) {
    e.preventDefault();
    const id = dashboardState.editingTableId;
    const number = Number(document.getElementById('table-form-number')?.value || 0);
    const capacity = Number(document.getElementById('table-form-capacity')?.value || 0);
    const is_active = Number(document.getElementById('table-form-active')?.value ?? 1);

    if (number < 1 || capacity < 1) {
        alert('Table number and capacity must be at least 1.');
        return;
    }

    const payload = {
        action: id ? 'edit_table' : 'add_table',
        table_number: number,
        table_capacity: capacity,
        is_active,
    };
    if (id) payload.id = id;

    const res = await dashboardAction(payload);
    if (!res.success) {
        alert(res.message || 'Failed to save table.');
        return;
    }

    dashboardState.modals.table?.hide();
    fetchRestaurantTables();
    fetchAndPopulateTableSelect(null);
}

function deleteReservation(id) {
    showDeleteConfirm('Are you sure you want to <strong>delete this reservation</strong>? This cannot be undone.', () => {
        // si confirm envoie a dashboardAction delete_reservation et id 
        dashboardAction({ action: 'delete_reservation', id }).then(res => {
            // si reponse positive recharge fetchReservations sinon alerte erreur
            if (res.success) fetchReservations();
            else alert(res.message);
        });
    });
}


function editReservation(id) {
    // recherche dans la memoire la reservation d'apres id
    const reservation = dashboardState.reservations.find((r) => r.id === id);
    if (!reservation) return;

    // mise a jour des info du modal
    dashboardState.editingReservationId = reservation.id;
    document.getElementById('reservationFormModalLabel').innerHTML =
        '<span class="modal-title-icon"><i class="fas fa-calendar-alt"></i></span> Edit Reservation';
    document.getElementById('reservation-form-submit-btn').innerHTML = '<i class="fas fa-check me-1"></i>Save';
    document.getElementById('reservation-form-id').value = String(reservation.id);
    document.getElementById('reservation-form-date').value = reservation.date || '';
    document.getElementById('reservation-form-time').value = normalizeDashboardTimeValue(reservation.time || '');
    document.getElementById('reservation-form-guests').value = String(reservation.guests ?? 1);
    document.getElementById('reservation-form-notes').value = reservation.special_notes || '';

    // afficher user selectionner
    selectReservationUser(reservation.user_id, reservation.customer_name, false);

    fetchAndPopulateTableSelect(reservation.table_id, {
        guests: reservation.guests,
        date: reservation.date,
        time: normalizeDashboardTimeValue(reservation.time || ''),
        excludeReservationId: reservation.id,
    });

    dashboardState.modals.reservation?.show();
}

async function fetchReviews() {
    try {
        const res = await requestJSON('../Backend/api/dashboard/fetch_reviews.php');
        dashboardState.reviews = res.success ? (res.data || []) : [];
    } catch (e) {
        console.error('fetchReviews failed:', e);
        dashboardState.reviews = [];
    }
    renderReviews();
}
function renderReviews() {
    const container = document.querySelector('#review .bg-white');
    if (!container) return;

    // recuperation valeur de search bar
    const term = dashboardState.filters.review.trim().toLowerCase();
    const rows = dashboardState.reviews.filter((rev) => {
        if (!term) return true;
        return `${rev.user_id || ''} ${rev.menu_id || ''} ${rev.content || ''} ${rev.created_at || ''}`
            .toLowerCase()
            .includes(term);
    });

    if (rows.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-star mb-2 d-block" style="font-size:2rem; color:#ddd;"></i>
                No reviews found.
            </div>
        `;
        return;
    }

    // affichage des donnees
    container.innerHTML = rows.map((rev) => {
        // gestion des etoiles
        const stars = Array(rev.rating).fill('<i class="fas fa-star text-warning"></i>').join('') +
            Array(5 - rev.rating).fill('<i class="fas fa-star text-muted"></i>').join('');

        // recuperation image et nom du plat
        const menuItem = dashboardState.menuItems.find(m => m.id === rev.menu_id);
        const imageSrc = menuItem ? (menuItem.image || '') : '';
        const menuName = menuItem ? menuItem.name : `Menu Item #${rev.menu_id}`;

        // affichage des donnees
        return `
            <div class="row align-items-center py-2 px-4">
                <div class="col-md d-flex align-items-start gap-3 py-1">
                    <img src="${imageSrc}" alt="Menu Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; margin-top: 1.5rem;">
                    <div>
                        <p class="mb-0"><strong>User #${rev.user_id || 'Unknown'}</strong> <span class="text-muted small">· ${menuName} · ${rev.created_at}</span></p>
                        <p class="mb-0">${stars}</p>
                        <p class="mb-0 mt-1 small">${rev.content}</p>
                    </div>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteReview(${rev.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
           
            </div>
            <hr class="mx-4">
        `;
    }).join('');
}

function deleteReview(id) {
    showDeleteConfirm('Are you sure you want to <strong>delete this review</strong>? This cannot be undone.', () => {
        // si confirm envoie a dashboardAction delete_review et id 
        dashboardAction({ action: 'delete_review', id }).then(res => {
            // si reponse positive recharge fetchReviews sinon alerte erreur
            if (res.success) fetchReviews();
            else alert(res.message);
        });
    });
}


async function fetchUsers() {
    try {
        const res = await requestJSON('../Backend/api/dashboard/fetch_all_users.php');
        dashboardState.users = res.success ? (res.data || []) : [];
    } catch (e) {
        console.error('Failed to fetch users : ', e);
        dashboardState.users = [];
    }
    renderUsers();
    updateDashboardStats();
}


function renderUsers() {
    const tbody = document.querySelector('#users tbody');
    if (!tbody) return;

    // recuperation valeur de search bar
    const term = dashboardState.filters.user.trim().toLowerCase();
    const rows = dashboardState.users.filter((u) => {
        if (!term) return true;
        return `${u.username} ${u.email} ${u.role}`
            .toLowerCase()
            .includes(term);
    });

    // application du tri d'apres sortRows
    const sortedRows = sortRows(rows, 'user');

    if (sortedRows.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    <i class="fas fa-users mb-2 d-block" style="font-size:2rem; color:#ddd;"></i>
                    No users found.
                </td>
            </tr>
        `;
        return;
    }

    // affichage des donnees
    tbody.innerHTML = sortedRows.map((u) => {
        let actionBtns = '';
        if (u.role !== 'Admin' && u.role !== 'admin') {
            actionBtns += `<button class="btn btn-sm btn-outline-primary" onclick="makeAdmin(${u.id})" title="Make Admin"><i class="fas fa-user-shield"></i></button>`;
        } else {
            actionBtns += `<button class="btn btn-sm btn-outline-secondary" onclick="makeUser(${u.id})" title="Make User"><i class="fas fa-user"></i></button>`;
        }
        actionBtns += `<button type="button" class="btn btn-sm btn-outline-danger" data-delete-action="user" data-id="${u.id}" title="Delete"><i class="fas fa-trash"></i></button>`;

        return `
            <tr>
                <td>${u.username}</td>
                <td>${u.email}</td>

                <td>${u.created_at || 'N/A'}</td>
                <td>${u.role}</td>
                <td class="text-nowrap">
                    <div class="d-inline-flex align-items-center flex-nowrap gap-1">
                        <button class="btn btn-sm btn-outline-secondary" onclick="viewUserRow(${u.id})" title="View"><i class="fas fa-eye"></i></button>
                        ${actionBtns}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function makeAdmin(id) {
    showPriviledgeConfirm('Are you sure you want to <strong>grant admin privileges</strong> to this user?', () => {
        // si confirm envoie a dashboardAction make_admin et id
        dashboardAction({ action: 'make_admin', id }).then(res => {
            // si reponse positive recharge fetchUsers sinon alerte erreur
            if (res.success) fetchUsers();
            else alert(res.message);
        });
    });
}

function makeUser(id) {
    showPriviledgeConfirm('Are you sure you want to <strong>remove admin privileges</strong> from this user?', () => {
        // si confirm envoie a dashboardAction make_user et id
        dashboardAction({ action: 'make_user', id }).then(res => {
            // si reponse positive recharge fetchUsers sinon alerte erreur
            if (res.success) fetchUsers();
            else alert(res.message);
        });
    });
}

function deleteUser(id) {
    showDeleteConfirm('Are you sure you want to <strong>permanently delete this user</strong>? This cannot be undone.', () => {
        // si confirm envoie a dashboardAction delete_user et id
        dashboardAction({ action: 'delete_user', id }).then(res => {
            // si reponse positive recharge fetchUsers sinon alerte erreur
            if (res.success) fetchUsers();
            else alert(res.message);
        });
    });
}

// rendre le frontend interactif
function setupDashboardInteractions() {
    // setup du tri des tables
    setupTableSorting();
    // setup de la recherche dans les tables (a chaque changement d etat on relence le render)
    wireSearch('menu', 'search-menu-input', 'search-menu-btn', renderMenuItems);
    wireSearch('order', 'search-order-input', 'search-order-btn', renderOrders);
    wireSearch('reservation', 'search-reservation-input', 'search-reservation-btn', renderReservations);
    wireSearch('review', 'search-review-input', 'search-review-btn', renderReviews);
    wireSearch('user', 'search-user-input', 'search-user-btn', renderUsers);

    // setup des boutons d'add des modal
    const addMenuBtn = document.getElementById('add-menu-btn');
    if (addMenuBtn) addMenuBtn.addEventListener('click', addMenuItem);

    const addReservationBtn = document.getElementById('add-reservation-btn');
    if (addReservationBtn) addReservationBtn.addEventListener('click', addReservation);

    const addOrderBtn = document.getElementById('add-order-btn');
    if (addOrderBtn) addOrderBtn.addEventListener('click', addOrder);

    const addReviewBtn = document.getElementById('add-review-btn');
    if (addReviewBtn) addReviewBtn.addEventListener('click', addReview);

    const addUserBtn = document.getElementById('add-user-btn');
    if (addUserBtn) addUserBtn.addEventListener('click', addUser);

    const addTableBtn = document.getElementById('add-table-btn');
    if (addTableBtn) addTableBtn.addEventListener('click', addRestaurantTable);

    const tableForm = document.getElementById('table-form');
    if (tableForm) tableForm.addEventListener('submit', submitTableForm);
    /*
        const quantityInput = document.getElementById('menu-form-quantity');
        if (quantityInput) quantityInput.addEventListener('input', updateMenuStockPreview);
    */

    // setup du modal
    initDashboardModals();

    // order modal - cart et search
    const orderAddItemBtn = document.getElementById('order-add-item-btn');
    if (orderAddItemBtn) orderAddItemBtn.addEventListener('click', addItemToOrderCart);

    const orderDiscountVal = document.getElementById('order-discount-value');
    if (orderDiscountVal) orderDiscountVal.addEventListener('input', recalcOrderTotal);
    document.querySelectorAll('input[name="discount-type"]').forEach(r =>
        r.addEventListener('change', recalcOrderTotal));

    const orderUserSearch = document.getElementById('order-user-search');
    if (orderUserSearch) orderUserSearch.addEventListener('input', onOrderUserSearchInput);

    // reservation modal - user search
    const resUserSearch = document.getElementById('reservation-user-search');
    if (resUserSearch) resUserSearch.addEventListener('input', onReservationUserSearchInput);

    // fermer les dropdowns au click a l'exterieur
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#order-user-search') && !e.target.closest('#order-user-search-results'))
            _hideDropdown('order-user-search-results');
        if (!e.target.closest('#reservation-user-search') && !e.target.closest('#reservation-user-search-results'))
            _hideDropdown('reservation-user-search-results');
    });

    // setup des bouttons save form
    const menuForm = document.getElementById('menu-form');
    if (menuForm) menuForm.addEventListener('submit', submitMenuForm);

    const reservationForm = document.getElementById('reservation-form');
    if (reservationForm) reservationForm.addEventListener('submit', submitReservationForm);

    const resGuestsInput = document.getElementById('reservation-form-guests');
    const resDateInput = document.getElementById('reservation-form-date');
    const resTimeInput = document.getElementById('reservation-form-time');
    const refreshReservationTables = () => {
        const guests = Number(resGuestsInput?.value || 0);
        const date = resDateInput?.value || '';
        const time = resTimeInput?.value || '';
        const selected = Number(document.getElementById('reservation-form-table-id')?.value || 0) || null;
        fetchAndPopulateTableSelect(selected, {
            guests,
            date,
            time,
            excludeReservationId: dashboardState.editingReservationId,
        });
    };
    if (resGuestsInput) resGuestsInput.addEventListener('input', refreshReservationTables);
    if (resDateInput) resDateInput.addEventListener('change', refreshReservationTables);
    if (resTimeInput) resTimeInput.addEventListener('change', refreshReservationTables);

    setupDashboardDeleteDelegation();

    const orderForm = document.getElementById('order-form');
    if (orderForm) orderForm.addEventListener('submit', submitOrderForm);

    const reviewForm = document.getElementById('review-form');
    if (reviewForm) reviewForm.addEventListener('submit', submitReviewForm);

    const userForm = document.getElementById('user-form');
    if (userForm) userForm.addEventListener('submit', submitUserForm);
}

// tri dynamique en cliquant sur les table header
function setupTableSorting() {
    // trouve tout colonnes triable
    const headers = document.querySelectorAll('th[data-sort-table][data-sort-key]');
    headers.forEach((header) => {
        header.addEventListener('click', () => {
            const table = header.getAttribute('data-sort-table');
            const key = header.getAttribute('data-sort-key');
            if (!table || !key || !dashboardState.sort[table]) return;

            const current = dashboardState.sort[table];
            let nextDirection = 1; // 1 = Croissant, -1 = Decroissant, 0 = Pas de tri

            // Cycle de tri : Croissant -> Decroissant -> Normal
            if (current.key === key) {
                if (current.direction === 1) nextDirection = -1;
                else if (current.direction === -1) nextDirection = 0;
                else nextDirection = 1;
            }

            // maj etat tri en memoire
            dashboardState.sort[table] = {
                key: nextDirection === 0 ? null : key,
                direction: nextDirection
            };

            // maj flèches et redessine tableau
            updateSortIndicators(table);
            renderTableByName(table);
        });
    });
}

// render tableau selon son nom
function renderTableByName(table) {
    if (table === 'menu') renderMenuItems();
    if (table === 'order') renderOrders();
    if (table === 'reservation') renderReservations();
    if (table === 'user') renderUsers();
}

// affiche fleches
function updateSortIndicators(table) {
    const headers = document.querySelectorAll(`th[data-sort-table="${table}"]`);
    headers.forEach((header) => {
        // retire fleche pour repartir du nom
        const baseText = (header.textContent || '').replace(/\s[▲▼]$/, '');
        const key = header.getAttribute('data-sort-key');
        const sort = dashboardState.sort[table];

        // ajoute fleche si colonne trie
        if (sort.key === key && sort.direction === 1) {
            header.textContent = `${baseText} ▲`;
        } else if (sort.key === key && sort.direction === -1) {
            header.textContent = `${baseText} ▼`;
        } else {
            header.textContent = baseText;
        }
    });
}

// trie effectif des lignes avant affichage
function sortRows(rows, table) {
    const sort = dashboardState.sort[table];
    // si pas tri actif renvoie tel quel
    if (!sort || !sort.key || sort.direction === 0) return rows;

    const direction = sort.direction;
    const key = sort.key;

    // .sort() fonction native de tri de liste
    return [...rows].sort((a, b) => {
        // normalise pour comparer correctement
        const av = normalizeSortValue(a[key]);
        const bv = normalizeSortValue(b[key]);
        if (av < bv) return -1 * direction;
        if (av > bv) return 1 * direction;
        return 0;
    });
}

// normalise pour comparer correctement
function normalizeSortValue(value) {
    if (value === null || value === undefined) return '';
    if (typeof value === 'number') return value;
    const asNumber = Number(value);
    if (!Number.isNaN(asNumber) && String(value).trim() !== '') return asNumber;
    const asDate = Date.parse(value);
    if (!Number.isNaN(asDate)) return asDate;
    return String(value).toLowerCase();
}

function wireSearch(filterKey, inputId, buttonId, renderFn) {
    // get input et button
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);
    if (!input) return;

    // prend l input et l'ajoute en memoire
    const applyFilter = () => {
        dashboardState.filters[filterKey] = input.value || '';
        renderFn();
    };

    // input et button apply le filtre
    input.addEventListener('input', applyFilter);
    if (button) button.addEventListener('click', applyFilter);
}

// modal mode add (sans preremplissage)
async function addMenuItem() {
    openMenuModal('add');
}

async function addReservation() {
    dashboardState.editingReservationId = null; // mode creation, pas edition

    // maj titre et bouton
    document.getElementById('reservationFormModalLabel').innerHTML =
        '<span class="modal-title-icon"><i class="fas fa-calendar-alt"></i></span> Add Reservation';
    document.getElementById('reservation-form-submit-btn').innerHTML = '<i class="fas fa-check me-1"></i>Add';

    // vide le formulaire
    document.getElementById('reservation-form')?.reset();
    document.getElementById('reservation-form-id').value = '';
    document.getElementById('reservation-form-user-id').value = '';
    document.getElementById('reservation-form-guests').value = '2';

    // reset user search
    const si = document.getElementById('reservation-user-search');
    if (si) si.value = '';
    const badge = document.getElementById('reservation-selected-user-badge');
    if (badge) { badge.style.display = 'none'; badge.innerHTML = ''; }
    _hideDropdown('reservation-user-search-results');

    fetchAndPopulateTableSelect(null, { guests: 2 });

    dashboardState.modals.reservation?.show();
}

async function addOrder() {
    dashboardState.editingOrderId = null;
    dashboardState.orderCart = [];

    // maj titre et bouton
    document.getElementById('orderFormModalLabel').innerHTML =
        '<span class="modal-title-icon"><i class="fas fa-receipt"></i></span> Add Order';
    document.getElementById('order-form-submit-btn').innerHTML = '<i class="fas fa-check me-1"></i>Add';

    // vide le formulaire
    document.getElementById('order-form')?.reset();
    document.getElementById('order-form-id').value = '';
    document.getElementById('order-form-user-id').value = '';
    document.getElementById('order-form-status').value = 'Pending';

    // reset user search
    const si = document.getElementById('order-user-search');
    if (si) si.value = '';
    const badge = document.getElementById('order-selected-user-badge');
    if (badge) { badge.style.display = 'none'; badge.innerHTML = ''; }
    _hideDropdown('order-user-search-results');

    // reset discount
    const dv = document.getElementById('order-discount-value');
    if (dv) dv.value = '';
    const pctRadio = document.querySelector('input[name="discount-type"][value="percent"]');
    if (pctRadio) pctRadio.checked = true;

    // prepa list plats panier et recalcule total
    populateOrderItemSelect();
    renderOrderCart();
    recalcOrderTotal();

    dashboardState.modals.order?.show();
}

async function addReview() {
    // vide formulaire
    document.getElementById('review-form')?.reset();
    document.getElementById('review-form-rating').value = '5';

    // remplit list avec les plats
    const menuSelect = document.getElementById('review-form-menu-id');
    if (menuSelect) {
        menuSelect.innerHTML = '<option value="" disabled selected>Select a menu item...</option>' +
            dashboardState.menuItems.map(m => `<option value="${m.id}">${m.name}</option>`).join('');
    }

    dashboardState.modals.review?.show();
}

async function addUser() {
    // vide formulaire
    document.getElementById('user-form')?.reset();
    document.getElementById('user-form-role').value = 'user';
    document.getElementById('userFormPasswordMessage').textContent = '';
    document.getElementById('userFormMatchMessage').textContent = '';
    dashboardState.modals.user?.show();
}

// maj stat dashboard
function updateDashboardStats() {
    // nombre pending orders
    const pendingOrders = dashboardState.orders.filter((o) => o.status === 'Pending').length;
    // nombre total users
    const activeUsers = dashboardState.users.length;
    // nombre total commandes
    const totalOrders = dashboardState.orders.length;
    // total sales
    const totalSales = dashboardState.orders.reduce((sum, order) => sum + Number(order.total_amount || 0), 0);

    const reservationsEl = document.getElementById('stat-reservations');
    const usersEl = document.getElementById('stat-users');
    const ordersEl = document.getElementById('stat-orders');
    const salesEl = document.getElementById('stat-sales');

    // badges (%)
    const reservationsBadgeEl = document.getElementById('stat-badge-reservations');
    const usersBadgeEl = document.getElementById('stat-badge-users');
    const ordersBadgeEl = document.getElementById('stat-badge-orders');
    const salesBadgeEl = document.getElementById('stat-badge-sales');

    // affiche valeur
    if (reservationsEl) reservationsEl.textContent = String(pendingOrders);
    if (usersEl) usersEl.textContent = String(activeUsers);
    if (ordersEl) ordersEl.textContent = String(totalOrders);
    if (salesEl) salesEl.textContent = `$${totalSales.toFixed(2)}`;

    // evolution (+X% ou -X%) / semaine 
    const pendingOrderGrowth = computeGrowthRateFromDateField(
        dashboardState.orders.filter((o) => o.status === 'Pending'),
        'order_date'
    );
    const userGrowth = computeGrowthRateFromDateField(dashboardState.users, 'created_at');
    const orderGrowth = computeGrowthRateFromDateField(dashboardState.orders, 'order_date');
    const salesGrowth = computeSalesGrowthRate(dashboardState.orders, 'order_date', 'total_amount');

    // maj affichage badges
    updateStatBadge(reservationsBadgeEl, pendingOrderGrowth);
    updateStatBadge(usersBadgeEl, userGrowth);
    updateStatBadge(ordersBadgeEl, orderGrowth);
    updateStatBadge(salesBadgeEl, salesGrowth);
}

function computeGrowthRateFromDateField(rows, dateField) {
    // date d'aujourd'hui
    const now = new Date();
    // debut semaine en cours (7 jours)
    const recentWindowStart = new Date(now);
    recentWindowStart.setDate(now.getDate() - 7);
    // debut semaine precedente (14 jours)
    const previousWindowStart = new Date(now);
    previousWindowStart.setDate(now.getDate() - 14);

    let recentCount = 0;
    let previousCount = 0;

    rows.forEach((row) => {
        // get date de l'element
        const raw = row?.[dateField];
        if (!raw) return;
        const d = new Date(raw);
        if (Number.isNaN(d.getTime())) return;

        // si date 7 derniers jours -> incremente semaine en cours
        if (d >= recentWindowStart && d <= now) recentCount += 1;
        // si semaine d'avant -> incremente semaine precedente
        else if (d >= previousWindowStart && d < recentWindowStart) previousCount += 1;
    });

    // retourn %
    return computeGrowthRate(previousCount, recentCount);
}

// calcule taux croissance ventes
function computeSalesGrowthRate(rows, dateField, amountField) {
    // meme window de temps (aujourd'hui, -7j, -14j)
    const now = new Date();
    const recentWindowStart = new Date(now);
    recentWindowStart.setDate(now.getDate() - 7);
    const previousWindowStart = new Date(now);
    previousWindowStart.setDate(now.getDate() - 14);

    let recentSales = 0;
    let previousSales = 0;

    // parcourt commande
    rows.forEach((row) => {
        const rawDate = row?.[dateField];
        if (!rawDate) return;
        const d = new Date(rawDate);
        if (Number.isNaN(d.getTime())) return;

        // montant commande
        const amount = Number(row?.[amountField] || 0);
        // + montant a bonne semaine
        if (d >= recentWindowStart && d <= now) recentSales += amount;
        else if (d >= previousWindowStart && d < recentWindowStart) previousSales += amount;
    });

    // retourn %
    return computeGrowthRate(previousSales, recentSales);
}

// formule calcul %
function computeGrowthRate(previousValue, currentValue) {
    // brgin 0 ?
    if (previousValue === 0) {
        return currentValue > 0 ? 100 : 0; // Croissance max : null
    }
    // ((Nouveau - Ancien) / Ancien) * 100
    return ((currentValue - previousValue) / previousValue) * 100;
}

function updateStatBadge(element, percent) {
    if (!element) return;
    const safePercent = Number.isFinite(percent) ? percent : 0;
    const rounded = Math.round(safePercent);
    // add "+" si positif
    const prefix = rounded > 0 ? '+' : '';
    // affiche text ("+15%")
    element.textContent = `${prefix}${rounded}%`;

    if (rounded > 0) {
        element.style.color = '#16c451';
    } else if (rounded < 0) {
        element.style.color = '#dc3545';
        // si 0 pas couleur 
    } else {
        element.style.color = '';
    }
}

// init modal
function initDashboardModals() {
    dashboardState.modals.menu = createModal('menuFormModal');
    dashboardState.modals.order = createModal('orderFormModal');
    dashboardState.modals.reservation = createModal('reservationFormModal');
    dashboardState.modals.review = createModal('reviewFormModal');
    dashboardState.modals.user = createModal('userFormModal');
    dashboardState.modals.rowView = createModal('rowViewModal');
    dashboardState.modals.deleteConfirm = createModal('deleteConfirmModal');
    dashboardState.modals.priviledgeConfirm = createModal('priviledgeConfirmModal');
    dashboardState.modals.table = createModal('tableFormModal');
}

function getDeleteConfirmModal() {
    const modalEl = document.getElementById('deleteConfirmModal');
    if (!modalEl || typeof bootstrap === 'undefined') {
        return null;
    }
    if (!dashboardState.modals.deleteConfirm) {
        dashboardState.modals.deleteConfirm = bootstrap.Modal.getOrCreateInstance(modalEl);
    }
    return dashboardState.modals.deleteConfirm;
}

function showDeleteConfirm(message, onConfirm) {
    const msgEl = document.getElementById('deleteConfirmMessage');
    const btnEl = document.getElementById('deleteConfirmBtn');
    const modal = getDeleteConfirmModal();

    if (!msgEl || !btnEl || !modal) {
        if (confirm(message.replace(/<[^>]*>/g, ''))) onConfirm();
        return;
    }

    msgEl.innerHTML = message;

    const newBtn = btnEl.cloneNode(true);
    btnEl.parentNode.replaceChild(newBtn, btnEl);
    newBtn.id = 'deleteConfirmBtn';

    // click -> hide modal & execute onConfirm
    newBtn.addEventListener('click', () => {
        modal.hide();
        onConfirm();
        // applique une fois
    }, { once: true });

    modal.show();
}

function showPriviledgeConfirm(message, onConfirm) {
    const msgEl = document.getElementById('priviledgeConfirmMessage');
    const btnEl = document.getElementById('priviledgeConfirmBtn');
    // si introuvable fallback -> confirm()
    if (!msgEl || !btnEl) {
        // retire balise html
        if (confirm(message.replace(/<[^>]*>/g, ''))) onConfirm();
        return;
    }

    msgEl.innerHTML = message;

    // clone bouton reset event listeners
    const newBtn = btnEl.cloneNode(true);
    btnEl.parentNode.replaceChild(newBtn, btnEl);
    newBtn.id = 'priviledgeConfirmBtn';

    // click -> hide modal & execute onConfirm
    newBtn.addEventListener('click', () => {
        dashboardState.modals.priviledgeConfirm?.hide();
        onConfirm();
        // applique une fois
    }, { once: true });

    dashboardState.modals.priviledgeConfirm?.show();
}

// helper si id not found ou bootstrap modal not ready -> null 
function createModal(id) {
    const el = document.getElementById(id);
    if (!el || typeof bootstrap === 'undefined') return null;
    return bootstrap.Modal.getOrCreateInstance(el);
}

function openMenuModal(mode, item = null) {
    const form = document.getElementById('menu-form');
    if (!form) return;

    const title = document.getElementById('menuFormModalLabel');
    const idInput = document.getElementById('menu-form-id');
    const nameInput = document.getElementById('menuName');
    const descriptionInput = document.getElementById('menu_description');
    const categoryInput = document.getElementById('menu-form-category');
    const priceInput = document.getElementById('menu-price');
    const quantityInput = document.getElementById('menu-form-quantity');
    const imageInput = document.getElementById('menu-form-image');

    form.reset();

    if (mode === 'edit' && item) {
        dashboardState.editingMenuId = item.id;
        if (title) title.textContent = 'Edit Menu Item';
        if (idInput) idInput.value = String(item.id);
        if (nameInput) nameInput.value = item.name || '';
        if (descriptionInput) descriptionInput.value = item.description || '';
        // select category option by category_id
        if (categoryInput) categoryInput.value = String(item.category_id || '');
        if (priceInput) priceInput.value = String(item.price ?? '');
        if (imageInput) imageInput.value = item.image || '';
    } else {
        dashboardState.editingMenuId = null;
        if (title) title.textContent = 'Add Menu Item';
        if (idInput) idInput.value = '';
        // first category
        if (categoryInput && categoryInput.options.length > 0) categoryInput.selectedIndex = 0;
        if (imageInput) imageInput.value = '';
    }

    dashboardState.modals.menu?.show();
}

async function submitMenuForm(e) {
    // empeche rechargement de page
    e.preventDefault();
    // id = edition, null = creation
    const id = dashboardState.editingMenuId;
    const name = document.getElementById('menuName')?.value.trim() || '';
    const description = document.getElementById('menu_description')?.value.trim() || '';
    const ingredients = document.getElementById('menu-form-ingredients')?.value.trim() || '';
    const categoryId = Number(document.getElementById('menu-form-category')?.value || 0);
    const price = Number(document.getElementById('menu-price')?.value || 0);
    const image_url = document.getElementById('menu-form-image')?.value.trim() || '';

    // validation des donnees requises
    if (!name || categoryId <= 0 || !Number.isFinite(price) || price <= 0) {
        alert('Please provide a valid name, category, and price.');
        return;
    }

    // objet a envoyer
    const payload = {
        action: id ? 'edit_menu' : 'add_menu',
        name, description, ingredients,
        category_id: categoryId,
        price, image_url,
    };
    // ajoute ID si on modifie
    if (id) payload.id = id;

    // envoi serveur (API)
    const res = await dashboardAction(payload);
    // erreur serveur
    if (!res.success) { alert(res.message || 'Failed to save menu item.'); return; }

    // ferme fenetre et recharge liste
    dashboardState.modals.menu?.hide();
    fetchMenuItems();
}
/*
function updateMenuStockPreview() {
    // get elements
    const previewEl = document.getElementById('menu-form-stock-preview');
    const quantityInput = document.getElementById('menu-form-quantity');
    if (!previewEl || !quantityInput) return;

    // render status
    const quantity = Number(quantityInput.value ?? 0);
    previewEl.innerHTML = renderStockStatus(quantity);
}
*/
function validateReservationClient(tableId, date, time, guests, excludeReservationId = null) {
    const table = dashboardState.restaurantTables.find((t) => t.id === tableId);
    if (!table) {
        return 'Please select a valid table.';
    }
    if (!table.is_active) {
        return 'This table is not active.';
    }
    if (table.capacity < guests) {
        return `Table capacity (${table.capacity}) is less than the number of guests (${guests}).`;
    }

    const timeKey = normalizeDashboardTimeValue(time);
    const conflict = dashboardState.reservations.find((r) => {
        if (excludeReservationId && r.id === excludeReservationId) return false;
        return r.table_id === tableId
            && r.date === date
            && reservationTimesOverlap(timeKey, normalizeDashboardTimeValue(r.time || ''));
    });

    if (conflict) {
        return 'This table is already reserved for that date and time slot.';
    }

    return null;
}

async function submitReservationForm(e) {
    // empeche rechargement de page
    e.preventDefault();
    // recup ids table et client
    const userId = Number(document.getElementById('reservation-form-user-id')?.value || 0);
    const tableId = Number(document.getElementById('reservation-form-table-id')?.value || 0);
    // recup values form horaires
    const date = document.getElementById('reservation-form-date')?.value || '';
    const time = document.getElementById('reservation-form-time')?.value || '';
    const guests = Number(document.getElementById('reservation-form-guests')?.value || 0);
    const notes = document.getElementById('reservation-form-notes')?.value.trim() || '';

    // verifications alertes
    if (!userId) { alert('Please select a customer.'); return; }
    if (!tableId) { alert('Please select a table.'); return; }
    if (!date || !time || guests <= 0) {
        alert('Please fill in Date, Time and number of Guests.');
        return;
    }

    // check mode edition
    const reservationId = dashboardState.editingReservationId;
    const clientError = validateReservationClient(tableId, date, time, guests, reservationId);
    if (clientError) {
        alert(clientError);
        return;
    }
    // objet a envoyer
    const payload = {
        action: reservationId ? 'edit_reservation' : 'add_reservation',
        user_id: userId,
        table_id: tableId,
        date,
        time: normalizeDashboardTimeValue(time),
        guests,
        special_notes: notes,
    };
    // ajoute ID si on modifie
    if (reservationId) payload.id = reservationId;

    // envoie serveur (API)
    const res = await dashboardAction(payload);
    if (!res.success) { alert(res.message || 'Failed to save reservation.'); return; }

    dashboardState.modals.reservation?.hide();
    await fetchReservations();
    fetchRestaurantTables();
}

async function submitOrderForm(e) {
    // empeche rechargement de page
    e.preventDefault();
    // user et cart prerequis 
    const user_id = Number(document.getElementById('order-form-user-id')?.value || 0);
    if (!user_id) { alert('Please select a customer.'); return; }
    if (dashboardState.orderCart.length === 0) { alert('Please add at least one item to the order.'); return; }

    // recup infos
    const status = document.getElementById('order-form-status')?.value || 'Pending';
    const special_instructions = document.getElementById('order-form-notes')?.value.trim() || '';
    const discountType = document.querySelector('input[name="discount-type"]:checked')?.value || 'percent';
    const discountValue = parseFloat(document.getElementById('order-discount-value')?.value || 0) || 0;

    // reformat list -> db ([{menu_id, quantity}])
    const items = dashboardState.orderCart.map(c => ({ menu_id: c.menu_id, quantity: c.quantity }));

    // check edition
    const orderId = dashboardState.editingOrderId;
    // objet a envoyer
    const payload = {
        action: orderId ? 'edit_order' : 'add_order',
        user_id,
        status,
        special_instructions,
        items,
        discount_type: discountType,
        discount_value: discountValue,
    };
    // ajoute ID si on modifie
    if (orderId) payload.id = orderId;

    // envoi serveur (API)
    const res = await dashboardAction(payload);
    if (!res.success) { alert(res.message || 'Failed to save order.'); return; }

    dashboardState.modals.order?.hide();
    fetchOrders();
}

async function submitReviewForm(e) {
    // empeche rechargement de page
    e.preventDefault();
    const menu_id = Number(document.getElementById('review-form-menu-id')?.value || 0);
    const rating = Number(document.getElementById('review-form-rating')?.value || 0);
    const content = document.getElementById('review-form-content')?.value.trim() || '';

    if (!menu_id || rating < 1 || rating > 5) {
        alert('Please select a menu item and a rating between 1 and 5.');
        return;
    }

    // API send (add review)
    const res = await dashboardAction({ action: 'add_review', menu_id, rating, content });
    if (!res.success) { alert(res.message || 'Failed to add review.'); return; }

    dashboardState.modals.review?.hide();
    fetchReviews();
}

async function submitUserForm(e) {
    e.preventDefault();
    const username = document.getElementById('user-form-username')?.value.trim() || '';
    const email = document.getElementById('user-form-email')?.value.trim() || '';
    const password = document.getElementById('user-form-password')?.value || '';
    const passwordConfirm = document.getElementById('user-form-password-confirm')?.value || '';
    const role = document.getElementById('user-form-role')?.value || 'user';

    if (!username || !email || !password) {
        alert('Please fill username, email and password.');
        return;
    }

    if (!isDashboardStrongPassword(password)) {
        checkDashboardUserPasswordStrength();
        alert('Password must have: min 8 chars, uppercase, lowercase, number, and special character.');
        return;
    }

    if (password !== passwordConfirm) {
        checkDashboardUserPasswordMatch();
        alert('Passwords do not match.');
        return;
    }

    // objet a envoyer
    const res = await dashboardAction({
        action: 'add_user',
        username,
        email,
        password,
        role
    });

    if (!res.success) {
        alert(res.message || 'Failed to add user.');
        return;
    }

    dashboardState.modals.user?.hide();
    fetchUsers();
}

function openRowViewModal(title, fields) {
    const titleEl = document.getElementById('rowViewModalLabel');
    const bodyEl = document.getElementById('rowViewModalBody');
    if (!titleEl || !bodyEl) return;

    titleEl.innerHTML = `<span class="modal-title-icon"><i class="fas fa-eye"></i></span> ${title}`;
    bodyEl.innerHTML = fields.map((field) => `
        <div class="mb-3">
            <p class="mb-1 text-muted small">${field.label}</p>
            <div class="p-2 border rounded bg-light-subtle">${field.value || '-'}</div>
        </div>
    `).join('');

    dashboardState.modals.rowView?.show();
}

function viewMenuRow(id) {
    // recup info
    const item = dashboardState.menuItems.find((m) => m.id === id);
    if (!item) return;
    openRowViewModal(`Menu Item #${item.id}`, [
        { label: 'Name', value: item.name },
        { label: 'Category', value: item.category },
        { label: 'Price', value: `$${Number(item.price || 0).toFixed(2)}` },
        { label: 'Description', value: item.description },
        { label: 'Image URL', value: item.image }
    ]);
}

function viewOrderRow(id) {
    // recup info
    const order = dashboardState.orders.find((o) => o.id === id);
    if (!order) return;

    // list items dans order
    let itemsHtml = '-';
    if (order.items && order.items.length > 0) {
        itemsHtml = '<ul class="mb-0" style="padding-left: 20px;">' +
            order.items.map(i => `<li>${i.quantity}x ${i.name} ($${Number(i.price || 0).toFixed(2)})</li>`).join('') +
            '</ul>';
    }

    openRowViewModal(`Order #${order.id}`, [
        { label: 'Date', value: order.order_date },
        { label: 'User', value: `${order.username || 'Unknown'} (ID: ${order.user_id})` },
        { label: 'Items', value: itemsHtml },
        { label: 'Total Amount', value: `$${Number(order.total_amount || 0).toFixed(2)}` },
        { label: 'Status', value: order.status },
        { label: 'Special Instructions', value: order.special_instructions }
    ]);
}

function viewReservationRow(id) {
    // recup info
    const reservation = dashboardState.reservations.find((r) => r.id === id);
    if (!reservation) return;
    openRowViewModal(`Reservation #${reservation.id}`, [
        { label: 'Date', value: reservation.date },
        { label: 'Time', value: reservation.time },
        { label: 'Customer', value: reservation.customer_name },
        { label: 'Guests', value: String(reservation.guests) },
        { label: 'Status', value: reservation.status },
        { label: 'Contact', value: reservation.contact }
    ]);
}

function viewUserRow(id) {
    // recup info
    const user = dashboardState.users.find((u) => u.id === id);
    if (!user) return;
    openRowViewModal(`User #${user.id}`, [
        { label: 'Username', value: user.username },
        { label: 'Email', value: user.email },
        { label: 'Role', value: user.role },
        { label: 'Registered On', value: user.created_at }
    ]);
}

// peuple dropdown items order
function populateOrderItemSelect() {
    const sel = document.getElementById('order-item-select');
    if (!sel) return;
    sel.innerHTML = '<option value="" disabled selected>Select a menu item...</option>' +
        dashboardState.menuItems.map(m =>
            `<option value="${m.id}" data-price="${m.price}">${m.name} — $${Number(m.price || 0).toFixed(2)}</option>`
        ).join('');
}

function addItemToOrderCart() {
    const sel = document.getElementById('order-item-select');
    const qtyEl = document.getElementById('order-item-qty');
    if (!sel || !sel.value) { alert('Please select a menu item.'); return; }

    const menuId = parseInt(sel.value);
    const qty = Math.max(1, parseInt(qtyEl?.value || 1));
    // recup info
    const menuItem = dashboardState.menuItems.find(m => m.id === menuId);
    if (!menuItem) return;

    // check existant panier
    const existing = dashboardState.orderCart.find(c => c.menu_id === menuId);
    if (existing) {
        // ajoute qt
        existing.quantity += qty;
    } else {
        // ajoute nouveau
        dashboardState.orderCart.push({
            menu_id: menuId,
            name: menuItem.name,
            price: menuItem.price,
            quantity: qty,
        });
    }

    // reset input qty
    if (qtyEl) qtyEl.value = '1';
    renderOrderCart();
    recalcOrderTotal();
}

function removeFromOrderCart(menuId) {
    // filtre tableau et maj order
    dashboardState.orderCart = dashboardState.orderCart.filter(c => c.menu_id !== menuId);
    renderOrderCart();
    recalcOrderTotal();
}

function renderOrderCart() {
    const el = document.getElementById('order-cart-list');
    if (!el) return;

    if (dashboardState.orderCart.length === 0) {
        el.innerHTML = '<p class="order-cart-empty text-center mb-0">No items added yet.</p>';
        return;
    }

    el.innerHTML = dashboardState.orderCart.map(item => `
        <div class="order-cart-item">
            <div class="item-icon"><i class="fas fa-utensils"></i></div>
            <div class="item-info">
                <div class="item-name">${item.name}</div>
                <div class="item-qty">× ${item.quantity} &nbsp;@ $${Number(item.price).toFixed(2)} each</div>
            </div>
            <span class="item-line-total">$${(item.price * item.quantity).toFixed(2)}</span>
            <button type="button" class="item-remove" onclick="removeFromOrderCart(${item.menu_id})"
                title="Remove item">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
}

function recalcOrderTotal() {
    // somme elements * qt
    const subtotal = dashboardState.orderCart.reduce((s, i) => s + i.price * i.quantity, 0);
    ``
    const discountType = document.querySelector('input[name="discount-type"]:checked')?.value || 'percent';
    const discountValue = parseFloat(document.getElementById('order-discount-value')?.value || 0) || 0;

    let discountAmount = 0;
    // calc %
    if (discountType === 'percent' && discountValue > 0)
        discountAmount = subtotal * (Math.min(discountValue, 100) / 100);
    // calc fixe
    else if (discountType === 'fixed' && discountValue > 0)
        discountAmount = Math.min(discountValue, subtotal);

    // sub total -> total final
    const total = Math.max(0, subtotal - discountAmount);

    // population des champs
    const sub = document.getElementById('order-subtotal-display');
    const disc = document.getElementById('order-discount-display');
    const tot = document.getElementById('order-total-display');
    if (sub) sub.textContent = `$${subtotal.toFixed(2)}`;
    if (disc) disc.textContent = `- $${discountAmount.toFixed(2)}`;
    if (tot) tot.textContent = `$${total.toFixed(2)}`;
}

//attendre une pause dans la frappe avant de chercher
let _orderUserSearchTimer = null;
// trigger a la frappe
function onOrderUserSearchInput(e) {
    // annule timer precedent
    clearTimeout(_orderUserSearchTimer);
    const term = e.target.value.trim();
    // annule si < 2 lettres
    if (term.length < 2) { _hideDropdown('order-user-search-results'); return; }
    // debounce requete api (on check le temp entre deux touche si trop grand (> 300) on envoie la requette)
    _orderUserSearchTimer = setTimeout(async () => {
        try {
            const res = await dashboardAction({ action: 'search_users', term });
            _renderUserDropdown(
                'order-user-search-results',
                res.success ? (res.data || []) : [],
                (id, name) => selectOrderUser(id, name, true)
            );
        } catch (err) {
            console.error('Order user search failed:', err);
            _renderUserDropdown('order-user-search-results', [], () => {});
        }
    }, 300);
}

function selectOrderUser(userId, username, focusSearch = true) {
    document.getElementById('order-form-user-id').value = userId;
    const si = document.getElementById('order-user-search');
    if (si && focusSearch) si.value = username;

    const badge = document.getElementById('order-selected-user-badge');
    if (badge) {
        badge.innerHTML =
            `<i class="fas fa-user-check" style="color:#16c451;"></i>
             <span class="fw-semibold">${username}</span>
             <span class="badge bg-secondary ms-auto" style="font-size:0.72rem;">ID: ${userId}</span>`;
        badge.style.display = 'flex';
    }
    _hideDropdown('order-user-search-results');
}

//attendre une pause dans la frappe avant de chercher
let _resUserSearchTimer = null;
// trigger a la frappe
function onReservationUserSearchInput(e) {
    clearTimeout(_resUserSearchTimer);
    const term = e.target.value.trim();
    // annule si < 2 lettres
    if (term.length < 2) { _hideDropdown('reservation-user-search-results'); return; }
    // debounce requete api (>300ms) on check le temp entre deux touche si plus grand que 300 on envoie la requette au serveur
    _resUserSearchTimer = setTimeout(async () => {
        const res = await dashboardAction({ action: 'search_users', term });
        _renderUserDropdown(
            'reservation-user-search-results',
            res.success ? (res.data || []) : [],
            (id, name) => selectReservationUser(id, name, true)
        );
    }, 300);
}

function selectReservationUser(userId, username, focusSearch = true) {
    document.getElementById('reservation-form-user-id').value = userId;
    const si = document.getElementById('reservation-user-search');
    if (si && focusSearch) si.value = username;

    const badge = document.getElementById('reservation-selected-user-badge');
    if (badge) {
        badge.innerHTML =
            `<i class="fas fa-user-check" style="color:#16c451;"></i>
             <span class="fw-semibold">${username}</span>
             <span class="badge bg-secondary ms-auto" style="font-size:0.72rem;">ID: ${userId}</span>`;
        badge.style.display = 'flex';
    }
    _hideDropdown('reservation-user-search-results');
}

function _renderUserDropdown(dropdownId, users, onSelect) {
    const el = document.getElementById(dropdownId);
    if (!el) return;

    if (users.length === 0) {
        el.innerHTML = '<div class="user-search-empty">No users found.</div>';
        el.style.display = 'block';
        return;
    }

    el.innerHTML = users.map(u => {
        const safeName = (u.username || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;');
        const safeEmail = (u.email || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;');
        return `
        <div class="user-search-item" data-uid="${u.id}" data-uname="${safeName}">
            <i class="fas fa-user-circle text-muted"></i>
            <div style="flex:1; min-width:0;">
                <div class="fw-semibold" style="font-size:0.88rem;">${safeName}</div>
                <div class="text-muted" style="font-size:0.75rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${safeEmail}</div>
            </div>
            <span class="badge bg-light text-secondary ms-1" style="font-size:0.72rem;">ID: ${u.id}</span>
        </div>`;
    }).join('');

    el.style.display = 'block';

    el.querySelectorAll('.user-search-item').forEach(item => {
        item.addEventListener('click', () => {
            const uid = parseInt(item.dataset.uid, 10);
            const uname = (item.dataset.uname || '')
                .replace(/&amp;/g, '&')
                .replace(/&quot;/g, '"')
                .replace(/&lt;/g, '<');
            if (typeof onSelect === 'function') onSelect(uid, uname);
        });
    });
}

function _hideDropdown(id) {
    const el = document.getElementById(id);
    if (el) { el.style.display = 'none'; el.innerHTML = ''; }
}

function isDashboardStrongPassword(password) {
    if (password.length < 8) return false;
    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_\-+=<>?{}[\]~]).+$/;
    return regex.test(password);
}

function toggleDashboardPassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    }
}

function checkDashboardUserPasswordStrength() {
    const pass = document.getElementById('user-form-password')?.value || '';
    const messageDiv = document.getElementById('userFormPasswordMessage');
    if (!messageDiv) return;
    if (pass === '') { messageDiv.textContent = ''; return; }
    if (isDashboardStrongPassword(pass)) {
        messageDiv.style.color = 'var(--green)';
        messageDiv.textContent = 'Strong password ✅';
    } else {
        messageDiv.style.color = 'red';
        messageDiv.textContent = 'Password must have: min 8 chars, uppercase, lowercase, number, special char ❌';
    }
}

function checkDashboardUserPasswordMatch() {
    const pass1 = document.getElementById('user-form-password')?.value || '';
    const pass2 = document.getElementById('user-form-password-confirm')?.value || '';
    const matchDiv = document.getElementById('userFormMatchMessage');
    if (!matchDiv) return;
    if (pass2 === '') { matchDiv.textContent = ''; return; }
    if (pass1 === pass2) {
        matchDiv.style.color = 'var(--green)';
        matchDiv.textContent = 'Passwords match ✅';
    } else {
        matchDiv.style.color = 'red';
        matchDiv.textContent = 'Passwords do not match ❌';
    }
}

window.checkDashboardUserPasswordStrength = checkDashboardUserPasswordStrength;
window.checkDashboardUserPasswordMatch = checkDashboardUserPasswordMatch;

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('toggle-user-form-password')?.addEventListener('click', (e) => {
        e.preventDefault();
        toggleDashboardPassword('user-form-password', 'userFormEye');
    });
    document.getElementById('toggle-user-form-password-confirm')?.addEventListener('click', (e) => {
        e.preventDefault();
        toggleDashboardPassword('user-form-password-confirm', 'userFormEyeConfirm');
    });
});

function filterTablesForReservation(tables, { guests = 0, date = '', time = '', excludeReservationId = null } = {}) {
    const timeKey = normalizeDashboardTimeValue(time);
    return tables.filter((t) => {
        if (!t.is_active) return false;
        if (guests > 0 && t.capacity < guests) return false;
        if (date && timeKey) {
            const booked = dashboardState.reservations.some((r) => {
                if (excludeReservationId && r.id === excludeReservationId) return false;
                return r.table_id === t.id
                    && r.date === date
                    && reservationTimesOverlap(timeKey, normalizeDashboardTimeValue(r.time || ''));
            });
            if (booked) return false;
        }
        return true;
    });
}

async function fetchAndPopulateTableSelect(selectedTableId = null, filters = {}) {
    const sel = document.getElementById('reservation-form-table-id');
    if (!sel) return;

    const guests = Number(filters.guests ?? document.getElementById('reservation-form-guests')?.value ?? 0);
    const date = filters.date ?? document.getElementById('reservation-form-date')?.value ?? '';
    const time = filters.time ?? document.getElementById('reservation-form-time')?.value ?? '';
    const excludeReservationId = filters.excludeReservationId ?? dashboardState.editingReservationId ?? null;

    let tables = dashboardState.restaurantTables.length
        ? [...dashboardState.restaurantTables]
        : [];

    if (tables.length === 0) {
        sel.innerHTML = '<option value="" disabled selected>Loading tables...</option>';
        try {
            const res = await dashboardAction({ action: 'get_tables' });
            tables = (res.success ? (res.data || []) : []).map((t) => ({
                id: t.id,
                number: t.number,
                capacity: t.capacity,
                is_active: true,
            }));
        } catch (err) {
            console.error('fetchAndPopulateTableSelect failed:', err);
            sel.innerHTML = '<option value="" disabled>Failed to load tables</option>';
            return;
        }
    }

    const available = filterTablesForReservation(tables, { guests, date, time, excludeReservationId });

    if (available.length === 0) {
        sel.innerHTML = '<option value="" disabled selected>No table available for this date, time and guests</option>';
        return;
    }

    const keepSelected = selectedTableId && available.some((t) => t.id === selectedTableId);

    sel.innerHTML = '<option value="" disabled' + (keepSelected ? '' : ' selected') + '>Select a table...</option>' +
        available.map((t) =>
            `<option value="${t.id}" ${t.id === selectedTableId ? 'selected' : ''}>
                Table #${t.number} (capacity: ${t.capacity})
             </option>`
        ).join('');

    if (!keepSelected && available.length > 0) {
        sel.selectedIndex = 1;
    }
}

function setupDashboardDeleteDelegation() {
    if (document.body.dataset.deleteDelegationBound === '1') return;
    document.body.dataset.deleteDelegationBound = '1';

    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-delete-action]');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();

        const action = btn.getAttribute('data-delete-action');
        const id = Number(btn.getAttribute('data-id'));
        if (!action || !id) return;

        switch (action) {
            case 'menu': deleteMenu(id); break;
            case 'order': deleteOrder(id); break;
            case 'reservation': deleteReservation(id); break;
            case 'table': deleteRestaurantTable(id); break;
            case 'user': deleteUser(id); break;
            default: break;
        }
    });
}

window.deleteMenu = deleteMenu;
window.deleteOrder = deleteOrder;
window.deleteReservation = deleteReservation;
window.deleteRestaurantTable = deleteRestaurantTable;
window.deleteUser = deleteUser;

