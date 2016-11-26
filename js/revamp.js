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
      var isSingle = !!$(this).data('select-single'); // ie true/false for if there can only be one of this filter

      // Single-select categories should have their active state wiped
      if (isSingle) {
        $facets
          .filter(function() { return $(this).data('list-facet') === facet; })
          .removeClass('active');
      }

      // Flag as active
      $(this).toggleClass('active');

      // Array of active
      var facets = $facets.filter('.active').map(function() {
        // return object instead with facet/value
        return {
          facet: $(this).data('list-facet'),
          value: $(this).data('facet-value'),
          isSingle: !!$(this).data('select-single')
        };
      }).get();

      console.log(facets);

      // When deselecting last, clear all filters
      if (facets.length === 0) {
        promiseList.filter();
        return; // Eject now
      }

      // Otherwise, filter on the array
      promiseList.filter(function(item) {

        var vals = item.values();

        // For all active filters, just one needs to flag as true to for entire reduce here to be true
        var singles = facets.reduce(function(found, facet) {

          return facet.isSingle && vals[facet.facet] === facet.value;
        }, false);

        var multis = facets.reduce(function(found, facet) {
          if (found) {
            return found;
          }
          return !facet.isSingle && vals[facet.facet] == facet.value;
        }, false);

        if (singles) {
          return singles;
        }

        if (multis) {
          return multis;
        }

        // console.log(singles);
        // console.log(multis);

        // return facets.reduce(function(found, facet) {
        //
        //   // console.log(vals[facet.facet]);
        //   // console.log(facet.value);
        //
        //   if (facet.isSingle && vals[facet.facet] !== facet.value) {
        //     return false;
        //   }
        //
        //   // found === true, then skip
        //   // if (found) {
        //   //   return found;
        //   // }
        //   if (!facet.isSingle && vals[facet.facet] === facet.value) {
        //     return true;
        //   }
        //   // return vals[facet.facet] === facet.value;
        //
        // }, true); // Start reduce at false

      }); // promiseList.filter()

    });
  });

})(jQuery);
