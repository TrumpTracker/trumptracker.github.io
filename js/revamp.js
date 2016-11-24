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

    var $search = $('#search');
    var $facets = $('[data-list-facet]');

    // Clear all
    function resetFilter(e) {
      // Visually reset buttons
      $facets.removeClass('active');
      // Clear out text field
      // $search.val('').change();
      // Wipe all filters
      promiseList.filter();
    }

    $('.promises__category--reset').on('click', resetFilter);

    // Any facet filter button
    $facets.on('click', function(e) {

      // Flag as active
      $(this).toggleClass('active');

      // Array of active
      var actives = $('[data-list-facet].active').map(function() {
        // return object instead with facet/value
        return {
          facet: $(this).data('list-facet'),
          value: $(this).data('facet-value')
        };
      }).get();

      console.log(actives);

      // When deselecting last, clear all filters
      if (actives.length === 0) {
        // resetFilter();
        promiseList.filter();
      }

      // Otherwise, filter on the array
      else {
        promiseList.filter(function(item) {

          // For all active filters, just one needs to flag as true to for entire reduce here to be true
          return actives.reduce(function(found, current) {

            // found === true, then skip
            if (found) {
              return found;
            }

            return item.values()[current.facet] === current.value;

          }, false); // Start reduce at false

        //   return (actives.indexOf(item.values()[facet]) !== -1);
        }); // promiseList.filter()
      }

    });

  });

})(jQuery);
