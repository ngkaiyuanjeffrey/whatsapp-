/**
 * WhatsApp Bot Control Panel - Frontend Application Logic
 */

const API_BASE = 'api/admin.php';

const state = {
  currentTab: 'dashboard',
  stats: null,
  contacts: [],
  campaigns: [],
  jobs: [],
  workers: [],
  searchQuery: '',
  selectedJobFilter: 'all',
  pollInterval: null,
};

// --- DOM Elements Helpers ---
const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => document.querySelectorAll(selector);

// --- Initialization ---
document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  setupNavigation();
  setupSearch();
  setupModals();
  setupLivePreview();

  // Initial data load
  refreshAllData();

  // Background polling every 5 seconds
  state.pollInterval = setInterval(() => {
    silentPoll();
  }, 5000);
});

// --- Theme Management ---
function initTheme() {
  const saved = localStorage.getItem('wa_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', saved);
  updateThemeIcon(saved);

  const toggleBtn = $('#themeToggleBtn');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      const current = document.documentElement.getAttribute('data-theme') || 'dark';
      const next = current === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('wa_theme', next);
      updateThemeIcon(next);
      showToast(`Switched to ${next} theme`, 'info');
    });
  }
}

function updateThemeIcon(theme) {
  const btn = $('#themeToggleBtn');
  if (!btn) return;
  btn.innerHTML = theme === 'dark' 
    ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>`
    : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;
}

// --- Navigation Tabs ---
function setupNavigation() {
  const tabs = $$('.tab-btn');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.tab;
      switchTab(target);
    });
  });

  $('#btnRefresh')?.addEventListener('click', () => {
    refreshAllData(true);
  });
}

function switchTab(tabName) {
  state.currentTab = tabName;
  $$('.tab-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.tab === tabName);
  });

  $$('.view-pane').forEach(pane => {
    pane.style.display = 'none';
  });

  const activePane = $(`#view-${tabName}`);
  if (activePane) {
    activePane.style.display = 'block';
  }

  // Update Main Stage Header
  const titles = {
    dashboard: { title: 'Dashboard Overview', desc: 'Real-time WhatsApp bot dispatch & queue statistics' },
    campaigns: { title: 'Broadcast Campaigns', desc: 'Create message templates and blast to audiences' },
    direct: { title: 'Direct Send (Quick Test)', desc: 'Instantly push a WhatsApp message to the dispatch queue' },
    contacts: { title: 'Contacts & Audience CRM', desc: 'Manage recipient phone numbers and company details' },
    jobs: { title: 'Message Jobs Queue', desc: 'Live dispatch queue, worker claims & delivery records' },
    workers: { title: 'Worker Automation Nodes', desc: 'Monitor Python Playwright workers & link WhatsApp' },
  };

  const meta = titles[tabName] || { title: 'WhatsApp Bot Panel', desc: '' };
  $('#stageTitle').textContent = meta.title;
  $('#stageDesc').textContent = meta.desc;

  // Render view-specific content
  if (tabName === 'dashboard') renderDashboard();
  if (tabName === 'campaigns') renderCampaigns();
  if (tabName === 'contacts') renderContacts();
  if (tabName === 'jobs') renderJobs();
  if (tabName === 'workers') renderWorkers();
  if (tabName === 'direct') updateDirectPreview();
}

// --- Search Filter ---
function setupSearch() {
  const searchInput = $('#sidebarSearchInput');
  if (!searchInput) return;
  searchInput.addEventListener('input', (e) => {
    state.searchQuery = e.target.value.toLowerCase().trim();
    if (state.currentTab === 'contacts') renderContacts();
    if (state.currentTab === 'jobs') renderJobs();
    if (state.currentTab === 'campaigns') renderCampaigns();
  });
}

