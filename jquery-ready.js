$(function() {
  $('.error').hide();
  $('input.text-input').css({backgroundColor:"#FFFFFF"});
  $('input.text-input').focus(function(){
    $(this).css({backgroundColor:"#FFDDAA"});
  });
  $('input.text-input').blur(function(){
    $(this).css({backgroundColor:"#FFFFFF"});
  });

  $(".button2").click(function() {
		// validate and process form
		// first hide any error messages
    $('.error').hide();
		
	  var phone = $("input#phone").val();
		if (phone == "") {
      $("label#phone_error").show();
      $("input#phone").focus();
      return false;
    }
		
		
		var dataString = 'phone='+ phone + '&phone=' + phone + '&email=' + email;
		//alert (dataString);return false;
		
		$.ajax({
      type: "POST",
      url: "bin/process.php",
      data: dataString,
      success: function() {
        $('#phone_form').html("<div id='phone_message'></div>");
        $('#phone_message').html("<h2><img src=\"images/check.png\" /></h2>")
        .append("<p>Successfully submitted! If you ever want to stop updates, text STOP</p>")
        .hide()
        .fadeIn(1500, function() {
          $('#message').append("");
        });
      }
     });
    return false;
	});
});
