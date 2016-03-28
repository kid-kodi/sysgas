(function () {

    var filterUnite = function () {

        return function (unites, filterValue) {
            if (!filterValue) return unites;

            var matches = [];
            filterValue = filterValue.toLowerCase();
            for (var i = 0; i < unites.length; i++) {
                var unite = unites[i];
                if (unite.uniteLib.toLowerCase().indexOf(filterValue) > -1 ) {

                    matches.push(unite);
                }
            }
            return matches;
        };
    };

    angular.module('app').filter('filterUnite', filterUnite);

}());