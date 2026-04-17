import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

let tooltipEl = null;
let tooltipBubbleEl = null;
let tooltipContentEl = null;
let tooltipArrowEl = null;
let activeTooltipTarget = null;

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

Alpine.start();
