(function () {

    var filterCommande = function () {

        return function (commandes, filterValue, filterStateId, cmd_value) {
            var matches = [];
            if (!filterValue) {
                for (var i = 0; i < commandes.length; i++) {
                    var commande = commandes[i];
                    if (commande.currentStateId == filterStateId) {
                        matches.push(commande);
                    }
                }
            }
            else {
                filterValue = filterValue.toLowerCase();
                for (var i = 0; i < commandes.length; i++) {
                    var commande = commandes[i];
                    if (commande.patientFullname.toLowerCase().indexOf(filterValue) > -1 &&
                        commande.currentStateId == filterStateId) {
                        matches.push(commande);
                    }
                }
            }

            return matches;
        };
    };

    angular.module('app').filter('filterCommande', filterCommande);

}());