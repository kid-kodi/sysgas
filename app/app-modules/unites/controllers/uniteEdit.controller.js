(function () {

    var injectParams = ['$window', '$scope', '$location', 'uniteService', '$timeout', '$routeParams', 'config_map'];

    var uniteEditController = function ($window, $scope, $location, uniteService, $timeout, $routeParams, config_map) {
        var vm = this;

        var uniteId = ($routeParams.id) ? parseInt($routeParams.id) : 0;

        vm.unite = {};
        vm.updateStatus = false;
        vm.message = null;
        vm.title = null;
        vm.buttonText = null;
        vm.departements = config_map.departements;

        vm.selection = [];
        vm.uniteLib = null;

        
        vm.saveUnite = function () {
            
            if (uniteId > 0) {
                uniteService.unite.edit(uniteId, vm.unite)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }
            else {


                uniteService.unite.add(vm.unite)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }

        };

        function processResponse(result) {
            var error = result.error;
            var unite = result.unite;
            if (error) {
                vm.message = "Le unite n'as pas été enregistré";
            }
            else {
                vm.message = "Modification(s) effectuée(s) !";
                vm.unite = {};
                $location.path('/unite');
            }
            vm.updateStatus = true;
            startTimer();
        }

        (function () {
            if (uniteId > 0) {
                vm.unite.id = uniteId;
                vm.title = 'Modifier les informations';
                vm.buttonText = 'Modifier';
                uniteService.getunite(uniteId);
            } else {
                vm.title = 'Enregistrer les informations';
                vm.buttonText = 'Enregistrer';
            }
        })();


        $scope.$on('app-set-unite', function (event, response) {
            console.log('set unite');
            console.log(response);

            //vm.unite = response.unite;
            vm.unite = response.unite;
        });


        function startTimer() {
            var timer = $timeout(function () {
                $timeout.cancel(timer);
                vm.message = '';
                vm.updateStatus = false;
            }, 5000);
        }

        $scope.$on('app-set-unite', function (event, response) {
            console.log(response);
            //vm.unite = response.unite;
            vm.unite = response.unite;
        });

    };

    uniteEditController.$inject = injectParams;

    angular.module('app').controller('uniteEditController', uniteEditController);

})();