{*
* confirmation.tpl
*
* Copyright (c) 2025 Payfast (Pty) Ltd
*
* @link       https://payfast.io/integration/plugins/prestashop/
*}
{extends file='page.tpl'}
{block name='content'}
    <div class="card">
        <div class="card-block">
            <h1>
                {if empty($status) || $status == 2}
                    {l s='Transaction declined' mod='fortis'}
                {elseif $status == 3}
                    {l s='Transaction cancelled' mod='fortis'}
                {/if}
            </h1>
            <p>Please <a href="{$link->getPageLink('cart')}?action=show">{l s='click here' mod='fortis'}</a> to try
                again.</p>
        </div>
    </div>
    <br/>
{/block}
