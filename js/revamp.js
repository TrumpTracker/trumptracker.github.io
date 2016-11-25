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

    var $search = $('#search'); // The form
    var $facets = $('[data-list-facet]'); // All buttons that can filter

    // Clear all
    function resetFilter(e) {
      // Visually reset buttons
      $facets.removeClass('active');
      // Clear out text field
      // $search.val('').change();
      // Wipe all filters
      promiseList.filter();
    }

    // Hard reset all the buttons
    $('.promises__category--reset').on('click', resetFilter);

    // Any facet filter button
    $facets.on('click', function(e) {
      var facet = $(this).data('list-facet'); // ie 'js-promise-category'
      var value = $(this).data('facet-value'); // ie 'Culture'

      // Single-select categories should have their active state wiped
      if ($(this).data('select-single')) {
        $facets
          .filter(function() { return $(this).data('list-facet') === facet; })
          .removeClass('active');
      }

      // Flag as active
      $(this).toggleClass('active');

      // Array of active
      var actives = $facets.filter('.active').map(function() {
        // return object instead with facet/value
        return {
          facet: facet,
          value: value
        };
      }).get();

      console.log(actives);

      // When deselecting last, clear all filters
      if (actives.length === 0) {
        // resetFilter();
        promiseList.filter();
        return; // Eject now
      }

      // Otherwise, filter on the array
      promiseList.filter(function(item) {



        // For all active filters, just one needs to flag as true to for entire reduce here to be true
        return actives.reduce(function(found, current) {
          // found === true, then skip
          // if (found) {
          //   return found;
          // }

          return item.values()[current.facet] === current.value;

        }, false); // Start reduce at false

      }); // promiseList.filter()

    });
  });

})(jQuery);
