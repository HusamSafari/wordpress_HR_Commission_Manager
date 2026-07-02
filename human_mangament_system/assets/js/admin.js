/**
 * JS Actions for Branch ERP & Sales Management Hub (Odoo/ERPNext Style)
 */
jQuery(document).ready(function($) {

    // ==================================================
    // 2. TOAST NOTIFICATION UTILITY
    // ==================================================
    window.showToast = function(message, type) {
        type = type || 'success';
        var toastId = 'toast-' + Math.random().toString(36).substr(2, 9);
        var toastHTML = '<div class="swvt-hr-toast ' + type + '" id="' + toastId + '">' +
                            '<span>' + message + '</span>' +
                            '<button type="button" class="swvt-hr-toast-close">&times;</button>' +
                        '</div>';
        
        $('#swvt-hr-toast-container').append(toastHTML);

        // Auto remove
        var timer = setTimeout(function() {
            $('#' + toastId).fadeOut(300, function() { $(this).remove(); });
        }, 3500);

        // Click to close
        $('#' + toastId + ' .swvt-hr-toast-close').on('click', function() {
            clearTimeout(timer);
            $(this).parent().fadeOut(200, function() { $(this).remove(); });
        });
    };

    // ==================================================
    // 3. THEME TOGGLER (LIGHT / DARK)
    // ==================================================
    $('#swvt-hr-theme-toggle').on('click', function() {
        var container = $('#swvt-hr-erp-container');
        container.toggleClass('swvt-hr-dark');
        var isDark = container.hasClass('swvt-hr-dark');
        var themeVal = isDark ? 'dark' : 'light';

        $.post(SWVT_HR.ajax, {
            action: 'swvt_hr_toggle_theme',
            nonce: SWVT_HR.nonce,
            theme: themeVal
        }, function(res) {
            if (res.success) {
                showToast(res.data.message, 'info');
            }
        });
    });

    // ==================================================
    // 4. GLOBAL SEARCH MODAL (CMD+K / CTRL+K)
    // ==================================================
    function openSearchModal() {
        $('#swvt-hr-search-modal').addClass('is-open');
        $('#swvt-hr-global-search-input').focus().val('');
        $('#swvt-hr-global-search-results').html('<div class="swvt-hr-search-placeholder">Type at least 2 characters to search...</div>');
    }

    function closeSearchModal() {
        $('#swvt-hr-search-modal').removeClass('is-open');
    }

    $('#swvt-hr-search-bar-trigger').on('click', function() {
        openSearchModal();
    });

    $('#swvt-hr-search-modal-close').on('click', function() {
        closeSearchModal();
    });

    // Close on outer click
    $('#swvt-hr-search-modal').on('click', function(e) {
        if ($(e.target).is('#swvt-hr-search-modal')) {
            closeSearchModal();
        }
    });

    // Keydown bind
    $(document).on('keydown', function(e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if ($('#swvt-hr-search-modal').hasClass('is-open')) {
                closeSearchModal();
            } else {
                openSearchModal();
            }
        }
    });

    // Live Debounced AJAX Search
    var searchTimeout = null;
    $('#swvt-hr-global-search-input').on('input', function() {
        var query = $(this).val().trim();
        clearTimeout(searchTimeout);

        if (query.length < 2) {
            $('#swvt-hr-global-search-results').html('<div class="swvt-hr-search-placeholder">Type at least 2 characters to search...</div>');
            return;
        }

        $('#swvt-hr-global-search-results').html('<div class="swvt-hr-search-placeholder">Searching systems database...</div>');

        searchTimeout = setTimeout(function() {
            $.post(SWVT_HR.ajax, {
                action: 'swvt_hr_global_search',
                nonce: SWVT_HR.nonce,
                query: query
            }, function(res) {
                if (res.success && res.data.length > 0) {
                    var html = '';
                    res.data.forEach(function(item) {
                        html += '<a href="' + item.link + '" class="swvt-hr-search-item">' +
                                    '<span class="swvt-hr-search-item-type">' + item.type + '</span>' +
                                    '<span class="swvt-hr-search-item-title">' + item.title + '</span>' +
                                    '<span class="swvt-hr-search-item-desc">' + item.desc + '</span>' +
                                '</a>';
                    });
                    $('#swvt-hr-global-search-results').html(html);
                } else {
                    $('#swvt-hr-global-search-results').html('<div class="swvt-hr-search-placeholder">No matching records found.</div>');
                }
            });
        }, 300);
    });

    // ==================================================
    // 5. DATA MUTATIONS (AJAX POST WRAPPERS WITH TOASTS)
    // ==================================================

    // Save Branch
    $('#swvt-hr-branch-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).css('opacity', 0.6);
        
        var data = form.serialize() + '&action=swvt_hr_save_branch&nonce=' + SWVT_HR.nonce;
        
        $.post(SWVT_HR.ajax, data, function(res) {
            submitBtn.prop('disabled', false).css('opacity', 1);
            if (res.success) {
                showToast(res.data.message, 'success');
                setTimeout(function() {
                    window.location.href = 'admin.php?page=swvt-hr-branches';
                }, 1000);
            } else {
                showToast(res.data.message || 'Error saving branch.', 'error');
            }
        });
    });

    // Delete Branch
    $('.swvt-hr-delete-branch').on('click', function() {
        if (!confirm(SWVT_HR.i18n.confirmDelete)) {
            return;
        }
        var btn = $(this);
        var id = btn.data('id');
        btn.prop('disabled', true);
        
        $.post(SWVT_HR.ajax, {
            action: 'swvt_hr_delete_branch',
            nonce: SWVT_HR.nonce,
            id: id
        }, function(res) {
            btn.prop('disabled', false);
            if (res.success) {
                showToast(res.data.message, 'success');
                $('#swvt-hr-branch-row-' + id).fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                showToast(res.data.message || 'Error deleting branch.', 'error');
            }
        });
    });

    // Save Employee
    $('#swvt-hr-employee-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).css('opacity', 0.6);
        
        var data = form.serialize() + '&action=swvt_hr_save_employee&nonce=' + SWVT_HR.nonce;
        
        $.post(SWVT_HR.ajax, data, function(res) {
            submitBtn.prop('disabled', false).css('opacity', 1);
            if (res.success) {
                showToast(res.data.message, 'success');
                setTimeout(function() {
                    window.location.href = 'admin.php?page=swvt-hr-employees';
                }, 1000);
            } else {
                showToast(res.data.message || 'Error saving employee.', 'error');
            }
        });
    });

    // Save Sales Targets (Legacy wrapper)
    $('#swvt-hr-sales-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).css('opacity', 0.6);
        
        var data = form.serialize() + '&action=swvt_hr_save_sales&nonce=' + SWVT_HR.nonce;
        
        $.post(SWVT_HR.ajax, data, function(res) {
            submitBtn.prop('disabled', false).css('opacity', 1);
            if (res.success) {
                showToast(res.data.message, 'success');
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                showToast(res.data.message || 'Error saving sales.', 'error');
            }
        });
    });

    // Save Rules
    $('#swvt-hr-rules-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('#swvt-hr-rules-submit');
        submitBtn.prop('disabled', true).css('opacity', 0.6);
        
        var data = form.serialize() + '&action=swvt_hr_save_rules&nonce=' + SWVT_HR.nonce;
        
        $.post(SWVT_HR.ajax, data, function(res) {
            submitBtn.prop('disabled', false).css('opacity', 1);
            if (res.success) {
                showToast(res.data.message, 'success');
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                showToast(res.data.message || 'Error saving rules.', 'error');
            }
        });
    });

    // Save Settings
    $('#swvt-hr-settings-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).css('opacity', 0.6);
        
        var data = form.serialize() + '&action=swvt_hr_save_settings&nonce=' + SWVT_HR.nonce;
        
        $.post(SWVT_HR.ajax, data, function(res) {
            submitBtn.prop('disabled', false).css('opacity', 1);
            if (res.success) {
                showToast(res.data.message, 'success');
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                showToast(res.data.message || 'Error saving settings.', 'error');
            }
        });
    });
});
