{*
 * Dowaba AI — Configure page header (Manifest URL + Current API Key + Regenerate button).
 *
 * Smarty template — PrestaShop validator requirement: PHP code must not contain HTML.
 * Module passes these vars via $this->context->smarty->assign() in getContent().
 *
 * @author    Aydın Acar <support@dowaba.com>
 * @copyright 2024 Aydın Acar (DoWaba)
 * @license   https://opensource.org/licenses/MIT  MIT License
 *}

<div class="panel" id="dowaba-ai-header">
    <div class="panel-heading">
        <i class="icon-rocket"></i> {l s='Dowaba AI Quick Setup' mod='dowaba_ai'}
    </div>

    <h4>{l s='Manifest URL (Copy to Dowaba Bundle Import)' mod='dowaba_ai'}</h4>
    <input type="text"
           value="{$dowaba_manifest_url|escape:'html':'UTF-8'}"
           readonly
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
