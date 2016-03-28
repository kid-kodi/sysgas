(function () {

    var injectParams = ['$window', '$scope', '$location', 'departementService', '$timeout', '$routeParams', 'config_map'];

    var departementEditController = function ($window, $scope, $location, departementService, $timeout, $routeParams, config_map) {
        var vm = this;

        var departementId = ($routeParams.id) ? parseInt($routeParams.id) : 0;

        vm.departement = {};
        vm.updateStatus = false;
        vm.message = null;
        vm.title = null;
        vm.buttonText = null;
        vm.capabilities = config_map.capabilities;

        vm.selection = [];
        vm.departementLib = null;

        
        vm.saveDepartement = function () {
            
            if (departementId > 0) {
                departementService.departement.edit(departementId, vm.departementLib, vm.reference)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }
            else {


                departementService.departement.add(vm.departementLib, vm.reference)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }

        };

        function processResponse(result) {
            var error = result.error;
            var departement = result.departement;
            if (error) {
                vm.message = "Le departement n'as pas été enregistré";
            }
            else {
                vm.message = "Modification(s) effectuée(s) !";
                vm.departement = {};
                $location.path('/departement');
            }
            vm.updateStatus = true;
            startTimer();
        }

        (function () {
            if (departementId > 0) {
                vm.departement.id = departementId;
                vm.title = 'Modifier les informations';
                vm.buttonText = 'Modifier';
                departementService.getdepartement(departementId);
            } else {
                vm.title = 'Enregistrer les informations';
                vm.buttonText = 'Enregistrer';
            }
        })();

        $scope.$on('app-set-departement', function (event, response) {
            console.log('set departement');
            console.log(response);

            //vm.departement = response.departement;
            vm.departementLib = response.departement.departementLib;
            vm.reference = response.departement.reference;
        });


        function startTimer() {
            var timer = $timeout(function () {
                $timeout.cancel(timer);
                vm.message = '';
                vm.updateStatus = false;
            }, 5000);
        }

    };

    departementEditController.$inject = injectParams;

    angular.module('app').controller('departementEditController', departementEditController);

})();