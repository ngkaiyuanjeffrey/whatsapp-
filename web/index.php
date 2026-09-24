<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
?><!doctype html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>WhatsApp Bot Control Panel</title>
  <link rel="stylesheet" href="assets/css/whatsapp.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2325D366'><path d='M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.06-2.148-.544-1.862-.772-3.056-2.678-3.149-2.802-.093-.123-.755-1.004-.755-1.914 0-.911.477-1.357.646-1.54.17-.184.37-.23.493-.23.123 0 .247.001.354.006.113.006.262-.043.411.314.154.37.525 1.277.571 1.37.046.092.077.2.015.323-.062.123-.093.2-.185.308-.092.107-.195.24-.278.323-.093.093-.19.194-.082.38.108.185.48 0.793 1.03 1.282.708.631 1.305.826 1.49.919.185.092.293.077.401-.046.108-.123.462-.538.585-.723.123-.185.247-.154.416-.092.169.061 1.077.508 1.262.6.185.093.308.139.354.216.046.077.046.446-.098.851z'/></svg>">
</head>
<body>

<div class="app-container">
  
  <!-- Left Navigation Rail & Sidebar -->
  <aside class="sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
      <div class="brand-section">
        <div class="brand-logo" title="WhatsApp Bot Engine">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.06-2.148-.544-1.862-.772-3.056-2.678-3.149-2.802-.093-.123-.755-1.004-.755-1.914 0-.911.477-1.357.646-1.54.17-.184.37-.23.493-.23.123 0 .247.001.354.006.113.006.262-.043.411.314.154.37.525 1.277.571 1.37.046.092.077.2.015.323-.062.123-.093.2-.185.308-.092.107-.195.24-.278.323-.093.093-.19.194-.082.38.108.185.48.793 1.03 1.282.708.631 1.305.826 1.49.919.185.092.293.077.401-.046.108-.123.462-.538.585-.723.123-.185.247-.154.416-.092.169.061 1.077.508 1.262.6.185.093.308.139.354.216.046.077.046.446-.098.851z"/>
          </svg>
        </div>
        <div>
          <div class="brand-title">WhatsApp Bot Panel</div>
          <div class="brand-subtitle">
            <span id="globalWorkerDot" class="status-indicator-dot pulse"></span>
            <span id="globalWorkerText">Checking Workers...</span>
          </div>
        </div>
      </div>

      <div class="header-actions">
        <!-- Quick Seed Demo -->
        <button class="icon-btn" title="Seed Demo Sample Data" onclick="seedSampleData()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
        </button>
        <!-- Refresh Button -->
        <button id="btnRefresh" class="icon-btn" title="Refresh All Data">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
        </button>
        <!-- Theme Toggle -->
        <button id="themeToggleBtn" class="icon-btn" title="Toggle Dark/Light Mode">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        </button>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="nav-tabs">
      <button class="tab-btn active" data-tab="dashboard">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        <span>Dashboard</span>
      </button>
      <button class="tab-btn" data-tab="campaigns">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
        <span>Campaigns</span>
      </button>
      <button class="tab-btn" data-tab="direct">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        <span>Direct Send</span>
      </button>
      <button class="tab-btn" data-tab="contacts">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span>Contacts</span>
      </button>
      <button class="tab-btn" data-tab="jobs">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <span>Queue</span>
        <span id="badgeJobsCount" class="tab-badge" style="display: none;">0</span>
      </button>
      <button class="tab-btn" data-tab="workers">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        <span>Workers</span>
      </button>
    </nav>

    <!-- Search Box -->
    <div class="search-container">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="sidebarSearchInput" placeholder="Search contacts, messages or jobs...">
      </div>
    </div>

    <!-- Sidebar Recent Live Feed -->
    <div class="sidebar-list">
      <div style="padding: 10px 16px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--wa-text-secondary); display: flex; justify-content: space-between;">
        <span>Recent Activity Stream</span>
        <span style="color: var(--wa-teal);">Live</span>
      </div>
      <div id="recentJobsFeed">
        <!-- Injected dynamically via JS -->
      </div>
    </div>
  </aside>

  <!-- Right Main Stage -->
  <main class="main-stage">
    
    <!-- Stage Header -->
    <header class="stage-header">
      <div class="stage-title-wrap">
        <div>
          <h1 id="stageTitle" class="stage-title">Dashboard Overview</h1>
          <p id="stageDesc" class="stage-subtitle">Real-time WhatsApp bot dispatch & queue statistics</p>
        </div>
      </div>
      
      <div class="stage-actions">
        <button class="btn btn-secondary btn-sm" onclick="simulateWorkerProcess()" title="Simulate 1 job processed by worker">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Simulate Worker Send
        </button>
        <button class="btn btn-primary btn-sm" onclick="switchTab('direct')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Quick Send
        </button>
      </div>
    </header>

    <!-- Stage Content Panes -->
    <div class="stage-content doodle-bg">
      
      <!-- VIEW 1: DASHBOARD -->
      <section id="view-dashboard" class="view-pane" style="display: block;">
        <!-- Metrics 4-Grid -->
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-icon teal">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            </div>
            <div>
              <div id="metricOnlineWorkers" class="metric-val">0 / 0</div>
              <div class="metric-label">Online Workers (Playwright)</div>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon amber">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
              <div id="metricPendingJobs" class="metric-val">0</div>
              <div class="metric-label">Pending Messages in Queue</div>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon blue">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div>
              <div id="metricSentCount" class="metric-val">0</div>
              <div class="metric-label">Successfully Delivered</div>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon teal">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
              <div id="metricContactsCount" class="metric-val">0</div>
              <div class="metric-label">Audience Contacts</div>
            </div>
          </div>
        </div>

        <!-- Queue Status Distribution Card -->
        <div class="editor-card" style="margin-bottom: 24px;">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="font-weight: 600; font-size: 15px; color: var(--wa-text-primary);">Real-Time Dispatch Queue Pipeline</div>
            <button class="btn btn-secondary btn-sm" onclick="switchTab('jobs')">View Full Queue</button>
          </div>

          <!-- Color segmented progress bar -->
          <div style="display: flex; height: 12px; background-color: var(--wa-border); border-radius: 6px; overflow: hidden; margin-top: 8px;">
            <div id="queueSentBar" style="background-color: var(--wa-teal); width: 0%; transition: width 0.3s;" title="Sent"></div>
            <div id="queueProcBar" style="background-color: var(--wa-blue-check); width: 0%; transition: width 0.3s;" title="Processing"></div>
            <div id="queuePendingBar" style="background-color: var(--wa-warning); width: 0%; transition: width 0.3s;" title="Pending"></div>
            <div id="queueFailBar" style="background-color: var(--wa-danger); width: 0%; transition: width 0.3s;" title="Failed"></div>
          </div>

          <div style="display: flex; gap: 20px; font-size: 12px; flex-wrap: wrap; margin-top: 4px;">
            <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: var(--wa-teal);"></span><span id="lblSentCount">0 Sent</span></div>
            <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: var(--wa-blue-check);"></span><span id="lblProcCount">0 In Progress</span></div>
            <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: var(--wa-warning);"></span><span id="lblPendingCount">0 Pending</span></div>
            <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: var(--wa-danger);"></span><span id="lblFailCount">0 Failed</span></div>
          </div>
        </div>

        <!-- System Architecture & Security Notice -->
        <div class="editor-card" style="border-left: 4px solid var(--wa-teal);">
          <div style="display: flex; gap: 14px; align-items: flex-start;">
            <div style="color: var(--wa-teal); margin-top: 2px;">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <div>
              <div style="font-weight: 600; color: var(--wa-text-primary); margin-bottom: 4px;">Consent-Based Messaging Architecture</div>
              <p style="font-size: 13px; color: var(--wa-text-secondary); line-height: 1.5;">
                Windows Python Worker connects via secure Bearer token authentication to claim pending messages from PHP. Worker controls its persistent Playwright browser profile without exposing direct MySQL credentials.
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- VIEW 2: CAMPAIGNS (BROADCAST & PHONE PREVIEW) -->
      <section id="view-campaigns" class="view-pane" style="display: none;">
        <div class="preview-split-layout">
          
          <!-- Campaign Form -->
          <div class="editor-card">
            <h2 style="font-size: 16px; font-weight: 600; color: var(--wa-text-primary);">Create New Broadcast Campaign</h2>
            
            <div class="form-group">
              <label class="form-label">Campaign Name</label>
              <input type="text" id="campNameInput" class="form-control" placeholder="e.g. VIP Customer Seasonal Notification">
            </div>

            <div class="form-group">
              <div class="form-label">
                <span>Message Template</span>
                <span style="font-size: 11px; color: var(--wa-teal);">WhatsApp Markdown Supported</span>
              </div>
              <textarea id="campTemplateInput" class="form-control" rows="6" placeholder="Hello *{name}* from _{company}_!&#10;&#10;Here is an exclusive update for you..."></textarea>
            </div>

            <!-- Variable Tags -->
            <div class="form-group">
              <span class="form-label">Click to Insert Dynamic Variables:</span>
              <div class="template-vars">
                <span class="var-tag" data-var="{name}">+ {name} (Recipient Name)</span>
                <span class="var-tag" data-var="{company}">+ {company} (Company)</span>
                <span class="var-tag" data-var="{phone}">+ {phone} (Phone Number)</span>
              </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 10px;">
              <button class="btn btn-primary" onclick="handleCreateCampaign(true)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Launch & Queue Now
              </button>
              <button class="btn btn-secondary" onclick="handleCreateCampaign(false)">Save as Draft</button>
            </div>
          </div>

          <!-- WhatsApp Live Phone Mockup Previewer -->
          <div class="phone-mockup">
            <div class="phone-screen">
              <!-- Phone Status Header -->
              <div class="phone-top-bar">
                <div class="phone-avatar">C</div>
                <div class="phone-contact-info">
                  <div class="phone-contact-name">Alex Tan (Preview)</div>
                  <div class="phone-contact-status">online</div>
                </div>
                <div style="color: var(--wa-text-secondary); display: flex; gap: 8px;">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                </div>
              </div>

              <!-- Phone Chat Body -->
              <div class="phone-chat-body">
                <div class="chat-date-pill">Today</div>
                
                <!-- Outgoing WhatsApp Bubble with real-time text -->
                <div class="chat-bubble out">
                  <div id="campaignPreviewBubble">Type your WhatsApp message to see live preview...</div>
                  <div class="bubble-meta">
                    <span id="previewTimestamp">16:40</span>
                    <span class="check-double-blue">✓✓</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Existing Campaigns History Table -->
        <div class="table-card" style="margin-top: 24px;">
          <div style="padding: 16px 20px; font-weight: 600; font-size: 15px; border-bottom: 1px solid var(--wa-border);">
            Campaign History & Progress
          </div>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Campaign Name & Template</th>
                  <th>Status</th>
                  <th>Delivery Progress</th>
                  <th>Created Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="campaignsListTable">
                <!-- Dynamically populated -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- VIEW 3: DIRECT SEND (QUICK TEST) -->
      <section id="view-direct" class="view-pane" style="display: none;">
        <div class="preview-split-layout">
          
          <div class="editor-card">
            <h2 style="font-size: 16px; font-weight: 600; color: var(--wa-text-primary);">Send Immediate WhatsApp Message</h2>
            <p style="font-size: 12px; color: var(--wa-text-secondary);">Directly dispatches a single personalized message to a specific number.</p>

            <div class="form-group">
              <label class="form-label">Recipient Phone Number (with Country Code)</label>
              <input type="text" id="directPhoneInput" class="form-control" placeholder="+60123456789" value="+60123456789">
            </div>

            <div class="form-group">
              <label class="form-label">Recipient Name (Optional)</label>
              <input type="text" id="directNameInput" class="form-control" placeholder="e.g. Jason" value="Alex Tan">
            </div>

            <div class="form-group">
              <label class="form-label">Message Content</label>
              <textarea id="directMessageInput" class="form-control" rows="6" placeholder="Hi! This is a test message from WhatsApp Bot Control Panel. *Bold* and _Italics_ work."></textarea>
            </div>

            <div style="margin-top: 10px;">
              <button class="btn btn-primary" onclick="handleDirectSend()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Send Message via Queue
              </button>
            </div>
          </div>

          <!-- Phone Mockup for Direct Send -->
          <div class="phone-mockup">
            <div class="phone-screen">
              <div class="phone-top-bar">
                <div class="phone-avatar">C</div>
                <div class="phone-contact-info">
                  <div id="directMockupName" class="phone-contact-name">Alex Tan</div>
                  <div class="phone-contact-status">online</div>
                </div>
              </div>

              <div class="phone-chat-body">
                <div class="chat-date-pill">Today</div>
                <div class="chat-bubble out">
                  <div id="directPreviewBubble">Type your message on the left to see it formatted here!</div>
                  <div class="bubble-meta">
                    <span>16:42</span>
                    <span class="check-double-blue">✓✓</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </section>

      <!-- VIEW 4: CONTACTS CRM -->
      <section id="view-contacts" class="view-pane" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
          <div>
            <span id="contactsTotalBadge" class="badge badge-sent" style="font-size: 13px; padding: 4px 12px;">0 Contacts</span>
          </div>
          <div style="display: flex; gap: 10px;">
            <button class="btn btn-secondary btn-sm" onclick="openBatchImport()">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Batch Import
            </button>
            <button class="btn btn-primary btn-sm" onclick="openAddContact()">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add Contact
            </button>
          </div>
        </div>

        <div class="table-card">
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Contact Name</th>
                  <th>WhatsApp Number</th>
                  <th>Company / Tag</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="contactsListTable">
                <!-- Dynamically populated -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- VIEW 5: JOBS QUEUE -->
      <section id="view-jobs" class="view-pane" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
          <!-- Filter Buttons -->
          <div style="display: flex; gap: 8px;">
            <button class="btn btn-secondary btn-sm job-filter-btn active" data-status="all" onclick="filterJobs('all')">All</button>
            <button class="btn btn-secondary btn-sm job-filter-btn" data-status="pending" onclick="filterJobs('pending')">Pending</button>
            <button class="btn btn-secondary btn-sm job-filter-btn" data-status="processing" onclick="filterJobs('processing')">Processing</button>
            <button class="btn btn-secondary btn-sm job-filter-btn" data-status="sent" onclick="filterJobs('sent')">Sent</button>
            <button class="btn btn-secondary btn-sm job-filter-btn" data-status="failed" onclick="filterJobs('failed')">Failed</button>
          </div>

          <div style="display: flex; align-items: center; gap: 10px;">
            <span id="jobsFilterBadge" class="badge badge-sent">0 Jobs</span>
            <button class="btn btn-primary btn-sm" onclick="simulateWorkerProcess()">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
              Simulate 1 Dispatch
            </button>
          </div>
        </div>

        <div class="table-card">
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Job ID</th>
                  <th>Recipient</th>
                  <th>Rendered Message</th>
                  <th>Status</th>
                  <th>Attempts</th>
                  <th>Worker Node</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="jobsListTable">
                <!-- Dynamically populated -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- VIEW 6: WORKERS MONITOR -->
      <section id="view-workers" class="view-pane" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <div>
            <div style="font-weight: 600; font-size: 15px; color: var(--wa-text-primary);">Windows Python Automation Workers</div>
            <div style="font-size: 12px; color: var(--wa-text-secondary);">Workers poll jobs over HTTP Bearer token and control WhatsApp Web via Playwright browser session.</div>
          </div>
          <button class="btn btn-primary btn-sm" onclick="openAddWorker()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Worker Node
          </button>
        </div>

        <!-- Worker Cards Grid -->
        <div id="workersGrid" class="metrics-grid">
          <!-- Dynamically populated -->
        </div>
      </section>

    </div>
  </main>
