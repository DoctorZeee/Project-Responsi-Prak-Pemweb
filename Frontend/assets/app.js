// ============================================================
// CraftBazaar — Shared Frontend Utilities
// Satu file untuk semua halaman
// ============================================================

// ── Path ke Backend (relative dari folder Frontend/) ────────
const API = {
    base:       '../Backend',
    userStatus: '../Backend/api/user_status.php',
    items:      '../Backend/api/items.php',
    itemDetail: '../Backend/api/item_detail.php',
    login:      '../Backend/auth/login.php',
    register:   '../Backend/auth/register.php',
    logout:     '../Backend/auth/logout.php',
    cartAdd:    '../Backend/buyer/cart/add.php',
    cartView:   '../Backend/buyer/cart/index.php',
    cartRemove: '../Backend/buyer/cart/remove.php',
    checkout:   '../Backend/buyer/orders/checkout.php',
    orders:     '../Backend/buyer/orders/index.php',
    sellerItems:   '../Backend/seller/items/index.php',
    sellerCreate:  '../Backend/seller/items/create.php',
    sellerUpdate:  '../Backend/seller/items/update.php',
    sellerDelete:  '../Backend/seller/items/delete.php',
};

// ── Format Rupiah ────────────────────────────────────────────
function fRupiah(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0
    }).format(amount);
}

// ── Toast Notification ───────────────────────────────────────
let _toastTimer;
function toast(msg, type = 'success') {
    let el = document.getElementById('_toast');
    if (!el) {
        el = document.createElement('div');
        el.id = '_toast';
        el.className = 'toast';
        document.body.appendChild(el);
    }
    el.textContent = (type === 'success' ? '✅ ' : '❌ ') + msg;
    el.className   = `toast ${type} show`;
    clearTimeout(_toastTimer);
    _toastTimer = setTimeout(() => el.classList.remove('show'), 3200);
}

// ── Safe Fetch (handle PHP error output) ─────────────────────
async function safeFetch(url, options = {}) {
    const res  = await fetch(url, options);
    const text = await res.text();
    try {
        return { ok: res.ok, status: res.status, data: JSON.parse(text) };
    } catch {
        console.error('Non-JSON from server:', text);
        return { ok: false, status: res.status, data: { success: false, message: 'Respons server tidak valid.' } };
    }
}

// ── Get/cache current user ───────────────────────────────────
let _cachedUser = null;

async function getUser(forceRefresh = false) {
    if (_cachedUser && !forceRefresh) return _cachedUser;
    const { data } = await safeFetch(API.userStatus);
    _cachedUser = data.logged_in ? data.user : null;
    return _cachedUser;
}

// ── Render Navbar User Zone ──────────────────────────────────
async function renderNavbar(opts = {}) {
    const navUserZone = document.getElementById('navUserZone');
    if (!navUserZone) return;

    const user = await getUser();

    if (user) {
        const cartBadge = (user.role === 'buyer' && user.cart_count > 0)
            ? `<span style="background:var(--color-primary);color:#000;font-size:10px;padding:1px 6px;border-radius:10px;font-family:'JetBrains Mono',monospace;">${user.cart_count}</span>`
            : '';

        let zoneHTML = '';

        if (user.role === 'buyer') {
            zoneHTML += `
                <span class="mono-badge" style="color:#FBBF24;background:rgba(251,191,36,0.08);border-color:rgba(251,191,36,0.2);">
                    💰 ${fRupiah(user.balance)}
                </span>
                <a href="Home-Checkout.html" style="color:#8892B0;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:4px;text-decoration:none;">
                    🛒 Keranjang ${cartBadge}
                </a>`;
        } else {
            zoneHTML += `<span class="mono-badge" style="color:#60A5FA;background:rgba(96,165,250,0.08);border-color:#1E40AF;text-transform:uppercase;">
                🛡️ ${user.role}
            </span>`;
        }

        zoneHTML += `
            <div class="nav-avatar-circle" title="${user.username}">🧑</div>
            <span style="font-size:12px;color:#8892B0;cursor:pointer;" onclick="doLogout()">Logout</span>`;

        navUserZone.innerHTML = zoneHTML;
    } else {
        navUserZone.innerHTML = `
            <button class="btn-primary" onclick="window.location.href='Signin.html'" style="padding:6px 14px;font-size:12px;border-radius:4px;">
                Masuk / Daftar
            </button>`;
    }

    // Update navAkun link
    const navAkun = document.getElementById('navAkun');
    if (navAkun) {
        navAkun.onclick = (e) => {
            e.preventDefault();
            if (!user) { window.location.href = 'Signin.html'; return; }
            window.location.href = (user.role === 'buyer') ? 'Home-Profile.html' : 'admin-home.html';
        };
    }
}

// ── Logout ───────────────────────────────────────────────────
async function doLogout() {
    await safeFetch(API.logout);
    _cachedUser = null;
    toast('Berhasil keluar akun.');
    setTimeout(() => window.location.href = 'Marketplace-main.html', 800);
}

// ── Require Auth Guard (untuk halaman terproteksi) ───────────
async function requireAuth(allowedRoles = []) {
    const user = await getUser();
    if (!user) {
        toast('Silakan login terlebih dahulu.', 'error');
        setTimeout(() => window.location.href = 'Signin.html', 800);
        return null;
    }
    if (allowedRoles.length && !allowedRoles.includes(user.role)) {
        toast('Akses ditolak.', 'error');
        setTimeout(() => window.location.href = 'Marketplace-main.html', 800);
        return null;
    }
    return user;
}