$(document).ready(function(){ 
  var isLive = $("input[name='payfast_mode']:checked").val();
  var merchantID = $("input[name='payfast_merchant_id']").val();
  var merchantKey = $("input[name='payfast_merchant_key']").val();
  var isSplitPayment = $("input[name='payfast_split_payments_enabled']:checked").val();
  var merchantID = $("input[name='payfast_merchant_id']").val();
  var splitMerchantID = $("input[name='payfast_split_payment_merchant_id']").val();
  var splitAmount = $("input[name='payfast_split_payment_amount']").val();
  var splitPercentage = $("input[name='payfast_split_payment_percentage']").val();
  var splitPaymentFields = ['payfast_split_payment_merchant_id','payfast_split_payment_amount','payfast_split_payment_percentage',
  'payfast_split_payment_min','payfast_split_payment_max'];
  var errorFields = [];

  if( isSplitPayment == 0){
    splitPaymentFields.forEach( function (value) {
      $('[name="'+value+'"]').prop("disabled", true); 
    });
  }

  $("input[name='payfast_mode']").change(function(){
    isLive = $("input[name='payfast_mode']:checked").val();
    if($('#payfastDetailsError').html().length > 0){
      validatePayFastForm();
    }
  });

  $("input[name='payfast_merchant_id']").change(function(){
    merchantID = $("input[name='payfast_merchant_id']").val();
  });

  $("input[name='payfast_merchant_key']").change(function(){
    merchantKey = $("input[name='payfast_merchant_key']").val();
  });

  $("input[name='payfast_split_payments_enabled']").change(function(){
    isSplitPayment = $("input[name='payfast_split_payments_enabled']:checked").val();
    if( isSplitPayment == 0){
      splitPaymentFields.forEach( function (value) {
        $('[name="'+value+'"]').prop("disabled", true);
      });
    } else{
      splitPaymentFields.forEach( function (value) {
        $('[name="'+value+'"]').prop("disabled", false); 
      });
    }
    if($('#payfastDetailsError').html().length > 0){
      validatePayFastForm();
    }
  });

  $("input[name='payfast_split_payment_amount']").change(function(){
    splitAmount = $("input[name='payfast_split_payment_amount']").val();
  });

  $("input[name='payfast_split_payment_percentage']").change(function(){
    splitPercentage = $("input[name='payfast_split_payment_percentage']").val();
  });

  $("input[name='payfast_split_payment_merchant_id']").change(function(){
    splitMerchantID = $("input[name='payfast_split_payment_merchant_id']").val();
  });


  $('#pf__button').on("click" , function(event){
    if(!validatePayFastForm(true)){ 
      event.preventDefault();
    }
  });

  function validatePayFastForm(saveChanges = false){

    if( $('#payfastDetailsError').html().includes("Required: Merchant ID") || $('#payfastDetailsError').html().includes("Key") || saveChanges)
    {
      isLive = $("input[name='payfast_mode']:checked").val();    
      if(isLive == 'live'){
          if(!merchantID){
            errorFields.push("Merchant ID");
          }
          if(!merchantKey){
            errorFields.push("Merchant Key");
          } 
      }
      $("input[name='payfast_merchant_id']").css('border-color', ( !merchantID && ( isLive == 'live' ) ) ? 'rgb(255, 0, 0)' : 'rgb(204, 204, 204)');
      $("input[name='payfast_merchant_key']").css('border-color', ( !merchantKey && ( isLive == 'live' ) ) ? 'rgb(255, 0, 0)' : 'rgb(204, 204, 204)'); 
    }
    if( $('#payfastDetailsError').html().includes("Split") || saveChanges)
    {  
      isSplitPayment = $("input[name='payfast_split_payments_enabled']:checked").val();
      if( isSplitPayment == 1 ){
        if(!splitMerchantID){
          errorFields.push("Split Payment Merchant ID");
        }
        if( !splitAmount && !splitPercentage){
          errorFields.push("Split Payment Amount or Percentage");        
        }
      }
      $("input[name='payfast_split_payment_merchant_id']").css('border-color', ( !splitMerchantID && ( isSplitPayment == 1 ) ) ? 'rgb(255, 0, 0)' : 'rgb(204, 204, 204)');
      $("input[name='payfast_split_payment_amount']").css('border-color', ( !splitAmount && !splitPercentage && ( isSplitPayment == 1 ) ) ? 'rgb(255, 0, 0)' : 'rgb(204, 204, 204)');
      $("input[name='payfast_split_payment_percentage']").css('border-color', ( !splitAmount && !splitPercentage && ( isSplitPayment == 1 ) ) ? 'rgb(255, 0, 0)' : 'rgb(204, 204, 204)'); 
    } 
    var total = errorFields.length;
    var message = "";
    $.each( errorFields, function ( index, value) {
      if( index == total - 1){
        message += value;
      } else{
        message += value + ", ";
      }
    });
    if( errorFields.length !== 0 ){
      $('#payfastDetailsError').html("Required: " + message);
      $('#payfastDetailsError').css('display', 'block');
      errorFields = [];
      return false;     
    }
    else
    {
      $('#payfastDetailsError').html("");
      return true;
    }
  }
});