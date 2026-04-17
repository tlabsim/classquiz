import './bootstrap';
import 'flatpickr/dist/flatpickr.min.css';

import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';

window.Alpine = Alpine;

let tooltipEl = null;
let tooltipBubbleEl = null;
let tooltipContentEl = null;
let tooltipArrowEl = null;
let activeTooltipTarget = null;
let copyToastEl = null;

function ensureCopyToast() {
    if (copyToastEl) return copyToastEl;

    copyToastEl = document.createElement('div');
    copyToastEl.className = 'pointer-events-none fixed bottom-4 right-4 z-50 rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white opacity-0 shadow-lg transition-all duration-200';
    copyToastEl.setAttribute('aria-live', 'polite');
    document.body.appendChild(copyToastEl);

    return copyToastEl;
}

let copyToastTimeout = null;

function showCopyToast(message) {
    const toast = ensureCopyToast();
    toast.textContent = message;
    toast.classList.remove('opacity-0', 'translate-y-2');
    toast.classList.add('opacity-100');

    if (copyToastTimeout) {
        window.clearTimeout(copyToastTimeout);
    }

    copyToastTimeout = window.setTimeout(() => {
        toast.classList.remove('opacity-100');
        toast.classList.add('opacity-0');
    }, 1800);
}

async function copyText(text) {
    if (navigator.clipboard?.writeText && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
        return true;
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    textarea.style.pointerEvents = 'none';
    document.body.appendChild(textarea);
    textarea.select();
    textarea.setSelectionRange(0, textarea.value.length);

    const success = document.execCommand('copy');
    document.body.removeChild(textarea);

    return success;
}

function ensureTooltip() {
    if (tooltipEl) return tooltipEl;

    tooltipEl = document.createElement('div');
    tooltipEl.className = 'cq-tooltip';
    tooltipEl.setAttribute('role', 'tooltip');
    tooltipEl.style.position = 'fixed';
    tooltipEl.style.top = '-9999px';
    tooltipEl.style.left = '-9999px';
    tooltipEl.style.display = 'block';

    tooltipBubbleEl = document.createElement('div');
    tooltipBubbleEl.className = 'cq-tooltip-bubble';

    tooltipContentEl = document.createElement('div');
    tooltipContentEl.className = 'cq-tooltip-content';

    tooltipArrowEl = document.createElement('div');
    tooltipArrowEl.className = 'cq-tooltip-arrow';
    tooltipArrowEl.setAttribute('aria-hidden', 'true');

    tooltipBubbleEl.appendChild(tooltipContentEl);
    tooltipBubbleEl.appendChild(tooltipArrowEl);
    tooltipEl.appendChild(tooltipBubbleEl);
    document.body.appendChild(tooltipEl);

    return tooltipEl;
}

function tooltipOptions(target) {
    return {
        placement: target.dataset.tooltipPlacement || 'top',
        align: target.dataset.tooltipAlign || 'center',
        maxWidth: target.dataset.tooltipMaxWidth || '20rem',
    };
}

function positionTooltip(target) {
    const tooltip = ensureTooltip();
    const options = tooltipOptions(target);
    const rect = target.getBoundingClientRect();
    const gap = 10;
    let placement = options.placement;
    let top = 0;
    let left = 0;
    let tooltipRect = tooltip.getBoundingClientRect();

    const horizontalLeft = {
        start: rect.left,
        center: rect.left + (rect.width / 2) - (tooltipRect.width / 2),
        end: rect.right - tooltipRect.width,
    };

    const verticalTop = {
        start: rect.top,
        center: rect.top + (rect.height / 2) - (tooltipRect.height / 2),
        end: rect.bottom - tooltipRect.height,
    };

    if (placement === 'bottom') {
        top = rect.bottom + gap;
        left = horizontalLeft[options.align] ?? horizontalLeft.center;
    } else if (placement === 'left') {
        top = verticalTop[options.align] ?? verticalTop.center;
        left = rect.left - tooltipRect.width - gap;
    } else if (placement === 'right') {
        top = verticalTop[options.align] ?? verticalTop.center;
        left = rect.right + gap;
    } else {
        placement = 'top';
        top = rect.top - tooltipRect.height - gap;
        left = horizontalLeft[options.align] ?? horizontalLeft.center;
    }

    if (placement === 'top' && top < 8) {
        placement = 'bottom';
        top = rect.bottom + gap;
    }

    if (placement === 'bottom' && top + tooltipRect.height > window.innerHeight - 8) {
        placement = 'top';
        top = rect.top - tooltipRect.height - gap;
    }

    if (placement === 'left' && left < 8) {
        placement = 'right';
        left = rect.right + gap;
    }

    if (placement === 'right' && left + tooltipRect.width > window.innerWidth - 8) {
        placement = 'left';
        left = rect.left - tooltipRect.width - gap;
    }

    const minLeft = 8;
    const maxLeft = window.innerWidth - tooltipRect.width - 8;
    const minTop = 8;
    const maxTop = window.innerHeight - tooltipRect.height - 8;

    left = Math.max(minLeft, Math.min(left, maxLeft));
    top = Math.max(minTop, Math.min(top, maxTop));

    tooltip.style.top = `${top}px`;
    tooltip.style.left = `${left}px`;
    tooltip.style.maxWidth = options.maxWidth;

    tooltipRect = tooltip.getBoundingClientRect();

    const arrowPadding = 16;
    const arrowX = Math.max(
        arrowPadding,
        Math.min(rect.left + (rect.width / 2) - left, tooltipRect.width - arrowPadding)
    );
    const arrowY = Math.max(
        arrowPadding,
        Math.min(rect.top + (rect.height / 2) - top, tooltipRect.height - arrowPadding)
    );

    tooltip.dataset.placement = placement;
    tooltip.dataset.align = options.align;
    tooltip.style.setProperty('--cq-tooltip-arrow-x', `${arrowX}px`);
    tooltip.style.setProperty('--cq-tooltip-arrow-y', `${arrowY}px`);
}

function showTooltip(target) {
    const text = target.dataset.tooltip;

    if (!text) return;

    const tooltip = ensureTooltip();
    activeTooltipTarget = target;
    tooltipContentEl.textContent = text;
    tooltip.style.maxWidth = tooltipOptions(target).maxWidth;
    tooltip.classList.add('is-visible');
    requestAnimationFrame(() => {
        if (activeTooltipTarget === target) {
            positionTooltip(target);
        }
    });
}

function hideTooltip() {
    if (!tooltipEl) return;
    activeTooltipTarget = null;
    tooltipEl.classList.remove('is-visible');
    delete tooltipEl.dataset.placement;
    delete tooltipEl.dataset.align;
    tooltipEl.style.top = '-9999px';
    tooltipEl.style.left = '-9999px';
    tooltipEl.style.removeProperty('--cq-tooltip-arrow-x');
    tooltipEl.style.removeProperty('--cq-tooltip-arrow-y');
}

document.addEventListener('mouseover', (event) => {
    const target = event.target.closest('[data-tooltip]');
    if (!target) return;
    if (target.contains(event.relatedTarget)) return;
    showTooltip(target);
});

document.addEventListener('mouseout', (event) => {
    const target = event.target.closest('[data-tooltip]');
    if (!target) return;
    if (target.contains(event.relatedTarget)) return;
    hideTooltip();
});

document.addEventListener('focusin', (event) => {
    const target = event.target.closest('[data-tooltip]');
    if (!target) return;
    showTooltip(target);
});

document.addEventListener('focusout', (event) => {
    if (!event.target.closest('[data-tooltip]')) return;
    hideTooltip();
});

document.addEventListener('click', async (event) => {
    const target = event.target.closest('[data-copy-text]');
    if (!target) return;

    const text = target.dataset.copyText || '';
    const label = target.dataset.copyLabel || 'Copied';

    try {
        const copied = await copyText(text);

        if (!copied) {
            throw new Error('Copy failed');
        }

        showCopyToast(`${label} copied`);
    } catch (error) {
        showCopyToast('Copy failed');
    }
});

function initFlatpickr() {
    document.querySelectorAll('[data-flatpickr]').forEach((element) => {
        if (element._flatpickr) return;

        flatpickr(element, {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            altInput: true,
            altFormat: 'M j, Y h:i K',
            allowInput: true,
        });
    });
}

function updateBreadcrumbs(container) {
    const isMobile = window.innerWidth < 640;
    const children = Array.from(container.children);
    const crumbs = children.filter((child) => child.textContent.trim() !== '/');
    const separators = children.filter((child) => child.textContent.trim() === '/');

    children.forEach((child) => {
        child.hidden = false;
    });

    container.querySelectorAll('[data-breadcrumb-ellipsis]').forEach((node) => node.remove());

    if (!isMobile || crumbs.length <= 2) {
        return;
    }

    const firstCrumb = crumbs[0];
    const lastCrumb = crumbs[crumbs.length - 1];

    crumbs.forEach((crumb) => {
        if (crumb !== firstCrumb && crumb !== lastCrumb) {
            crumb.hidden = true;
        }
    });

    separators.forEach((separator) => {
        separator.hidden = true;
    });

    const firstSeparator = separators[0];
    const lastSeparator = separators[separators.length - 1];

    if (firstSeparator) {
        firstSeparator.hidden = false;
    }

    if (lastSeparator && lastSeparator !== firstSeparator) {
        lastSeparator.hidden = false;
    }

    const ellipsis = document.createElement('span');
    ellipsis.dataset.breadcrumbEllipsis = 'true';
    ellipsis.className = 'text-gray-400';
    ellipsis.textContent = '...';

    const insertBeforeNode = lastSeparator ?? lastCrumb;
    container.insertBefore(ellipsis, insertBeforeNode);
}

function initBreadcrumbs() {
    document.querySelectorAll('[data-breadcrumbs]').forEach(updateBreadcrumbs);
}

window.addEventListener('scroll', () => {
    if (activeTooltipTarget) {
        positionTooltip(activeTooltipTarget);
    }
}, { passive: true });

window.addEventListener('resize', () => {
    if (activeTooltipTarget) {
        positionTooltip(activeTooltipTarget);
    }
});

document.addEventListener('DOMContentLoaded', initFlatpickr);
document.addEventListener('DOMContentLoaded', initBreadcrumbs);

Alpine.start();

window.addEventListener('resize', initBreadcrumbs);