</div>

<!-- ================= MODALS ================= -->

<!-- Add / Edit Contact Modal -->
<div id="contactModal" class="modal-overlay">
  <div class="modal-card">
    <div class="modal-header">
      <div id="contactModalTitle" class="modal-title">Add WhatsApp Contact</div>
      <button class="icon-btn" onclick="closeModal('contactModal')">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="contactIdInput">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" id="contactNameInput" class="form-control" placeholder="e.g. John Tan">
      </div>
      <div class="form-group">
        <label class="form-label">WhatsApp Number (e.g. +60123456789)</label>
        <input type="text" id="contactPhoneInput" class="form-control" placeholder="+60123456789">
      </div>
      <div class="form-group">
        <label class="form-label">Company / Group Tag (Optional)</label>
        <input type="text" id="contactCompanyInput" class="form-control" placeholder="e.g. Shopee Merchant">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('contactModal')">Cancel</button>
      <button class="btn btn-primary" onclick="handleSaveContact()">Save Contact</button>
    </div>
  </div>
</div>

<!-- Batch Import Modal -->
<div id="batchImportModal" class="modal-overlay">
  <div class="modal-card">
    <div class="modal-header">
      <div class="modal-title">Batch Import Contacts</div>
      <button class="icon-btn" onclick="closeModal('batchImportModal')">&times;</button>
    </div>
    <div class="modal-body">
      <p style="font-size: 12px; color: var(--wa-text-secondary);">
        Paste contact list below. Supported formats: comma, semicolon or tab separated:<br>
        <code>Name, Phone Number, Company</code>
      </p>
      <textarea id="batchImportInput" class="form-control" rows="8" placeholder="David Lee, +60172348899, TechCorp&#10;Grace Lim, +60163334455, FoodsMart"></textarea>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('batchImportModal')">Cancel</button>
      <button class="btn btn-primary" onclick="handleBatchImport()">Start Import</button>
    </div>
  </div>