// --- Live WhatsApp Previewer ---
function setupLivePreview() {
  const templateInput = $('#campTemplateInput');
  if (templateInput) {
    templateInput.addEventListener('input', () => {
      updateCampaignPreview();
    });
  }

  const directInput = $('#directMessageInput');
  if (directInput) {
    directInput.addEventListener('input', () => {
      updateDirectPreview();
    });
  }

  // Quick variable inserters
  $$('.var-tag').forEach(tag => {
    tag.addEventListener('click', () => {
      const varText = tag.dataset.var;
      insertAtCursor($('#campTemplateInput'), varText);
      updateCampaignPreview();
    });
  });
}

function insertAtCursor(textarea, text) {
  if (!textarea) return;
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const val = textarea.value;
  textarea.value = val.substring(0, start) + text + val.substring(end);
  textarea.selectionStart = textarea.selectionEnd = start + text.length;
  textarea.focus();
}

function formatWhatsAppText(text, sampleContact = {}) {
  if (!text) return '<span style="color: rgba(255,255,255,0.4);">Type your WhatsApp message to see live preview...</span>';

  // Replace variables
  let parsed = text
    .replace(/\{name\}/g, sampleContact.name || 'Alex Tan (陈先生)')
    .replace(/\{company\}/g, sampleContact.company || 'Shopee Seller')
    .replace(/\{phone\}/g, sampleContact.phone || '+60123456789');

  // Escape HTML
  parsed = parsed
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');

  // Formatting rules
  // *bold*
  parsed = parsed.replace(/\*(.*?)\*/g, '<strong>$1</strong>');
  // _italic_
  parsed = parsed.replace(/_(.*?)_/g, '<em>$1</em>');
  // ~strikethrough~
  parsed = parsed.replace(/~(.*?)~/g, '<del>$1</del>');
  // ```code```
  parsed = parsed.replace(/```(.*?)```/gs, '<code>$1</code>');
  // Newlines
  parsed = parsed.replace(/\n/g, '<br>');

  return parsed;
}

function nowTimeString() {
  const d = new Date();
  return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
}

function updateCampaignPreview() {
  const text = $('#campTemplateInput')?.value || '';
  const bubble = $('#campaignPreviewBubble');
  if (bubble) {
    bubble.innerHTML = formatWhatsAppText(text);
  }
  const ts = $('#previewTimestamp');
  if (ts) {
    ts.textContent = nowTimeString();
  }
}

function updateDirectPreview() {
  const text = $('#directMessageInput')?.value || '';
  const name = $('#directNameInput')?.value || 'Recipient';
  const phone = $('#directPhoneInput')?.value || '+60123456789';
  const bubble = $('#directPreviewBubble');
  if (bubble) {
    bubble.innerHTML = formatWhatsAppText(text, { name, phone });
  }
  const phoneName = $('#directMockupName');
  if (phoneName) {
    phoneName.textContent = name || phone || 'Customer';
  }
  const directBubbleMeta = bubble?.parentElement?.querySelector('.bubble-meta span:first-child');
  if (directBubbleMeta) {
    directBubbleMeta.textContent = nowTimeString();
  }
}

// --- Data Fetching ---
async function fetchAPI(action, method = 'GET', body = null) {
  const url = `${API_BASE}?action=${action}`;
  const options = {
    method,
    headers: { 'Content-Type': 'application/json' },
  };
  if (body) {
    options.body = JSON.stringify(body);
  }

  try {
    const res = await fetch(url, options);
    const json = await res.json();
    return json;
  } catch (err) {
    console.error(`API Error on [${action}]:`, err);
    return { success: false, message: err.message };
  }
}

async function refreshAllData(showToastMsg = false) {
  const [statsRes, contactsRes, campaignsRes, jobsRes, workersRes] = await Promise.all([
    fetchAPI('stats'),
    fetchAPI('contacts'),
    fetchAPI('campaigns'),
    fetchAPI('jobs'),
    fetchAPI('workers'),
  ]);

  if (statsRes.success) state.stats = statsRes.data;
  if (contactsRes.success) state.contacts = contactsRes.data;
  if (campaignsRes.success) state.campaigns = campaignsRes.data;
  if (jobsRes.success) state.jobs = jobsRes.data;
  if (workersRes.success) state.workers = workersRes.data;

  updateGlobalStatus();

  // Re-render current active tab
  switchTab(state.currentTab);

  if (showToastMsg) {
    showToast('All data refreshed successfully', 'success');
  }
}

