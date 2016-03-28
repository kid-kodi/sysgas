(function () {

    var filterMescommande = function () {

        return function (mescommandes, filterValue, filterStateId) {
            var matches = [];
            if (!filterValue) {
                for (var i = 0; i < mescommandes.length; i++) {
                    var mescommande = mescommandes[i];
                    if (mescommande.currentStateId == filterStateId) {
                        matches.push(mescommande);
                    }
                }
            }
            else {
                filterValue = filterValue.toLowerCase();
                for (var i = 0; i < mescommandes.length; i++) {
                    var mescommande = mescommandes[i];
                    if (mescommande.patientFullname.toLowerCase().indexOf(filterValue) > -1 &&
                        mescommande.currentStateId == filterStateId) {
                        matches.push(mescommande);
                    }
                }
            }

            return matches;
        };
    };

    angular.module('app').filter('filterMescommande', filterMescommande);

}());