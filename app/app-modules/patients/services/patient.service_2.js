(function () {

    var injectParams = ['$http', '$q', '$rootScope'];

    var patientFactory = function ($http, $q, $rootScope) {
        var serviceBase = 'api/v1/patient/', patientList = [],
        patient = {
            pageNumber: 1,
            pageSize : 50
        };

        patient.search = function( arg_map ){
            //GetAllPatient(int pageSize, int pageNumber)
            pageSize   = arg_map.pageSize;
            pageNumber = arg_map.pageNumber;
            searchText = arg_map.searchText;

            return $http.get(serviceBase + 'patient?page=' + pageNumber + '&limit=' + pageSize + '&searchText=' + searchText).then(
            function (results) {
                console.log(results);

                var arg_list = results.data;
                _update_list(arg_list);
                return arg_list;
            });
        };

        patient.create = function(){};
        patient.update = function(){};
        patient.config = function(){};
        patient.delete = function(){};

        return patient;
    };

    patientFactory.$inject = injectParams;

    angular.module('app').factory('patientService', patientFactory);

}());