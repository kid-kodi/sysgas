(function () {

    var filterFacture = function () {

        return function (factures, filterValue) {
            if (!filterValue) return factures;

            var matches = [];
            filterValue = filterValue.toLowerCase();
            for (var i = 0; i < factures.length; i++) {
                var facture = factures[i];
                if (facture.patientFullName.toLowerCase().indexOf(filterValue) > -1 ) {

                    matches.push(facture);
                }
            }
            return matches;
        };
    };

    angular.module('app').filter('filterFacture', filterFacture);

}());