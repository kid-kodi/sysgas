(function () {

    var filterPatient = function () {

        return function (patients, filterValue) {
            if (!filterValue) return patients;

            var matches = [];
            filterValue = filterValue.toLowerCase();
            for (var i = 0; i < patients.length; i++) {
                var patient = patients[i];
                if (patient.nom.toLowerCase().indexOf(filterValue) > -1 ||
                    patient.prenom.toLowerCase().indexOf(filterValue) > -1) {

                    matches.push(patient);
                }
            }
            return matches;
        };
    };

    angular.module('app').filter('filterPatient', filterPatient);

}());