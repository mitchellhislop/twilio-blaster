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
		
	  var name = $("input#name").val();
		if (name == "") {
      $("label#name_error").show();
      $("input#name").focus();
      return false;
    }
		
		var phone = $("input#phone").val();
		if (phone == "") {
      $("label#phone_error").show();
      $("input#phone").focus();
      return false;
    }
	
	var email = $("input#email").val();
		if (email == "") {
      $("label#email_error").show();
      $("input#email").focus();
      return false;
    }
		
		var dataString = 'name='+ name + '&phone=' + phone + '&email=' + email;
		//alert (dataString);return false;
		
		$.ajax({
      type: "POST",
      url: "bin/process.php",
      data: dataString,
      success: function() {
        $('#encyclopedia_form').html("<div id='encyclopedia_message'></div>");
        $('#encyclopedia_message').html("<h2><img src=\"images/check.png\" /></h2>")
        .append("<p>Successfully submitted! Please <a href='http://www.smcpros.com/wp-content/uploads/2010/08/SMEf2010.pdf' onload='_gaq.push(['_trackEvent', 'Downloads', 'Download', 'Encyc']);'>download your encyclopedia</a></p>")
        .hide()
        .fadeIn(1500, function() {
          $('#message').append("");
        });
      }
     });
    return false;
	});
});
