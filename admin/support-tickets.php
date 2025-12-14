<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';

$auth = requireAuth('support_agent');
$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .support-dashboard {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .support-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .support-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 18px;
            padding: 1.75rem;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .support-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ff5900 0%, #ff8a00 100%);
        }
        .support-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.1);
        }
        .support-card h4 {
            margin: 0;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        .agent-ops {
            display: grid;
            grid-template-columns: minmax(300px, 1fr) minmax(280px, 0.9fr);
            gap: 1.5rem;
        }
        .status-card,
        .queue-card,
        .agent-board-card {
            background: #fff;
            border-radius: 18px;
            padding: 1.75rem;
            box-shadow: 0 2px 16px rgba(15, 23, 42, 0.04);
            border: 1px solid #e2e8f0;
        }
        .status-card h3,
        .queue-card h3 {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
        }
            .status-meta,
            .queue-card p {
                margin: 0.35rem 0 0;
                font-size: 0.85rem;
                color: #94a3b8;
            }
            .status-toggles {
                display: flex;
                flex-direction: column;
                gap: 0.85rem;
                margin-top: 1rem;
            }
            .status-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
            }
            .status-row span {
                font-weight: 600;
                color: #475569;
            }
            .toggle-switch {
                width: 54px;
                height: 30px;
                border-radius: 999px;
                border: none;
                background: #e2e8f0;
                position: relative;
                cursor: pointer;
                padding: 0;
                transition: background 0.2s ease;
            }
            .toggle-switch span {
                position: absolute;
                top: 3px;
                left: 4px;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                background: #fff;
                box-shadow: 0 4px 10px rgba(15, 23, 42, 0.12);
                transition: transform 0.2s ease;
            }
            .toggle-switch.active {
                background: linear-gradient(120deg, #ff6a00 0%, #ff5000 100%);
            }
            .toggle-switch.active span {
                transform: translateX(22px);
            }
            .slider-row {
                margin-top: 1rem;
            }
            .slider-row label {
                display: flex;
                justify-content: space-between;
                font-weight: 600;
                color: #475569;
                font-size: 0.9rem;
            }
            .slider-row input[type="range"] {
                width: 100%;
                margin-top: 0.5rem;
            }
            .queue-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .queue-count {
                background: #fff1e6;
                color: #ff6a00;
                font-weight: 700;
                padding: 0.3rem 0.8rem;
                border-radius: 999px;
            }
            .queue-list {
                margin-top: 1rem;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                max-height: 320px;
                overflow-y: auto;
            }
        .queue-item {
            border: 1px solid #f1f5f9;
            border-radius: 14px;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #fff9f5 0%, #ffffff 100%);
            transition: all 0.2s ease;
            border-left: 3px solid #ff5900;
        }
        .queue-item:hover {
            transform: translateX(2px);
            box-shadow: 0 2px 12px rgba(255, 89, 0, 0.1);
        }
        .queue-item h5 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
        }
            .queue-meta {
                display: flex;
                justify-content: space-between;
                font-size: 0.8rem;
                color: #94a3b8;
                margin-top: 0.35rem;
            }
            .queue-actions {
                margin-top: 1rem;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            .queue-mini-actions {
                margin-top: 0.75rem;
                display: flex;
                gap: 0.5rem;
            }
            .btn-small {
                padding: 0.35rem 0.85rem;
                font-size: 0.8rem;
            }
            .full-width {
                width: 100%;
                text-align: center;
            }
            .agent-board-card {
                padding: 0;
            }
            .agent-board-grid {
                padding: 1.25rem 1.5rem 1.5rem;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 1rem;
            }
        .agent-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.25rem;
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }
        .agent-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
        }
        .agent-card strong {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }
            .agent-card small {
                color: #94a3b8;
                font-size: 0.85rem;
            }
            .agent-pills {
                display: flex;
                gap: 0.4rem;
                flex-wrap: wrap;
            }
            .pill {
                padding: 0.2rem 0.7rem;
                border-radius: 999px;
                font-size: 0.75rem;
                font-weight: 600;
                border: 1px solid #e2e8f0;
                color: #475569;
            }
            .pill.online {background:#dcfce7;border-color:#22c55e;color:#15803d;}
            .pill.offline {background:#f1f5f9;}
            .pill.auto {background:#eef2ff;border-color:#6366f1;color:#4338ca;}
            .pill.manual {background:#fff1e6;border-color:#ff6a00;color:#c2410c;}
            .agent-capacity {
                font-size: 0.85rem;
                color: #475569;
            }
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .support-card .value {
            font-size: 2rem;
            font-weight: 700;
            margin-top: 0.5rem;
            color: #0f172a;
        }
        .support-card .subtext {
            margin-top: 0.25rem;
            color: #94a3b8;
            font-size: 0.85rem;
        }
        .support-filters,
        .support-content {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 32px rgba(15, 23, 42, 0.06);
            border: 1px solid #e2e8f0;
        }
        .support-filters {
            padding: 1.75rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            align-items: flex-end;
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
        }
        .support-filters .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 190px;
            flex: 1;
        }
        .support-filters label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .support-filters select,
        .support-filters input {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            background: #fff;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        .support-filters select:focus,
        .support-filters input:focus {
            outline: none;
            border-color: #ff5900;
            box-shadow: 0 0 0 3px rgba(255, 89, 0, 0.1);
        }
        .support-content {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            min-height: 650px;
            overflow: hidden;
        }
        .ticket-list {
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            max-height: 100%;
            background: #fafbfc;
        }
        .ticket-header,
        .ticket-detail-header {
            padding: 1.5rem 1.75rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
        }
        .ticket-header strong,
        .ticket-detail-header strong {
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
        }
        .ticket-items {
            overflow-y: auto;
        }
        .ticket-item {
            padding: 1.25rem 1.75rem;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fff;
            position: relative;
        }
        .ticket-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #ff5900;
            transform: scaleY(0);
            transition: transform 0.2s ease;
        }
        .ticket-item:hover {
            background: #fff9f5;
            transform: translateX(2px);
        }
        .ticket-item.active {
            background: linear-gradient(90deg, #fff9f5 0%, #ffffff 100%);
            border-left: 3px solid #ff5900;
        }
        .ticket-item.active::before {
            transform: scaleY(1);
        }
        .ticket-item h5 {
            margin: 0;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 600;
        }
        .ticket-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #64748b;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.65rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .badge.status-open {background:#dbeafe;color:#1d4ed8;}
        .badge.status-in_progress {background:#fef3c7;color:#b45309;}
        .badge.status-waiting_customer {background:#fce7f3;color:#be185d;}
        .badge.status-resolved {background:#dcfce7;color:#15803d;}
        .badge.status-closed {background:#e2e8f0;color:#475569;}
        .badge.priority-low {background:#e0f2fe;color:#0369a1;}
        .badge.priority-medium {background:#fef9c3;color:#a16207;}
        .badge.priority-high {background:#fee2e2;color:#b91c1c;}
        .badge.priority-urgent {background:#ffe4e6;color:#be123c;}
        .ticket-detail {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .ticket-detail-body {
            padding: 1.5rem;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .customer-insights {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.25rem;
            background: #fff;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .insight-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.75rem;
        }
        .insight-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
        }
        .insight-card span {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 0.05em;
        }
        .insight-card strong {
            display: block;
            font-size: 1.1rem;
            color: #0f172a;
            margin-top: 0.35rem;
        }
        .insight-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .insight-list li {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            font-size: 0.9rem;
        }
        .insight-list li span {
            color: #475569;
        }
        .ticket-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            font-size: 0.9rem;
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
        }
        .ticket-thread {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .message {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
        }
        .message::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 4px;
        }
        .message-agent {
            background: #fff;
        }
        .message-agent::before {
            background: #3b82f6;
        }
        .message-customer {
            background: linear-gradient(135deg, #fff9f5 0%, #ffffff 100%);
        }
        .message-customer::before {
            background: #ff5900;
        }
        .message-internal {
            background: #f1f5f9;
            border-style: dashed;
        }
        .message-internal::before {
            background: #64748b;
        }
        .message header {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #475569;
            font-weight: 700;
            margin-bottom: 0.75rem;
            padding-left: 0.75rem;
        }
        .message p {
            margin: 0;
            color: #1e293b;
            white-space: pre-wrap;
            line-height: 1.6;
            padding-left: 0.75rem;
        }
        .reply-box {
            border-top: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
            background: #fff;
            border-radius: 0 0 16px 0;
        }
        textarea.reply-input {
            width: 100%;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 1rem 1.25rem;
            min-height: 100px;
            resize: vertical;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        textarea.reply-input:focus {
            outline: none;
            border-color: #ff5900;
            box-shadow: 0 0 0 4px rgba(255, 89, 0, 0.1);
        }
        .reply-actions {
            margin-top: 0.75rem;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .btn-primary {
            background: linear-gradient(120deg, #ff5900 0%, #e64f00 100%);
            border: none;
            padding: 0.75rem 1.75rem;
            border-radius: 10px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(255, 89, 0, 0.2);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 89, 0, 0.3);
        }
        .btn-outline {
            border: 2px solid #e2e8f0;
            background: #fff;
            color: #475569;
            border-radius: 10px;
            padding: 0.75rem 1.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }
        .btn-outline:hover {
            border-color: #ff5900;
            color: #ff5900;
            background: #fff9f5;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
            color: #94a3b8;
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
                .agent-ops {grid-template-columns: 1fr;}
        .pagination button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        @media (max-width: 1100px) {
            .support-content {grid-template-columns: 1fr;}
            .ticket-detail {border-top: 1px solid #e2e8f0;}
        }
    </style>
</head>
<body class="admin-body">
<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <div>
                <h1>Support Tickets</h1>
                <p>Monitor, assign, and resolve customer support requests</p>
            </div>
            <button class="btn-primary" id="btnRefresh">Refresh</button>
        </header>

        <section class="support-dashboard">
            <div class="support-stats" id="supportStats">
                <!-- stats cards inserted via JS -->
            </div>

            <section class="agent-ops">
                <div class="status-card">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                        <h3>My Availability</h3>
                        <span id="myOnlineStatus" class="pill offline">Offline</span>
                    </div>
                    <p class="status-meta">Control when tickets auto-assign to you</p>
                    <div class="status-toggles">
                        <div class="status-row">
                            <span>Online</span>
                            <button type="button" class="toggle-switch" id="toggleOnline"><span></span></button>
                        </div>
                        <div class="status-row">
                            <span>Auto Assign</span>
                            <button type="button" class="toggle-switch" id="toggleAutoAssign"><span></span></button>
                        </div>
                    </div>
                    <div class="slider-row">
                        <label>Max Active Tickets <strong id="maxTicketsValue">3</strong></label>
                        <input type="range" min="1" max="10" value="3" id="maxTicketsRange">
                    </div>
                    <div class="status-row" style="margin-top:1rem;">
                        <span>Sound Alerts</span>
                        <button type="button" class="toggle-switch" id="toggleSound"><span></span></button>
                    </div>
                </div>

                <div class="queue-card">
                    <div class="queue-header">
                        <div>
                            <h3>Ticket Queue</h3>
                            <p>Waiting tickets auto-dispatch when someone is free</p>
                        </div>
                        <span class="queue-count" id="queueCount">0</span>
                    </div>
                    <div id="queueList" class="queue-list">
                        <div class="empty-state" style="padding:1rem 0;">Queue is clear</div>
                    </div>
                    <div class="queue-actions">
                        <button type="button" class="btn-primary full-width" id="btnClaimNext">Assign Next To Me</button>
                        <button type="button" class="btn-outline full-width" id="btnAutoAssign">Send To Available Agent</button>
                    </div>
                </div>
            </section>

            <div class="agent-board-card">
                <div class="ticket-header">
                    <strong>Agent Board</strong>
                    <span id="agentBoardTimestamp" class="text-muted">Syncing...</span>
                </div>
                <div id="agentBoardGrid" class="agent-board-grid">
                    <div class="empty-state" style="grid-column:1 / -1;">Loading team snapshot...</div>
                </div>
            </div>

            <form class="support-filters" id="filterForm">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="waiting_customer">Waiting Customer</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority">
                        <option value="">All</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="form-group">
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
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Subject, ticket #, customer">
                </div>
                <div class="form-group">
                    <label>Date From</label>
                    <input type="date" name="date_from">
                </div>
                <div class="form-group">
                    <label>Date To</label>
                    <input type="date" name="date_to">
                </div>
                <div class="form-group" style="min-width:140px;">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-primary">Apply Filters</button>
                </div>
            </form>

            <div class="support-content">
                <div class="ticket-list">
                    <div class="ticket-header">
                        <strong>Tickets</strong>
                        <span id="ticketCount" class="text-muted"></span>
                    </div>
                    <div class="ticket-items" id="ticketList">
                        <div class="empty-state">No tickets found. Adjust filters or refresh.</div>
                    </div>
                    <div class="pagination">
                        <button type="button" id="prevPage">Prev</button>
                        <span id="pageInfo">Page 1 of 1</span>
                        <button type="button" id="nextPage">Next</button>
                    </div>
                </div>
                <div class="ticket-detail" id="ticketDetail">
                    <div class="empty-state">Select a ticket to view details</div>
                </div>
            </div>
        </section>
    </main>
</div>
<script>
const state = {
    page: 1,
    pages: 1,
    limit: 15,
    filters: {},
    tickets: [],
    activeTicket: null,
    loading: false
};
const currentAdminId = <?php echo (int)$currentUser['id']; ?>;
const agentState = {
    myStatus: null,
    queueIds: [],
    lastTicketId: null,
    initialized: false,
    allAgents: []
};
let audioCtx = null;
let boardTimer = null;
let heartbeatTimer = null;

const ticketListEl = document.getElementById('ticketList');
const ticketCountEl = document.getElementById('ticketCount');
const ticketDetailEl = document.getElementById('ticketDetail');
const statsEl = document.getElementById('supportStats');
const pageInfoEl = document.getElementById('pageInfo');
const prevBtn = document.getElementById('prevPage');
const nextBtn = document.getElementById('nextPage');
const filterForm = document.getElementById('filterForm');
const agentBoardEl = document.getElementById('agentBoardGrid');
const agentTimestampEl = document.getElementById('agentBoardTimestamp');
const queueListEl = document.getElementById('queueList');
const queueCountEl = document.getElementById('queueCount');
const toggleOnlineBtn = document.getElementById('toggleOnline');
const toggleAutoAssignBtn = document.getElementById('toggleAutoAssign');
const toggleSoundBtn = document.getElementById('toggleSound');
const maxTicketsRange = document.getElementById('maxTicketsRange');
const maxTicketsValue = document.getElementById('maxTicketsValue');
const btnClaimNext = document.getElementById('btnClaimNext');
const btnAutoAssign = document.getElementById('btnAutoAssign');

const statusBadge = status => `badge status-${status}`;
const priorityBadge = priority => `badge priority-${priority}`;
const formatDateTime = value => value ? new Date(value).toLocaleString() : '—';
const formatCurrency = (amount, currency) => {
    if (amount === null || amount === undefined) return '—';
    const num = Number(amount);
    if (Number.isNaN(num)) return '—';
    const formatted = num.toLocaleString(undefined, { maximumFractionDigits: 2 });
    return currency ? `${currency} ${formatted}` : formatted;
};
const formatTimeAgo = value => {
    if (!value) return 'just now';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'just now';
    const diffMins = Math.floor((Date.now() - date.getTime()) / 60000);
    if (diffMins < 1) return 'just now';
    if (diffMins === 1) return '1 min ago';
    if (diffMins < 60) return `${diffMins} mins ago`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours === 1) return '1 hr ago';
    if (diffHours < 24) return `${diffHours} hrs ago`;
    const diffDays = Math.floor(diffHours / 24);
    return diffDays === 1 ? '1 day ago' : `${diffDays} days ago`;
};

function fetchJSON(url, options = {}) {
    return fetch(url, {
        credentials: 'same-origin',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        ...options
    }).then(res => res.json());
}

function loadStats() {
    fetchJSON('support_api.php?action=tickets.stats')
        .then(res => {
            if (!res.success) throw new Error(res.error || 'Failed to load stats');
            renderStats(res.stats);
        })
        .catch(err => console.error(err));
}

function renderStats(stats) {
    statsEl.innerHTML = `
        <div class="support-card">
            <h4>Total Tickets</h4>
            <div class="value">${stats.tickets.total || 0}</div>
            <div class="subtext">${stats.today.created || 0} new today</div>
        </div>
        <div class="support-card">
            <h4>Open</h4>
            <div class="value">${stats.tickets.open || 0}</div>
            <div class="subtext">${stats.tickets.waiting_customer || 0} waiting customer</div>
        </div>
        <div class="support-card">
            <h4>In Progress</h4>
            <div class="value">${stats.tickets.in_progress || 0}</div>
            <div class="subtext">${stats.today.closed || 0} closed today</div>
        </div>
        <div class="support-card">
            <h4>Live Chats</h4>
            <div class="value">${stats.chats.active || 0}</div>
            <div class="subtext">${stats.chats.waiting || 0} waiting assignment</div>
        </div>`;
}

function submitAgentStatus(payload, silent = false) {
    const formData = new FormData();
    formData.append('action', 'agents.status');
    Object.entries(payload).forEach(([key, value]) => formData.append(key, value));

    return fetchJSON('support_api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => {
            if (!res.success) throw new Error(res.error || 'Failed to update status');
            if (!silent) {
                loadAgentBoard();
            }
            return res;
        })
        .catch(err => {
            console.error(err);
            if (!silent) {
                alert(err.message);
            }
        });
}

function renderMyStatus(status) {
    agentState.myStatus = status;
    const online = status && Number(status.is_online) === 1;
    const autoAssign = status && Number(status.auto_assign) === 1;
    const sound = status && Number(status.sound_enabled) === 1;
    const maxTickets = status ? Number(status.max_active_tickets || 3) : 3;

    toggleOnlineBtn.classList.toggle('active', online);
    toggleAutoAssignBtn.classList.toggle('active', autoAssign);
    toggleSoundBtn.classList.toggle('active', sound);
    maxTicketsRange.value = maxTickets;
    maxTicketsValue.textContent = maxTickets;
    
    // Update online status badge
    const statusBadge = document.getElementById('myOnlineStatus');
    if (statusBadge) {
        statusBadge.textContent = online ? 'Online' : 'Offline';
        statusBadge.className = online ? 'pill online' : 'pill offline';
    }
    
    [btnClaimNext, btnAutoAssign].forEach(btn => {
        if (btn) {
            btn.disabled = queueCountEl.textContent === '0';
        }
    });
}

function renderAgentBoardAgents(agents) {
    if (!agents || !agents.length) {
        agentBoardEl.innerHTML = '<div class="empty-state" style="grid-column:1 / -1;">No active agents yet</div>';
        return;
    }

    agentBoardEl.innerHTML = agents.map(agent => {
        const online = Number(agent.is_online) === 1;
        const autoAssign = Number(agent.auto_assign) === 1;
        const activeCount = Number(agent.active_count) || 0;
        const maxAllowed = Number(agent.max_active_tickets) || 0;
        const ticketLabel = agent.last_ticket_number || (agent.last_ticket_id ? `#${agent.last_ticket_id}` : '');
        return `<div class="agent-card">
            <strong>${agent.full_name}</strong>
            <small>${online ? 'Available' : 'Offline'}${ticketLabel ? ` • ${ticketLabel}` : ''}</small>
            <div class="agent-pills">
                <span class="pill ${online ? 'online' : 'offline'}">${online ? 'Online' : 'Offline'}</span>
                <span class="pill ${autoAssign ? 'auto' : 'manual'}">${autoAssign ? 'Auto assign' : 'Manual'}</span>
            </div>
            <div class="agent-capacity">Active: ${activeCount}/${maxAllowed}</div>
        </div>`;
    }).join('');
}

function renderQueue(queue) {
    const count = queue ? queue.length : 0;
    queueCountEl.textContent = count;
    const disable = count === 0;
    [btnClaimNext, btnAutoAssign].forEach(btn => {
        if (btn) {
            btn.disabled = disable;
        }
    });

    if (!count) {
        queueListEl.innerHTML = '<div class="empty-state" style="padding:1rem 0;">Queue is clear</div>';
        return;
    }

    queueListEl.innerHTML = queue.map(item => {
        const waitLabel = formatTimeAgo(item.queued_at);
        return `<div class="queue-item">
            <h5>${item.subject}</h5>
            <div class="queue-meta">
                <span>#${item.ticket_number} • ${item.priority}</span>
                <span>${waitLabel}</span>
            </div>
            <div style="margin-top:0.4rem;font-size:0.85rem;color:#475569;">${item.customer_name || 'Guest'}</div>
            <div class="queue-mini-actions">
                <button type="button" class="btn-primary btn-small" data-assign-me="${item.ticket_id}">Take</button>
                <button type="button" class="btn-outline btn-small" data-assign-auto="${item.ticket_id}">Auto</button>
            </div>
        </div>`;
    }).join('');
}

function playNotificationSound() {
    if (!agentState.myStatus || Number(agentState.myStatus.sound_enabled) !== 1) {
        return;
    }
    try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) {
            return;
        }
        if (!audioCtx) {
            audioCtx = new AudioContextClass();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        const duration = 0.25;
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(880, audioCtx.currentTime);
        gain.gain.setValueAtTime(0.001, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.3, audioCtx.currentTime + 0.01);
        gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
        osc.connect(gain).connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + duration);
    } catch (err) {
        console.warn('Notification sound unavailable', err);
    }
}

function processAgentNotifications(board, myStatus, initialLoad) {
    const queueIds = board.queue.map(item => item.ticket_id);
    const myTicketId = myStatus ? myStatus.last_ticket_id : null;

    if (!agentState.initialized || initialLoad) {
        agentState.queueIds = queueIds;
        agentState.lastTicketId = myTicketId;
        agentState.initialized = true;
        return;
    }

    const hadNewQueue = queueIds.some(id => !agentState.queueIds.includes(id));
    const gotNewTicket = myTicketId && myTicketId !== agentState.lastTicketId;

    agentState.queueIds = queueIds;
    agentState.lastTicketId = myTicketId;

    if ((hadNewQueue || gotNewTicket) && myStatus && Number(myStatus.sound_enabled) === 1) {
        playNotificationSound();
    }
}

function loadAgentBoard(initial = false) {
    fetchJSON('support_api.php?action=agents.board')
        .then(res => {
            if (!res.success) throw new Error(res.error || 'Failed to load agent board');
            const agents = res.agents || [];
            const queue = res.queue || [];
            const myStatus = agents.find(agent => agent.id === currentAdminId) || null;
            agentState.allAgents = agents;
            renderQueue(queue);
            renderMyStatus(myStatus);
            renderAgentBoardAgents(agents);
            agentTimestampEl.textContent = 'Updated ' + new Date().toLocaleTimeString();
            processAgentNotifications({ queue }, myStatus, initial);
        })
        .catch(err => console.error(err));
}

function assignFromQueue(ticketId = null, agentId = currentAdminId) {
    const formData = new FormData();
    formData.append('action', 'queue.assign');
    if (ticketId) {
        const parsedTicketId = parseInt(ticketId, 10);
        if (!Number.isNaN(parsedTicketId)) {
            formData.append('ticket_id', parsedTicketId);
        }
    }
    if (agentId) {
        const parsedAgentId = parseInt(agentId, 10);
        if (!Number.isNaN(parsedAgentId)) {
            formData.append('agent_id', parsedAgentId);
        }
    }

    fetchJSON('support_api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => {
            if (!res || !res.success) throw new Error(res && res.error ? res.error : 'Queue assignment failed');
            loadAgentBoard();
            loadTickets();
            if (res.ticket_id && res.agent_id === currentAdminId) {
                loadTicketDetail(res.ticket_id);
            }
        })
        .catch(err => alert(err.message));
}

function buildQuery(params) {
    const filtered = Object.entries(params)
        .filter(([, value]) => value !== undefined && value !== null && value !== '');
    return filtered.length ? '?' + filtered
        .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
        .join('&') : '';
}

function loadTickets() {
    state.loading = true;
    ticketListEl.innerHTML = '<div class="empty-state">Loading tickets...</div>';

    const query = buildQuery({
        action: 'tickets.list',
        page: state.page,
        limit: state.limit,
        ...state.filters
    });

    fetchJSON('support_api.php' + query)
        .then(res => {
            state.loading = false;
            if (!res.success) throw new Error(res.error || 'Failed to load tickets');
            state.tickets = res.tickets || [];
            state.page = res.page;
            state.pages = Math.max(1, res.pages);
            renderTickets();
        })
        .catch(err => {
            console.error(err);
            ticketListEl.innerHTML = `<div class="empty-state">${err.message}</div>`;
        });
}

function renderTickets() {
    if (!state.tickets.length) {
        ticketListEl.innerHTML = '<div class="empty-state">No tickets match your filters.</div>';
        ticketCountEl.textContent = '';
        pageInfoEl.textContent = `Page ${state.page} of ${state.pages}`;
        prevBtn.disabled = state.page <= 1;
        nextBtn.disabled = state.page >= state.pages;
        ticketDetailEl.innerHTML = '<div class="empty-state">Select a ticket to view details</div>';
        state.activeTicket = null;
        return;
    }

    ticketCountEl.textContent = `${state.tickets.length} tickets`;
    const items = state.tickets.map(ticket => {
        const lastUpdate = new Date(ticket.updated_at).toLocaleString();
        const customer = ticket.customer_id
            ? `${ticket.first_name || ''} ${ticket.last_name || ''}`.trim() || ticket.business_name || 'Customer'
            : ticket.guest_name || 'Guest';
        return `<div class="ticket-item ${state.activeTicket && state.activeTicket.id === ticket.id ? 'active' : ''}" data-id="${ticket.id}">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">
                <div>
                    <h5>${ticket.subject}</h5>
                    <small style="color:#94a3b8;">#${ticket.ticket_number} • ${customer}</small>
                </div>
                <div style="text-align:right;">
                    <div class="badge status-${ticket.status}">${ticket.status.replace('_',' ')}</div>
                    <div class="badge priority-${ticket.priority}" style="margin-top:0.35rem;">${ticket.priority}</div>
                </div>
            </div>
            <div class="ticket-meta">
                <span>Category: ${ticket.category}</span>
                <span>Last update: ${lastUpdate}</span>
                <span>${ticket.assigned_name ? 'Agent: ' + ticket.assigned_name : 'Unassigned'}</span>
            </div>
        </div>`;
    }).join('');

    ticketListEl.innerHTML = items;
    pageInfoEl.textContent = `Page ${state.page} of ${state.pages}`;
    prevBtn.disabled = state.page <= 1;
    nextBtn.disabled = state.page >= state.pages;
}

function loadTicketDetail(ticketId) {
    ticketDetailEl.innerHTML = '<div class="empty-state">Loading ticket...</div>';
    fetchJSON('support_api.php?action=tickets.detail&ticket_id=' + ticketId)
        .then(res => {
            if (!res.success) throw new Error(res.error || 'Failed to load ticket');
            state.activeTicket = res.ticket;
            renderTicketDetail();
        })
        .catch(err => {
            ticketDetailEl.innerHTML = `<div class="empty-state">${err.message}</div>`;
        });
}

function renderTicketDetail() {
    if (!state.activeTicket) {
        ticketDetailEl.innerHTML = '<div class="empty-state">Select a ticket to view details</div>';
        return;
    }

    const ticket = state.activeTicket;
    const customerName = ticket.customer_id
        ? `${ticket.first_name || ''} ${ticket.last_name || ''}`.trim() || ticket.business_name || 'Customer'
        : ticket.guest_name || 'Guest';

    let insightsHtml = '';
    if (ticket.customer_profile) {
        const profile = ticket.customer_profile;
        const stats = profile.stats || {};
        const orders = (profile.recent_orders || []).map(order => `
            <li>
                <span>#${order.order_number || order.id} • ${order.status}</span>
                <span>${formatCurrency(order.total_amount, order.currency)} • ${formatDateTime(order.created_at)}</span>
            </li>`).join('') || '<li><span>No recent orders</span><span></span></li>';
        const otherTickets = (profile.recent_tickets || []).map(item => `
            <li>
                <span>#${item.ticket_number} • ${item.status.replace('_',' ')}</span>
                <span>${formatDateTime(item.created_at)}</span>
            </li>`).join('') || '<li><span>No other tickets</span><span></span></li>';

        insightsHtml = `
            <div class="customer-insights">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
                    <strong>Customer Insights</strong>
                    <span style="color:#94a3b8;font-size:0.85rem;">Helps agents resolve faster</span>
                </div>
                <div class="insight-cards">
                    <div class="insight-card">
                        <span>Total Orders</span>
                        <strong>${stats.total_orders ?? 0}</strong>
                        <small style="color:#94a3b8;">Last: ${formatDateTime(stats.last_order_date)}</small>
                    </div>
                    <div class="insight-card">
                        <span>Open Tickets</span>
                        <strong>${stats.open_tickets ?? 0}</strong>
                        <small style="color:#94a3b8;">All-time: ${stats.total_tickets ?? 0}</small>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem;">
                    <div>
                        <h4 style="margin:0 0 0.4rem 0;color:#0f172a;font-size:0.95rem;">Recent Orders</h4>
                        <ul class="insight-list">${orders}</ul>
                    </div>
                    <div>
                        <h4 style="margin:0 0 0.4rem 0;color:#0f172a;font-size:0.95rem;">Other Tickets</h4>
                        <ul class="insight-list">${otherTickets}</ul>
                    </div>
                </div>
            </div>`;
    }

    const messages = (ticket.messages || []).map(msg => {
        const typeClass = msg.is_internal ? 'message message-internal' : `message message-${msg.sender_type}`;
        return `<div class="${typeClass}">
            <header>
                <span>${msg.sender_name || msg.sender_type}</span>
                <span>${new Date(msg.created_at).toLocaleString()}</span>
            </header>
            <p>${msg.message.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</p>
        </div>`;
    }).join('');

    ticketDetailEl.innerHTML = `
        <div class="ticket-detail-header">
            <div>
                <strong>#${ticket.ticket_number}</strong>
                <div style="color:#94a3b8;font-size:0.9rem;">${ticket.subject}</div>
            </div>
            <div style="text-align:right;">
                <div class="badge status-${ticket.status}">${ticket.status.replace('_',' ')}</div>
                <div class="badge priority-${ticket.priority}" style="margin-top:0.35rem;">${ticket.priority}</div>
            </div>
        </div>
        <div class="ticket-detail-body">
            <div class="ticket-info">
                <div>
                    <strong>Customer</strong>
                    <div>${customerName}</div>
                    <div style="color:#94a3b8;font-size:0.9rem;">${ticket.customer_email || ticket.guest_email || 'Not provided'}</div>
                </div>
                <div>
                    <strong>Category</strong>
                    <div>${ticket.category}</div>
                    <div style="color:#94a3b8;font-size:0.9rem;">Created ${new Date(ticket.created_at).toLocaleString()}</div>
                </div>
                <div>
                    <strong>Assigned To</strong>
                    <div id="assignedAgentDisplay">${ticket.assigned_name || 'Unassigned'}</div>
                    <select id="assignAgentSelect" class="form-control" style="margin-top:0.5rem;font-size:0.9rem;">
                        <option value="">-- Reassign to --</option>
                        ${agentState.allAgents.map(a => `<option value="${a.id}" ${a.id == ticket.assigned_to ? 'selected' : ''}>${a.full_name}</option>`).join('')}
                    </select>
                </div>
            </div>
            ${insightsHtml}
            <div class="ticket-thread">${messages || '<div class="empty-state">No messages yet</div>'}</div>
        </div>
        <div class="reply-box">
            <textarea class="reply-input" id="replyMessage" placeholder="Write a reply or internal note..."></textarea>
            <div class="reply-actions">
                <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.9rem;color:#475569;">
                    <input type="checkbox" id="internalNote"> Internal note
                </label>
                <button type="button" class="btn-outline" id="btnResolve">Mark Resolved</button>
                <button type="button" class="btn-primary" id="btnSendReply">Send Reply</button>
            </div>
        </div>`;

    document.getElementById('btnSendReply').addEventListener('click', sendReply);
    document.getElementById('btnResolve').addEventListener('click', () => updateStatus('resolved'));
    document.getElementById('assignAgentSelect').addEventListener('change', e => {
        const agentId = parseInt(e.target.value, 10);
        if (agentId && agentId !== ticket.assigned_to) {
            reassignTicket(ticket.id, agentId);
        }
    });
}

function sendReply() {
    const textarea = document.getElementById('replyMessage');
    const internal = document.getElementById('internalNote').checked;
    const message = textarea.value.trim();
    if (!message) {
        alert('Please enter a message');
        return;
    }
    const formData = new FormData();
    formData.append('action', 'tickets.reply');
    formData.append('ticket_id', state.activeTicket.id);
    formData.append('message', message);
    if (internal) formData.append('internal', '1');

    fetch('support_api.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
        .then(res => res.json())
        .then(res => {
            if (!res.success) throw new Error(res.error || 'Failed to send reply');
            textarea.value = '';
            document.getElementById('internalNote').checked = false;
            loadTicketDetail(state.activeTicket.id);
            loadTickets();
        })
        .catch(err => alert(err.message));
}

function updateStatus(status) {
    if (!state.activeTicket) return;
    const formData = new FormData();
    formData.append('action', 'tickets.update_status');
    formData.append('ticket_id', state.activeTicket.id);
    formData.append('status', status);
    fetch('support_api.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
        .then(res => res.json())
        .then(res => {
            if (!res.success) throw new Error(res.error || 'Failed to update status');
            loadTicketDetail(state.activeTicket.id);
            loadStats();
            loadTickets();
        })
        .catch(err => alert(err.message));
}

function reassignTicket(ticketId, agentId) {
    const formData = new FormData();
    formData.append('action', 'tickets.assign');
    formData.append('ticket_id', ticketId);
    formData.append('assigned_to', agentId);
    fetch('support_api.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
        .then(res => res.json())
        .then(res => {
            if (!res.success) throw new Error(res.error || 'Failed to reassign ticket');
            loadTicketDetail(ticketId);
            loadStats();
            loadTickets();
            loadAgentBoard();
        })
        .catch(err => {
            alert(err.message);
            loadTicketDetail(ticketId);
        });
}

// Event bindings
filterForm.addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData(filterForm);
    state.filters = Object.fromEntries(Array.from(formData.entries()).filter(([, v]) => v));
    state.page = 1;
    loadTickets();
});

prevBtn.addEventListener('click', () => {
    if (state.page > 1) {
        state.page--;
        loadTickets();
    }
});
nextBtn.addEventListener('click', () => {
    if (state.page < state.pages) {
        state.page++;
        loadTickets();
    }
});

ticketListEl.addEventListener('click', e => {
    const item = e.target.closest('.ticket-item');
    if (!item) return;
    const id = parseInt(item.dataset.id, 10);
    loadTicketDetail(id);
    document.querySelectorAll('.ticket-item').forEach(el => el.classList.remove('active'));
    item.classList.add('active');
});

document.getElementById('btnRefresh').addEventListener('click', () => {
    loadStats();
    loadTickets();
    loadAgentBoard();
});

if (toggleOnlineBtn) {
    toggleOnlineBtn.addEventListener('click', () => {
        const next = agentState.myStatus && Number(agentState.myStatus.is_online) === 1 ? 0 : 1;
        submitAgentStatus({ is_online: next });
    });
}

if (toggleAutoAssignBtn) {
    toggleAutoAssignBtn.addEventListener('click', () => {
        const next = agentState.myStatus && Number(agentState.myStatus.auto_assign) === 1 ? 0 : 1;
        submitAgentStatus({ auto_assign: next });
    });
}

if (toggleSoundBtn) {
    toggleSoundBtn.addEventListener('click', () => {
        const next = agentState.myStatus && Number(agentState.myStatus.sound_enabled) === 1 ? 0 : 1;
        submitAgentStatus({ sound_enabled: next });
    });
}

if (maxTicketsRange) {
    maxTicketsRange.addEventListener('input', () => {
        maxTicketsValue.textContent = maxTicketsRange.value;
    });
    maxTicketsRange.addEventListener('change', () => {
        submitAgentStatus({ max_active_tickets: parseInt(maxTicketsRange.value, 10) });
    });
}

if (btnClaimNext) {
    btnClaimNext.addEventListener('click', () => assignFromQueue(null, currentAdminId));
}

if (btnAutoAssign) {
    btnAutoAssign.addEventListener('click', () => assignFromQueue(null, null));
}

if (queueListEl) {
    queueListEl.addEventListener('click', e => {
        const takeBtn = e.target.closest('[data-assign-me]');
        if (takeBtn) {
            assignFromQueue(takeBtn.dataset.assignMe, currentAdminId);
            return;
        }
        const autoBtn = e.target.closest('[data-assign-auto]');
        if (autoBtn) {
            assignFromQueue(autoBtn.dataset.assignAuto || null, null);
        }
    });
}

document.addEventListener('click', () => {
    if (!audioCtx) {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (AudioContextClass) {
            audioCtx = new AudioContextClass();
        }
    } else if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
}, { once: true });

loadStats();
loadTickets();
loadAgentBoard(true);
boardTimer = setInterval(() => {
    loadAgentBoard();
    loadStats();
    if (!state.loading && !state.activeTicket) {
        loadTickets();
    }
}, 10000);
heartbeatTimer = setInterval(() => submitAgentStatus({ heartbeat: 1 }, true), 30000);

window.addEventListener('beforeunload', () => {
    if (boardTimer) clearInterval(boardTimer);
    if (heartbeatTimer) clearInterval(heartbeatTimer);
});
</script>
</body>
</html>
