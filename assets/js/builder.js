(function () {
  document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelector('.scbd-lite-floating-launcher')) return;
    var adminBarLink = document.querySelector('#wp-admin-bar-scbd-lite a');
    if (!adminBarLink) return;
    var link = document.createElement('a');
    link.className = 'scbd-lite-floating-launcher';
    link.href = adminBarLink.href;
    link.textContent = 'SEO Bridge Lite';
    document.body.appendChild(link);
  });
})();
