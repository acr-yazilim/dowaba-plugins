<?php
if (!defined('ABSPATH')) exit;
/**
 * Admin settings page view.
 * Variables from class-dowaba-admin.php:
 *   $manifest_url, $api_key_prefix
 */
$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('dowaba_ai_ajax');
?>
<div class="wrap">
  <h1>🤖 DoWaba AI Settings</h1>
  <p class="description">AI chatbot for WhatsApp / Instagram DM / TikTok. <a href="https://dowaba.com" target="_blank">dowaba.com</a></p>

  <form method="post" action="">
    <?php wp_nonce_field('dowaba_ai_settings_save'); ?>

    <h2>Step 1 — API Key</h2>
    <p>Generate a random API key. DoWaba sends this token in Authorization header on every call.</p>
    <table class="form-table">
      <tr>
        <th>Current key</th>
        <td>
          <input type="text" id="api-key-display" value="<?php echo esc_attr($api_key_prefix ? $api_key_prefix . '... (hash stored)' : '— not generated yet —'); ?>" class="regular-text" readonly>
          <button type="button" class="button button-secondary" id="btn-regenerate-key">🔑 Regenerate API Key</button>
          <div id="api-key-plain-banner" class="notice notice-success inline" style="display:none;margin-top:8px;padding:12px;">
            <p><strong>⚠️ Plain key shown ONCE — copy to your DoWaba Bundle Import now!</strong></p>
            <input type="text" id="api-key-plain" value="" class="large-text" readonly>
            <button type="button" class="button" id="btn-copy-key">📋 Copy</button>
          </div>
        </td>
      </tr>
    </table>

    <h2>Step 2 — Manifest URL</h2>
    <p>Paste this URL into your DoWaba panel → Bundle Import.</p>
    <table class="form-table">
      <tr>
        <th>Manifest URL</th>
        <td>
          <input type="text" id="manifest-url" value="<?php echo esc_attr($manifest_url); ?>" class="large-text" readonly>
          <button type="button" class="button" id="btn-copy-manifest">📋 Copy</button>
        </td>
      </tr>
    </table>

    <h2>Step 3 — IP Whitelist (optional)</h2>
    <table class="form-table">
      <tr>
        <th>Allowed IPs</th>
        <td>
          <input type="text" name="dowaba_ai_ip_whitelist" value="<?php echo esc_attr(get_option('dowaba_ai_ip_whitelist', '')); ?>" class="regular-text" placeholder="178.105.68.170, 49.13.120.112">
          <p class="description">Comma-separated. Empty = no restriction. DoWaba prod IPs: 178.105.68.170, 49.13.120.112</p>
        </td>
      </tr>
    </table>

    <h2>Step 4 — Scopes</h2>
    <table class="form-table">
      <tr>
        <th>Read access</th>
        <td>
          <label><input type="checkbox" name="dowaba_ai_scope_read" value="1" <?php checked(get_option('dowaba_ai_scope_read', 1), 1); ?>> Products, orders, customers (recommended ON)</label>
        </td>
      </tr>
      <tr>
        <th>Write access</th>
        <td>
          <label><input type="checkbox" name="dowaba_ai_scope_write" value="1" <?php checked(get_option('dowaba_ai_scope_write', 0), 1); ?>> Create orders (AI can place orders on behalf of customers — only with explicit confirmation)</label>
          <p class="description">⚠️ Enables AI to create real orders. Always requires customer "yes" via 2-step preview/confirm flow.</p>
        </td>
      </tr>
    </table>

    <h2>Step 5 — Connection Test</h2>
    <table class="form-table">
      <tr>
        <th></th>
        <td>
          <button type="button" class="button button-primary" id="btn-test-connection">🔌 Test connection</button>
          <div id="test-result" style="margin-top:12px;"></div>
        </td>
      </tr>
    </table>

    <h2>General</h2>
    <table class="form-table">
      <tr>
        <th>Status</th>
        <td><label><input type="checkbox" name="dowaba_ai_status" value="1" <?php checked(get_option('dowaba_ai_status', 0), 1); ?>> <strong>Enable DoWaba AI module</strong></label></td>
      </tr>
      <tr>
        <th>Audit retention</th>
        <td><input type="number" name="dowaba_ai_audit_retention_days" value="<?php echo (int) get_option('dowaba_ai_audit_retention_days', 30); ?>" min="1" max="365" class="small-text"> days</td>
      </tr>
    </table>

    <?php submit_button('Save Settings'); ?>
  </form>

  <!-- Audit Log -->
  <div class="card" style="max-width:none;margin-top:24px;">
    <h2>📋 Audit Log — Last API Requests</h2>
    <p>Last requests from DoWaba. Filter by function slug or status.</p>

    <p>
      <select id="audit-filter-function">
        <option value="">(all functions)</option>
        <?php foreach (['opc_product_search','opc_product_detail','opc_product_compare','opc_stock_check','opc_category_list','opc_order_status','opc_customer_lookup','opc_cart_recover','opc_order_preview','opc_order_confirm'] as $slug): ?>
          <option><?php echo esc_html($slug); ?></option>
        <?php endforeach; ?>
      </select>
      <select id="audit-filter-status">
        <option value="">(all status)</option>
        <option value="200">200 OK</option>
        <option value="400">400 Bad Request</option>
        <option value="401">401 Unauthorized</option>
        <option value="403">403 Forbidden</option>
        <option value="404">404 Not Found</option>
        <option value="500">500 Server Error</option>
      </select>
      <button type="button" class="button" id="btn-audit-refresh">🔄 Refresh</button>
    </p>

    <table class="wp-list-table widefat striped">
      <thead>
        <tr><th>When</th><th>Function</th><th>IP</th><th>Status</th><th>ms</th><th>Error</th></tr>
      </thead>
      <tbody id="audit-tbody">
        <tr><td colspan="6" style="text-align:center;color:#999;">Loading...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
