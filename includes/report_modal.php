<?php
// Shared Report Modal markup
?>
<div class="report-modal" id="reportModal" hidden>
    <div class="report-modal__backdrop" data-report-modal-close data-report-modal-backdrop></div>
    <div class="report-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="reportModalTitle" aria-describedby="reportModalSummary" tabindex="-1">
        <button type="button" class="report-modal__close" data-report-modal-close aria-label="Close report details">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
            </svg>
        </button>
        <div class="report-modal__content">
            <header class="report-modal__header">
                <div class="report-modal__header-info">
                    <h3 id="reportModalTitle" data-report-modal-title>Citizen report</h3>
                    <p class="report-modal__submitted">Submitted <span data-report-modal-submitted>—</span></p>
                </div>
                <div class="report-modal__badges">
                    <span class="chip chip-category" data-report-modal-category>Category</span>
                    <span class="chip chip-status" data-report-modal-status>Status</span>
                </div>
            </header>

            <dl class="report-modal__meta-grid">
                <div class="report-modal__meta-item">
                    <dt>Reporter</dt>
                    <dd data-report-modal-reporter>—</dd>
                </div>
                <div class="report-modal__meta-item" data-report-modal-meta="location">
                    <dt>Location</dt>
                    <dd data-report-modal-location>—</dd>
                </div>
            </dl>

            <div class="report-modal__media-large" data-report-modal-media>
                <img data-report-modal-image alt="" hidden>
                <div class="report-modal__media-placeholder" data-report-modal-placeholder>
                    <div class="report-media--placeholder-icon">
                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <circle cx="8.5" cy="10.5" r="2" />
                            <path d="M21 15.5 16.5 11 6 19" />
                        </svg>
                    </div>
                    <span>No photo provided</span>
                </div>
            </div>

            <div class="report-modal__description">
                <h4>Description</h4>
                <div class="report-modal__description-content">
                    <p id="reportModalSummary" data-report-modal-summary>—</p>
                </div>
            </div>

            <div class="report-modal__media-actions" data-report-modal-actions>
                <button type="button" class="report-modal__action-btn" data-report-open-full>
                    View full image
                </button>
                <button type="button" class="report-modal__action-btn" data-report-download>
                    Download
                </button>
            </div>
        </div>
    </div>
    <div class="report-image-viewer" data-report-image-viewer hidden>
        <button type="button" class="report-image-viewer__close" data-report-viewer-close aria-label="Close full image">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false" width="22" height="22">
                <path d="M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="m6 6 12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
        <img data-report-viewer-image alt="" />
    </div>
</div>
