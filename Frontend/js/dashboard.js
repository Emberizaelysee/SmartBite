document.addEventListener('DOMContentLoaded', async () => {
    /*    const canLoadDashboard = await checkAdminSession();
        if (!canLoadDashboard) return;*/
    setupDashboardInteractions();
    fetchMenuItems();
    fetchOrders();
    fetchReservations();
    fetchReviews();
    fetchUsers();
});

const dashboardDefaultAvatarPath = './img/profile.jpg';
/*
async function checkAdminSession() {
    try {
        const data = await requestJSON('../Backend/api/session_check.php');
        const role = (data.role || '').toLowerCase();
        
        if (!data.logged_in || role !== 'admin') {
            window.location.href = 'signin.html';
            return false;
        }

        const navUsername = document.getElementById('username');
        if (navUsername) {
            navUsername.textContent = data.username || 'Admin';
        }

        const profileData = await requestJSON('../Backend/api/get_profile.php');
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
    } catch(e) {
        console.error("Session check failed", e);
        window.location.href = 'signin.html';
        return false;
    }
}

/**
 * Generic fetch wrapper used by all API calls.
 * - Includes cookies (credentials: 'include') for session auth
 * - Throws on non-2xx responses so callers can use try/catch
 * @param {string} url   - API endpoint path
 * @param {object} options - fetch() options (method, headers, body, etc.)
 * @returns {Promise<object>} Parsed JSON response
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
    if (/^https?:\/\//i.test(normalized) || normalized.startsWith('/')) return normalized;
    if (normalized.startsWith('./') || normalized.startsWith('../')) return normalized;
    if (normalized.startsWith('Frontend/')) return `./${normalized.slice('Frontend/'.length)}`;
    if (normalized.startsWith('Backend/')) return `../${normalized}`;
    if (normalized.startsWith('uploads/')) return `../Backend/${normalized}`;
    if (/^[^/]+\.(png|jpe?g|webp|gif)$/i.test(normalized)) return `../Backend/uploads/avatars/${normalized}`;
    return `../Backend/${normalized}`;
}

const dashboardState = {
    menuItems: [],
    orders: [],
    reservations: [],
    reviews: [],
    users: [],
    filters: {
        menu: '',
        order: '',
        reservation: '',
        review: '',
        user: ''
    },
    sort: {
        menu: { key: null, direction: 0 },
        order: { key: null, direction: 0 },
        reservation: { key: null, direction: 0 },
        user: { key: null, direction: 0 }
    },
    editingMenuId: null,
    editingOrderId: null,
    editingReservationId: null,
    modals: {}
};
async function dashboardAction(payload) {
    console.log('Mock dashboardAction:', payload);
    return { success: true, message: 'Mock action successful' };
}

async function fetchMenuItems() {
    if (dashboardState.menuItems.length === 0) {
        dashboardState.menuItems = [
            { id: 1, name: 'Classic Beef Burger', description: 'Juicy beef patty with fresh lettuce and tomato', ingredients: 'Beef, Lettuce, Tomato, Bun', price: 12.99, image: 'https://images.immediate.co.uk/production/volatile/sites/2/2015/04/2015-02-24-olive-test-d5b505c.jpg', category: 'Mains' },
            { id: 2, name: 'Margherita Pizza', description: 'Classic cheese and tomato pizza', ingredients: 'Dough, Tomato Sauce, Mozzarella, Basil', price: 14.50, image: './img/Margherita.jpg', category: 'Mains' }
        ];
    }
    renderMenuItems();
}
function renderMenuItems() {
    const tbody = document.querySelector('#menu tbody');
    if (!tbody) return;

    const term = dashboardState.filters.menu.trim().toLowerCase();
    const rows = dashboardState.menuItems.filter((item) => {
        if (!term) return true;
        return `${item.name} ${item.description || ''} ${item.category || ''} ${item.ingredients || ''}`
            .toLowerCase()
            .includes(term);
    });

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
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteMenu(${item.id})"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
    `).join('');
}/*

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
        dashboardAction({ action: 'delete_menu', id }).then(res => {
            if (res.success) fetchMenuItems();
            else alert(res.message);
        });
    });
}

function editMenu(id) {
    const item = dashboardState.menuItems.find(m => m.id === id);
    if (!item) return;
    openMenuModal('edit', item);
}
async function fetchOrders() {
    if (dashboardState.orders.length === 0) {
        dashboardState.orders = [
            {
                id: 12345,
                order_date: '2024-06-01',
                user_id: 1,
                username: 'John Doe',
                total_amount: 25.00,
                status: 'Completed',
                special_instructions: 'No onions',
                items: [
                    { name: 'Classic beef burger', quantity: 1, price: 15.00 },
                    { name: 'Chicken burger', quantity: 1, price: 10.00 }
                ]
            }
        ];
    }
    renderOrders();
    updateDashboardStats();
}
function renderOrders() {
    const tbody = document.querySelector('#order tbody');
    if (!tbody) return;

    const term = dashboardState.filters.order.trim().toLowerCase();
    const rows = dashboardState.orders.filter((o) => {
        if (!term) return true;
        return `${o.id} ${o.username || ''} ${o.status} ${o.order_date || ''} ${o.special_instructions || ''}`
            .toLowerCase()
            .includes(term);
    });

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

    tbody.innerHTML = sortedRows.map((o) => `
        <tr>
            <td>${o.order_date || '-'}</td>
            <td>#${o.id}</td>
            <td>${o.username || `User ${o.user_id}`}</td>
            <td>$${Number(o.total_amount || 0).toFixed(2)}</td>
            <td>
                <select class="form-select form-select-sm status-select" onchange="updateOrderStatus(${o.id}, this.value)">
                    <option value="Pending" ${o.status === 'Pending' ? 'selected' : ''}>Pending</option>
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
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteOrder(${o.id})"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
    `).join('');
}

function deleteOrder(id) {
    showDeleteConfirm('Are you sure you want to <strong>delete this order</strong>? This cannot be undone.', () => {
        dashboardAction({ action: 'delete_order', id }).then((res) => {
            if (res.success) fetchOrders();
            else alert(res.message);
        });
    });
}

function updateOrderStatus(id, newStatus) {
    dashboardAction({ action: 'update_order_status', id, status: newStatus }).then((res) => {
        if (res.success) fetchOrders();
        else alert(res.message);
    });
}

function editOrder(id) {
    const order = dashboardState.orders.find((o) => o.id === id);
    if (!order) return;

    dashboardState.editingOrderId = order.id;
    document.getElementById('orderFormModalLabel').textContent = 'Edit Order';
    document.getElementById('order-form-submit-btn').textContent = 'Save';
    document.getElementById('order-form-id').value = String(order.id);
    document.getElementById('order-form-user-id').value = String(order.user_id ?? '');
    document.getElementById('order-form-total').value = String(order.total_amount ?? '');
    document.getElementById('order-form-status').value = order.status || 'Pending';
    document.getElementById('order-form-notes').value = order.special_instructions || '';

    const itemsSelect = document.getElementById('order-form-items');
    if (itemsSelect) {
        itemsSelect.innerHTML = dashboardState.menuItems.map(m => {
            const isSelected = order.items && order.items.some(i => i.name === m.name);
            return `<option value="${m.id}" ${isSelected ? 'selected' : ''}>${m.name} ($${Number(m.price || 0).toFixed(2)})</option>`;
        }).join('');
    }

    dashboardState.modals.order?.show();
}
async function fetchReservations() {
    if (dashboardState.reservations.length === 0) {
        dashboardState.reservations = [
            { id: 12345, date: '2024-06-10', time: '19:00', guests: 4, customer_name: 'John Doe', table_id: 5, special_notes: 'Window seat' }
        ];
    }
    renderReservations();
    updateDashboardStats();
}

function renderReservations() {
    const tbody = document.querySelector('#reservation tbody');
    if (!tbody) return;

    const term = dashboardState.filters.reservation.trim().toLowerCase();
    const rows = dashboardState.reservations.filter((r) => {
        if (!term) return true;
        return `${r.date} ${r.time} ${r.customer_name || ''} ${r.table_id || ''}`
            .toLowerCase()
            .includes(term);
    });

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
                <button class="btn btn-sm btn-outline-danger" onclick="deleteReservation(${r.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function deleteReservation(id) {
    showDeleteConfirm('Are you sure you want to <strong>delete this reservation</strong>? This cannot be undone.', () => {
        dashboardAction({ action: 'delete_reservation', id }).then(res => {
            if (res.success) fetchReservations();
            else alert(res.message);
        });
    });
}



function editReservation(id) {
    const reservation = dashboardState.reservations.find((r) => r.id === id);
    if (!reservation) return;

    dashboardState.editingReservationId = reservation.id;
    document.getElementById('reservationFormModalLabel').textContent = 'Edit Reservation';
    document.getElementById('reservation-form-submit-btn').textContent = 'Save';
    document.getElementById('reservation-form-id').value = String(reservation.id);
    document.getElementById('reservation-form-name').value = reservation.customer_name || '';
    document.getElementById('reservation-form-date').value = reservation.date || '';
    document.getElementById('reservation-form-time').value = (reservation.time || '').substring(0, 5);
    document.getElementById('reservation-form-guests').value = String(reservation.guests ?? 1);
    document.getElementById('reservation-form-table-id').value = String(reservation.table_id ?? '');
    document.getElementById('reservation-form-notes').value = reservation.special_notes || '';
    dashboardState.modals.reservation?.show();
}
async function fetchReviews() {
    if (dashboardState.reviews.length === 0) {
        dashboardState.reviews = [
            { id: 1, user_id: 1, menu_id: 1, rating: 5, content: 'Excellent food!', created_at: '2024-06-05' }
        ];
    }
    renderReviews();
}
function renderReviews() {
    const container = document.querySelector('#review .bg-white');
    if (!container) return;

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

    container.innerHTML = rows.map((rev) => {
        const stars = Array(rev.rating).fill('<i class="fas fa-star text-warning"></i>').join('') +
            Array(5 - rev.rating).fill('<i class="fas fa-star text-muted"></i>').join('');

        const menuItem = dashboardState.menuItems.find(m => m.id === rev.menu_id);
        const imageSrc = menuItem ? (menuItem.image || './img/placeholder.jpg') : './img/placeholder.jpg';
        const menuName = menuItem ? menuItem.name : `Menu Item #${rev.menu_id}`;

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
        dashboardAction({ action: 'delete_review', id }).then(res => {
            if (res.success) fetchReviews();
            else alert(res.message);
        });
    });
}
async function fetchUsers() {
    if (dashboardState.users.length === 0) {
        dashboardState.users = [
            { id: 1, username: 'johndoe', email: 'john@example.com', role: 'Customer', created_at: '2024-05-20' },
            { id: 2, username: 'admin', email: 'admin@smartbite.com', role: 'Admin', created_at: '2024-05-21' }
        ];
    }
    renderUsers();
    updateDashboardStats();
}
function renderUsers() {
    const tbody = document.querySelector('#users tbody');
    if (!tbody) return;

    const term = dashboardState.filters.user.trim().toLowerCase();
    const rows = dashboardState.users.filter((u) => {
        if (!term) return true;
        return `${u.username} ${u.email} ${u.role}`
            .toLowerCase()
            .includes(term);
    });

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

    tbody.innerHTML = sortedRows.map((u) => {
        let actionBtns = '';
        if (u.role !== 'Admin') {
            actionBtns += `<button class="btn btn-sm btn-outline-primary me-1" onclick="makeAdmin(${u.id})"><i class="fas fa-user-shield"></i> Make Admin</button>`;
        } else {
            actionBtns += `<button class="btn btn-sm btn-outline-secondary me-1" onclick="makeUser(${u.id})"><i class="fas fa-user"></i> Make User &nbsp;&nbsp;&nbsp;</button>`;
        }
        actionBtns += `<button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${u.id})"><i class="fas fa-trash"></i></button>`;

        return `
            <tr>
                <td>${u.username}</td>
                <td>${u.email}</td>

                <td>${u.created_at || 'N/A'}</td>
                <td>${u.role}</td>
                <td>
                    <button class="btn btn-sm btn-outline-secondary me-1" onclick="viewUserRow(${u.id})"><i class="fas fa-eye"></i></button>
                    ${actionBtns}
                </td>
            </tr>
        `;
    }).join('');
}

function makeAdmin(id) {
    showPriviledgeConfirm('Are you sure you want to <strong>grant admin privileges</strong> to this user?', () => {
        dashboardAction({ action: 'make_admin', id }).then(res => {
            if (res.success) fetchUsers();
            else alert(res.message);
        });
    });
}

function makeUser(id) {
    showPriviledgeConfirm('Are you sure you want to <strong>remove admin privileges</strong> from this user?', () => {
        dashboardAction({ action: 'make_user', id }).then(res => {
            if (res.success) fetchUsers();
            else alert(res.message);
        });
    });
}

function deleteUser(id) {
    showDeleteConfirm('Are you sure you want to <strong>permanently delete this user</strong>? This cannot be undone.', () => {
        dashboardAction({ action: 'delete_user', id }).then(res => {
            if (res.success) fetchUsers();
            else alert(res.message);
        });
    });
}

function setupDashboardInteractions() {
    setupTableSorting();
    wireSearch('menu', 'search-menu-input', 'search-menu-btn', renderMenuItems);
    wireSearch('order', 'search-order-input', 'search-order-btn', renderOrders);
    wireSearch('reservation', 'search-reservation-input', 'search-reservation-btn', renderReservations);
    wireSearch('review', 'search-review-input', 'search-review-btn', renderReviews);
    wireSearch('user', 'search-user-input', 'search-user-btn', renderUsers);

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

    const quantityInput = document.getElementById('menu-form-quantity');
    if (quantityInput) quantityInput.addEventListener('input', updateMenuStockPreview);

    initDashboardModals();

    const menuForm = document.getElementById('menu-form');
    if (menuForm) menuForm.addEventListener('submit', submitMenuForm);

    const reservationForm = document.getElementById('reservation-form');
    if (reservationForm) reservationForm.addEventListener('submit', submitReservationForm);

    const orderForm = document.getElementById('order-form');
    if (orderForm) orderForm.addEventListener('submit', submitOrderForm);

    const reviewForm = document.getElementById('review-form');
    if (reviewForm) reviewForm.addEventListener('submit', submitReviewForm);

    const userForm = document.getElementById('user-form');
    if (userForm) userForm.addEventListener('submit', submitUserForm);
}

function setupTableSorting() {
    const headers = document.querySelectorAll('th[data-sort-table][data-sort-key]');
    headers.forEach((header) => {
        header.addEventListener('click', () => {
            const table = header.getAttribute('data-sort-table');
            const key = header.getAttribute('data-sort-key');
            if (!table || !key || !dashboardState.sort[table]) return;

            const current = dashboardState.sort[table];
            let nextDirection = 1;
            if (current.key === key) {
                if (current.direction === 1) nextDirection = -1;
                else if (current.direction === -1) nextDirection = 0;
                else nextDirection = 1;
            }

            dashboardState.sort[table] = {
                key: nextDirection === 0 ? null : key,
                direction: nextDirection
            };

            updateSortIndicators(table);
            renderTableByName(table);
        });
    });
}

function renderTableByName(table) {
    if (table === 'menu') renderMenuItems();
    if (table === 'order') renderOrders();
    if (table === 'reservation') renderReservations();
    if (table === 'user') renderUsers();
}

function updateSortIndicators(table) {
    const headers = document.querySelectorAll(`th[data-sort-table="${table}"]`);
    headers.forEach((header) => {
        const baseText = (header.textContent || '').replace(/\s[▲▼]$/, '');
        const key = header.getAttribute('data-sort-key');
        const sort = dashboardState.sort[table];
        if (sort.key === key && sort.direction === 1) {
            header.textContent = `${baseText} ▲`;
        } else if (sort.key === key && sort.direction === -1) {
            header.textContent = `${baseText} ▼`;
        } else {
            header.textContent = baseText;
        }
    });
}

function sortRows(rows, table) {
    const sort = dashboardState.sort[table];
    if (!sort || !sort.key || sort.direction === 0) return rows;

    const direction = sort.direction;
    const key = sort.key;

    return [...rows].sort((a, b) => {
        const av = normalizeSortValue(a[key]);
        const bv = normalizeSortValue(b[key]);
        if (av < bv) return -1 * direction;
        if (av > bv) return 1 * direction;
        return 0;
    });
}

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
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);
    if (!input) return;

    const applyFilter = () => {
        dashboardState.filters[filterKey] = input.value || '';
        renderFn();
    };

    input.addEventListener('input', applyFilter);
    if (button) button.addEventListener('click', applyFilter);
}

async function addMenuItem() {
    openMenuModal('add');
}

async function addReservation() {
    dashboardState.editingReservationId = null;
    document.getElementById('reservationFormModalLabel').textContent = 'Add Reservation';
    document.getElementById('reservation-form-submit-btn').textContent = 'Add';
    document.getElementById('reservation-form')?.reset();
    document.getElementById('reservation-form-id').value = '';
    document.getElementById('reservation-form-guests').value = '2';
    document.getElementById('reservation-form-status').value = 'Pending';
    dashboardState.modals.reservation?.show();
}

async function addOrder() {
    dashboardState.editingOrderId = null;
    document.getElementById('orderFormModalLabel').textContent = 'Add Order';
    document.getElementById('order-form-submit-btn').textContent = 'Add';
    document.getElementById('order-form')?.reset();
    document.getElementById('order-form-id').value = '';
    document.getElementById('order-form-status').value = 'Pending';

    const itemsSelect = document.getElementById('order-form-items');
    if (itemsSelect) {
        itemsSelect.innerHTML = dashboardState.menuItems.map(m => `<option value="${m.id}">${m.name} ($${Number(m.price || 0).toFixed(2)})</option>`).join('');
    }

    dashboardState.modals.order?.show();
}

async function addReview() {
    document.getElementById('review-form')?.reset();
    document.getElementById('review-form-rating').value = '5';

    const menuSelect = document.getElementById('review-form-menu-id');
    if (menuSelect) {
        menuSelect.innerHTML = '<option value="" disabled selected>Select a menu item...</option>' +
            dashboardState.menuItems.map(m => `<option value="${m.id}">${m.name}</option>`).join('');
    }

    dashboardState.modals.review?.show();
}

async function addUser() {
    document.getElementById('user-form')?.reset();
    document.getElementById('user-form-role').value = 'Customer';
    dashboardState.modals.user?.show();
}

function updateDashboardStats() {
    const pendingReservations = dashboardState.reservations.filter((r) => r.status === 'Pending').length;
    const activeUsers = dashboardState.users.length;
    const totalOrders = dashboardState.orders.length;
    const totalSales = dashboardState.orders.reduce((sum, order) => sum + Number(order.total_amount || 0), 0);

    const reservationsEl = document.getElementById('stat-reservations');
    const usersEl = document.getElementById('stat-users');
    const ordersEl = document.getElementById('stat-orders');
    const salesEl = document.getElementById('stat-sales');
    const reservationsBadgeEl = document.getElementById('stat-badge-reservations');
    const usersBadgeEl = document.getElementById('stat-badge-users');
    const ordersBadgeEl = document.getElementById('stat-badge-orders');
    const salesBadgeEl = document.getElementById('stat-badge-sales');

    if (reservationsEl) reservationsEl.textContent = String(pendingReservations);
    if (usersEl) usersEl.textContent = String(activeUsers);
    if (ordersEl) ordersEl.textContent = String(totalOrders);
    if (salesEl) salesEl.textContent = `$${totalSales.toFixed(2)}`;

    const reservationGrowth = computeGrowthRateFromDateField(dashboardState.reservations, 'date');
    const userGrowth = computeGrowthRateFromDateField(dashboardState.users, 'created_at');
    const orderGrowth = computeGrowthRateFromDateField(dashboardState.orders, 'order_date');
    const salesGrowth = computeSalesGrowthRate(dashboardState.orders, 'order_date', 'total_amount');

    updateStatBadge(reservationsBadgeEl, reservationGrowth);
    updateStatBadge(usersBadgeEl, userGrowth);
    updateStatBadge(ordersBadgeEl, orderGrowth);
    updateStatBadge(salesBadgeEl, salesGrowth);
}

function computeGrowthRateFromDateField(rows, dateField) {
    const now = new Date();
    const recentWindowStart = new Date(now);
    recentWindowStart.setDate(now.getDate() - 7);
    const previousWindowStart = new Date(now);
    previousWindowStart.setDate(now.getDate() - 14);

    let recentCount = 0;
    let previousCount = 0;

    rows.forEach((row) => {
        const raw = row?.[dateField];
        if (!raw) return;
        const d = new Date(raw);
        if (Number.isNaN(d.getTime())) return;

        if (d >= recentWindowStart && d <= now) recentCount += 1;
        else if (d >= previousWindowStart && d < recentWindowStart) previousCount += 1;
    });

    return computeGrowthRate(previousCount, recentCount);
}

function computeSalesGrowthRate(rows, dateField, amountField) {
    const now = new Date();
    const recentWindowStart = new Date(now);
    recentWindowStart.setDate(now.getDate() - 7);
    const previousWindowStart = new Date(now);
    previousWindowStart.setDate(now.getDate() - 14);

    let recentSales = 0;
    let previousSales = 0;

    rows.forEach((row) => {
        const rawDate = row?.[dateField];
        if (!rawDate) return;
        const d = new Date(rawDate);
        if (Number.isNaN(d.getTime())) return;

        const amount = Number(row?.[amountField] || 0);
        if (d >= recentWindowStart && d <= now) recentSales += amount;
        else if (d >= previousWindowStart && d < recentWindowStart) previousSales += amount;
    });

    return computeGrowthRate(previousSales, recentSales);
}

function computeGrowthRate(previousValue, currentValue) {
    if (previousValue === 0) {
        return currentValue > 0 ? 100 : 0;
    }
    return ((currentValue - previousValue) / previousValue) * 100;
}

function updateStatBadge(element, percent) {
    if (!element) return;
    const safePercent = Number.isFinite(percent) ? percent : 0;
    const rounded = Math.round(safePercent);
    const prefix = rounded > 0 ? '+' : '';
    element.textContent = `${prefix}${rounded}%`;

    if (rounded > 0) {
        element.style.color = '#16c451';
    } else if (rounded < 0) {
        element.style.color = '#dc3545';
    } else {
        element.style.color = '';
    }
}

function initDashboardModals() {
    dashboardState.modals.menu = createModal('menuFormModal');
    dashboardState.modals.order = createModal('orderFormModal');
    dashboardState.modals.reservation = createModal('reservationFormModal');
    dashboardState.modals.review = createModal('reviewFormModal');
    dashboardState.modals.user = createModal('userFormModal');
    dashboardState.modals.rowView = createModal('rowViewModal');
    dashboardState.modals.deleteConfirm = createModal('deleteConfirmModal');
    dashboardState.modals.priviledgeConfirm = createModal('priviledgeConfirmModal');
}

function showDeleteConfirm(message, onConfirm) {
    const msgEl = document.getElementById('deleteConfirmMessage');
    const btnEl = document.getElementById('deleteConfirmBtn');
    if (!msgEl || !btnEl) {
        if (confirm(message.replace(/<[^>]*>/g, ''))) onConfirm();
        return;
    }

    msgEl.innerHTML = message;

    const newBtn = btnEl.cloneNode(true);
    btnEl.parentNode.replaceChild(newBtn, btnEl);
    newBtn.id = 'deleteConfirmBtn';

    newBtn.addEventListener('click', () => {
        dashboardState.modals.deleteConfirm?.hide();
        onConfirm();
    }, { once: true });

    dashboardState.modals.deleteConfirm?.show();
}

function showPriviledgeConfirm(message, onConfirm) {
    const msgEl = document.getElementById('priviledgeConfirmMessage');
    const btnEl = document.getElementById('priviledgeConfirmBtn');
    if (!msgEl || !btnEl) {
        if (confirm(message.replace(/<[^>]*>/g, ''))) onConfirm();
        return;
    }

    msgEl.innerHTML = message;

    const newBtn = btnEl.cloneNode(true);
    btnEl.parentNode.replaceChild(newBtn, btnEl);
    newBtn.id = 'priviledgeConfirmBtn';

    newBtn.addEventListener('click', () => {
        dashboardState.modals.priviledgeConfirm?.hide();
        onConfirm();
    }, { once: true });

    dashboardState.modals.priviledgeConfirm?.show();
}

function createModal(id) {
    const el = document.getElementById(id);
    if (!el || typeof bootstrap === 'undefined') return null;
    return new bootstrap.Modal(el);
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
        if (categoryInput) categoryInput.value = item.category || 'Mains';
        if (priceInput) priceInput.value = String(item.price ?? '');
        if (quantityInput) quantityInput.value = String(item.quantity ?? 0);
        if (imageInput) imageInput.value = item.image || '';
    } else {
        dashboardState.editingMenuId = null;
        if (title) title.textContent = 'Add Menu Item';
        if (idInput) idInput.value = '';
        if (categoryInput) categoryInput.value = 'Mains';
        if (quantityInput) quantityInput.value = '0';
        if (imageInput) imageInput.value = './img/placeholder.jpg';
    }

    updateMenuStockPreview();
    dashboardState.modals.menu?.show();
}

async function submitMenuForm(e) {
    e.preventDefault();
    const id = dashboardState.editingMenuId;
    const name = document.getElementById('menuName')?.value.trim() || '';
    const description = document.getElementById('menu_description')?.value.trim() || '';
    const category = document.getElementById('menu-form-category')?.value.trim() || '';
    const price = Number(document.getElementById('menu-price')?.value || 0);
    const quantity = Number(document.getElementById('menu-form-quantity')?.value ?? 0);
    const image_url = document.getElementById('menu-form-image')?.value.trim() || '';

    if (!name || !category || !Number.isFinite(price) || price <= 0 || !Number.isInteger(quantity) || quantity < 0) {
        alert('Please provide valid name, category, price and quantity.');
        return;
    }

    const payload = {
        action: id ? 'edit_menu' : 'add_menu',
        name,
        description,
        category,
        price,
        quantity,
        image_url
    };

    if (id) payload.id = id;

    const res = await dashboardAction(payload);
    if (!res.success) {
        alert(res.message || 'Failed to save menu item.');
        return;
    }

    dashboardState.modals.menu?.hide();
    fetchMenuItems();
}

function updateMenuStockPreview() {
    const previewEl = document.getElementById('menu-form-stock-preview');
    const quantityInput = document.getElementById('menu-form-quantity');
    if (!previewEl || !quantityInput) return;

    const quantity = Number(quantityInput.value ?? 0);
    previewEl.innerHTML = renderStockStatus(quantity);
}

async function submitReservationForm(e) {
    e.preventDefault();
    const name = document.getElementById('reservation-form-name')?.value.trim() || '';
    const date = document.getElementById('reservation-form-date')?.value || '';
    const time = document.getElementById('reservation-form-time')?.value || '';
    const guests = Number(document.getElementById('reservation-form-guests')?.value || 0);
    const contact = document.getElementById('reservation-form-contact')?.value.trim() || '';
    const status = document.getElementById('reservation-form-status')?.value || 'Pending';

    if (!name || !date || !time || !Number.isInteger(guests) || guests <= 0) {
        alert('Please fill all required reservation fields.');
        return;
    }

    const reservationId = dashboardState.editingReservationId;
    const payload = {
        action: reservationId ? 'edit_reservation' : 'add_reservation',
        name,
        date,
        time: `${time}:00`,
        guests,
        contact,
        status
    };
    if (reservationId) payload.id = reservationId;

    const res = await dashboardAction(payload);

    if (!res.success) {
        alert(res.message || 'Failed to add reservation.');
        return;
    }

    dashboardState.modals.reservation?.hide();
    fetchReservations();
}

async function submitOrderForm(e) {
    e.preventDefault();
    const user_id = Number(document.getElementById('order-form-user-id')?.value || 0);
    const total_amount = Number(document.getElementById('order-form-total')?.value || 0);
    const status = document.getElementById('order-form-status')?.value || 'Pending';
    const special_instructions = document.getElementById('order-form-notes')?.value.trim() || '';

    if (!Number.isInteger(user_id) || user_id <= 0 || !Number.isFinite(total_amount) || total_amount <= 0) {
        alert('Please provide a valid user ID and total amount.');
        return;
    }

    const orderId = dashboardState.editingOrderId;

    const itemsSelect = document.getElementById('order-form-items');
    let items = [];
    if (itemsSelect) {
        items = Array.from(itemsSelect.selectedOptions).map(opt => {
            const menuItem = dashboardState.menuItems.find(m => m.id === Number(opt.value));
            if (menuItem) {
                return { name: menuItem.name, quantity: 1, price: menuItem.price };
            }
            return null;
        }).filter(Boolean);
    }

    const payload = {
        action: orderId ? 'edit_order' : 'add_order',
        user_id,
        total_amount,
        status,
        special_instructions,
        items
    };
    if (orderId) payload.id = orderId;

    const res = await dashboardAction(payload);

    if (!res.success) {
        alert(res.message || 'Failed to add order.');
        return;
    }

    dashboardState.modals.order?.hide();
    fetchOrders();
}

async function submitReviewForm(e) {
    e.preventDefault();
    const user_id = 2; // Automatically use logged in admin's ID
    const menu_id = Number(document.getElementById('review-form-menu-id')?.value || 0);
    const rating = Number(document.getElementById('review-form-rating')?.value || 0);
    const content = document.getElementById('review-form-content')?.value.trim() || '';

    if (!menu_id || !content || !Number.isInteger(rating) || rating < 1 || rating > 5) {
        alert('Please provide a valid menu item ID, rating and content.');
        return;
    }

    const res = await dashboardAction({
        action: 'add_review',
        user_id,
        menu_id,
        rating,
        content
    });

    if (!res.success) {
        alert(res.message || 'Failed to add review.');
        return;
    }

    dashboardState.modals.review?.hide();
    fetchReviews();
}

async function submitUserForm(e) {
    e.preventDefault();
    const username = document.getElementById('user-form-username')?.value.trim() || '';
    const email = document.getElementById('user-form-email')?.value.trim() || '';
    const password = document.getElementById('user-form-password')?.value || '';
    const role = document.getElementById('user-form-role')?.value || 'Customer';

    if (!username || !email || !password) {
        alert('Please fill username, email and password.');
        return;
    }

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
    const order = dashboardState.orders.find((o) => o.id === id);
    if (!order) return;

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
    const user = dashboardState.users.find((u) => u.id === id);
    if (!user) return;
    openRowViewModal(`User #${user.id}`, [
        { label: 'Username', value: user.username },
        { label: 'Email', value: user.email },
        { label: 'Role', value: user.role },
        { label: 'Registered On', value: user.created_at }
    ]);
}
