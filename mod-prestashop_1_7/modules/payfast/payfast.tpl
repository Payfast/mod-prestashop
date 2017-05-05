{*Copyright (c) 2008 PayFast (Pty) Ltd
You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.*}
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
