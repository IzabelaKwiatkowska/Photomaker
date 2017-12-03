
var init = function() {
    $('#submit').click(formSubmitClick);
};

var formSubmitClick = function(e) {
    var $width, $height, width, height, errors = [];
    
    $width = $('[name="width"]'); 
    $height = $('[name="height"]');
    
    width = $width.val();
    height = $height.val();
    
    if (width.length === 0 || height.length === 0) {
        errors.push('Pole nie może być puste');
    } else if (width < 5 || height < 5) {
        errors.push('Wartość nie może być mniejsza niż 5 px');
    }
    
    if (width > 1020 || height > 1020) {
        errors.push('Wartość nie może być większa niż 1020 px');
    }
     
    if (errors.length > 0) {
        showErrors(errors);
        return false;
    } else {
        return true;
    }
    
};

var showErrors = function(errors) {
    var i, html = '', errorsLength;
    
    errorsLength = errors.length;
    
    for (i = 0; i < errorsLength; i ++) {
        html += '<div class="error"><div class="error-in">'+errors[i]+'</div></div>';
    }
    $('.errors-holder').html(html);
    $('.errors-holder').slideDown();
};

$(document).ready(init);

