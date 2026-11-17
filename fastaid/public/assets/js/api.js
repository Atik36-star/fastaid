/* API Contract */
const USE_MOCK = false;

function csrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}

function post(action, payload = {}) {
  return fetch('api.php?action=' + encodeURIComponent(action), {
    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken() },
    body: JSON.stringify({ ...payload, csrf: csrfToken() })
  }).then(r => r.json());
}

function get(action, query = '') {
  return fetch('api.php?action=' + encodeURIComponent(action) + query).then(r => r.json());
}

const api = {
  register: (d) => post('register', d),
  login: (d) => post('login', d),
  logout: () => post('logout'),
  get_me: () => get('get_me'),
  create_request: (d) => post('create_request', d),
  get_request_status: (d) => get('get_request_status', '?id=' + (d.id || 'r101')),
  get_my_requests: () => get('get_my_requests'),
  get_incoming_requests: () => get('get_incoming_requests'),
  accept_request: (d) => post('accept_request', d),
  update_request_status: (d) => post('update_request_status', d),
  update_availability: (d) => post('update_availability', d),
  set_dnd_hours: (d) => post('set_dnd_hours', d),
  submit_rating: (d) => post('submit_rating', d),
  submit_debrief: (d) => post('submit_debrief', d),
  get_case_report: (id) => get('get_case_report', '?id=' + encodeURIComponent(id)),
  get_notifications: () => get('get_notifications'),
  mark_read: (d) => post('mark_read', d),
  mark_all_read: () => post('mark_all_read'),
  admin_stats: () => get('admin_stats'),
  admin_users: () => get('admin_users'),
  admin_pending_volunteers: () => get('admin_pending_volunteers'),
  admin_verify_volunteer: (d) => post('admin_verify_volunteer', d),
  admin_reject_volunteer: (d) => post('admin_reject_volunteer', d),
  admin_requests: () => get('admin_requests'),
  subscribeStream: (requestId, callbacks = {}) => {
    const url = 'api.php?action=stream' + (requestId ? '&request_id=' + encodeURIComponent(requestId) : '');
    const es = new EventSource(url);
    if (callbacks.onRequestUpdate) {
      es.addEventListener('request_update', (e) => {
        try { callbacks.onRequestUpdate(JSON.parse(e.data)); } catch (err) {}
      });
    }
    if (callbacks.onNotification) {
      es.addEventListener('notification', (e) => {
        try { callbacks.onNotification(JSON.parse(e.data)); } catch (err) {}
      });
    }
    if (callbacks.onError) {
      es.onerror = callbacks.onError;
    }
    return es;
  }
};

function escapeHtml(str) {
  if (str == null) return '';
  const div = document.createElement('div');
  div.textContent = String(str);
  return div.innerHTML;
}
