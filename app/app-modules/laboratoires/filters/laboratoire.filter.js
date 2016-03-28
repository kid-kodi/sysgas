(function () {

    var filterLaboratoire = function () {

        return function (laboratoires, filterValue) {
            if (!filterValue) return laboratoires;

            var matches = [];
            filterValue = filterValue.toLowerCase();
            for (var i = 0; i < laboratoires.length; i++) {
                var laboratoire = laboratoires[i];
                if (laboratoire.laboratoireLib.toLowerCase().indexOf(filterValue) > -1 ) {

                    matches.push(laboratoire);
                }
            }
            return matches;
        };
    };

    angular.module('app').filter('filterLaboratoire', filterLaboratoire);

}());