async function silentPoll() {
  const statsRes = await fetchAPI('stats');
  if (statsRes.success) {
    state.stats = statsRes.data;
    updateGlobalStatus();
    if (state.currentTab === 'dashboard') {
      renderDashboard();
    }
  }

  // Refresh workers and jobs in background
  if (state.currentTab === 'jobs' || state.currentTab === 'dashboard') {
    const jobsRes = await fetchAPI('jobs');
    if (jobsRes.success) {
      state.jobs = jobsRes.data;
      if (state.currentTab === 'jobs') renderJobs();
    }
  }

  if (state.currentTab === 'workers' || state.currentTab === 'dashboard') {
    const workersRes = await fetchAPI('workers');
    if (workersRes.success) {
      state.workers = workersRes.data;
      if (state.currentTab === 'workers') renderWorkers();
    }
  }
}

function updateGlobalStatus() {
  if (!state.stats) return;
  const workers = state.stats.workers || {};
  const onlineCount = parseInt(workers.online_workers || 0, 10);

  const dot = $('#globalWorkerDot');
  const text = $('#globalWorkerText');

  if (dot && text) {
    if (onlineCount > 0) {
      dot.className = 'status-indicator-dot pulse';
      text.textContent = `${onlineCount} Worker Online (Active)`;
    } else {
      dot.className = 'status-indicator-dot';
      dot.style.backgroundColor = 'var(--wa-danger)';
      text.textContent = '0 Workers Online (Idle)';
    }
  }

  // Update tab badge numbers
  const jobsBadge = $('#badgeJobsCount');
  if (jobsBadge && state.stats.jobs) {
    const pending = state.stats.jobs.pending || 0;
    jobsBadge.textContent = pending > 0 ? pending : '';
    jobsBadge.style.display = pending > 0 ? 'inline-block' : 'none';
  }
}

// --- View Renderers ---

