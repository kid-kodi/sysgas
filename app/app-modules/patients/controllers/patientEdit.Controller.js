(function () {

    var injectParams = ['$window', '$scope', '$location', 'patientService', '$timeout', '$routeParams', 'config_map', 'FlashService'];

    var patientEditController = function ($window, $scope, $location, patientService, $timeout, $routeParams, config_map, FlashService) {
        var vm = this;

        var patientId = ($routeParams.id) ? parseInt($routeParams.id) : 0;

        vm.patient = {};
        vm.updateStatus = false;
        vm.message = null;
        vm.title = null;
        vm.buttonText = null;
        vm.jours = config_map.jours;
        vm.mois = config_map.mois;
        vm.annees = config_map.annees;
        vm.pays = config_map.pays;
        vm.communes = config_map.communes;
        vm.typePieceFournits = config_map.typePieceFournits;

        vm.submitted = false;
        vm.sending = false;
        

        vm.savePatient = function () {
            vm.sending = true;
            if ($scope.patientForm.$valid) { // Submit as normal 

                if (patientId > 0) {
                    patientService.patient.edit(vm.patient)
                    .then(function (result) {
                        console.log(result);
                        processResponse(result);
                    });
                }
                else {
                    patientService.patient.add(vm.patient)
                    .then(function (result) {
                        console.log(result);
                        processResponse(result);
                    });
                }
            }
            else {
                vm.submitted = true;
                vm.sending = false;
            }
        };

        function processResponse(result) {
            var error = result.error;
            var message = result.message;
            var patient = result.patient;
            vm.message = message;
            if (!error) {
                vm.patient = {};
                $location.path('/patient');
            }
            vm.updateStatus = true;
            vm.sending = false;
            startTimer();
        }

        (function () {
            if (patientId > 0) {
                vm.patient.id = patientId;
                vm.title = 'Modifier les informations';
                vm.buttonText = 'Modifier';
                patientService.getPatient(patientId);

                //vm.patient = patientService.patient.get_patient(patientId)[0];
            } else {
                vm.title = 'Enregistrer les informations';
                vm.buttonText = 'Enregistrer';
            }
            $scope.$parent.vm.pageTitle = vm.title;
        })();

        $scope.$on('app-set-patient', function (event, response) {
            console.log('set patient');
            console.log(response);

            vm.patient = response.patient;
            vm.commande_list = response.patient.commande_list;
        });


        function startTimer() {
            var timer = $timeout(function () {
                $timeout.cancel(timer);
                vm.message = '';
                vm.updateStatus = false;
            }, 5000);
        }

        $scope.$on('app-set-patient', function (event, response) {
            console.log(response);
            //vm.patient = response.patient;
            vm.patient = response.patient;
        });

    };

    patientEditController.$inject = injectParams;

    angular.module('app').controller('patientEditController', patientEditController);

})();