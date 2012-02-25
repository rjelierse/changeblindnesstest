// Script settings.
var slideDuration = 400;
var blankDuration = 100;

// Script internals.
var $currentSlide;
var $slides;
var d1 = new Date();
var startTime;

$(document).ready(function(){
    $slides = $('#rotator').children('img');

    $slides.each(function(index){
        $(this).hide();
    });

    $currentSlide = $slides.first();
    $currentSlide.show();

    setTimeout(slideHide, slideDuration);

    startTime = d1.getTime();

    $('#rotator').mousedown(function(event){
        document.getElementById('xcoordinate').value = event.pageX - this.offsetLeft;
        document.getElementById('ycoordinate').value = event.pageY - this.offsetTop;
        var d2 = new Date(); var endTime = d2.getTime();
        document.getElementById('responsetime').value = endTime - startTime;
        document.changeform.submit();
    });

    $('#placeholder').hide();
    $('#rotator').show();
});

function slideHide() {
    $currentSlide.hide();

    setTimeout(slideShow, blankDuration);
}

function slideShow() {
    $currentSlide = $currentSlide.siblings().first();
    $currentSlide.show();

    setTimeout(slideHide, slideDuration);
}