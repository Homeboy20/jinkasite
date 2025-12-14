<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';

$auth = requireAuth('support_agent');
$currentUser = $auth->getCurrentUser();
$db = Database::getInstance()->getConnection();
$agents = [];
$result = $db->query("SELECT id, full_name FROM admin_users WHERE is_active = 1 ORDER BY full_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $agents[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Satisfaction - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .feedback-dashboard {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .feedback-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }
        .feedback-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
        }
        .feedback-card h4 {
            margin: 0;
            font-size: 0.875rem;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 0.05em;
        }
        .feedback-card .value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.35rem 0;
            color: #0f172a;
        }
        .feedback-card small {
            color: #94a3b8;
        }
        .distribution {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .distribution-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .distribution-bar {
            flex: 1;
            height: 12px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
        }
        .distribution-bar span {
            display: block;
            height: 100%;
            background: linear-gradient(120deg, #ff5900, #fb923c);
        }
        .feedback-filters, .feedback-table {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }
        .feedback-filters {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
        }
        .feedback-filters label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
        }
        .feedback-filters select,
        .feedback-filters input {
            margin-top: 0.35rem;
            border: 1px solid #cbd5f5;
            border-radius: 10px;
            padding: 0.6rem 0.75rem;
            background: #f8fafc;
        }
        .reviews-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .reviews-list {
            max-height: 520px;
            overflow-y: auto;
        }
        .review-item {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            gap: 1rem;
        }
        .review-item:last-child {
            border-bottom: none;
        }
        .review-emoji {
            font-size: 2.2rem;
            line-height: 1;
        }
        .review-body {
            flex: 1;
        }
        .review-body header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.35rem;
        }
        .review-body h5 {
            margin: 0;
            font-size: 1rem;
            color: #0f172a;
        }
        .review-body p {
            margin: 0.3rem 0 0;
            color: #475569;
        }
        .pagination {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pagination button {
            border: none;
            background: #f1f5f9;
            color: #475569;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            font-weight: 600;
            cursor: pointer;
        }
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="admin-body">
<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <div>
                <h1>Support Satisfaction</h1>
                <p>Track customer sentiment across resolved tickets</p>
            </div>
            <button class="btn-primary" id="btnRefresh">Refresh</button>
        </header>

        <section class="feedback-dashboard">
            <div class="feedback-grid" id="feedbackStats">
                <!-- Stats injected via JS -->
            </div>

            <form class="feedback-filters" id="filterForm">
                <div>
                    <label>Agent</label>
                    <select name="agent_id">
                        <option value="">All agents</option>
                        <?php foreach ($agents as $agent): ?>
                        <option value="<?php echo (int)$agent['id']; ?>"><?php echo htmlspecialchars($agent['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Score</label>
                    <select name="score">
                        <option value="">All</option>
                        <option value="5">🤩 Excellent</option>
                        <option value="4">🙂 Good</option>
                        <option value="3">😐 Okay</option>
                        <option value="2">😕 Poor</option>
                        <option value="1">😭 Terrible</option>
                    </select>
                </div>
                <div>
                    <label>Category</label>
                    <select name="category">
                        <option value="">All</option>
                        <option value="general">General</option>
                        <option value="product">Product</option>
                        <option value="technical">Technical</option>
                        <option value="billing">Billing</option>
                        <option value="shipping">Shipping</option>
                        <option value="warranty">Warranty</option>
                    </select>
                </div>
                <div>
                    <label>Date From</label>
                    <input type="date" name="date_from">
                </div>
                <div>
                    <label>Date To</label>
                    <input type="date" name="date_to">
                </div>
                <div style="display:flex;align-items:flex-end;">
                    <button class="btn-primary" type="submit" style="width:100%;">Apply</button>
                </div>
            </form>

            <div class="feedback-table">
                <div class="reviews-header">
                    <strong>Recent Reviews</strong>
                    <span id="reviewCount" style="color:#94a3b8;font-size:0.9rem;"></span>
                </div>
                <div class="reviews-list" id="reviewsList">
                    <div class="empty-state" style="padding:2rem;text-align:center;color:#94a3b8;">No feedback yet.</div>
                </div>
                <div class="pagination">
                    <button type="button" id="prevPage">Prev</button>
                    <span id="pageInfo">Page 1 of 1</span>
                    <button type="button" id="nextPage">Next</button>
                </div>
            </div>
        </section>
    </main>
</div>
<script>
const state = {
    filters: {},
    page: 1,
    pages: 1,
    limit: 10,
    reviews: []
};

const ratingFaces = {
    1: { emoji: '😭', text: 'Terrible' },
    2: { emoji: '😕', text: 'Poor' },
    3: { emoji: '😐', text: 'Okay' },
    4: { emoji: '🙂', text: 'Good' },
    5: { emoji: '🤩', text: 'Excellent' }
};

const statsEl = document.getElementById('feedbackStats');
const reviewsListEl = document.getElementById('reviewsList');
const reviewCountEl = document.getElementById('reviewCount');
const pageInfoEl = document.getElementById('pageInfo');
const prevBtn = document.getElementById('prevPage');
const nextBtn = document.getElementById('nextPage');
const filterForm = document.getElementById('filterForm');
const btnRefresh = document.getElementById('btnRefresh');

function buildQuery(params) {
    const filtered = Object.entries(params)
        .filter(([, value]) => value !== undefined && value !== null && value !== '');
    if (!filtered.length) return '';
    return '?' + filtered.map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`).join('&');
}

function fetchJSON(url) {
    return fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => res.json());
}

function loadStats() {
    const query = buildQuery({ action: 'feedback.stats', ...state.filters });
    fetchJSON('support_api.php' + query)
        .then(res => {
            if (!res.success) throw new Error(res.error || 'Failed to load stats');
            renderStats(res.stats);
        })
        .catch(err => console.error(err));
}

function loadReviews() {
    const query = buildQuery({ action: 'feedback.list', page: state.page, limit: state.limit, ...state.filters });
    fetchJSON('support_api.php' + query)
        .then(res => {
            if (!res.success) throw new Error(res.error || 'Failed to load reviews');
            state.reviews = res.reviews || [];
            state.page = res.page;
            state.pages = Math.max(1, res.pages);
            renderReviews(res.total || 0);
        })
        .catch(err => {
            console.error(err);
            reviewsListEl.innerHTML = `<div class="empty-state" style="padding:2rem;color:#ef4444;">${err.message}</div>`;
        });
}

function renderStats(stats) {
    const overall = stats?.overall || {};
    const distribution = stats?.distribution || {};
    const recent = stats?.recent || [];

    statsEl.innerHTML = `
        <div class="feedback-card">
            <h4>Average Score</h4>
            <div class="value">${overall.average_score ?? '—'}</div>
            <small>${overall.total_rated || 0} tickets rated</small>
        </div>
        <div class="feedback-card">
            <h4>Positive Share</h4>
            <div class="value">${overall.positive_share ?? 0}%</div>
            <small>4 & 5-score tickets</small>
        </div>
        <div class="feedback-card">
            <h4>Distribution</h4>
            <div class="distribution">
                ${[5,4,3,2,1].map(score => {
                    const total = distribution[score] || 0;
                    const percent = overall.total_rated ? Math.round((total / overall.total_rated) * 100) : 0;
                    return `
                        <div class="distribution-row">
                            <span>${ratingFaces[score].emoji}</span>
                            <div class="distribution-bar"><span style="width:${percent}%"></span></div>
                            <small>${total}</small>
                        </div>`;
                }).join('')}
            </div>
        </div>
        <div class="feedback-card">
            <h4>Latest Comments</h4>
            ${recent.length ? recent.map(item => `
                <div style="margin-top:0.65rem;">
                    <strong>${item.ticket_number}</strong>
                    <div style="font-size:0.85rem;color:#475569;">${ratingFaces[item.satisfaction_score]?.emoji || '🙂'} · ${item.agent_name || 'Unassigned'}</div>
                    ${item.satisfaction_note ? `<p style="margin:0.35rem 0 0;color:#0f172a;">${escapeHtml(item.satisfaction_note)}</p>` : ''}
                </div>
            `).join('') : '<p style="margin-top:0.5rem;color:#94a3b8;">No feedback yet.</p>'}
        </div>`;
}

function renderReviews(total) {
    reviewCountEl.textContent = `${total} reviews`;
    pageInfoEl.textContent = `Page ${state.page} of ${state.pages}`;
    prevBtn.disabled = state.page <= 1;
    nextBtn.disabled = state.page >= state.pages;

    if (!state.reviews.length) {
        reviewsListEl.innerHTML = '<div class="empty-state" style="padding:2rem;text-align:center;color:#94a3b8;">No reviews match your filters.</div>';
        return;
    }

    const items = state.reviews.map(review => {
        const face = ratingFaces[review.satisfaction_score] || ratingFaces[3];
        const customer = [review.first_name, review.last_name].filter(Boolean).join(' ') || review.business_name || 'Customer';
        const submitted = review.satisfaction_at ? new Date(review.satisfaction_at).toLocaleString() : '—';
        return `
            <div class="review-item">
                <div class="review-emoji">${face.emoji}</div>
                <div class="review-body">
                    <header>
                        <h5>${review.ticket_number} · ${face.text}</h5>
                        <span style="color:#94a3b8;font-size:0.85rem;">${submitted}</span>
                    </header>
                    <div style="color:#475569;font-size:0.9rem;margin-bottom:0.35rem;">
                        ${customer} • ${review.agent_name || 'Unassigned'} • ${review.category}
                    </div>
                    ${review.satisfaction_note ? `<p>${escapeHtml(review.satisfaction_note)}</p>` : ''}
                </div>
            </div>`;
    }).join('');

    reviewsListEl.innerHTML = items;
}

function escapeHtml(text = '') {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

filterForm.addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData(filterForm);
    state.filters = Object.fromEntries(Array.from(formData.entries()).filter(([, v]) => v));
    state.page = 1;
    loadStats();
    loadReviews();
});

prevBtn.addEventListener('click', () => {
    if (state.page > 1) {
        state.page--;
        loadReviews();
    }
});

nextBtn.addEventListener('click', () => {
    if (state.page < state.pages) {
        state.page++;
        loadReviews();
    }
});

btnRefresh.addEventListener('click', () => {
    loadStats();
    loadReviews();
});

loadStats();
loadReviews();
</script>
</body>
</html>
