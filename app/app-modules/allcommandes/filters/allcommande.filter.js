(function () {

    var filterAllcommande = function () {

        return function (allcommandes, filterValue, filterStateId) {
            var matches = [];
            if (!filterValue) {
                for (var i = 0; i < allcommandes.length; i++) {
                    var allcommande = allcommandes[i];
                    if (allcommande.currentStateId == filterStateId) {
                        matches.push(allcommande);
                    }
                }
            }
            else {
                filterValue = filterValue.toLowerCase();
                for (var i = 0; i < allcommandes.length; i++) {
                    var allcommande = allcommandes[i];
                    if (allcommande.patientFullname.toLowerCase().indexOf(filterValue) > -1 &&
                        allcommande.currentStateId == filterStateId) {
                        matches.push(allcommande);
                    }
                }
            }

            return matches;
        };
    };

    angular.module('app').filter('filterAllcommande', filterAllcommande);

}());