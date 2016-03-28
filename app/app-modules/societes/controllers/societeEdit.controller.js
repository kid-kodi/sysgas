(function () {

    var injectParams = ['$window', '$scope', '$location', 'societeService', '$timeout', '$routeParams'];

    var societeEditController = function ($window, $scope, $location, societeService, $timeout, $routeParams) {
        var vm = this;

        var societeId = ($routeParams.id) ? parseInt($routeParams.id) : 0;

        vm.societe = {};
        vm.updateStatus = false;
        vm.message = null;
        vm.title = null;
        vm.buttonText = null;

        vm.selection = [];
        vm.societeLib = null;

        vm.toggleSelection = function toggleSelection(typeContratId) {
            console.log(vm.selection);
            var idx = vm.selection.indexOf(typeContratId);

            // is currently selected
            if (idx > -1) {
                vm.selection.splice(idx, 1);
            }

                // is newly selected
            else {
                vm.selection.push(typeContratId);
            }

            console.log(vm.selection);
        };

        
        vm.saveSociete = function () {
            
            if (societeId > 0) {
                societeService.societe.edit(societeId, vm.societeLib, vm.selection)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }
            else {


                societeService.societe.add(vm.societeLib, vm.selection)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }

        };

        function processResponse(result) {
            var error = result.error;
            var societe = result.societe;
            if (error) {
                vm.message = "Le societe n'as pas été enregistré";
            }
            else {
                vm.message = "Modification(s) effectuée(s) !";
                vm.societe = {};
                $location.path('/societe');
            }
            vm.updateStatus = true;
            startTimer();
        }

        (function () {
            if (societeId > 0) {
                vm.societe.id = societeId;
                vm.title = 'Modifier les informations';
                vm.buttonText = 'Modifier';
                societeService.getsociete(societeId);
            } else {
                vm.title = 'Enregistrer les informations';
                vm.buttonText = 'Enregistrer';
            }
        })();

        $scope.$on('app-set-societe', function (event, response) {
            console.log('set societe');
            vm.societeLib = response.societe.societeLib;
        });


        function startTimer() {
            var timer = $timeout(function () {
                $timeout.cancel(timer);
                vm.message = '';
                vm.updateStatus = false;
            }, 5000);
        }

        /*$scope.$on('app-set-societe', function (event, response) {
            console.log(response);
            //vm.societe = response.societe;
            vm.societe = response.societe;
        });*/

    };

    societeEditController.$inject = injectParams;

    angular.module('app').controller('societeEditController', societeEditController);

})();