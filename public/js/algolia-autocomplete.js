$(document).ready(function () {
    $('.js-user-autocomplete')
        .each(function () {
            var el = $(this);
            var autocompleteUrl = el.data('autocomplete-url');

            el.autocomplete({hint: false}, [
                {
                    source: function (query, cb) {
                        $.ajax({
                            url: `${autocompleteUrl}?query=${query}`
                        }).then(function (data) {
                            cb(data.users);
                        });
                    },
                    displayKey: 'email',
                    debounce: 500
                }
            ]);
        });
});
