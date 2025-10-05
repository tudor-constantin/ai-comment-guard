/**
 * AI Comment Guard Admin JavaScript
 * 
 * @package AICOG
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Main Admin Controller
     */
    const AICommentGuard = {
        
        // Configuration
        config: {
            selectors: {
                form: '#ai-comment-guard-form',
                testButton: '#test-ai-connection',
                testResult: '#test-result',
                providerField: 'select[name="aicog_settings[ai_provider]"]',
                tokenField: 'input[name="aicog_settings[ai_provider_token]"]',
                logCheckbox: '#log_enabled_checkbox',
                saveWarning: '#save-warning',
                connectionWarning: '#connection-required-warning',
                actionFilter: '#action-filter',
                filterButton: '#filter-logs'
            },
            state: {
                connectionTested: false,
                originalProvider: null,
                originalToken: null,
                formChanged: false,
                tokenLocked: false,
                validatedProvider: null
            }
        },
        
        /**
         * Initialize the admin interface
         */
        init: function() {
            this.bindEvents();
            this.initTokenFieldEvents();
            this.storeOriginalValues();
            this.initializeTooltips();
            this.checkInitialState();
        },
        
        /**
         * Store original form values
         */
        storeOriginalValues: function() {
            const c = this.config;
            c.state.originalProvider = $(c.selectors.providerField).val();
            c.state.originalToken = $(c.selectors.tokenField).val();
        },
        
        /**
         * Check initial state on page load
         */
        checkInitialState: function() {
            const provider = $(this.config.selectors.providerField).val();
            const hasTokenSaved = $('.ai-comment-guard-token-saved').length > 0;
            
            // Disable token field if no provider selected or if it's the default "Select provider..." option
            if (!provider || provider === '') {
                $(this.config.selectors.tokenField).prop('disabled', true);
            }
            
            // If we have saved values, assume they were validated
            if (provider && hasTokenSaved) {
                this.config.state.connectionTested = true;
                this.config.state.validatedProvider = provider;
                $('#test-connection-section').hide(); // Hide since already validated
                $(this.config.selectors.saveWarning).hide();
            } else if (provider && !hasTokenSaved) {
                // Provider selected but no token saved - show test section when token is entered
                const token = $(this.config.selectors.tokenField).val();
                if (token) {
                    $('#test-connection-section').show();
                    $(this.config.selectors.saveWarning).show();
                }
            } else {
                $('#test-connection-section').hide();
            }
        },
        
        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            // Test connection
            $(this.config.selectors.testButton).on('click', this.testConnection.bind(this));
            
            // Form submission
            $(this.config.selectors.form).on('submit', this.validateForm.bind(this));
            
            // Provider changes
            $(this.config.selectors.providerField)
                .on('change', this.handleProviderChange.bind(this));
            
            // Token changes
            $(this.config.selectors.tokenField)
                .on('input keyup change', this.handleTokenChange.bind(this));
            
            // Log checkbox
            $(this.config.selectors.logCheckbox).on('change', this.handleLogToggle.bind(this));
            
            // Threshold validation
            $('input[type="number"][name*="threshold"]').on('change', this.validateThreshold.bind(this));
            
            // Form change detection
            $(this.config.selectors.form).find('input, select, textarea')
                .on('change', this.markFormChanged.bind(this));
            
            // Filter logs
            $(this.config.selectors.filterButton).on('click', this.filterLogs.bind(this));
            
            // Warn before leaving with unsaved changes
            window.addEventListener('beforeunload', this.handleBeforeUnload.bind(this));
        },
        
        /**
         * Test AI connection
         */
        testConnection: function(e) {
            e.preventDefault();
            
            const $button = $(this.config.selectors.testButton);
            const $result = $(this.config.selectors.testResult);
            const provider = $(this.config.selectors.providerField).val();
            
            // Get token from the appropriate field (change section or main field)
            let token = $('#token-change-section').is(':visible') 
                ? $('#token-change-section input').val()
                : $(this.config.selectors.tokenField).val();
            
            // Validate inputs
            if (!provider || !token) {
                this.showResult('error', aicog_ajax.strings.provider_required);
                return;
            }
            
            // Show loading state
            $button.prop('disabled', true)
                   .html(aicog_ajax.strings.testing + ' <span class="ai-comment-guard-loading"></span>');
            $result.hide();
            
            // Make AJAX request
            $.ajax({
                url: aicog_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'aicog_test_connection',
                    nonce: aicog_ajax.nonce,
                    ai_provider: provider,
                    ai_provider_token: token
                },
                success: (response) => {
                    if (response.success) {
                        this.showResult('success', response.data.message);
                        this.config.state.connectionTested = true;
                        this.config.state.validatedProvider = provider;
                        this.lockTokenField();
                        $(this.config.selectors.saveWarning).hide();
                        $(this.config.selectors.connectionWarning).hide();
                        
                        // Hide test section after successful validation
                        setTimeout(() => {
                            $('#test-connection-section').fadeOut(300);
                        }, 2000);
                    } else {
                        this.showResult('error', response.data);
                        this.config.state.connectionTested = false;
                    }
                },
                error: (xhr, status, error) => {
                    this.showResult('error', aicog_ajax.strings.test_error + ' ' + error);
                    this.config.state.connectionTested = false;
                },
                complete: () => {
                    $button.prop('disabled', false)
                           .html(aicog_ajax.strings.test_button || 'Test AI Connection');
                }
            });
        },
        
        /**
         * Show test result
         */
        showResult: function(type, message) {
            const $result = $(this.config.selectors.testResult);
            $result.removeClass('success error')
                   .addClass(type)
                   .html('<strong>' + (type === 'success' ? '✓ ' : '✗ ') + '</strong>' + message)
                   .show();
        },
        
        /**
         * Validate form before submission
         */
        validateForm: function(e) {
            const provider = $(this.config.selectors.providerField).val();
            const token = $(this.config.selectors.tokenField).val();
            
            // Check if provider changed or connection not tested
            if (provider && token && !this.config.state.connectionTested) {
                this.showWarning('connection');
                e.preventDefault();
                return false;
            }
            
            // Require both fields if one is set
            if ((provider && !token) || (!provider && token)) {
                this.showWarning('connection');
                e.preventDefault();
                return false;
            }
            
            // Clear the form changed flag to prevent beforeunload warning
            this.config.state.formChanged = false;
            
            // Hide warnings if OK
            $(this.config.selectors.connectionWarning).hide();
            $(this.config.selectors.saveWarning).hide();
            
            return true;
        },
        
        /**
         * Check if provider settings changed
         */
        hasProviderChanged: function() {
            const provider = $(this.config.selectors.providerField).val();
            const token = $(this.config.selectors.tokenField).val();
            
            return provider !== this.config.state.originalProvider || 
                   token !== this.config.state.originalToken;
        },
        
        /**
         * Handle provider changes
         */
        handleProviderChange: function() {
            const provider = $(this.config.selectors.providerField).val();
            const hasTokenSaved = $('.ai-comment-guard-token-saved').length > 0;
            
            // Enable/disable token field based on provider selection
            if (provider && provider !== '') {
                $(this.config.selectors.tokenField).prop('disabled', false);
            } else {
                $(this.config.selectors.tokenField).prop('disabled', true).val('');
                $('#test-connection-section').hide();
            }
            
            // Check if provider changed from validated one
            if (this.config.state.validatedProvider && provider !== this.config.state.validatedProvider) {
                // Provider changed - require re-validation
                this.config.state.connectionTested = false;
                this.config.state.validatedProvider = null;
                $(this.config.selectors.testResult).hide();
                
                // If token is saved, show warning that token needs to be re-entered
                if (hasTokenSaved) {
                    this.showProviderChangeWarning();
                }
                
                $(this.config.selectors.saveWarning).hide();
            }
        },
        
        /**
         * Handle token changes
         */
        handleTokenChange: function() {
            const provider = $(this.config.selectors.providerField).val();
            const token = $(this.config.selectors.tokenField).val();
            const isInChangeMode = $('#token-change-section').is(':visible');
            
            // Show/hide test connection section based on provider and token
            if (provider && token && (isInChangeMode || !$('.ai-comment-guard-token-saved').length)) {
                $('#test-connection-section').show();
                if (!this.config.state.connectionTested) {
                    $(this.config.selectors.saveWarning).show();
                }
            } else if (!token) {
                $('#test-connection-section').hide();
                $(this.config.selectors.saveWarning).hide();
            }
            
            // Reset connection test state when token changes
            if (token !== this.config.state.originalToken) {
                this.config.state.connectionTested = false;
            }
        },
        
        /**
         * Handle log checkbox toggle
         */
        handleLogToggle: function(e) {
            const isChecked = $(e.target).is(':checked');
            const $info = $('.log-menu-info');
            
            if (isChecked) {
                if (!$info.length) {
                    $(e.target).closest('td').append(
                        '<div class="log-menu-info notice notice-info inline" style="margin-top: 10px;">' +
                        '<p><strong>' + aicog_ajax.strings.log_tab_note + '</strong></p>' +
                        '</div>'
                    );
                }
            } else {
                $info.remove();
            }
        },
        
        /**
         * Validate threshold values
         */
        validateThreshold: function(e) {
            const value = parseFloat($(e.target).val());
            if (value < 0 || value > 1) {
                alert(aicog_ajax.strings.threshold_error);
                $(e.target).val(value < 0 ? 0 : 1).focus();
            }
        },
        
        /**
         * Mark form as changed
         */
        markFormChanged: function() {
            this.config.state.formChanged = true;
            
            if (!$('.unsaved-changes').length) {
                $(this.config.selectors.form).prepend(
                    '<div class="notice notice-warning unsaved-changes">' +
                    '<p><strong>' + aicog_ajax.strings.unsaved_changes_notice + '</strong></p>' +
                    '</div>'
                );
            }
        },
        
        /**
         * Handle before unload
         */
        handleBeforeUnload: function(e) {
            // Only warn if form changed AND we're not submitting
            if (this.config.state.formChanged && !$(this.config.selectors.form).hasClass('submitting')) {
                const message = aicog_ajax.strings.unsaved_changes || 'You have unsaved changes';
                e.returnValue = message;
                return message;
            }
        },
        
        /**
         * Filter logs
         */
        filterLogs: function() {
            const filter = $(this.config.selectors.actionFilter).val();
            const url = new URL(window.location);
            
            if (filter) {
                url.searchParams.set('action_filter', filter);
            } else {
                url.searchParams.delete('action_filter');
            }
            
            url.searchParams.delete('paged');
            window.location = url.toString();
        },
        
        /**
         * Show warning
         */
        showWarning: function(type) {
            const selector = type === 'connection' 
                ? this.config.selectors.connectionWarning 
                : this.config.selectors.saveWarning;
            
            $(selector).show();
            $('html, body').animate({
                scrollTop: $(selector).offset().top - 100
            }, 500);
        },
        
        /**
         * Initialize tooltips
         */
        initializeTooltips: function() {
            $('.ai-comment-guard-tooltip')
                .on('mouseenter', function() {
                    $(this).find('.tooltiptext').fadeIn(200);
                })
                .on('mouseleave', function() {
                    $(this).find('.tooltiptext').fadeOut(200);
                });
        },
        
        /**
         * Utility: Get statistics
         */
        getStatistics: function(days = 30) {
            return $.ajax({
                url: aicog_ajax.ajaxurl,
                type: 'GET',
                data: {
                    action: 'aicog_get_stats',
                    nonce: aicog_ajax.nonce,
                    days: days
                }
            });
        },
        
        /**
         * Utility: Delete logs
         */
        deleteLogs: function(days = 0) {
            if (!confirm(aicog_ajax.strings.confirm_delete_logs)) {
                return false;
            }
            
            return $.ajax({
                url: aicog_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'aicog_delete_logs',
                    nonce: aicog_ajax.nonce,
                    days: days
                }
            });
        },
        
        /**
         * Lock token field after successful validation
         */
        lockTokenField: function() {
            const $tokenField = $(this.config.selectors.tokenField);
            $tokenField.prop('readonly', true)
                      .addClass('token-locked')
                      .attr('title', aicog_ajax.strings.token_validated_tooltip || 'Token validated. Change provider to modify.');
            
            // Add visual indicator
            if (!$tokenField.next('.token-lock-indicator').length) {
                const lockText = aicog_ajax.strings.token_validated || '🔒 Validated';
                $tokenField.after('<span class="token-lock-indicator" style="margin-left: 8px; color: var(--success-color); font-weight: 600;">' + lockText + '</span>');
            }
            
            this.config.state.tokenLocked = true;
        },
        
        /**
         * Unlock token field when provider changes
         */
        unlockTokenField: function() {
            const $tokenField = $(this.config.selectors.tokenField);
            $tokenField.prop('readonly', false)
                      .removeClass('token-locked')
                      .removeAttr('title');
            
            // Remove visual indicator
            $tokenField.next('.token-lock-indicator').remove();
            
            this.config.state.tokenLocked = false;
        },
        
        /**
         * Show warning when provider changes and token is saved
         */
        showProviderChangeWarning: function() {
            if (!$('.provider-change-warning').length) {
                $('.ai-comment-guard-token-saved').after(
                    '<div class="notice notice-warning provider-change-warning" style="margin-top: 10px;">' +
                    '<p><strong>' + aicog_ajax.strings.provider_changed_warning + '</strong></p>' +
                    '</div>'
                );
            }
        },
        
        /**
         * Handle token field events for the new interface
         */
        initTokenFieldEvents: function() {
            const self = this;
            
            // Handle change token button
            $(document).on('click', '#change-token-btn', function(e) {
                e.preventDefault();
                $('.ai-comment-guard-token-saved .token-display, .token-actions').hide();
                $('#token-change-section').show();
                $('#token-change-section input').focus();
                $('.provider-change-warning').remove();
                $('#test-connection-section').show();
                $(self.config.selectors.saveWarning).show();
                self.config.state.connectionTested = false;
            });
            
            // Handle cancel change button
            $(document).on('click', '#cancel-change-btn', function(e) {
                e.preventDefault();
                $('#token-change-section').hide();
                $('#token-change-section input').val('');
                $('.ai-comment-guard-token-saved .token-display, .token-actions').show();
                $('#test-connection-section').hide();
                $(self.config.selectors.saveWarning).hide();
                $('.provider-change-warning').remove();
                
                // Reset connection tested state if provider hasn't changed
                const provider = $(self.config.selectors.providerField).val();
                if (provider === self.config.state.validatedProvider) {
                    self.config.state.connectionTested = true;
                }
            });
            
            // Handle input in token change field
            $(document).on('input keyup change', '#token-change-section input', function() {
                const token = $(this).val();
                if (token && token.length > 0) {
                    $('#test-connection-section').show();
                    $(self.config.selectors.saveWarning).show();
                } else {
                    $('#test-connection-section').hide();
                    $(self.config.selectors.saveWarning).hide();
                }
                self.config.state.connectionTested = false;
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        AICommentGuard.init();
        
        // Auto-refresh statistics if on logs page
        if ($('.ai-comment-guard-stats').length) {
            setInterval(function() {
                AICommentGuard.getStatistics().done(function(response) {
                    if (response.success) {
                        // Update statistics display
                        console.log('Statistics updated:', response.data);
                    }
                });
            }, 300000); // Every 5 minutes
        }
    });
    
})(jQuery);
