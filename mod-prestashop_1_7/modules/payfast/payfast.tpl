<div class='payfastPayNow'>
<form id='payfastPayNow' action="{$data.payfast_url}" method="post">
    <p class="payment_module"> 
    {foreach $data.info as $k=>$v}
        <input type="hidden" name="{$k}" value="{$v}" />
    {/foreach}  
     <a href='#' onclick='document.getElementById("payfastPayNow").submit();return false;'>{$data.payfast_paynow_text}
      {if $data.payfast_paynow_logo=='on'} <img align='{$data.payfast_paynow_align}' alt='Pay Now With PayFast' title='Pay Now With PayFast' src="{$base_dir}modules/payfast/logo.png">{/if}</a>
       <noscript><input type="image" src="{$base_dir}modules/payfast/logo.png"></noscript>
    </p> 
</form>
</div>
<div class="clear"></div>
