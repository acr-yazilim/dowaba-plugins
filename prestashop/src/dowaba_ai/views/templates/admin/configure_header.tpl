{*
 * Dowaba AI — Configure page header.
 *   - "Connect in 3 steps" guide + one-click "Connect to DoWaba" button
 *   - Manifest URL + Current API Key + Regenerate button
 *
 * Smarty template — PrestaShop validator requirement: PHP code must not contain HTML.
 * Module passes these vars via $this->context->smarty->assign() in renderForm().
 *
 * @author    Aydın Acar <support@dowaba.com>
 * @copyright 2024 Aydın Acar (DoWaba)
 * @license   https://opensource.org/licenses/MIT  MIT License
 *}

{* ===== One-click result: key just regenerated → finish on DoWaba ===== *}
{if $dowaba_connect_with_key}
<div class="alert alert-success" id="dowaba-ai-oneclick">
    <p><strong>{l s='Your API key is ready.' mod='dowaba_ai'}</strong>
       {l s='Click below to finish on DoWaba — the manifest URL and key are pre-filled automatically.' mod='dowaba_ai'}</p>
    <a href="{$dowaba_connect_with_key|escape:'html':'UTF-8'}" target="_blank" rel="noopener" class="btn btn-success btn-lg">
        <i class="icon-external-link"></i> {l s='Finish on DoWaba →' mod='dowaba_ai'}
    </a>
</div>
{/if}

{* ===== How to connect — 3 steps ===== *}
<div class="panel" id="dowaba-ai-howto">
    <div class="panel-heading">
        <i class="icon-magic"></i> {l s='Connect to DoWaba in 3 steps' mod='dowaba_ai'}
    </div>
    <ol style="margin:0 0 14px 18px;padding:0;line-height:1.9;">
        <li>{l s='Below, turn ON "Module enabled" and "Read scope", then click Save.' mod='dowaba_ai'}</li>
        <li>{l s='Click "Regenerate API Key" and copy the key shown once.' mod='dowaba_ai'}</li>
        <li>{l s='Click "Connect to DoWaba", choose your store and paste the key — done.' mod='dowaba_ai'}</li>
    </ol>
    <a href="{$dowaba_connect_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener" class="btn btn-primary btn-lg">
        <i class="icon-external-link"></i> {l s='Connect to DoWaba' mod='dowaba_ai'}
    </a>
    <p class="help-block" style="margin-top:10px;">
        {l s='No DoWaba account yet? You can create one for free during this step. PrestaShop reviewers: test credentials are in the integration guide PDF (docs/ folder of this module).' mod='dowaba_ai'}
    </p>
</div>

{* ===== Quick setup: manifest URL + API key + regenerate ===== *}
<div class="panel" id="dowaba-ai-header">
    <div class="panel-heading">
        <i class="icon-rocket"></i> {l s='Manifest URL & API Key' mod='dowaba_ai'}
    </div>

    <h4>{l s='Manifest URL (copy to DoWaba "Connect store")' mod='dowaba_ai'}</h4>
    <input type="text"
           value="{$dowaba_manifest_url|escape:'html':'UTF-8'}"
           readonly
           onclick="this.select();"
           class="form-control"
           style="width:100%;margin-bottom:10px;">

    <h4>{l s='Current API Key' mod='dowaba_ai'}</h4>
    <input type="text"
           value="{if $dowaba_api_key_prefix}{$dowaba_api_key_prefix|escape:'html':'UTF-8'}... (hash stored){else}{l s='— not generated yet —' mod='dowaba_ai'}{/if}"
           readonly
           class="form-control"
           style="width:100%;margin-bottom:10px;">

    <a href="{$dowaba_regenerate_url|escape:'html':'UTF-8'}" class="btn btn-warning">
        <i class="icon-refresh"></i> {l s='Regenerate API Key' mod='dowaba_ai'}
    </a>
</div>
