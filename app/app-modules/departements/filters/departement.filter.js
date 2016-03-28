(function () {

    var filterDepartement = function () {

        return function (departements, filterValue) {
            if (!filterValue) return departements;

            var matches = [];
            filterValue = filterValue.toLowerCase();
            for (var i = 0; i < departements.length; i++) {
                var departement = departements[i];
                if (departement.departementLib.toLowerCase().indexOf(filterValue) > -1 ) {

                    matches.push(departement);
                }
            }
            return matches;
        };
    };

    angular.module('app').filter('filterDepartement', filterDepartement);

}());