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
    // console.log(promiseList);

    var $search = $('#search');
    var $facets = $('[data-list-facet]');

    // Clear all
    $('.promises__category--reset').on('click', function(e) {
      // Visually reset buttons
      $facets.removeClass('active');
      // Clear out text field
      $search.val('').change();
      // Wipe all filters
      promiseList.filter();
    });

    // Any facet filter button
    $facets.on('click', function(e) {

      var facet = $(this).data('list-facet');
      var value = $(this).data('facet-value');

      // Visually
      $(this).toggleClass('active');

      // Array of active
      var actives = $('[data-list-facet="' + facet + '"].active').map(function() {
        return $(this).data('facet-value');
      }).get();

      if (actives.length === 0) {
        promiseList.filter();
      }
      else {
        promiseList.filter(function(item) {
          return (actives.indexOf(item.values()[facet]) !== -1);
        });
      }

    });

  });

})(jQuery);