(function() {
  const ajaxUrl = <?php echo wp_json_encode($ajax_url); ?>;
  const nonce = <?php echo wp_json_encode($nonce); ?>;

  function ajax(action, data) {
    const params = new URLSearchParams({ action, nonce, ...data });
    return fetch(ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' }).then(r => r.json());
  }

  function copyText(text) {
    return navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.resolve();
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }

  function statusBadge(code) {
    const color = code === 200 ? '#46b450' : code >= 500 ? '#dc3232' : code >= 400 ? '#ffb900' : '#0073aa';
    return '<span style="background:' + color + ';color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">' + code + '</span>';
  }

  document.getElementById('btn-regenerate-key')?.addEventListener('click', async function() {
    if (!confirm('Existing API key will be revoked. Continue?')) return;
    const btn = this; btn.disabled = true; btn.textContent = '⏳ Generating...';
    const res = await ajax('dowaba_regenerate_key', {});
    btn.disabled = false; btn.textContent = '🔑 Regenerate API Key';
    if (res.success) {
      document.getElementById('api-key-display').value = res.data.prefix + '... (hash stored)';
      document.getElementById('api-key-plain').value = res.data.plain_key;
      document.getElementById('api-key-plain-banner').style.display = 'block';
    } else {
      alert('❌ ' + (res.data?.error || 'Error'));
    }
  });

  document.getElementById('btn-copy-key')?.addEventListener('click', () => {
    copyText(document.getElementById('api-key-plain').value).then(() => alert('✅ Copied'));
  });
  document.getElementById('btn-copy-manifest')?.addEventListener('click', () => {
    copyText(document.getElementById('manifest-url').value).then(() => alert('✅ Copied'));
  });

  document.getElementById('btn-test-connection')?.addEventListener('click', async function() {
    const btn = this; const result = document.getElementById('test-result');
    btn.disabled = true; btn.textContent = '⏳ Testing...';
    result.innerHTML = '';
    const res = await ajax('dowaba_test_connection', {});
    btn.disabled = false; btn.textContent = '🔌 Test connection';
    if (res.success) {
      result.innerHTML = '<div class="notice notice-success inline"><p>✅ Connection OK — schema ' + res.data.schema_version + ', ' + res.data.function_count + ' functions.<br><small>' + escapeHtml(res.data.url) + '</small></p></div>';
    } else {
      result.innerHTML = '<div class="notice notice-error inline"><p>❌ ' + escapeHtml(res.data?.error) + '<br><small>' + escapeHtml(res.data?.url || '') + '</small></p></div>';
    }
  });

  async function loadAuditLog() {
    const fn = document.getElementById('audit-filter-function').value;
    const st = document.getElementById('audit-filter-status').value;
    const tbody = document.getElementById('audit-tbody');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">⏳</td></tr>';
    const data = { limit: 100 };
    if (fn) data.function_slug = fn;
    if (st) data.status = st;
    const res = await ajax('dowaba_audit_log', data);
    if (!res.success || !res.data.rows.length) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#999;">No requests logged yet.</td></tr>';
      return;
    }
    tbody.innerHTML = res.data.rows.map(r =>
      '<tr>' +
      '<td><small>' + escapeHtml(r.created_at) + '</small></td>' +
      '<td><code>' + escapeHtml(r.function_slug) + '</code></td>' +
      '<td><small>' + escapeHtml(r.request_ip) + '</small></td>' +
      '<td>' + statusBadge(parseInt(r.status_code)) + '</td>' +
      '<td>' + r.duration_ms + '</td>' +
      '<td><small style="color:#666;">' + escapeHtml(r.error_message) + '</small></td>' +
      '</tr>'
    ).join('');
  }
  document.getElementById('btn-audit-refresh')?.addEventListener('click', loadAuditLog);
  document.getElementById('audit-filter-function')?.addEventListener('change', loadAuditLog);
  document.getElementById('audit-filter-status')?.addEventListener('change', loadAuditLog);
  loadAuditLog();
})();
</script>

<style>
.card { background: #fff; border: 1px solid #c3c4c7; padding: 16px 20px; box-shadow: 0 1px 1px rgba(0,0,0,0.04); }
#audit-tbody code { background: #f0f0f1; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
</style>
