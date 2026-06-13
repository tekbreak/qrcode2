function fallbackCopy(text) {
    const el = document.createElement('textarea');
    el.value = text;
    el.setAttribute('readonly', '');
    el.style.position = 'fixed';
    el.style.opacity = '0';
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
}

export function copyQrText(button) {
    const wrapper = button.closest('[data-copy-wrapper]');
    const source = wrapper?.querySelector('[data-copy-source]');

    if (!source) {
        return;
    }

    const text = source.textContent ?? '';

    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(text).catch(() => fallbackCopy(text));
    } else {
        fallbackCopy(text);
    }
}

window.copyQrText = copyQrText;
