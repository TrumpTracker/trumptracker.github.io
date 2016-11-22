(function($) {

  var listOptions = {
    valueNames: [
      'js-promise-text',
      'js-promise-category',
      'js-promise-status'
    ]
  };

  $(function() {

    // List.js object that we can filter upon
    var promiseList = new List('promises', listOptions);

    $('.promises__category--reset').on('click', function(e) {
      $('.promises__category', '.promises__categories').removeClass('active');
    });

    $('.promises__categories').on('click', '.promises__category', function(e) {
      $(this).toggleClass('active');
    });

  });

})(jQuery);
