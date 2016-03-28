(function () {

    var injectParams = ['$window', '$scope', '$location', 'laboratoireService', '$timeout', '$routeParams', 'config_map'];

    var laboratoireEditController = function ($window, $scope, $location, laboratoireService, $timeout, $routeParams, config_map) {
        var vm = this;

        var laboratoireId = ($routeParams.id) ? parseInt($routeParams.id) : 0;

        vm.laboratoire = {};
        vm.updateStatus = false;
        vm.message = null;
        vm.title = null;
        vm.buttonText = null;
        vm.departements = config_map.departements;
        vm.unites = config_map.unites;
        vm.uniteList = vm.unites;
        vm.uniteId = null;

        vm.selection = [];
        vm.laboratoireLib = null;

        
        vm.saveLaboratoire = function () {
            
            if (laboratoireId > 0) {
                laboratoireService.laboratoire.edit(laboratoireId, vm.laboratoire)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }
            else {


                laboratoireService.laboratoire.add(vm.laboratoire)
                .then(function (result) {
                    console.log(result);
                    processResponse(result);
                });
            }

        };

        function processResponse(result) {
            var error = result.error;
            var laboratoire = result.laboratoire;
            if (error) {
                vm.message = "Le laboratoire n'as pas été enregistré";
            }
            else {
                vm.message = "Modification(s) effectuée(s) !";
                vm.laboratoire = {};
                $location.path('/laboratoire');
            }
            vm.updateStatus = true;
            startTimer();
        }

        (function () {
            if (laboratoireId > 0) {
                vm.laboratoire.id = laboratoireId;
                vm.title = 'Modifier les informations';
                vm.buttonText = 'Modifier';
                laboratoireService.getlaboratoire(laboratoireId);
            } else {
                vm.title = 'Enregistrer les informations';
                vm.buttonText = 'Enregistrer';
            }
        })();

        vm.onDeptChange = function () {
            //set unite List
            var deptId = vm.departementId;
            setUniteByDeptId(deptId);
        }

        function setUniteByDeptId(deptId) {
            vm.uniteList = [];
            for (var i = 0; i < vm.unites.length; i++) {
                if (vm.unites[i].departementId === deptId) {
                    vm.uniteList.push(vm.unites[i]);
                }
            }
        }

        function getDepartement(deptId) {
            var departement = {};

            for (var i = 0; i < vm.departements.length; i++) {
                if (vm.departements[i].id === deptId) {
                    departement = vm.departements[i];
                }
            }

            return departement;
        }

        function getUnite(uniteId) {
            var unite = {};

            for (var i = 0; i < vm.unites.length; i++) {
                if (vm.unites[i].id === uniteId) {
                    unite = vm.unites[i];
                }
            }

            return unite;
        }

        $scope.$on('app-set-laboratoire', function (event, response) {
            console.log('set laboratoire');
            console.log(response);
            vm.laboratoire = response.laboratoire;
            console.log(vm.laboratoire.uniteId);

            var departement = {};
            var unite = {};
            unite = getUnite(vm.laboratoire.uniteId);
            departement = getDepartement(unite.departementId);

            vm.departementId = departement.id;
        });


        function startTimer() {
            var timer = $timeout(function () {
                $timeout.cancel(timer);
                vm.message = '';
                vm.updateStatus = false;
            }, 5000);
        }

    };

    laboratoireEditController.$inject = injectParams;

    angular.module('app').controller('laboratoireEditController', laboratoireEditController);

})();