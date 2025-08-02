// resources/js/po-approved.js
// Enhanced JavaScript Components สำหรับ PO Approved System

/**
 * ========== PO Approved Page Manager ==========
 */
class PoApprovedManager {
    constructor() {
        this.currentPage = 1;
        this.currentFilters = {};
        this.isLoading = false;
        this.autoRefreshInterval = null;
        this.lastRefreshTime = null;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadInitialData();
        this.startAutoRefresh();
        this.setupExportHandlers();
        this.setupFilterHandlers();
    }

    /**
     * Bind Event Listeners
     */
    bindEvents() {
        // Search form submission
        const searchForm = document.querySelector('#poApprovedSearchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }

        // Quick filter buttons
        document.querySelectorAll('.quick-filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const filterType = e.target.dataset.filter;
                const filterValue = e.target.dataset.value;
                this.applyQuickFilter(filterType, filterValue);
            });
        });

        // Pagination links
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('page-link') && e.target.dataset.page) {
                e.preventDefault();
                this.loadPage(parseInt(e.target.dataset.page));
            }
        });

        // Refresh button
        const refreshBtn = document.querySelector('#refreshDataBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshData();
            });
        }

        // Per page selector
        const perPageSelector = document.querySelector('#perPageSelector');
        if (perPageSelector) {
            perPageSelector.addEventListener('change', (e) => {
                this.changePerPage(parseInt(e.target.value));
            });
        }
    }

    /**
     * Load Initial Data
     */
    async loadInitialData() {
        this.showLoading();
        try {
            await this.loadApprovedPOs();
            await this.loadStats();
        } catch (error) {
            this.showError('Error loading initial data: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Load Approved POs
     */
    async loadApprovedPOs(page = 1, filters = {}) {
        try {
            this.currentPage = page;
            this.currentFilters = { ...this.currentFilters, ...filters };

            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.getPerPage(),
                ...this.currentFilters
            });

            const response = await fetch(`/api/po-approved/list?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            
            if (data.success) {
                this.renderApprovedPOs(data.data);
                this.renderPagination(data.pagination);
                this.updateUrl();
                this.lastRefreshTime = new Date();
                this.updateRefreshIndicator();
            } else {
                throw new Error(data.error || 'Unknown error occurred');
            }

        } catch (error) {
            console.error('Error loading approved POs:', error);
            this.showError('Error loading data: ' + error.message);
        }
    }

    /**
     * Load Statistics
     */
    async loadStats() {
        try {
            const params = new URLSearchParams(this.currentFilters);
            const response = await fetch(`/api/po-approved/stats?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                this.renderStats(data.general);
                this.renderMonthlyStats(data.monthly);
                this.renderTopCustomers(data.top_customers);
            }

        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    /**
     * Perform Search
     */
    performSearch() {
        const formData = new FormData(document.querySelector('#poApprovedSearchForm'));
        const filters = {};
        
        for (let [key, value] of formData.entries()) {
            if (value.trim()) {
                filters[key] = value.trim();
            }
        }

        this.loadApprovedPOs(1, filters);
    }

    /**
     * Apply Quick Filter
     */
    applyQuickFilter(filterType, filterValue) {
        const filters = { ...this.currentFilters };
        filters[filterType] = filterValue;
        
        this.loadApprovedPOs(1, filters);
    }

    /**
     * Load Specific Page
     */
    loadPage(page) {
        if (page !== this.currentPage && !this.isLoading) {
            this.loadApprovedPOs(page, this.currentFilters);
        }
    }

    /**
     * Change Per Page
     */
    changePerPage(perPage) {
        localStorage.setItem('po_approved_per_page', perPage);
        this.loadApprovedPOs(1, this.currentFilters);
    }

    /**
     * Get Per Page from localStorage or default
     */
    getPerPage() {
        return localStorage.getItem('po_approved_per_page') || 20;
    }

    /**
     * Refresh Data
     */
    async refreshData() {
        this.showRefreshIndicator();
        await this.loadApprovedPOs(this.currentPage, this.currentFilters);
        await this.loadStats();
        this.hideRefreshIndicator();
    }

    /**
     * Start Auto Refresh
     */
    startAutoRefresh() {
        // Auto refresh ทุก 5 นาที
        this.autoRefreshInterval = setInterval(() => {
            if (!this.isLoading && document.visibilityState === 'visible') {
                this.refreshData();
            }
        }, 300000); // 5 minutes

        // หยุด auto refresh เมื่อ page ถูก hidden
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                clearInterval(this.autoRefreshInterval);
            } else if (document.visibilityState === 'visible') {
                this.startAutoRefresh();
                // Refresh data ทันทีเมื่อกลับมา
                if (this.lastRefreshTime && (new Date() - this.lastRefreshTime) > 60000) {
                    this.refreshData();
                }
            }
        });
    }

    /**
     * Render Approved POs Table
     */
    renderApprovedPOs(pos) {
        const tbody = document.querySelector('#approvedPOsTableBody');
        if (!tbody) return;

        if (!pos || pos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No approved POs found matching your criteria</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = pos.map(po => `
            <tr data-po-docno="${po.po_docno}">
                <td>
                    <strong class="text-primary">${po.po_docno}</strong>
                </td>
                <td>
                    <strong>${po.customer_name || 'N/A'}</strong>
                </td>
                <td class="text-center">
                    <span class="badge bg-info">${po.item_count || 0} items</span>
                </td>
                <td class="text-end">
                    <strong>${this.formatNumber(po.po_amount)}</strong>
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center align-items-center">
                        ${this.renderApprovalLevelIndicators(po.max_approval_level)}
                    </div>
                    <small class="text-muted">Level ${po.max_approval_level}/3</small>
                </td>
                <td class="text-center">
                    <span class="badge bg-${po.status_class}">
                        ${po.status_label}
                    </span>
                </td>
                <td class="text-center">
                    <div class="progress" style="height: 10px; min-width: 80px;">
                        <div class="progress-bar bg-${po.status_class}" 
                             style="width: ${po.progress_percentage}%"
                             title="${po.progress_percentage.toFixed(1)}%">
                        </div>
                    </div>
                    <small class="text-muted">${Math.round(po.progress_percentage)}%</small>
                </td>
                <td>
                    ${po.formatted_last_approval || 'N/A'}
                    ${po.last_approval_human ? `<br><small class="text-muted">${po.last_approval_human}</small>` : ''}
                </td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="viewPODetail('${po.po_docno}')" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <a href="/po/${po.po_docno}" class="btn btn-outline-success btn-sm" title="Full View">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                        <a href="/po/${po.po_docno}/print" target="_blank" class="btn btn-outline-info btn-sm" title="Print">
                            <i class="fas fa-print"></i>
                        </a>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    /**
     * Render Approval Level Indicators
     */
    renderApprovalLevelIndicators(maxLevel) {
        let indicators = '';
        for (let level = 1; level <= 3; level++) {
            const approved = level <= maxLevel;
            const icon = approved ? 'fas fa-check-circle text-success' : 'fas fa-circle text-muted';
            indicators += `<i class="${icon} mx-1" title="Level ${level} ${approved ? 'Approved' : 'Pending'}"></i>`;
        }
        return indicators;
    }

    /**
     * Render Pagination
     */
    renderPagination(pagination) {
        const paginationContainer = document.querySelector('#paginationContainer');
        if (!paginationContainer || !pagination) return;

        if (pagination.total_pages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        let paginationHTML = '<nav aria-label="PO Approved Pagination"><ul class="pagination justify-content-center">';

        // Previous button
        if (pagination.has_previous) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="1">First</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.previous_page}">Previous</a>
                </li>
            `;
        } else {
            paginationHTML += `
                <li class="page-item disabled">
                    <span class="page-link">First</span>
                </li>
                <li class="page-item disabled">
                    <span class="page-link">Previous</span>
                </li>
            `;
        }

        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            if (i === pagination.current_page) {
                paginationHTML += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                paginationHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            }
        }

        // Next button
        if (pagination.has_more) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.next_page}">Next</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.total_pages}">Last</a>
                </li>
            `;
        } else {
            paginationHTML += `
                <li class="page-item disabled">
                    <span class="page-link">Next</span>
                </li>
                <li class="page-item disabled">
                    <span class="page-link">Last</span>
                </li>
            `;
        }

        paginationHTML += '</ul></nav>';
        paginationContainer.innerHTML = paginationHTML;

        // Update pagination info
        const paginationInfo = document.querySelector('#paginationInfo');
        if (paginationInfo) {
            paginationInfo.textContent = `Showing ${((pagination.current_page - 1) * pagination.per_page) + 1} to ${Math.min(pagination.current_page * pagination.per_page, pagination.total)} of ${pagination.total} entries`;
        }
    }

    /**
     * Render Statistics
     */
    renderStats(stats) {
        if (!stats) return;

        // Update summary cards
        this.updateElementText('#totalPOsCard', this.formatNumber(stats.total_pos));
        this.updateElementText('#totalAmountCard', this.formatNumber(stats.total_amount, true));
        this.updateElementText('#avgAmountCard', this.formatNumber(stats.avg_amount, true));
        this.updateElementText('#uniqueCustomersCard', this.formatNumber(stats.unique_customers));
    }

    /**
     * Setup Export Handlers
     */
    setupExportHandlers() {
        // CSV Export
        const csvExportBtn = document.querySelector('#exportCSVBtn');
        if (csvExportBtn) {
            csvExportBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportData('csv');
            });
        }

        // Excel Export
        const excelExportBtn = document.querySelector('#exportExcelBtn');
        if (excelExportBtn) {
            excelExportBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportData('excel');
            });
        }
    }

    /**
     * Export Data
     */
    async exportData(format) {
        try {
            this.showExportIndicator(format);

            const params = new URLSearchParams(this.currentFilters);
            const url = `/api/po-approved/export/${format}?${params}`;

            // สร้าง invisible link เพื่อ download
            const link = document.createElement('a');
            link.href = url;
            link.download = `approved_pos_${new Date().toISOString().split('T')[0]}.${format}`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            this.hideExportIndicator(format);
            this.showSuccess(`${format.toUpperCase()} export completed successfully!`);

        } catch (error) {
            this.hideExportIndicator(format);
            this.showError('Export failed: ' + error.message);
        }
    }

    /**
     * Setup Filter Handlers
     */
    setupFilterHandlers() {
        // Clear filters button
        const clearFiltersBtn = document.querySelector('#clearFiltersBtn');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                this.clearAllFilters();
            });
        }

        // Save filters to localStorage
        const saveFiltersBtn = document.querySelector('#saveFiltersBtn');
        if (saveFiltersBtn) {
            saveFiltersBtn.addEventListener('click', () => {
                this.saveCurrentFilters();
            });
        }

        // Load saved filters
        this.loadSavedFilters();
    }

    /**
     * Clear All Filters
     */
    clearAllFilters() {
        // Clear form inputs
        const form = document.querySelector('#poApprovedSearchForm');
        if (form) {
            form.reset();
        }

        // Clear current filters and reload
        this.currentFilters = {};
        this.loadApprovedPOs(1, {});
    }

    /**
     * Save Current Filters
     */
    saveCurrentFilters() {
        localStorage.setItem('po_approved_filters', JSON.stringify(this.currentFilters));
        this.showSuccess('Filters saved successfully!');
    }

    /**
     * Load Saved Filters
     */
    loadSavedFilters() {
        try {
            const savedFilters = localStorage.getItem('po_approved_filters');
            if (savedFilters) {
                const filters = JSON.parse(savedFilters);
                
                // ใส่ค่าในฟอร์ม
                Object.keys(filters).forEach(key => {
                    const input = document.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = filters[key];
                    }
                });
                
                // Apply filters
                this.currentFilters = filters;
            }
        } catch (error) {
            console.error('Error loading saved filters:', error);
        }
    }

    /**
     * Utility Methods
     */
    formatNumber(number, isCurrency = false) {
        if (!number) return '0';
        const formatted = parseFloat(number).toLocaleString('en-US', {
            minimumFractionDigits: isCurrency ? 2 : 0,
            maximumFractionDigits: isCurrency ? 2 : 0
        });
        return isCurrency ? `฿${formatted}` : formatted;
    }

    updateElementText(selector, text) {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = text;
        }
    }

    updateUrl() {
        const params = new URLSearchParams({
            page: this.currentPage,
            ...this.currentFilters
        });
        
        const newUrl = `${window.location.pathname}?${params}`;
        window.history.replaceState({}, '', newUrl);
    }

    showLoading() {
        this.isLoading = true;
        document.body.style.cursor = 'wait';
        
        const loadingIndicator = document.querySelector('#loadingIndicator');
        if (loadingIndicator) {
            loadingIndicator.style.display = 'block';
        }
    }

    hideLoading() {
        this.isLoading = false;
        document.body.style.cursor = 'default';
        
        const loadingIndicator = document.querySelector('#loadingIndicator');
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
        }
    }

    showRefreshIndicator() {
        const refreshBtn = document.querySelector('#refreshDataBtn');
        if (refreshBtn) {
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            refreshBtn.disabled = true;
        }
    }

    hideRefreshIndicator() {
        const refreshBtn = document.querySelector('#refreshDataBtn');
        if (refreshBtn) {
            refreshBtn.innerHTML = '<i class="fas fa-refresh"></i> Refresh';
            refreshBtn.disabled = false;
        }
    }

    showExportIndicator(format) {
        const btn = document.querySelector(`#export${format.toUpperCase()}Btn`);
        if (btn) {
            btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Exporting...`;
            btn.disabled = true;
        }
    }

    hideExportIndicator(format) {
        const btn = document.querySelector(`#export${format.toUpperCase()}Btn`);
        if (btn) {
            btn.innerHTML = `<i class="fas fa-file-${format}"></i> Export ${format.toUpperCase()}`;
            btn.disabled = false;
        }
    }

    updateRefreshIndicator() {
        const indicator = document.querySelector('#lastRefreshTime');
        if (indicator && this.lastRefreshTime) {
            indicator.textContent = `Last updated: ${this.lastRefreshTime.toLocaleTimeString()}`;
        }
    }

    showSuccess(message) {
        this.showAlert(message, 'success');
    }

    showError(message) {
        this.showAlert(message, 'danger');
    }

    showAlert(message, type) {
        // สร้าง alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alertDiv);

        // Auto hide after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

/**
 * ========== PO Detail Modal Manager ==========
 */
async function viewPODetail(docNo) {
    try {
        const response = await fetch(`/api/po-approved/detail/${docNo}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            showPODetailModal(data.data);
        } else {
            alert('Error: ' + data.error);
        }

    } catch (error) {
        console.error('Error loading PO detail:', error);
        alert('Error loading PO details');
    }
}

function showPODetailModal(poData) {
    // สร้าง Modal HTML
    const modalHTML = `
        <div class="modal fade" id="poDetailModal" tabindex="-1" aria-labelledby="poDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="poDetailModalLabel">
                            <i class="fas fa-file-invoice"></i> PO Details: ${poData.po_docno}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Customer:</strong> ${poData.customer_name || 'N/A'}
                            </div>
                            <div class="col-md-6">
                                <strong>Items:</strong> ${poData.item_count || 0} items
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Amount:</strong> ฿${parseFloat(poData.po_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}
                            </div>
                            <div class="col-md-6">
                                <strong>Max Level:</strong> Level ${poData.max_approval_level}/3
                            </div>
                        </div>
                        
                        <h6><i class="fas fa-history"></i> Approval History</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Level</th>
                                        <th>Approver</th>
                                        <th>Date</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${poData.approvals.map(approval => `
                                        <tr>
                                            <td><span class="badge bg-info">Level ${approval.approval_level}</span></td>
                                            <td>
                                                <strong>${approval.approver_name}</strong><br>
                                                <small class="text-muted">(${approval.approver_username})</small>
                                            </td>
                                            <td>
                                                ${approval.formatted_date}<br>
                                                <small class="text-muted">${approval.human_date}</small>
                                            </td>
                                            <td>${approval.approval_note || '-'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="/po/${poData.po_docno}" class="btn btn-primary">
                            <i class="fas fa-external-link-alt"></i> View Full Details
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // ลบ Modal เก่า (ถ้ามี)
    const existingModal = document.querySelector('#poDetailModal');
    if (existingModal) {
        existingModal.remove();
    }

    // เพิ่ม Modal ใหม่
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // แสดง Modal
    const modal = new bootstrap.Modal(document.querySelector('#poDetailModal'));
    modal.show();

    // ลบ Modal เมื่อปิด
    document.querySelector('#poDetailModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

/**
 * ========== Initialize ==========
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize PO Approved Manager หากอยู่ในหน้า PO Approved
    if (window.location.pathname.includes('po-approved')) {
        window.poApprovedManager = new PoApprovedManager();
    }
});

// Export สำหรับใช้ใน global scope
window.viewPODetail = viewPODetail;