// 1. Dashboard View
function renderDashboard() {
  if (!state.stats) return;
  const s = state.stats;

  // Metric cards
  $('#metricOnlineWorkers').textContent = `${s.workers.online_workers || 0} / ${s.workers.total_workers || 0}`;
  $('#metricContactsCount').textContent = s.contacts_count || 0;
  $('#metricPendingJobs').textContent = s.jobs.pending || 0;
  
  const totalCompleted = (s.jobs.sent || 0) + (s.jobs.failed || 0);
  const successRate = totalCompleted > 0 ? Math.round(((s.jobs.sent || 0) / totalCompleted) * 100) : 100;
  $('#metricSentCount').textContent = `${s.jobs.sent || 0} (${successRate}%)`;

  // Queue visual breakdown bar
  const total = s.jobs.total || 1;
  const pSent = Math.round(((s.jobs.sent || 0) / total) * 100);
  const pPending = Math.round(((s.jobs.pending || 0) / total) * 100);
  const pProc = Math.round(((s.jobs.processing || 0) / total) * 100);
  const pFail = Math.round(((s.jobs.failed || 0) / total) * 100);

  $('#queueSentBar').style.width = `${pSent}%`;
  $('#queuePendingBar').style.width = `${pPending}%`;
  $('#queueProcBar').style.width = `${pProc}%`;
  $('#queueFailBar').style.width = `${pFail}%`;

  $('#lblSentCount').textContent = `${s.jobs.sent || 0} Sent`;
  $('#lblPendingCount').textContent = `${s.jobs.pending || 0} Pending`;
  $('#lblProcCount').textContent = `${s.jobs.processing || 0} In Progress`;
  $('#lblFailCount').textContent = `${s.jobs.failed || 0} Failed`;

  // Recent jobs live feed
  const container = $('#recentJobsFeed');
  if (container && s.recent_jobs) {
    if (s.recent_jobs.length === 0) {
      container.innerHTML = `<div style="padding: 24px; text-align: center; color: var(--wa-text-secondary);">No messages dispatched yet. Click "Quick Direct Send" or "Broadcast Campaigns" to launch!</div>`;
      return;
    }

    container.innerHTML = s.recent_jobs.map(job => {
      const time = job.completed_at ? job.completed_at.split(' ')[1] : job.created_at.split(' ')[1];
      let statusBadge = `<span class="badge badge-pending">Pending</span>`;
      let checkIcon = `<span class="check-double-gray">✓</span>`;

      if (job.status === 'sent') {
        statusBadge = `<span class="badge badge-sent">Sent</span>`;
        checkIcon = `<span class="check-double-blue">✓✓</span>`;
      } else if (job.status === 'processing') {
        statusBadge = `<span class="badge badge-processing">Processing</span>`;
        checkIcon = `<span class="check-double-gray">✓✓</span>`;
      } else if (job.status === 'failed') {
        statusBadge = `<span class="badge badge-failed">Failed</span>`;
        checkIcon = `<span style="color: var(--wa-danger);">✗</span>`;
      }

      return `
        <div class="list-item" style="border-radius: var(--radius-md); margin-bottom: 6px; background-color: var(--wa-bg-card);">
          <div class="item-avatar teal">${(job.contact_name || 'C').charAt(0)}</div>
          <div class="item-content">
            <div class="item-top">
              <span class="item-title">${escapeHtml(job.contact_name)} <span style="font-weight: normal; color: var(--wa-text-secondary); font-size: 12px;">(${escapeHtml(job.contact_phone)})</span></span>
              <span class="item-time">${time}</span>
            </div>
            <div class="item-bottom">
              <span class="item-snippet">${escapeHtml(job.rendered_message.substring(0, 70))}...</span>
              <div style="display: flex; align-items: center; gap: 8px;">
                ${statusBadge}
                ${checkIcon}
              </div>
            </div>
          </div>
        </div>
      `;
    }).join('');
  }
}