</div>

<!-- Add Worker Modal -->
<div id="addWorkerModal" class="modal-overlay">
  <div class="modal-card">
    <div class="modal-header">
      <div class="modal-title">Register New Worker Node</div>
      <button class="icon-btn" onclick="closeModal('addWorkerModal')">&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Worker Name / Hostname</label>
        <input type="text" id="workerNameInput" class="form-control" placeholder="e.g. Windows-Worker-01">
      </div>
      <p style="font-size: 12px; color: var(--wa-text-secondary);">
        A unique Bearer Token will be securely generated for this worker. You will receive configuration instructions immediately.
      </p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('addWorkerModal')">Cancel</button>
      <button class="btn btn-primary" onclick="handleCreateWorker()">Generate Token & Register</button>
    </div>
  </div>
</div>

<!-- Worker Token & Config Modal -->
<div id="workerTokenModal" class="modal-overlay">
  <div class="modal-card" style="max-width: 580px;">
    <div class="modal-header">
      <div class="modal-title">Worker Successfully Registered!</div>
      <button class="icon-btn" onclick="closeModal('workerTokenModal')">&times;</button>
    </div>
    <div class="modal-body">
      <div style="font-size: 13px; color: var(--wa-text-primary);">
        Worker: <strong id="workerCreatedName" style="color: var(--wa-teal);"></strong>
      </div>

      <div class="form-group">
        <label class="form-label">Secret Worker Bearer Token (Save now, won't be shown again):</label>
        <div class="code-box">
          <span id="workerTokenDisplay"></span>
          <button class="copy-btn" onclick="copyToClipboard('workerTokenDisplay')">Copy</button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Paste into your <code>worker/config.ini</code>:</label>
        <div class="code-box">
          <span id="workerConfigSample"></span>
          <button class="copy-btn" onclick="copyToClipboard('workerConfigSample')">Copy Config</button>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-primary" onclick="closeModal('workerTokenModal')">Done</button>
    </div>
  </div>
</div>

<!-- App Core Script -->
<script src="assets/js/app.js"></script>
</body>
</html>
