// Shared UI helpers
function showToast(message, type = 'success') {
  const existing = document.querySelector('.toast');
  if (existing) existing.remove();
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = message;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 3000);
}

function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('open');
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
}

function initNav() {
  // Mobile hamburger already handled by inline onclick in HTML
}

// Initialize on load
window.addEventListener('DOMContentLoaded', () => {
  initNav();
});
