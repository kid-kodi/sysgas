(function () {

    var filterSociete = function () {

        return function (societes, filterValue) {
            if (!filterValue) return societes;

            var matches = [];
            filterValue = filterValue.toLowerCase();
            for (var i = 0; i < societes.length; i++) {
                var societe = societes[i];
                if (societe.societeLib.toLowerCase().indexOf(filterValue) > -1 ) {

                    matches.push(societe);
                }
            }
            return matches;
        };
    };

    angular.module('app').filter('filterSociete', filterSociete);

}());