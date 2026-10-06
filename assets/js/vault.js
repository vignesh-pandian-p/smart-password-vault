/**
 * Smart Password Vault - Client Interactions & Helpers (Light Mode)
 */

// Ensure light mode is strictly enforced
document.documentElement.classList.remove('dark');

// Text Scramble Decryption Animation Effect (Slower, Deliberate Pace)
function animateTextScramble(element, finalText, duration = 1200) {
    if (!element || !finalText) return;
    const chars = '!@#$%^&*()_+-=[]{}|;:,.<>?/0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    const length = finalText.length;
    const startTime = performance.now();

    function update(now) {
        const elapsed = now - startTime;
        const progress = Math.min(1, elapsed / duration);
        const revealedCount = Math.floor(progress * length);

        let currentStr = '';
        for (let i = 0; i < length; i++) {
            if (i < revealedCount) {
                currentStr += finalText[i];
            } else {
                currentStr += chars[Math.floor(Math.random() * chars.length)];
            }
        }

        element.innerText = currentStr;

        if (progress < 1) {
            requestAnimationFrame(update);
        } else {
            element.innerText = finalText;
        }
    }

    requestAnimationFrame(update);
}

// Toast Notifications System
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `vault-toast vault-toast-${type}`;

    let iconName = 'check-circle';
    if (type === 'error') iconName = 'alert-circle';
    if (type === 'info') iconName = 'info';

    toast.innerHTML = `
        <i data-lucide="${iconName}" class="vault-toast-icon"></i>
        <span>${message}</span>
    `;

    container.appendChild(toast);
    if (window.lucide) {
        window.lucide.createIcons();
    }

    requestAnimationFrame(() => {
        toast.classList.add('show');
    });

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 250);
    }, 2800);
}

// Copy to Clipboard with Feedback
async function copyToClipboard(text, label = 'Copied to clipboard!') {
    if (!text) return;
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            textArea.remove();
        }
        showToast(label, 'success');
    } catch (err) {
        showToast('Failed to copy to clipboard', 'error');
    }
}

// Global DOM Listeners
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) {
        window.lucide.createIcons();
    }
});