// 2. Campaigns View
function renderCampaigns() {
  const container = $('#campaignsListTable');
  if (!container) return;

  if (state.campaigns.length === 0) {
    container.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 30px; color: var(--wa-text-secondary);">No campaigns created yet. Fill the form above and click "Create & Launch Campaign".</td></tr>`;
    return;
  }

  container.innerHTML = state.campaigns.map(c => {
    const total = parseInt(c.total_jobs || 0, 10);
    const sent = parseInt(c.sent_jobs || 0, 10);
    const failed = parseInt(c.failed_jobs || 0, 10);
    const pending = parseInt(c.pending_jobs || 0, 10);
    const proc = parseInt(c.processing_jobs || 0, 10);

    const percent = total > 0 ? Math.round((sent / total) * 100) : (c.status === 'completed' ? 100 : 0);

    let statusBadge = `<span class="badge badge-pending">${c.status}</span>`;
    if (c.status === 'running') statusBadge = `<span class="badge badge-processing">Running</span>`;
    if (c.status === 'completed') statusBadge = `<span class="badge badge-sent">Completed</span>`;
    if (c.status === 'paused') statusBadge = `<span class="badge badge-pending">Paused</span>`;
    if (c.status === 'cancelled') statusBadge = `<span class="badge badge-failed">Cancelled</span>`;

    return `
      <tr>
        <td style="font-weight: 600;">#${c.id}</td>
        <td>
          <div style="font-weight: 600; color: var(--wa-text-primary);">${escapeHtml(c.name)}</div>
          <div style="font-size: 11px; color: var(--wa-text-secondary); max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(c.message_template)}</div>
        </td>
        <td>${statusBadge}</td>
        <td style="min-width: 140px;">
          <div style="font-size: 11px; display: flex; justify-content: space-between;">
            <span>${sent} / ${total} sent</span>
            <span>${percent}%</span>
          </div>
          <div class="progress-bar-wrap">
            <div class="progress-bar-fill" style="width: ${percent}%;"></div>
          </div>
        </td>
        <td style="font-size: 12px; color: var(--wa-text-secondary);">${c.created_at.split(' ')[0]}</td>
        <td>
          <div style="display: flex; gap: 6px;">
            ${c.status === 'running' 
              ? `<button class="btn btn-secondary btn-sm" onclick="changeCampaignStatus(${c.id}, 'paused')">Pause</button>`
              : (c.status === 'paused' ? `<button class="btn btn-primary btn-sm" onclick="changeCampaignStatus(${c.id}, 'running')">Resume</button>` : '')
            }
            ${c.status !== 'cancelled' && c.status !== 'completed'
              ? `<button class="btn btn-danger btn-sm" onclick="changeCampaignStatus(${c.id}, 'cancelled')">Cancel</button>`
              : ''
            }
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

// 3. Contacts View
function renderContacts() {
  const container = $('#contactsListTable');
  if (!container) return;

  let filtered = state.contacts;
  if (state.searchQuery) {
    filtered = filtered.filter(c => 
      c.name.toLowerCase().includes(state.searchQuery) ||
      c.phone.toLowerCase().includes(state.searchQuery) ||
      (c.company && c.company.toLowerCase().includes(state.searchQuery))
    );
  }

  $('#contactsTotalBadge').textContent = `${filtered.length} Contacts`;

  if (filtered.length === 0) {
    container.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 30px; color: var(--wa-text-secondary);">No contacts found. Click "Add Contact" or "Batch Import" above.</td></tr>`;
    return;
  }

  container.innerHTML = filtered.map(c => `
    <tr>
      <td>
        <div style="display: flex; align-items: center; gap: 10px;">
          <div class="item-avatar teal" style="width: 32px; height: 32px; font-size: 12px;">${c.name.charAt(0)}</div>
          <span style="font-weight: 500;">${escapeHtml(c.name)}</span>
        </div>
      </td>
      <td style="font-family: monospace; color: var(--wa-text-link);">${escapeHtml(c.phone)}</td>
      <td>${escapeHtml(c.company || '—')}</td>
      <td><span class="badge badge-sent">Active</span></td>
      <td>
        <div style="display: flex; gap: 6px;">
          <button class="btn btn-secondary btn-sm" onclick="openDirectWithContact('${escapeHtml(c.phone)}', '${escapeHtml(c.name)}')">Send</button>
          <button class="btn btn-secondary btn-sm" onclick="openEditContact(${c.id}, '${escapeHtml(c.name)}', '${escapeHtml(c.phone)}', '${escapeHtml(c.company || '')}')">Edit</button>
          <button class="btn btn-danger btn-sm" onclick="deleteContact(${c.id})">Delete</button>
        </div>
      </td>
    </tr>
  `).join('');
}

// 4. Jobs Queue View
function renderJobs() {
  const container = $('#jobsListTable');
  if (!container) return;

  let filtered = state.jobs;
  if (state.selectedJobFilter !== 'all') {
    filtered = filtered.filter(j => j.status === state.selectedJobFilter);
  }
  if (state.searchQuery) {
    filtered = filtered.filter(j => 
      j.contact_name.toLowerCase().includes(state.searchQuery) ||
      j.contact_phone.toLowerCase().includes(state.searchQuery) ||
      j.rendered_message.toLowerCase().includes(state.searchQuery)
    );
  }

  $('#jobsFilterBadge').textContent = `${filtered.length} Jobs`;

  if (filtered.length === 0) {
    container.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 30px; color: var(--wa-text-secondary);">No jobs found in this category.</td></tr>`;
    return;
  }

  container.innerHTML = filtered.map(j => {
    let statusBadge = `<span class="badge badge-pending">Pending</span>`;
    if (j.status === 'processing') statusBadge = `<span class="badge badge-processing">Processing</span>`;
    if (j.status === 'sent') statusBadge = `<span class="badge badge-sent">Sent ✓✓</span>`;
    if (j.status === 'failed') statusBadge = `<span class="badge badge-failed">Failed</span>`;
    if (j.status === 'cancelled') statusBadge = `<span class="badge badge-offline">Cancelled</span>`;

    return `
      <tr>
        <td style="font-weight: 600;">#${j.id}</td>
        <td>
          <div style="font-weight: 600;">${escapeHtml(j.contact_name)}</div>
          <div style="font-size: 11px; font-family: monospace; color: var(--wa-text-secondary);">${escapeHtml(j.contact_phone)}</div>
        </td>
        <td>
          <div style="max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 12px;">${escapeHtml(j.rendered_message)}</div>
          ${j.last_error ? `<div style="color: var(--wa-danger); font-size: 11px; margin-top: 2px;">Error: ${escapeHtml(j.last_error)}</div>` : ''}
        </td>
        <td>${statusBadge}</td>
        <td style="font-size: 12px;">${j.attempts} / ${j.max_attempts}</td>
        <td style="font-size: 11px; color: var(--wa-text-secondary);">${j.worker_name || '—'}</td>
        <td>
          <div style="display: flex; gap: 6px;">
            ${(j.status === 'failed' || j.status === 'cancelled') 
              ? `<button class="btn btn-primary btn-sm" onclick="retryJob(${j.id})">Retry</button>` 
              : ''
            }
            ${j.status === 'pending'
              ? `<button class="btn btn-danger btn-sm" onclick="cancelJob(${j.id})">Cancel</button>`
              : ''
            }
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

// 5. Workers View
function renderWorkers() {
  const container = $('#workersGrid');
  if (!container) return;

  if (state.workers.length === 0) {
    container.innerHTML = `<div style="grid-column: 1/-1; padding: 40px; text-align: center; color: var(--wa-text-secondary);">No worker instances registered yet. Click "Add Worker Node" to register a Windows Playwright worker.</div>`;
    return;
  }

  container.innerHTML = state.workers.map(w => {
    const isOnline = w.connection_state === 'online';
    const statusText = isOnline ? 'Online' : 'Offline';
    const badgeClass = isOnline ? 'badge-online' : 'badge-offline';

    let waBadge = `<span class="badge badge-offline">WhatsApp: Unknown</span>`;
    if (w.whatsapp_status === 'connected') waBadge = `<span class="badge badge-sent">WhatsApp: Connected ✓</span>`;
    if (w.whatsapp_status === 'qr_required') waBadge = `<span class="badge badge-pending">WhatsApp: QR Code Required</span>`;
    if (w.whatsapp_status === 'disconnected') waBadge = `<span class="badge badge-failed">WhatsApp: Disconnected</span>`;

    return `
      <div class="metric-card" style="flex-direction: column; align-items: stretch; gap: 14px;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div class="metric-icon teal" style="width: 40px; height: 40px;">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            </div>
            <div>
              <div style="font-weight: 600; font-size: 15px;">${escapeHtml(w.name)}</div>
              <div style="font-size: 11px; color: var(--wa-text-secondary);">ID: #${w.id} • Version: ${escapeHtml(w.worker_version || '1.0.0')}</div>
            </div>
          </div>
          <span class="badge ${badgeClass}">${statusText}</span>
        </div>

        <div style="display: flex; flex-direction: column; gap: 6px; font-size: 12px; background: rgba(0,0,0,0.15); padding: 10px; border-radius: 8px;">
          <div style="display: flex; justify-content: space-between;">
            <span style="color: var(--wa-text-secondary);">WhatsApp Link:</span>
            ${waBadge}
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="color: var(--wa-text-secondary);">Last Heartbeat:</span>
            <span style="color: var(--wa-text-primary); font-family: monospace;">${w.last_heartbeat_at || 'Never'}</span>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="color: var(--wa-text-secondary);">Current Task:</span>
            <span>${w.current_job_id ? `<strong style="color: var(--wa-blue-check);">Job #${w.current_job_id}</strong>` : 'Idle'}</span>
          </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--wa-border); padding-top: 10px;">
          <button class="btn btn-secondary btn-sm" onclick="toggleWorkerActive(${w.id})">${w.is_active ? 'Disable' : 'Enable'}</button>
          <button class="btn btn-danger btn-sm" onclick="deleteWorker(${w.id})">Delete</button>
        </div>
      </div>
    `;
  }).join('');
}

// --- Action Handlers ---

// Create & Launch Campaign
async function handleCreateCampaign(launchNow = true) {
  const name = $('#campNameInput')?.value.trim();
  const template = $('#campTemplateInput')?.value.trim();

  if (!name || !template) {
    showToast('Please enter both Campaign Name and Message Template', 'warning');
    return;
  }

  const res = await fetchAPI('create_campaign', 'POST', {
    name,
    message_template: template,
    launch_now: launchNow,
  });

  if (res.success) {
    showToast(res.message, 'success');
    $('#campNameInput').value = '';
    refreshAllData();
  } else {
    showToast(res.message, 'error');
  }
}

async function changeCampaignStatus(id, status) {
  const res = await fetchAPI('update_campaign_status', 'POST', { id, status });
  if (res.success) {
    showToast(res.message, 'success');
    refreshAllData();
  } else {
    showToast(res.message, 'error');
  }
}

// Direct Single Send
async function handleDirectSend() {
  const name = $('#directNameInput')?.value.trim() || 'Recipient';
  const phone = $('#directPhoneInput')?.value.trim();
  const message = $('#directMessageInput')?.value.trim();

  if (!phone || !message) {
    showToast('Please enter both recipient phone number and message', 'warning');
    return;
  }

  const res = await fetchAPI('direct_send', 'POST', { name, phone, message });
  if (res.success) {
    showToast('Message queued into dispatch list!', 'success');
    $('#directMessageInput').value = '';
    refreshAllData();
    switchTab('jobs');
  } else {
    showToast(res.message, 'error');
  }
}

function openDirectWithContact(phone, name) {
  switchTab('direct');
  $('#directPhoneInput').value = phone;
  $('#directNameInput').value = name;
  updateDirectPreview();
}

// Save Contact Modal
function openAddContact() {
  $('#contactModalTitle').textContent = 'Add WhatsApp Contact';
  $('#contactIdInput').value = '';
  $('#contactNameInput').value = '';
  $('#contactPhoneInput').value = '';
  $('#contactCompanyInput').value = '';
  openModal('contactModal');
}

function openEditContact(id, name, phone, company) {
  $('#contactModalTitle').textContent = 'Edit Contact';
  $('#contactIdInput').value = id;
  $('#contactNameInput').value = name;
  $('#contactPhoneInput').value = phone;
  $('#contactCompanyInput').value = company;
  openModal('contactModal');
}

async function handleSaveContact() {
  const id = $('#contactIdInput').value;
  const name = $('#contactNameInput').value.trim();
  const phone = $('#contactPhoneInput').value.trim();
  const company = $('#contactCompanyInput').value.trim();

  if (!name || !phone) {
    showToast('Name and phone are required', 'warning');
    return;
  }

  const res = await fetchAPI('save_contact', 'POST', { id: id ? parseInt(id, 10) : null, name, phone, company });
  if (res.success) {
    showToast('Contact saved successfully', 'success');
    closeModal('contactModal');
    refreshAllData();
  } else {
    showToast(res.message, 'error');
  }
}

async function deleteContact(id) {
  if (!confirm('Are you sure you want to delete this contact?')) return;
  const res = await fetchAPI('delete_contact', 'POST', { id });
  if (res.success) {
    showToast('Contact deleted', 'success');
    refreshAllData();
  } else {
    showToast(res.message, 'error');
  }
}

// Batch Import Contacts Modal
function openBatchImport() {
  $('#batchImportInput').value = '';
  openModal('batchImportModal');
}

async function handleBatchImport() {
  const raw = $('#batchImportInput').value.trim();
  if (!raw) {
    showToast('Please paste contact lines to import', 'warning');
    return;
  }

  const res = await fetchAPI('import_contacts', 'POST', { raw });
  if (res.success) {
    showToast(res.message, 'success');
    closeModal('batchImportModal');
    refreshAllData();
  } else {
    showToast(res.message, 'error');
  }
}

// Jobs Queue Actions
async function retryJob(id) {
  const res = await fetchAPI('retry_job', 'POST', { id });
  if (res.success) {
    showToast('Job requeued', 'success');
    refreshAllData();
  } else {
    showToast(res.message, 'error');
  }
}

async function cancelJob(id) {
  const res = await fetchAPI('cancel_job', 'POST', { id });
  if (res.success) {
    showToast('Job cancelled', 'success');
    refreshAllData();
  } else {
    showToast(res.message, 'error');
  }
}

function filterJobs(status) {
  state.selectedJobFilter = status;
  $$('.job-filter-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.status === status);
  });
  renderJobs();
}

// Simulate Worker Processing (Demonstration tool)
async function simulateWorkerProcess() {
  showToast('Simulating worker claiming and delivering 1 job...', 'info');
  const res = await fetchAPI('simulate_worker', 'POST');
  if (res.success) {
    showToast(res.message, 'success');
    refreshAllData();
  } else {
    showToast(res.message, 'warning');
  }
}

// Seed Demo Samples
async function seedSampleData() {
  if (!confirm('This will load sample contacts, a broadcast campaign, and message jobs for demonstration. Proceed?')) return;
  const res = await fetchAPI('seed_samples');
  if (res.success) {
    showToast(res.message, 'success');
    refreshAllData();
  } else {
    showToast(res.message, 'error');
  }
}

// Workers Management
function openAddWorker() {
  $('#workerNameInput').value = 'Windows-Worker-' + Math.floor(100 + Math.random() * 900);
  openModal('addWorkerModal');
}

async function handleCreateWorker() {
  const name = $('#workerNameInput').value.trim();
  if (!name) {
    showToast('Worker name is required', 'warning');
    return;
  }

  const res = await fetchAPI('create_worker', 'POST', { name });
  if (res.success) {
    closeModal('addWorkerModal');
    // Show token success modal
    $('#workerCreatedName').textContent = res.data.name;
    $('#workerTokenDisplay').textContent = res.data.token;
    $('#workerConfigSample').textContent = res.data.config_sample;
    openModal('workerTokenModal');
    refreshAllData();
  } else {
    showToast(res.message, 'error');
  }
}

async function toggleWorkerActive(id) {
  const res = await fetchAPI('toggle_worker', 'POST', { id });
  if (res.success) {
    showToast('Worker status toggled', 'success');
    refreshAllData();
  } else {
    showToast(res.message, 'error');
  }
}

async function deleteWorker(id) {
  if (!confirm('Are you sure you want to remove this worker node?')) return;
  const res = await fetchAPI('delete_worker', 'POST', { id });
  if (res.success) {
    showToast('Worker removed', 'success');
    refreshAllData();
  } else {
    showToast(res.message, 'error');
  }
}

function copyToClipboard(elementId) {
  const el = document.getElementById(elementId);
  if (!el) return;
  navigator.clipboard.writeText(el.textContent).then(() => {
    showToast('Copied to clipboard!', 'success');
  }).catch(() => {
    showToast('Failed to copy', 'error');
  });
}

// --- Modals Management ---
function setupModals() {
  $$('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        overlay.classList.remove('active');
      }
    });
  });
}

function openModal(id) {
  const modal = $(`#${id}`);
  if (modal) modal.classList.add('active');
}

function closeModal(id) {
  const modal = $(`#${id}`);
  if (modal) modal.classList.remove('active');
}

// --- Toast Alerts ---
function showToast(message, type = 'info') {
  let container = $('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = message;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

// --- Utilities ---
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
