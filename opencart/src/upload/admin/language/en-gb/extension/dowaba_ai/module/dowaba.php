<?php
// Heading
$_['heading_title']            = 'Dowaba AI Integration';

// Text
$_['text_extension']           = 'Extensions';
$_['text_success']             = 'Success: You have modified Dowaba AI module!';
$_['text_edit']                = 'Edit Dowaba AI Integration';

// Wizard steps
$_['text_step_1_title']        = 'Step 1 — API Key';
$_['text_step_1_desc']         = 'Generate a random API key. Dowaba will send this token in the Authorization header on every function call.';
$_['text_step_2_title']        = 'Step 2 — Manifest URL';
$_['text_step_2_desc']         = 'Paste this URL into your Dowaba admin → Bundle Import. Dowaba will fetch it once and auto-create all 9 AI functions.';
$_['text_step_3_title']        = 'Step 3 — IP Whitelist (optional)';
$_['text_step_3_desc']         = 'Comma-separated list of allowed source IPs. Empty = no restriction. Dowaba production IPs: 178.105.68.170, 49.13.120.112';
$_['text_step_4_title']        = 'Step 4 — Scopes';
$_['text_step_4_desc']         = 'Toggle which categories of functions the AI can call.';
$_['text_step_5_title']        = 'Step 5 — Connection Test';
$_['text_step_5_desc']         = 'Verify that your manifest URL is reachable and returns valid JSON.';

// Entry labels
$_['entry_status']             = 'Status';
$_['entry_api_key']            = 'API Key';
$_['entry_api_key_prefix']     = 'Current key';
$_['entry_ip_whitelist']       = 'IP Whitelist';
$_['entry_scope_read']         = 'Read access (products, orders, customers)';
$_['entry_scope_write']        = 'Write access (create orders)';
$_['entry_audit_retention']    = 'Audit retention (days)';

// Help
$_['help_api_key']             = 'Plain key is shown ONCE — save it to your Dowaba admin Bundle Import. After that only the hash remains in OpenCart.';
$_['help_scope_write']         = 'WARNING: Enabling write allows AI to create orders on behalf of customers. Use only with customer confirmation flow (opc_order_preview → opc_order_confirm).';
$_['help_manifest_url']        = 'This URL serves the .well-known/dowaba-bundle.json manifest that Dowaba imports.';

// Buttons
$_['button_save']              = 'Save';
$_['button_cancel']            = 'Cancel';
$_['button_regenerate']        = 'Regenerate API Key';
$_['button_copy']              = 'Copy';
$_['button_test']              = 'Test connection';

// Error
$_['error_permission']         = 'Warning: You do not have permission to modify Dowaba AI module!';
$_['error_invalid_ip']         = 'Invalid IP address: %s';
