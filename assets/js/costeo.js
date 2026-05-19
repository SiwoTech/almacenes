document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const sub = params.get('sub') || 'estructura';
    document.querySelectorAll('.sub-tab').forEach(tab => {
        if ((tab.getAttribute('href') || '').includes(`sub=${sub}`)) tab.classList.add('active');
    });
});
